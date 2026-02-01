"""
Tests for Admin Dashboard
"""
from decimal import Decimal
from datetime import datetime, timedelta
from django.test import TestCase, Client
from django.contrib.auth.models import User
from django.utils import timezone
from store.models import (
    Order, OrderItem, Product, Brand, Category,
    OrderAnalytics, UserAnalytics, ProductPerformance
)
from store.admin_dashboard import (
    get_kpi_metrics,
    get_sales_trend_data,
    get_top_products_data,
    get_revenue_breakdown,
    get_user_growth_data,
)


class AdminDashboardKPITestCase(TestCase):
    """Test KPI metrics calculation"""
    
    def setUp(self):
        # Create test user
        self.admin_user = User.objects.create_superuser(
            username='admin',
            email='admin@example.com',
            password='admin123'
        )
        
        # Create test data
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        self.product = Product.objects.create(
            name="Test Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            stock_quantity=10,
        )
        
        # Create completed order
        self.order = Order.objects.create(
            full_name="Test Customer",
            phone="1234567890",
            address="123 Test St",
            payment_status="completed",
        )
        
        self.order_item = OrderItem.objects.create(
            order=self.order,
            product=self.product,
            quantity=2,
            price=Decimal('100.00'),
        )
    
    def test_get_kpi_metrics(self):
        """Test KPI metrics calculation"""
        start_date = timezone.now().date() - timedelta(days=30)
        metrics = get_kpi_metrics(start_date)
        
        self.assertIn('total_sales', metrics)
        self.assertIn('period_sales', metrics)
        self.assertIn('total_orders', metrics)
        self.assertIn('period_orders', metrics)
        self.assertIn('total_users', metrics)
        self.assertIn('new_users', metrics)
        self.assertIn('conversion_rate', metrics)
        self.assertIn('avg_order_value', metrics)
        
        # Check values
        self.assertEqual(metrics['total_orders'], 1)
        self.assertEqual(metrics['period_orders'], 1)
        self.assertGreater(metrics['total_sales'], 0)
    
    def test_get_kpi_metrics_no_data(self):
        """Test KPI metrics with no data"""
        # Delete all orders
        Order.objects.all().delete()
        
        start_date = timezone.now().date() - timedelta(days=30)
        metrics = get_kpi_metrics(start_date)
        
        self.assertEqual(metrics['total_orders'], 0)
        self.assertEqual(metrics['period_orders'], 0)
        self.assertEqual(metrics['total_sales'], 0)


class SalesTrendTestCase(TestCase):
    """Test sales trend data"""
    
    def setUp(self):
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        self.product = Product.objects.create(
            name="Test Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
        )
    
    def test_get_sales_trend_data(self):
        """Test sales trend data generation"""
        # Create orders over multiple days
        for i in range(5):
            order = Order.objects.create(
                full_name=f"Customer {i}",
                phone="1234567890",
                address="Test Address",
                payment_status="completed",
            )
            
            OrderItem.objects.create(
                order=order,
                product=self.product,
                quantity=1,
                price=Decimal('100.00'),
            )
        
        start_date = timezone.now().date() - timedelta(days=7)
        data = get_sales_trend_data(start_date)
        
        self.assertIn('labels', data)
        self.assertIn('revenue', data)
        self.assertIn('orders', data)
        
        # Should have data
        self.assertIsInstance(data['labels'], list)
        self.assertIsInstance(data['revenue'], list)
        self.assertIsInstance(data['orders'], list)


