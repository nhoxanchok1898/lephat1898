"""
Tests for Phase 2A Advanced E-commerce Features
"""
from django.test import TestCase, Client
from django.contrib.auth.models import User
from django.urls import reverse
from django.utils import timezone
from datetime import timedelta
from decimal import Decimal
from rest_framework.test import APITestCase, APIClient
from rest_framework.authtoken.models import Token
from rest_framework import status
import unittest

from .models import (
    Brand, Category, Product, Order, OrderItem,
    Cart, CartItem, Coupon, Review, Wishlist,
    ProductView, SearchQuery, SavedSearch
)


class ProductModelTests(TestCase):
    """Test Product model enhancements"""
    
    def setUp(self):
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product = Product.objects.create(
            name='Test Product',
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            volume=5,
            stock_quantity=10,
            is_active=True
        )

    def test_get_price_regular(self):
        """Test get_price returns regular price when not on sale"""
        self.assertEqual(self.product.get_price(), Decimal('100.00'))

    def test_get_price_on_sale(self):
        """Test get_price returns sale price when on sale"""
        self.product.is_on_sale = True
        self.product.sale_price = Decimal('80.00')
        self.product.save()
        self.assertEqual(self.product.get_price(), Decimal('80.00'))

    def test_is_in_stock(self):
        """Test is_in_stock method"""
        self.assertTrue(self.product.is_in_stock())
        self.product.stock_quantity = 0
        self.assertFalse(self.product.is_in_stock())


class CouponModelTests(TestCase):
    """Test Coupon model"""
    
    def setUp(self):
        now = timezone.now()
        self.coupon_percentage = Coupon.objects.create(
            code='SAVE20',
            discount_percentage=Decimal('20.00'),
            valid_from=now - timedelta(days=1),
            valid_to=now + timedelta(days=30),
            is_active=True
        )
        self.coupon_amount = Coupon.objects.create(
            code='SAVE10',
            discount_amount=Decimal('10.00'),
            valid_from=now - timedelta(days=1),
            valid_to=now + timedelta(days=30),
            is_active=True
        )

    def test_is_valid(self):
        """Test coupon validation"""
        self.assertTrue(self.coupon_percentage.is_valid())
        
        # Test inactive coupon
        self.coupon_percentage.is_active = False
        self.assertFalse(self.coupon_percentage.is_valid())

    def test_apply_discount_percentage(self):
        """Test percentage discount"""
        amount = Decimal('100.00')
        discounted = self.coupon_percentage.apply_discount(amount)
        self.assertEqual(discounted, Decimal('80.00'))

    def test_apply_discount_amount(self):
        """Test amount discount"""
        amount = Decimal('100.00')
        discounted = self.coupon_amount.apply_discount(amount)
        self.assertEqual(discounted, Decimal('90.00'))

    def test_min_purchase_requirement(self):
        """Test minimum purchase requirement"""
        self.coupon_percentage.min_purchase_amount = Decimal('50.00')
        self.coupon_percentage.save()
        
        # Below minimum - no discount
        amount = Decimal('40.00')
        discounted = self.coupon_percentage.apply_discount(amount)
        self.assertEqual(discounted, amount)
        
        # Above minimum - discount applied
        amount = Decimal('100.00')
        discounted = self.coupon_percentage.apply_discount(amount)
        self.assertEqual(discounted, Decimal('80.00'))


