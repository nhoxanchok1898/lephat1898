from django.test import TestCase
from django.contrib.auth.models import User
from store.models import (
    Brand, Category, Product, UserProfile, Wishlist,
    Review, ReviewImage, ReviewHelpful, PaymentLog, EmailLog, Order
)


class UserProfileModelTest(TestCase):
    def setUp(self):
        self.user = User.objects.create_user(username='testuser', email='test@example.com', password='testpass123')
    
    def test_user_profile_creation(self):
        profile = UserProfile.objects.create(
            user=self.user,
            phone='123-456-7890',
            address='123 Test St',
            email_verified=True
        )
        self.assertEqual(profile.user, self.user)
        self.assertEqual(profile.phone, '123-456-7890')
        self.assertTrue(profile.email_verified)
    
    def test_user_profile_str(self):
        profile = UserProfile.objects.create(user=self.user)
        self.assertEqual(str(profile), f"Profile of {self.user.username}")


class WishlistModelTest(TestCase):
    def setUp(self):
        self.user = User.objects.create_user(username='testuser', password='testpass123')
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        self.product = Product.objects.create(
            name='Test Paint',
            brand=self.brand,
            category=self.category,
            price=100.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )
    
    def test_wishlist_creation(self):
        wishlist_item = Wishlist.objects.create(user=self.user, product=self.product)
        self.assertEqual(wishlist_item.user, self.user)
        self.assertEqual(wishlist_item.product, self.product)
    
    def test_wishlist_unique_together(self):
        Wishlist.objects.create(user=self.user, product=self.product)
        # Attempting to create duplicate should raise IntegrityError
        from django.db import IntegrityError
        with self.assertRaises(IntegrityError):
            Wishlist.objects.create(user=self.user, product=self.product)
    
    def test_wishlist_str(self):
        wishlist_item = Wishlist.objects.create(user=self.user, product=self.product)
        self.assertIn(self.user.username, str(wishlist_item))
        self.assertIn(self.product.name, str(wishlist_item))


class ReviewModelTest(TestCase):
    def setUp(self):
        self.user = User.objects.create_user(username='reviewer', password='testpass123')
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        self.product = Product.objects.create(
            name='Test Paint',
            brand=self.brand,
            category=self.category,
            price=100.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )
    
    def test_review_creation(self):
        review = Review.objects.create(
            product=self.product,
            user=self.user,
            rating=5,
            comment='Great product!',
            verified_purchase=True,
            is_approved=True
        )
        self.assertEqual(review.rating, 5)
        self.assertEqual(review.comment, 'Great product!')
        self.assertTrue(review.verified_purchase)
        self.assertTrue(review.is_approved)
    
    def test_review_str(self):
        review = Review.objects.create(
            product=self.product,
            user=self.user,
            rating=4,
            comment='Good product'
        )
        self.assertIn(self.user.username, str(review))
        self.assertIn(self.product.name, str(review))
        self.assertIn('4', str(review))
    
    def test_review_helpful_count(self):
        review = Review.objects.create(
            product=self.product,
            user=self.user,
            rating=5,
            comment='Excellent!'
        )
        self.assertEqual(review.helpful_count, 0)
        
        review.helpful_count = 5
        review.save()
        self.assertEqual(review.helpful_count, 5)


class ReviewHelpfulModelTest(TestCase):
    def setUp(self):
        self.user1 = User.objects.create_user(username='reviewer', password='testpass123')
        self.user2 = User.objects.create_user(username='voter', password='testpass123')
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        self.product = Product.objects.create(
            name='Test Paint',
            brand=self.brand,
            category=self.category,
            price=100.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )
        self.review = Review.objects.create(
            product=self.product,
            user=self.user1,
            rating=5,
            comment='Great!'
        )
    
    def test_review_helpful_creation(self):
        helpful = ReviewHelpful.objects.create(review=self.review, user=self.user2)
        self.assertEqual(helpful.review, self.review)
        self.assertEqual(helpful.user, self.user2)
    
    def test_review_helpful_unique_together(self):
        ReviewHelpful.objects.create(review=self.review, user=self.user2)
        from django.db import IntegrityError
        with self.assertRaises(IntegrityError):
            ReviewHelpful.objects.create(review=self.review, user=self.user2)


class PaymentLogModelTest(TestCase):
    def setUp(self):
        self.order = Order.objects.create(
            full_name='Test User',
            phone='123-456-7890',
            address='123 Test St'
        )
    
    def test_payment_log_creation(self):
        payment_log = PaymentLog.objects.create(
            order=self.order,
            transaction_id='TXN123456',
            amount=100.00,
            status='success',
            payment_method='stripe',
            raw_response='{"status": "success"}'
        )
        self.assertEqual(payment_log.transaction_id, 'TXN123456')
        self.assertEqual(payment_log.amount, 100.00)
        self.assertEqual(payment_log.status, 'success')
    
    def test_payment_log_str(self):
        payment_log = PaymentLog.objects.create(
            order=self.order,
            transaction_id='TXN789',
            amount=50.00,
            status='pending',
            payment_method='paypal'
        )
        self.assertIn('TXN789', str(payment_log))
        self.assertIn(str(self.order.pk), str(payment_log))


class EmailLogModelTest(TestCase):
    def test_email_log_creation(self):
        email_log = EmailLog.objects.create(
            recipient='test@example.com',
            subject='Test Email',
            template_name='welcome_email',
            status='sent'
        )
        self.assertEqual(email_log.recipient, 'test@example.com')
        self.assertEqual(email_log.subject, 'Test Email')
        self.assertEqual(email_log.status, 'sent')
    
    def test_email_log_str(self):
        email_log = EmailLog.objects.create(
            recipient='user@example.com',
            subject='Order Confirmation',
            template_name='order_confirm',
            status='pending'
        )
        self.assertIn('user@example.com', str(email_log))
        self.assertIn('Order Confirmation', str(email_log))