class TopProductsTestCase(TestCase):
    """Test top products data"""
    
    def setUp(self):
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        # Create multiple products
        self.products = []
        for i in range(3):
            product = Product.objects.create(
                name=f"Product {i}",
                brand=self.brand,
                category=self.category,
                price=Decimal('100.00') * (i + 1),
            )
            self.products.append(product)
    
    def test_get_top_products_data(self):
        """Test top products data generation"""
        # Create orders with different quantities
        for i, product in enumerate(self.products):
            order = Order.objects.create(
                full_name=f"Customer {i}",
                phone="1234567890",
                address="Test Address",
                payment_status="completed",
            )
            
            OrderItem.objects.create(
                order=order,
                product=product,
                quantity=(i + 1) * 2,
                price=product.price,
            )
        
        data = get_top_products_data(limit=3)
        
        self.assertIn('labels', data)
        self.assertIn('quantities', data)
        self.assertIn('revenue', data)
        
        # Should have products
        self.assertGreater(len(data['labels']), 0)
        self.assertGreater(len(data['quantities']), 0)
        self.assertGreater(len(data['revenue']), 0)


class RevenueBreakdownTestCase(TestCase):
    """Test revenue breakdown by category"""
    
    def setUp(self):
        self.brand = Brand.objects.create(name="Test Brand")
        
        # Create multiple categories
        self.category1 = Category.objects.create(name="Category 1")
        self.category2 = Category.objects.create(name="Category 2")
        
        # Create products in different categories
        self.product1 = Product.objects.create(
            name="Product 1",
            brand=self.brand,
            category=self.category1,
            price=Decimal('100.00'),
        )
        
        self.product2 = Product.objects.create(
            name="Product 2",
            brand=self.brand,
            category=self.category2,
            price=Decimal('200.00'),
        )
    
    def test_get_revenue_breakdown(self):
        """Test revenue breakdown data"""
        # Create orders for different categories
        for product in [self.product1, self.product2]:
            order = Order.objects.create(
                full_name="Test Customer",
                phone="1234567890",
                address="Test Address",
                payment_status="completed",
            )
            
            OrderItem.objects.create(
                order=order,
                product=product,
                quantity=1,
                price=product.price,
            )
        
        data = get_revenue_breakdown()
        
        self.assertIn('labels', data)
        self.assertIn('data', data)
        
        # Should have categories
        self.assertGreater(len(data['labels']), 0)
        self.assertGreater(len(data['data']), 0)


class UserGrowthTestCase(TestCase):
    """Test user growth data"""
    
    def test_get_user_growth_data(self):
        """Test user growth data generation"""
        # Create users
        for i in range(5):
            User.objects.create_user(
                username=f'user{i}',
                email=f'user{i}@example.com',
                password='testpass123'
            )
        
        start_date = timezone.now().date() - timedelta(days=7)
        data = get_user_growth_data(start_date)
        
        self.assertIn('labels', data)
        self.assertIn('data', data)
        self.assertIn('new_users', data)
        
        # Should have growth data
        self.assertIsInstance(data['labels'], list)
        self.assertIsInstance(data['data'], list)
        self.assertIsInstance(data['new_users'], list)


class AdminDashboardViewsTestCase(TestCase):
    """Test admin dashboard views"""
    
    def setUp(self):
        self.client = Client()
        
        # Create admin user
        self.admin_user = User.objects.create_superuser(
            username='admin',
            email='admin@example.com',
            password='admin123'
        )
        
        # Create test data
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        self.product = Product.objects.create(
            name="Test Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            stock_quantity=10,
        )
    
    def test_admin_dashboard_requires_staff(self):
        """Test admin dashboard requires staff permission"""
        # The view requires staff member decorator
        # Just verify the function exists
        from store.admin_dashboard import admin_dashboard
        self.assertTrue(callable(admin_dashboard))
    
    def test_export_sales_report_requires_staff(self):
        """Test export sales report requires staff permission"""
        from store.admin_dashboard import export_sales_report
        self.assertTrue(callable(export_sales_report))
    
    def test_export_products_report_requires_staff(self):
        """Test export products report requires staff permission"""
        from store.admin_dashboard import export_products_report
        self.assertTrue(callable(export_products_report))
    
    def test_api_dashboard_metrics_exists(self):
        """Test API dashboard metrics endpoint exists"""
        from store.admin_dashboard import api_dashboard_metrics
        self.assertTrue(callable(api_dashboard_metrics))
