from django.test import TestCase
from django.contrib.auth.models import User
from django.core import mail
from store.models import Brand, Category, Product, Order, OrderItem, EmailQueue, EmailTemplate
from store.email_views import (
    send_welcome_email, send_order_confirmation,
    send_cart_abandonment, send_back_in_stock
)


class EmailTests(TestCase):
    def setUp(self):
        self.user = User.objects.create_user(
            username='testuser',
            email='test@example.com',
            password='testpass'
        )
        
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        
        self.product = Product.objects.create(
            name='Test Paint', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )
    
    def test_welcome_email(self):
        """Test sending welcome email"""
        send_welcome_email(self.user)
        
        # Check email was sent
        self.assertEqual(len(mail.outbox), 1)
        self.assertIn('Welcome', mail.outbox[0].subject)
        self.assertEqual(mail.outbox[0].to, [self.user.email])
    
    def test_order_confirmation_email(self):
        """Test sending order confirmation email"""
        order = Order.objects.create(
            full_name='Test User',
            phone='123456',
            address='Test Address'
        )
        OrderItem.objects.create(order=order, product=self.product, quantity=2, price=100)
        
        send_order_confirmation(order)
        
        # Check email was sent
        self.assertEqual(len(mail.outbox), 1)
        self.assertIn('Order Confirmation', mail.outbox[0].subject)
    
    def test_cart_abandonment_email(self):
        """Test cart abandonment email scheduling"""
        cart_items = [
            {'product': self.product, 'quantity': 2}
        ]
        
        send_cart_abandonment(self.user, cart_items)
        
        # Check email was queued
        queued = EmailQueue.objects.filter(to_email=self.user.email).first()
        self.assertIsNotNone(queued)
        self.assertIsNotNone(queued.scheduled_for)
    
    def test_back_in_stock_email(self):
        """Test back in stock notification email"""
        result = send_back_in_stock(self.user, self.product)
        
        # Check email was sent
        self.assertTrue(result)
        self.assertEqual(len(mail.outbox), 1)
    
    def test_email_queue_creation(self):
        """Test email queue entry creation"""
        email = EmailQueue.objects.create(
            to_email='test@example.com',
            subject='Test Subject',
            html_content='<p>Test content</p>',
            status='pending'
        )
        
        self.assertEqual(email.status, 'pending')
        self.assertEqual(email.retry_count, 0)
    
    def test_email_template_creation(self):
        """Test email template creation"""
        template = EmailTemplate.objects.create(
            name='Test Template',
            email_type='welcome',
            subject='Welcome {user_name}',
            html_content='<p>Hello {user_name}</p>',
            is_active=True
        )
        
        self.assertEqual(template.email_type, 'welcome')
        self.assertTrue(template.is_active)
    
    def test_email_with_template(self):
        """Test sending email using template"""
        # Create template
        EmailTemplate.objects.create(
            name='Welcome Email',
            email_type='welcome',
            subject='Welcome to Our Store!',
            html_content='<p>Hello {user_name}, Welcome!</p>',
            is_active=True
        )
        
        send_welcome_email(self.user)
        
        self.assertEqual(len(mail.outbox), 1)
    
    def test_newsletter_subscription(self):
        """Test newsletter subscription model"""
        from store.models import NewsletterSubscription
        
        subscription = NewsletterSubscription.objects.create(
            email='newsletter@example.com',
            user=self.user,
            is_active=True
        )
        
        self.assertTrue(subscription.is_active)
        self.assertIsNone(subscription.unsubscribed_at)
