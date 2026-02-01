from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from django.utils import timezone
from datetime import timedelta
from store.models import Brand, Category, Product, Coupon, AppliedCoupon


class CouponTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.user = User.objects.create_user(username='testuser', password='testpass')
        self.admin = User.objects.create_user(username='admin', password='admin', is_staff=True)
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        
        self.product = Product.objects.create(
            name='Test Paint', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )
        
        # Create a test coupon
        self.coupon = Coupon.objects.create(
            code='TEST10',
            description='Test 10% discount',
            discount_type='percentage',
            discount_value=10,
            min_purchase_amount=50,
            start_date=timezone.now() - timedelta(days=1),
            end_date=timezone.now() + timedelta(days=30),
            is_active=True
        )
    
    def test_apply_coupon(self):
        """Test applying a valid coupon"""
        # Add product to cart
        session = self.client.session
        session['cart'] = {str(self.product.id): 2}  # $200 total
        session.save()
        
        url = reverse('store:apply_coupon')
        response = self.client.post(url, {'coupon_code': 'TEST10'})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['success'])
        self.assertEqual(float(data['discount']), 20.0)  # 10% of 200
        self.assertEqual(float(data['final_total']), 180.0)
    
    def test_apply_invalid_coupon(self):
        """Test applying an invalid coupon code"""
        url = reverse('store:apply_coupon')
        response = self.client.post(url, {'coupon_code': 'INVALID'})
        
        self.assertEqual(response.status_code, 404)
        data = response.json()
        self.assertIn('error', data)
    
    def test_coupon_validation(self):
        """Test coupon validation logic"""
        # Valid coupon
        self.assertTrue(self.coupon.is_valid())
        
        # Expired coupon
        self.coupon.end_date = timezone.now() - timedelta(days=1)
        self.coupon.save()
        self.assertFalse(self.coupon.is_valid())
    
    def test_percentage_discount(self):
        """Test percentage discount calculation"""
        discount = self.coupon.calculate_discount(100)
        self.assertEqual(discount, 10)  # 10% of 100
    
    def test_fixed_discount(self):
        """Test fixed amount discount calculation"""
        fixed_coupon = Coupon.objects.create(
            code='FIXED20',
            discount_type='fixed',
            discount_value=20,
            start_date=timezone.now() - timedelta(days=1),
            end_date=timezone.now() + timedelta(days=30),
            is_active=True
        )
        
        discount = fixed_coupon.calculate_discount(100)
        self.assertEqual(discount, 20)
    
    def test_minimum_purchase_requirement(self):
        """Test minimum purchase amount validation"""
        # Cart total below minimum
        session = self.client.session
        session['cart'] = {str(self.product.id): 1}  # $100 total
        session.save()
        
        # Create coupon with higher minimum
        high_min_coupon = Coupon.objects.create(
            code='HIGH100',
            discount_type='percentage',
            discount_value=10,
            min_purchase_amount=150,
            start_date=timezone.now() - timedelta(days=1),
            end_date=timezone.now() + timedelta(days=30),
            is_active=True
        )
        
        url = reverse('store:apply_coupon')
        response = self.client.post(url, {'coupon_code': 'HIGH100'})
        
        self.assertEqual(response.status_code, 400)
        data = response.json()
        self.assertIn('error', data)
        self.assertIn('Minimum purchase', data['error'])
    
    def test_remove_coupon(self):
        """Test removing an applied coupon"""
        # Apply coupon first
        session = self.client.session
        session['cart'] = {str(self.product.id): 2}
        session['applied_coupon'] = {
            'code': 'TEST10',
            'discount': '20.00'
        }
        session.save()
        
        url = reverse('store:remove_coupon')
        response = self.client.post(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['success'])
    
    def test_coupon_usage_limit(self):
        """Test coupon max uses limit"""
        # Set max uses to 1
        self.coupon.max_uses = 1
        self.coupon.used_count = 1
        self.coupon.save()
        
        self.assertFalse(self.coupon.is_valid())
    
    def test_coupon_list_admin(self):
        """Test admin coupon list view"""
        self.client.login(username='admin', password='admin')
        
        url = reverse('store:coupon_list_admin')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        self.assertIn('coupons', response.context)
    
    def test_coupon_create_admin(self):
        """Test admin coupon creation"""
        self.client.login(username='admin', password='admin')
        
        url = reverse('store:coupon_create_admin')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
    
    def test_user_specific_coupon(self):
        """Test coupons restricted to specific users"""
        user_coupon = Coupon.objects.create(
            code='USER10',
            discount_type='percentage',
            discount_value=10,
            start_date=timezone.now() - timedelta(days=1),
            end_date=timezone.now() + timedelta(days=30),
            is_active=True
        )
        user_coupon.allowed_users.add(self.user)
        
        self.client.login(username='testuser', password='testpass')
        
        # Add to cart
        session = self.client.session
        session['cart'] = {str(self.product.id): 2}
        session.save()
        
        url = reverse('store:apply_coupon')
        response = self.client.post(url, {'coupon_code': 'USER10'})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['success'])
