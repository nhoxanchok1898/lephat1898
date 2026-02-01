"""
Comprehensive tests for product listing UI redesign
Tests all filtering, sorting, and UI features
"""
from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import Product, Category, Brand
from decimal import Decimal
from datetime import timedelta
from django.utils import timezone


class ProductListingUITest(TestCase):
    """Test suite for redesigned product listing page"""
    
    def setUp(self):
        """Set up test data"""
        self.client = Client()
        
        # Create categories
        self.cat_paint = Category.objects.create(name="Sơn", slug="son")
        self.cat_waterproof = Category.objects.create(name="Chống thấm", slug="chong-tham")
        
        # Create brands
        self.brand_dulux = Brand.objects.create(name="Dulux", slug="dulux")
        self.brand_jotun = Brand.objects.create(name="Jotun", slug="jotun")
        
        # Create products with explicit timestamps to ensure consistent ordering
        now = timezone.now()
        
        self.product1 = Product.objects.create(
            name="Sơn Dulux Trắng",
            slug="son-dulux-trang",
            category=self.cat_paint,
            brand=self.brand_dulux,
            price=Decimal('150000'),
            sale_price=Decimal('120000'),
            quantity=10,
            is_active=True,
            created_at=now  # Most recent
        )
        
        self.product2 = Product.objects.create(
            name="Sơn Jotun Xanh",
            slug="son-jotun-xanh",
            category=self.cat_paint,
            brand=self.brand_jotun,
            price=Decimal('200000'),
            quantity=5,
            is_active=True,
            created_at=now - timedelta(days=60)  # Oldest (60 days old)
        )
        
        self.product3 = Product.objects.create(
            name="Chống thấm Dulux",
            slug="chong-tham-dulux",
            category=self.cat_waterproof,
            brand=self.brand_dulux,
            price=Decimal('300000'),
            quantity=0,  # Out of stock
            is_active=True,
            created_at=now - timedelta(days=10)  # 10 days old
        )
        
        self.product4 = Product.objects.create(
            name="Sơn Jotun Đỏ",
            slug="son-jotun-do",
            category=self.cat_paint,
            brand=self.brand_jotun,
            price=Decimal('180000'),
            quantity=20,
            is_active=True,
            created_at=now - timedelta(days=5)  # 5 days old
        )
    
    def test_product_list_page_loads(self):
        """Test that product list page loads successfully"""
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        self.assertTemplateUsed(response, 'store/product_list.html')
        
    def test_all_products_displayed(self):
        """Test that all active products are displayed"""
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Dulux Trắng")
        self.assertContains(response, "Sơn Jotun Xanh")
        self.assertContains(response, "Chống thấm Dulux")
        self.assertContains(response, "Sơn Jotun Đỏ")
    
    def test_category_filter(self):
        """Test filtering by category"""
        response = self.client.get(
            reverse('store:product_list'),
            {'category': self.cat_paint.id}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Dulux Trắng")
        self.assertContains(response, "Sơn Jotun Xanh")
        self.assertNotContains(response, "Chống thấm Dulux")
    
    def test_brand_filter(self):
        """Test filtering by brand"""
        response = self.client.get(
            reverse('store:product_list'),
            {'brand': self.brand_dulux.id}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Dulux Trắng")
        self.assertContains(response, "Chống thấm Dulux")
        self.assertNotContains(response, "Sơn Jotun")
    
    def test_price_range_filter(self):
        """Test filtering by price range"""
        response = self.client.get(
            reverse('store:product_list'),
            {'min_price': '150000', 'max_price': '200000'}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Dulux Trắng")
        self.assertContains(response, "Sơn Jotun Xanh")
        self.assertContains(response, "Sơn Jotun Đỏ")
        self.assertNotContains(response, "Chống thấm Dulux")
    
    def test_on_sale_filter(self):
        """Test filtering products on sale"""
        response = self.client.get(
            reverse('store:product_list'),
            {'on_sale': '1'}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Dulux Trắng")
        # Only product1 has sale_price
        products = response.context['products']
        self.assertEqual(len(products), 1)
    
    def test_new_arrivals_filter(self):
        """Test filtering new arrivals (last 30 days)"""
        response = self.client.get(
            reverse('store:product_list'),
            {'new_arrivals': '1'}
        )
        self.assertEqual(response.status_code, 200)
        # Should show products created in last 30 days
        self.assertContains(response, "Sơn Dulux Trắng")
        self.assertContains(response, "Chống thấm Dulux")
        self.assertContains(response, "Sơn Jotun Đỏ")
        # product2 is 60 days old - check it's not in the product list
        products = list(response.context['products'])
        product_names = [p.name for p in products]
        self.assertNotIn("Sơn Jotun Xanh", product_names)
    
    def test_in_stock_filter(self):
        """Test filtering products in stock"""
        response = self.client.get(
            reverse('store:product_list'),
            {'in_stock': '1'}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Dulux Trắng")
        self.assertContains(response, "Sơn Jotun Xanh")
        # product3 has quantity=0
        self.assertNotContains(response, "Chống thấm Dulux")
    
    def test_search_functionality(self):
        """Test search by product name"""
        response = self.client.get(
            reverse('store:product_list'),
            {'q': 'Jotun'}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Sơn Jotun Xanh")
        self.assertContains(response, "Sơn Jotun Đỏ")
        # Check that Dulux products are not in the results
        products = list(response.context['products'])
        product_names = [p.name for p in products]
        self.assertNotIn("Sơn Dulux Trắng", product_names)
        self.assertNotIn("Chống thấm Dulux", product_names)
    
    def test_sort_by_price_ascending(self):
        """Test sorting by price (low to high)"""
        response = self.client.get(
            reverse('store:product_list'),
            {'sort': 'price_asc'}
        )
        self.assertEqual(response.status_code, 200)
        products = list(response.context['products'])
        prices = [p.price for p in products]
        self.assertEqual(prices, sorted(prices))
    
    def test_sort_by_price_descending(self):
        """Test sorting by price (high to low)"""
        response = self.client.get(
            reverse('store:product_list'),
            {'sort': 'price_desc'}
        )
        self.assertEqual(response.status_code, 200)
        products = list(response.context['products'])
        prices = [p.price for p in products]
        self.assertEqual(prices, sorted(prices, reverse=True))
    
    def test_sort_by_name_ascending(self):
        """Test sorting by name A-Z"""
        response = self.client.get(
            reverse('store:product_list'),
            {'sort': 'name_asc'}
        )
        self.assertEqual(response.status_code, 200)
        products = list(response.context['products'])
        names = [p.name for p in products]
        self.assertEqual(names, sorted(names))
    
    def test_sort_by_newest(self):
        """Test sorting by newest"""
        response = self.client.get(
            reverse('store:product_list'),
            {'sort': 'newest'}
        )
        self.assertEqual(response.status_code, 200)
        products = list(response.context['products'])
        # product1 is newest
        self.assertEqual(products[0].slug, 'son-dulux-trang')
    
    def test_pagination(self):
        """Test pagination works correctly"""
        # Create more products to test pagination
        for i in range(15):
            Product.objects.create(
                name=f"Test Product {i}",
                slug=f"test-product-{i}",
                category=self.cat_paint,
                brand=self.brand_dulux,
                price=Decimal('100000'),
                quantity=10,
                is_active=True
            )
        
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        # Should have pagination (12 per page by default)
        self.assertTrue('page_obj' in response.context)
        self.assertTrue(response.context['page_obj'].has_next())
    
    def test_combined_filters(self):
        """Test combining multiple filters"""
        response = self.client.get(
            reverse('store:product_list'),
            {
                'category': self.cat_paint.id,
                'brand': self.brand_jotun.id,
                'min_price': '150000',
                'max_price': '250000',
                'in_stock': '1',
                'sort': 'price_asc'
            }
        )
        self.assertEqual(response.status_code, 200)
        products = list(response.context['products'])
        # Should show Jotun products in paint category, in stock, price range
        self.assertTrue(len(products) >= 1)
        for p in products:
            self.assertEqual(p.category, self.cat_paint)
            self.assertEqual(p.brand, self.brand_jotun)
            self.assertGreaterEqual(p.price, Decimal('150000'))
            self.assertLessEqual(p.price, Decimal('250000'))
            self.assertGreater(p.quantity, 0)
    
    def test_context_data(self):
        """Test that all required context data is passed to template"""
        response = self.client.get(
            reverse('store:product_list'),
            {'category': self.cat_paint.id, 'q': 'test'}
        )
        self.assertEqual(response.status_code, 200)
        context = response.context
        
        # Check all required context variables
        self.assertIn('products', context)
        self.assertIn('categories', context)
        self.assertIn('brands', context)
        self.assertIn('page_obj', context)
        self.assertIn('current_category', context)
        self.assertIn('current_category_name', context)
        self.assertIn('q', context)
    
    def test_empty_results(self):
        """Test page displays correctly when no products match"""
        response = self.client.get(
            reverse('store:product_list'),
            {'q': 'nonexistent product xyz'}
        )
        self.assertEqual(response.status_code, 200)
        # Should show "no products" message
        self.assertContains(response, "Không tìm thấy")
    
    def test_invalid_filter_values(self):
        """Test handling of invalid filter values"""
        response = self.client.get(
            reverse('store:product_list'),
            {
                'category': 'invalid',
                'min_price': 'not_a_number',
                'max_price': 'abc'
            }
        )
        # Should still load without errors
        self.assertEqual(response.status_code, 200)
    
    def test_categories_have_product_count(self):
        """Test that categories include product count"""
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        categories = response.context['categories']
        
        # Check that categories have product_count annotation
        for cat in categories:
            self.assertTrue(hasattr(cat, 'product_count'))
    
    def test_brands_have_product_count(self):
        """Test that brands include product count"""
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        brands = response.context['brands']
        
        # Check that brands have product_count annotation
        for brand in brands:
            self.assertTrue(hasattr(brand, 'product_count'))
    
    def test_sale_badge_display(self):
        """Test that sale badges are displayed correctly"""
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        # product1 has sale_price, should show discount badge
        self.assertContains(response, "badge-sale")
    
    def test_breadcrumb_navigation(self):
        """Test breadcrumb navigation is present"""
        response = self.client.get(
            reverse('store:product_list'),
            {'category': self.cat_paint.id}
        )
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "breadcrumb")
        self.assertContains(response, self.cat_paint.name)
    
    def test_responsive_design_elements(self):
        """Test that responsive design elements are present"""
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        # Check for responsive classes
        self.assertContains(response, "col-lg-3")
        self.assertContains(response, "col-lg-9")
        self.assertContains(response, "product-grid")


class ProductListingPerformanceTest(TestCase):
    """Performance tests for product listing"""
    
    def test_query_optimization(self):
        """Test that queries are optimized with select_related"""
        # Create test data
        cat = Category.objects.create(name="Test", slug="test")
        brand = Brand.objects.create(name="Test Brand", slug="test-brand")
        
        for i in range(50):
            Product.objects.create(
                name=f"Product {i}",
                slug=f"product-{i}",
                category=cat,
                brand=brand,
                price=Decimal('100000'),
                quantity=10,
                is_active=True
            )
        
        # Check that queries are optimized
        from django.test.utils import override_settings
        from django.db import connection
        from django.test.utils import CaptureQueriesContext
        
        with CaptureQueriesContext(connection) as context:
            response = self.client.get(reverse('store:product_list'))
            self.assertEqual(response.status_code, 200)
            
            # Should not have N+1 query problem
            # Exact number depends on middleware, but should be reasonable
            self.assertLess(len(context.captured_queries), 20)


if __name__ == '__main__':
    import django
    django.setup()
    from django.test.utils import get_runner
    from django.conf import settings
    
    TestRunner = get_runner(settings)
    test_runner = TestRunner()
    failures = test_runner.run_tests(["store.test_product_listing"])
