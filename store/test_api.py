from django.test import TestCase
from django.urls import reverse
from django.contrib.auth.models import User
from rest_framework.test import APIClient
from rest_framework import status
from store.models import Brand, Category, Product, Order, OrderItem


class APITests(TestCase):
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user(username='testuser', password='testpass')
        self.admin = User.objects.create_user(username='admin', password='admin', is_staff=True)
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        
        self.product1 = Product.objects.create(
            name='Paint 1', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )
        self.product2 = Product.objects.create(
            name='Paint 2', brand=self.brand, category=self.category,
            price=150.00, unit_type=Product.UNIT_LIT, volume=10, is_active=True
        )
    
    def test_products_endpoint(self):
        """Test GET /api/products/"""
        url = reverse('store:api-product-list')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['count'], 2)
    
    def test_product_detail_endpoint(self):
        """Test GET /api/products/<id>/"""
        url = reverse('store:api-product-detail', args=[self.product1.id])
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['name'], 'Paint 1')
        self.assertEqual(float(response.data['price']), 100.00)
    
    def test_product_search(self):
        """Test product search functionality"""
        url = reverse('store:api-product-list')
        response = self.client.get(url, {'search': 'Paint 1'})
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['count'], 1)
    
    def test_product_ordering(self):
        """Test product ordering"""
        url = reverse('store:api-product-list')
        response = self.client.get(url, {'ordering': 'price'})
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        results = response.data['results']
        self.assertEqual(results[0]['name'], 'Paint 1')  # Cheaper first
    
    def test_cart_endpoint(self):
        """Test GET /api/cart/"""
        url = reverse('store:api_cart')
        
        # Empty cart
        response = self.client.get(url)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['items']), 0)
    
    def test_cart_add_endpoint(self):
        """Test POST /api/cart/add/"""
        # Authenticate the client to match the requirement
        self.client.force_authenticate(user=self.user)
        
        url = reverse('store:api_cart_add')
        response = self.client.post(url, {
            'product_id': self.product1.id,
            'quantity': 2
        }, format='json')
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
        self.assertEqual(response.data['quantity'], 2)
    
    def test_cart_remove_endpoint(self):
        """Test DELETE /api/cart/remove/<id>/"""
        # First add to cart
        session = self.client.session
        session['cart'] = {str(self.product1.id): 2}
        session.save()
        
        url = reverse('store:api_cart_remove', args=[self.product1.id])
        response = self.client.delete(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
    
    def test_recommendations_endpoint(self):
        """Test GET /api/recommendations/"""
        url = reverse('store:api_recommendations')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertIn('recommendations', response.data)
    
    def test_product_recommendations_action(self):
        """Test product recommendations via viewset action"""
        url = reverse('store:api-product-recommendations', args=[self.product1.id])
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertIn('also_viewed', response.data)
        self.assertIn('also_bought', response.data)
        self.assertIn('similar', response.data)
    
    def test_track_view_endpoint(self):
        """Test POST /api/products/<id>/track_view/"""
        url = reverse('store:api-product-track-view', args=[self.product1.id])
        response = self.client.post(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
    
    def test_pagination(self):
        """Test API pagination"""
        # Create more products
        for i in range(25):
            Product.objects.create(
                name=f'Paint {i+3}', brand=self.brand, category=self.category,
                price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
            )
        
        url = reverse('store:api-product-list')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['results']), 20)  # Default page size
        self.assertIsNotNone(response.data['next'])
    
    def test_authentication(self):
        """Test token authentication"""
        from rest_framework.authtoken.models import Token
        
        # Create token
        token = Token.objects.create(user=self.user)
        
        # Test with token
        self.client.credentials(HTTP_AUTHORIZATION=f'Token {token.key}')
        url = reverse('store:api-product-list')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
    
    def test_orders_endpoint_requires_auth(self):
        """Test orders endpoint requires authentication"""
        url = reverse('store:api-order-list')
        response = self.client.get(url)
        
        # With TokenAuthentication configured, DRF returns 401 for missing credentials
        self.assertEqual(response.status_code, status.HTTP_401_UNAUTHORIZED)
    
    def test_orders_endpoint_with_auth(self):
        """Test orders endpoint with authentication"""
        self.client.force_authenticate(user=self.admin)
        
        url = reverse('store:api-order-list')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
    
    def test_authenticated_cart_add_item(self):
        """Test authenticated cart add item API"""
        self.client.force_authenticate(user=self.user)
        
        url = reverse('store:cart-add-item')
        response = self.client.post(url, {
            'product_id': self.product1.id,
            'quantity': 2
        }, format='json')
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
        self.assertEqual(response.data['quantity'], 2)
    
    def test_authenticated_cart_update_item(self):
        """Test authenticated cart update item API"""
        from store.models import Cart, CartItem
        
        self.client.force_authenticate(user=self.user)
        
        # First add an item
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(
            cart=cart,
            product=self.product1,
            quantity=2,
            price=self.product1.price
        )
        
        # Now update it
        url = reverse('store:cart-update-item')
        response = self.client.post(url, {
            'product_id': self.product1.id,
            'quantity': 5
        }, format='json')
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
        self.assertEqual(response.data['quantity'], 5)
    
    def test_authenticated_cart_remove_item(self):
        """Test authenticated cart remove item API"""
        from store.models import Cart, CartItem
        
        self.client.force_authenticate(user=self.user)
        
        # First add an item
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(
            cart=cart,
            product=self.product1,
            quantity=2,
            price=self.product1.price
        )
        
        # Now remove it
        url = reverse('store:cart-remove-item')
        response = self.client.post(url, {
            'product_id': self.product1.id
        }, format='json')
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
    
    def test_authenticated_cart_clear(self):
        """Test authenticated cart clear API"""
        from store.models import Cart, CartItem
        
        self.client.force_authenticate(user=self.user)
        
        # Add some items
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(cart=cart, product=self.product1, quantity=2, price=self.product1.price)
        CartItem.objects.create(cart=cart, product=self.product2, quantity=1, price=self.product2.price)
        
        # Clear cart
        url = reverse('store:cart-clear')
        response = self.client.post(url, format='json')
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
        self.assertEqual(response.data['total_items'], 0)
    
    def test_authenticated_cart_apply_coupon(self):
        """Test authenticated cart apply coupon API"""
        from store.models import Cart, CartItem, Coupon
        from datetime import timedelta
        from django.utils import timezone
        
        self.client.force_authenticate(user=self.user)
        
        # Create a coupon
        coupon = Coupon.objects.create(
            code='TEST10',
            discount_percentage=10,
            valid_from=timezone.now() - timedelta(days=1),
            valid_to=timezone.now() + timedelta(days=30),
            is_active=True,
            min_purchase_amount=50
        )
        
        # Add items to cart
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(cart=cart, product=self.product1, quantity=1, price=self.product1.price)
        
        # Apply coupon
        url = reverse('store:cart-apply-coupon')
        response = self.client.post(url, {
            'code': 'TEST10'
        }, format='json')
        
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertTrue(response.data['success'])
        self.assertEqual(response.data['code'], 'TEST10')
        self.assertGreater(response.data['discount_amount'], 0)
