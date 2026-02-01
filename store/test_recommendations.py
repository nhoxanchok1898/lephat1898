from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import (
    Brand, Category, Product, ProductView, 
    ProductViewAnalytics, Order, OrderItem
)


class RecommendationTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.user = User.objects.create_user(username='testuser', password='testpass')
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        
        # Create test products
        self.product1 = Product.objects.create(
            name='Paint 1', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )
        self.product2 = Product.objects.create(
            name='Paint 2', brand=self.brand, category=self.category,
            price=150.00, unit_type=Product.UNIT_LIT, volume=10, is_active=True
        )
        self.product3 = Product.objects.create(
            name='Paint 3', brand=self.brand, category=self.category,
            price=200.00, unit_type=Product.UNIT_LIT, volume=20, is_active=True
        )
    
    def test_people_also_viewed(self):
        """Test people who viewed this also viewed recommendations"""
        # Create view records
        ProductView.objects.create(product=self.product1, user=self.user)
        ProductView.objects.create(product=self.product2, user=self.user)
        
        url = reverse('store:recommendations', args=[self.product1.id])
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('also_viewed', data)
        self.assertIn('also_bought', data)
        self.assertIn('similar', data)
    
    def test_people_also_bought(self):
        """Test people who bought this also bought recommendations"""
        # Create an order with products
        order = Order.objects.create(
            full_name='Test User',
            phone='123456',
            address='Test Address'
        )
        OrderItem.objects.create(order=order, product=self.product1, quantity=1, price=100)
        OrderItem.objects.create(order=order, product=self.product2, quantity=1, price=150)
        
        url = reverse('store:recommendations', args=[self.product1.id])
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('also_bought', data)
    
    def test_trending_products(self):
        """Test trending products endpoint"""
        # Create some views
        ProductView.objects.create(product=self.product1, user=self.user)
        ProductView.objects.create(product=self.product1, user=self.user)
        ProductView.objects.create(product=self.product2, user=self.user)
        
        url = reverse('store:trending')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('most_viewed', data)
        self.assertIn('most_sold', data)
    
    def test_personalized_recommendations(self):
        """Test personalized recommendations for authenticated users"""
        self.client.login(username='testuser', password='testpass')
        
        # Create viewing history
        ProductView.objects.create(product=self.product1, user=self.user)
        ProductView.objects.create(product=self.product2, user=self.user)
        
        url = reverse('store:personalized')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('recommendations', data)
    
    def test_product_view_tracker(self):
        """Test product view tracking"""
        url = reverse('store:track_view', args=[self.product1.id])
        response = self.client.post(url)
        
        self.assertEqual(response.status_code, 200)
        self.assertEqual(ProductView.objects.filter(product=self.product1).count(), 1)
    
    def test_product_view_analytics_update(self):
        """Test analytics update on product view"""
        ProductView.objects.create(product=self.product1, user=self.user)
        
        analytics, created = ProductViewAnalytics.objects.get_or_create(product=self.product1)
        analytics.update_view_count()
        
        self.assertEqual(analytics.total_views, 1)
        self.assertIsNotNone(analytics.last_viewed)