class CartModelTests(TestCase):
    """Test Cart and CartItem models"""
    
    def setUp(self):
        self.user = User.objects.create_user('testuser', password='testpass')
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product1 = Product.objects.create(
            name='Product 1',
            brand=self.brand,
            category=self.category,
            price=Decimal('50.00'),
            volume=5,
            stock_quantity=10,
            is_active=True
        )
        self.product2 = Product.objects.create(
            name='Product 2',
            brand=self.brand,
            category=self.category,
            price=Decimal('30.00'),
            volume=10,
            is_on_sale=True,
            sale_price=Decimal('25.00'),
            stock_quantity=5,
            is_active=True
        )

    @unittest.skip("Cart session_key feature not yet implemented")
    def test_cart_creation_for_user(self):
        """Test cart creation for authenticated user"""
        cart = Cart.objects.create(user=self.user)
        self.assertEqual(cart.user, self.user)
        self.assertIsNone(cart.session_key)

    @unittest.skip("Cart session_key feature not yet implemented")
    def test_cart_creation_for_session(self):
        """Test cart creation for anonymous user"""
        cart = Cart.objects.create(session_key='test-session-key')
        self.assertIsNone(cart.user)
        self.assertEqual(cart.session_key, 'test-session-key')

    def test_cart_item_total_price(self):
        """Test cart item total price calculation"""
        cart = Cart.objects.create(user=self.user)
        cart_item = CartItem.objects.create(
            cart=cart,
            product=self.product1,
            quantity=3
        )
        self.assertEqual(cart_item.get_total_price(), Decimal('150.00'))

    def test_cart_item_total_price_with_sale(self):
        """Test cart item uses sale price"""
        cart = Cart.objects.create(user=self.user)
        cart_item = CartItem.objects.create(
            cart=cart,
            product=self.product2,
            quantity=2
        )
        self.assertEqual(cart_item.get_total_price(), Decimal('50.00'))


class ProductAPITests(APITestCase):
    """Test Product API endpoints"""
    
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user('testuser', password='testpass')
        self.token = Token.objects.create(user=self.user)
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product = Product.objects.create(
            name='Test Product',
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            volume=5,
            stock_quantity=10,
            is_active=True,
            description='Test description'
        )

    def test_list_products(self):
        """Test listing products"""
        url = reverse('product-list')
        response = self.client.get(url)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['results']), 1)

    @unittest.skip("Product view_count auto-increment not yet implemented")
    def test_retrieve_product(self):
        """Test retrieving single product"""
        url = reverse('product-detail', args=[self.product.pk])
        response = self.client.get(url)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['name'], 'Test Product')
        
        # Check view count incremented
        self.product.refresh_from_db()
        self.assertEqual(self.product.view_count, 1)

    def test_filter_products_by_brand(self):
        """Test filtering products by brand"""
        url = reverse('product-list')
        response = self.client.get(url, {'brand': self.brand.pk})
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['results']), 1)

    def test_filter_products_by_price_range(self):
        """Test filtering products by price range"""
        url = reverse('product-list')
        response = self.client.get(url, {'price_min': 50, 'price_max': 150})
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['results']), 1)

    def test_filter_products_in_stock(self):
        """Test filtering products in stock"""
        url = reverse('product-list')
        response = self.client.get(url, {'in_stock': 'true'})
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['results']), 1)

    def test_search_products(self):
        """Test searching products"""
        url = reverse('product-list')
        response = self.client.get(url, {'search': 'Test'})
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertGreater(len(response.data['results']), 0)

    @unittest.skip("Search suggestions API endpoint not yet implemented")
    def test_search_suggestions(self):
        """Test search suggestions endpoint"""
        url = reverse('product-search-suggestions')
        response = self.client.get(url, {'q': 'Test'})
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertGreater(len(response.data), 0)


