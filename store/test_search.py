"""
Tests for Advanced Search Functionality
"""
from django.test import TestCase, Client
from django.contrib.auth.models import User
from store.models import Product, Brand, Category, SearchQuery
from store.search import ProductSearch, SearchAnalytics
from decimal import Decimal


class ProductSearchTest(TestCase):
    """Test ProductSearch class"""
    
    def setUp(self):
        """Set up test data"""
        self.brand1 = Brand.objects.create(name='Brand A')
        self.brand2 = Brand.objects.create(name='Brand B')
        self.category1 = Category.objects.create(name='Category A')
        self.category2 = Category.objects.create(name='Category B')
        
        # Create test products
        self.product1 = Product.objects.create(
            name='Red Paint 5L',
            description='High quality red paint',
            brand=self.brand1,
            category=self.category1,
            price=Decimal('100000'),
            stock_quantity=50,
            is_active=True,
            rating=Decimal('4.5')
        )
        
        self.product2 = Product.objects.create(
            name='Blue Paint 10L',
            description='Premium blue paint',
            brand=self.brand2,
            category=self.category2,
            price=Decimal('200000'),
            sale_price=Decimal('180000'),
            stock_quantity=30,
            is_active=True,
            is_on_sale=True,
            rating=Decimal('4.8')
        )
        
        self.product3 = Product.objects.create(
            name='White Paint 1L',
            description='Basic white paint',
            brand=self.brand1,
            category=self.category1,
            price=Decimal('50000'),
            stock_quantity=0,
            is_active=True,
            rating=Decimal('3.5')
        )
        
        self.searcher = ProductSearch()
    
    def test_full_text_search(self):
        """Test full-text search functionality"""
        results = self.searcher.search('Red', page=1)
        self.assertEqual(len(results['results']), 1)
        self.assertEqual(results['results'][0].name, 'Red Paint 5L')
    
    def test_search_with_category_filter(self):
        """Test search with category filter"""
        results = self.searcher.search(
            'Paint',
            filters={'category': self.category1.id}
        )
        self.assertEqual(len(results['results']), 2)
    
    def test_search_with_brand_filter(self):
        """Test search with brand filter"""
        results = self.searcher.search(
            'Paint',
            filters={'brand': self.brand2.id}
        )
        self.assertEqual(len(results['results']), 1)
        self.assertEqual(results['results'][0].brand, self.brand2)
    
    def test_search_with_price_filter(self):
        """Test search with price range filter"""
        results = self.searcher.search(
            'Paint',
            filters={'price_min': 60000, 'price_max': 150000}
        )
        self.assertEqual(len(results['results']), 1)
        self.assertEqual(results['results'][0].name, 'Red Paint 5L')
    
    def test_search_with_in_stock_filter(self):
        """Test search with in-stock filter"""
        results = self.searcher.search(
            'Paint',
            filters={'in_stock': True}
        )
        # Should exclude out-of-stock product
        self.assertEqual(len(results['results']), 2)
        self.assertNotIn(self.product3, results['results'])
    
    def test_search_with_on_sale_filter(self):
        """Test search with on-sale filter"""
        results = self.searcher.search(
            'Paint',
            filters={'on_sale': True}
        )
        self.assertEqual(len(results['results']), 1)
        self.assertEqual(results['results'][0].name, 'Blue Paint 10L')
    
    def test_search_sorting_price_asc(self):
        """Test sorting by price ascending"""
        results = self.searcher.search('Paint', sort_by='price_asc')
        self.assertEqual(results['results'][0].name, 'White Paint 1L')
        self.assertEqual(results['results'][-1].name, 'Blue Paint 10L')
    
    def test_search_sorting_price_desc(self):
        """Test sorting by price descending"""
        results = self.searcher.search('Paint', sort_by='price_desc')
        self.assertEqual(results['results'][0].name, 'Blue Paint 10L')
    
    def test_search_sorting_rating(self):
        """Test sorting by rating"""
        results = self.searcher.search('Paint', sort_by='rating')
        self.assertEqual(results['results'][0].rating, Decimal('4.8'))
    
    def test_pagination(self):
        """Test search pagination"""
        results = self.searcher.search('Paint', page=1, per_page=2)
        self.assertEqual(len(results['results']), 2)
        self.assertEqual(results['total_pages'], 2)
        self.assertTrue(results['has_next'])
        self.assertFalse(results['has_previous'])
    
    def test_autocomplete(self):
        """Test autocomplete suggestions"""
        suggestions = self.searcher.autocomplete('Red')
        self.assertEqual(len(suggestions), 1)
        self.assertEqual(suggestions[0]['text'], 'Red Paint 5L')
    
    def test_autocomplete_short_prefix(self):
        """Test autocomplete with short prefix (should return empty)"""
        suggestions = self.searcher.autocomplete('R')
        self.assertEqual(len(suggestions), 0)
    
    def test_track_search(self):
        """Test search tracking"""
        user = User.objects.create_user('testuser', 'test@test.com', 'password')
        self.searcher.track_search('Red Paint', user=user, result_count=1)
        
        search_query = SearchQuery.objects.filter(query='Red Paint').first()
        self.assertIsNotNone(search_query)
        self.assertEqual(search_query.user, user)
        self.assertEqual(search_query.result_count, 1)
    
    def test_get_facets(self):
        """Test facet generation"""
        results = self.searcher.search('Paint')
        facets = results['facets']
        
        self.assertIn('categories', facets)
        self.assertIn('brands', facets)
        self.assertIn('price_ranges', facets)
        self.assertIn('ratings', facets)


