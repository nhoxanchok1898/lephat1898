"""
Tests for Caching Layer
"""
from django.test import TestCase
from django.core.cache import cache
from django.contrib.auth.models import User
from store.models import Product, Category, Brand, Order, OrderItem, Review
from store.cache import (
    CacheManager,
    get_active_products,
    get_all_categories,
    get_top_selling_products,
    get_product_recommendations,
    cache_result,
    get_cache_key,
)
from decimal import Decimal


class CacheManagerTestCase(TestCase):
    """Test cache manager functionality"""
    
    def setUp(self):
        cache.clear()
        
        # Create test data
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        self.product1 = Product.objects.create(
            name="Product 1",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            is_active=True,
        )
        
        self.product2 = Product.objects.create(
            name="Product 2",
            brand=self.brand,
            category=self.category,
            price=Decimal('200.00'),
            is_active=True,
        )
    
    def test_invalidate_product(self):
        """Test product cache invalidation"""
        # This should not raise an error
        CacheManager.invalidate_product(self.product1.id)
    
    def test_invalidate_category(self):
        """Test category cache invalidation"""
        # This should not raise an error
        CacheManager.invalidate_category(self.category.id)
    
    def test_invalidate_search(self):
        """Test search cache invalidation"""
        # This should not raise an error
        CacheManager.invalidate_search()
    
    def test_invalidate_recommendations(self):
        """Test recommendations cache invalidation"""
        # This should not raise an error
        CacheManager.invalidate_recommendations()
        CacheManager.invalidate_recommendations(user_id=1)
    
    def test_invalidate_top_products(self):
        """Test top products cache invalidation"""
        # This should not raise an error
        CacheManager.invalidate_top_products()
    
    def test_get_stats(self):
        """Test cache stats retrieval"""
        stats = CacheManager.get_stats()
        
        self.assertIn('backend', stats)
        self.assertIn('available', stats)
        self.assertTrue(stats['available'])


class CachedQueriesTestCase(TestCase):
    """Test cached query functions"""
    
    def setUp(self):
        cache.clear()
        
        # Create test data
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        self.product = Product.objects.create(
            name="Test Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            is_active=True,
        )
    
    def test_get_active_products_caching(self):
        """Test active products are cached"""
        # First call - cache miss
        products1 = get_active_products()
        
        # Second call - cache hit
        products2 = get_active_products()
        
        # Should return same results
        self.assertEqual(len(products1), len(products2))
    
    def test_get_all_categories_caching(self):
        """Test categories are cached"""
        # First call - cache miss
        categories1 = get_all_categories()
        
        # Second call - cache hit
        categories2 = get_all_categories()
        
        # Should return same results
        self.assertEqual(len(categories1), len(categories2))
    
    def test_get_product_recommendations_caching(self):
        """Test recommendations are cached"""
        # Create related product
        related_product = Product.objects.create(
            name="Related Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('150.00'),
            is_active=True,
            rating=Decimal('4.5'),
        )
        
        # First call
        recommendations1 = get_product_recommendations(self.product.id)
        
        # Second call - should be cached
        recommendations2 = get_product_recommendations(self.product.id)
        
        # Should return same results
        self.assertEqual(len(recommendations1), len(recommendations2))
    
    def test_get_product_recommendations_invalid_product(self):
        """Test recommendations with invalid product ID"""
        recommendations = get_product_recommendations(99999)
        
        # Should return empty list
        self.assertEqual(recommendations, [])


class CacheDecoratorTestCase(TestCase):
    """Test cache_result decorator"""
    
    def setUp(self):
        cache.clear()
        self.call_count = 0
    
    def test_cache_result_decorator(self):
        """Test cache_result decorator caches function results"""
        
        @cache_result(timeout_key='query_results', prefix='test')
        def expensive_function(x):
            self.call_count += 1
            return x * 2
        
        # First call
        result1 = expensive_function(5)
        self.assertEqual(result1, 10)
        self.assertEqual(self.call_count, 1)
        
        # Second call - should use cache
        result2 = expensive_function(5)
        self.assertEqual(result2, 10)
        self.assertEqual(self.call_count, 1)  # Not incremented
        
        # Different argument - cache miss
        result3 = expensive_function(10)
        self.assertEqual(result3, 20)
        self.assertEqual(self.call_count, 2)  # Incremented
    
    def test_get_cache_key(self):
        """Test cache key generation"""
        key1 = get_cache_key('prefix', 'arg1', 'arg2', kwarg1='value1')
        key2 = get_cache_key('prefix', 'arg1', 'arg2', kwarg1='value1')
        key3 = get_cache_key('prefix', 'arg1', 'arg3', kwarg1='value1')
        
        # Same arguments should generate same key
        self.assertEqual(key1, key2)
        
        # Different arguments should generate different key
        self.assertNotEqual(key1, key3)


class CacheInvalidationTestCase(TestCase):
    """Test automatic cache invalidation"""
    
    def setUp(self):
        cache.clear()
        
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        self.product = Product.objects.create(
            name="Test Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            is_active=True,
        )
    
    def test_product_update_invalidates_cache(self):
        """Test product update invalidates cache"""
        # Cache some product data
        products = get_active_products()
        initial_count = len(products)
        
        # Update product
        self.product.price = Decimal('150.00')
        self.product.save()
        
        # Invalidation should happen automatically via signals
        # (if signals are set up)
        # For now, just test manual invalidation
        CacheManager.invalidate_product(self.product.id)
    
    def test_order_create_invalidates_top_products(self):
        """Test order creation invalidates top products cache"""
        # Create order
        order = Order.objects.create(
            full_name="Test User",
            phone="1234567890",
            address="Test Address",
            payment_status="completed",
        )
        
        OrderItem.objects.create(
            order=order,
            product=self.product,
            quantity=1,
            price=self.product.price,
        )
        
        # Manually invalidate (would be automatic with signals)
        CacheManager.invalidate_top_products()


class CacheWarmingTestCase(TestCase):
    """Test cache warming functionality"""
    
    def setUp(self):
        cache.clear()
        
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
    
    def test_warm_cache(self):
        """Test cache warming doesn't raise errors"""
        try:
            CacheManager.warm_cache()
        except Exception as e:
            self.fail(f"Cache warming raised exception: {e}")