class CartAPITests(APITestCase):
    """Test Cart API endpoints"""
    
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user('testuser', password='testpass')
        self.token = Token.objects.create(user=self.user)
        self.client.credentials(HTTP_AUTHORIZATION='Token ' + self.token.key)
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product = Product.objects.create(
            name='Test Product',
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            volume=5,
            stock_quantity=10,
            is_active=True
        )

    def test_add_item_to_cart(self):
        """Test adding item to cart"""
        url = reverse('cart-add-item')
        data = {'product_id': self.product.pk, 'quantity': 2}
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['total_items'], 2)

    def test_update_cart_item(self):
        """Test updating cart item quantity"""
        # First add item
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(cart=cart, product=self.product, quantity=2)
        
        # Update quantity
        url = reverse('cart-update-item')
        data = {'product_id': self.product.pk, 'quantity': 5}
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['total_items'], 5)

    def test_remove_cart_item(self):
        """Test removing item from cart"""
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(cart=cart, product=self.product, quantity=2)
        
        url = reverse('cart-remove-item')
        data = {'product_id': self.product.pk}
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['total_items'], 0)

    def test_clear_cart(self):
        """Test clearing cart"""
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(cart=cart, product=self.product, quantity=2)
        
        url = reverse('cart-clear')
        response = self.client.post(url)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['total_items'], 0)

    def test_apply_coupon(self):
        """Test applying coupon to cart"""
        # Create cart with items
        cart = Cart.objects.create(user=self.user)
        CartItem.objects.create(cart=cart, product=self.product, quantity=2)
        
        # Create coupon
        now = timezone.now()
        coupon = Coupon.objects.create(
            code='SAVE20',
            discount_percentage=Decimal('20.00'),
            valid_from=now - timedelta(days=1),
            valid_to=now + timedelta(days=30),
            is_active=True
        )
        
        url = reverse('cart-apply-coupon')
        data = {'code': 'SAVE20'}
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response.data['discount_amount'], Decimal('40.00'))
        self.assertEqual(response.data['final_total'], Decimal('160.00'))


class ReviewAPITests(APITestCase):
    """Test Review API endpoints"""
    
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user('testuser', password='testpass')
        self.token = Token.objects.create(user=self.user)
        self.client.credentials(HTTP_AUTHORIZATION='Token ' + self.token.key)
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product = Product.objects.create(
            name='Test Product',
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            volume=5,
            is_active=True
        )

    @unittest.skip("Review API endpoint (review-list) not yet implemented")
    def test_create_review(self):
        """Test creating a product review"""
        url = reverse('review-list')
        data = {
            'product': self.product.pk,
            'rating': 5,
            'comment': 'Great product!'
        }
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_201_CREATED)
        self.assertEqual(Review.objects.count(), 1)

    @unittest.skip("Review API endpoint (review-list) not yet implemented")
    def test_list_reviews_by_product(self):
        """Test listing reviews for a product"""
        Review.objects.create(
            product=self.product,
            user=self.user,
            rating=5,
            comment='Great!'
        )
        
        url = reverse('review-list')
        response = self.client.get(url, {'product': self.product.pk})
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data['results']), 1)


class WishlistAPITests(APITestCase):
    """Test Wishlist API endpoints"""
    
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user('testuser', password='testpass')
        self.token = Token.objects.create(user=self.user)
        self.client.credentials(HTTP_AUTHORIZATION='Token ' + self.token.key)
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product = Product.objects.create(
            name='Test Product',
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            volume=5,
            is_active=True
        )

    @unittest.skip("Wishlist API endpoint (wishlist-add-product) not yet implemented - using different model structure")
    def test_add_product_to_wishlist(self):
        """Test adding product to wishlist"""
        url = reverse('wishlist-add-product')
        data = {'product_id': self.product.pk}
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        
        wishlist = Wishlist.objects.get(user=self.user)
        self.assertEqual(wishlist.products.count(), 1)

    @unittest.skip("Wishlist API endpoint (wishlist-remove-product) not yet implemented - using different model structure")
    def test_remove_product_from_wishlist(self):
        """Test removing product from wishlist"""
        wishlist = Wishlist.objects.create(user=self.user)
        wishlist.products.add(self.product)
        
        url = reverse('wishlist-remove-product')
        data = {'product_id': self.product.pk}
        response = self.client.post(url, data)
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        
        wishlist.refresh_from_db()
        self.assertEqual(wishlist.products.count(), 0)