class SearchAnalyticsTest(TestCase):
    """Test SearchAnalytics class"""
    
    def setUp(self):
        """Set up test data"""
        self.user = User.objects.create_user('testuser', 'test@test.com', 'password')
        
        # Create search queries
        SearchQuery.objects.create(query='red paint', user=self.user, result_count=5)
        SearchQuery.objects.create(query='blue paint', result_count=3)
        SearchQuery.objects.create(query='nonexistent', result_count=0)
    
    def test_get_search_stats(self):
        """Test search statistics"""
        stats = SearchAnalytics.get_search_stats(days=30)
        
        self.assertEqual(stats['total_searches'], 3)
        self.assertEqual(stats['unique_queries'], 3)
        self.assertEqual(stats['zero_result_searches'], 1)
        self.assertGreater(stats['zero_result_rate'], 0)
        self.assertGreater(stats['avg_results_per_search'], 0)
    
    def test_get_failed_searches(self):
        """Test failed searches (zero results)"""
        failed = SearchAnalytics.get_failed_searches(limit=10, days=7)
        failed_list = list(failed)
        
        self.assertEqual(len(failed_list), 1)
        self.assertEqual(failed_list[0]['query'], 'nonexistent')


class SearchViewsTest(TestCase):
    """Test search API views"""
    
    def setUp(self):
        """Set up test data"""
        self.client = Client()
        self.brand = Brand.objects.create(name='Test Brand')
        self.category = Category.objects.create(name='Test Category')
        
        self.product = Product.objects.create(
            name='Test Paint',
            brand=self.brand,
            category=self.category,
            price=Decimal('100000'),
            stock_quantity=50,
            is_active=True
        )
    
    def test_product_search_view(self):
        """Test product search API endpoint"""
        response = self.client.get('/search/?q=Test')
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        
        self.assertTrue(data['success'])
        self.assertEqual(len(data['results']), 1)
        self.assertIn('pagination', data)
        self.assertIn('facets', data)
    
    def test_autocomplete_view(self):
        """Test autocomplete API endpoint"""
        response = self.client.get('/search/autocomplete/?q=Test')
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        
        self.assertTrue(data['success'])
        self.assertIn('suggestions', data)
    
    def test_autocomplete_short_query(self):
        """Test autocomplete with short query"""
        response = self.client.get('/search/autocomplete/?q=T')
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        
        self.assertTrue(data['success'])
        self.assertEqual(len(data['suggestions']), 0)
    
    def test_search_analytics_view_unauthorized(self):
        """Test search analytics view without authentication"""
        response = self.client.get('/search/analytics/')
        
        self.assertEqual(response.status_code, 302)  # Redirect to login
    
    def test_search_analytics_view_not_staff(self):
        """Test search analytics view as non-staff user"""
        user = User.objects.create_user('testuser', 'test@test.com', 'password')
        self.client.force_login(user)
        
        response = self.client.get('/search/analytics/')
        
        self.assertEqual(response.status_code, 403)  # Forbidden
    
    def test_popular_searches_view(self):
        """Test popular searches API endpoint"""
        # Create some search queries
        SearchQuery.objects.create(query='popular search', result_count=10)
        SearchQuery.objects.create(query='popular search', result_count=10)
        
        response = self.client.get('/search/popular/')
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        
        self.assertTrue(data['success'])
        self.assertIn('popular_searches', data)
