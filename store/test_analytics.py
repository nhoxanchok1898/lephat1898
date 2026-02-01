from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import Brand, Category, Product, Order, OrderItem, ProductView
from datetime import timedelta
from django.utils import timezone


class AnalyticsTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.admin = User.objects.create_user(username='admin', password='admin', is_staff=True)
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        
        self.product1 = Product.objects.create(
            name='Paint 1', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )
        self.product2 = Product.objects.create(
            name='Paint 2', brand=self.brand, category=self.category,
            price=200.00, unit_type=Product.UNIT_LIT, volume=10, is_active=True
        )
    
    def test_admin_dashboard_access(self):
        """Test admin dashboard requires authentication"""
        # Without login
        url = reverse('store:admin_dashboard')
        response = self.client.get(url)
        self.assertEqual(response.status_code, 302)  # Redirect to login
        
        # With login
        self.client.login(username='admin', password='admin')
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)
    
    def test_sales_metrics(self):
        """Test sales metrics calculation"""
        self.client.login(username='admin', password='admin')
        
        # Create orders
        order1 = Order.objects.create(
            full_name='User 1', phone='123', address='Addr'
        )
        OrderItem.objects.create(order=order1, product=self.product1, quantity=2, price=100)
        
        order2 = Order.objects.create(
            full_name='User 2', phone='456', address='Addr'
        )
        OrderItem.objects.create(order=order2, product=self.product2, quantity=1, price=200)
        
        url = reverse('store:admin_dashboard')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        self.assertIn('today_orders', response.context)
        self.assertIn('today_revenue', response.context)
    
    def test_top_products(self):
        """Test top selling products"""
        self.client.login(username='admin', password='admin')
        
        # Create orders
        order = Order.objects.create(
            full_name='User', phone='123', address='Addr'
        )
        OrderItem.objects.create(order=order, product=self.product1, quantity=5, price=100)
        OrderItem.objects.create(order=order, product=self.product2, quantity=2, price=200)
        
        url = reverse('store:admin_dashboard')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        self.assertIn('top_products', response.context)
    
    def test_conversion_rate(self):
        """Test conversion rate calculation"""
        self.client.login(username='admin', password='admin')
        
        # Create views
        user = User.objects.create_user(username='user1', password='pass')
        ProductView.objects.create(product=self.product1, user=user)
        ProductView.objects.create(product=self.product1, user=user)
        
        # Create purchase
        order = Order.objects.create(
            full_name='User', phone='123', address='Addr'
        )
        OrderItem.objects.create(order=order, product=self.product1, quantity=1, price=100)
        
        url = reverse('store:product_performance')
        response = self.client.get(url, {'product_id': self.product1.id, 'days': 30})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('performance', data)
    
    def test_user_analytics(self):
        """Test user analytics in dashboard"""
        self.client.login(username='admin', password='admin')
        
        # Create users
        User.objects.create_user(username='user2', password='pass')
        User.objects.create_user(username='user3', password='pass')
        
        url = reverse('store:admin_dashboard')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        self.assertIn('total_users', response.context)
        self.assertGreaterEqual(response.context['total_users'], 3)
    
    def test_analytics_data(self):
        """Test analytics data endpoint"""
        self.client.login(username='admin', password='admin')
        
        # Create an order
        order = Order.objects.create(
            full_name='User', phone='123', address='Addr'
        )
        OrderItem.objects.create(order=order, product=self.product1, quantity=1, price=100)
        
        url = reverse('store:analytics_data')
        response = self.client.get(url, {'days': 7})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('sales', data)
        self.assertIn('period', data)
    
    def test_sales_chart_data(self):
        """Test sales chart data endpoint"""
        self.client.login(username='admin', password='admin')
        
        url = reverse('store:sales_chart')
        response = self.client.get(url, {'days': 7})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('daily_sales', data)
        self.assertIn('top_products', data)
