from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import (
    Brand, Category, Product, StockLevel, 
    StockAlert, PreOrder, BackInStockNotification
)


class InventoryTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.user = User.objects.create_user(username='admin', password='admin', is_staff=True)
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        
        self.product = Product.objects.create(
            name='Test Paint', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )
    
    def test_real_time_stock_update(self):
        """Test updating stock levels"""
        self.client.login(username='admin', password='admin')
        
        url = reverse('store:update_stock', args=[self.product.id])
        response = self.client.post(url, {'quantity': 50})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['success'])
        self.assertEqual(data['quantity'], 50)
        
        # Verify stock level was created
        stock = StockLevel.objects.get(product=self.product)
        self.assertEqual(stock.quantity, 50)
    
    def test_low_stock_alert(self):
        """Test low stock alert creation"""
        self.client.login(username='admin', password='admin')
        
        # Set stock to low level
        url = reverse('store:update_stock', args=[self.product.id])
        self.client.post(url, {'quantity': 5})
        
        # Check if alert was created
        alert = StockAlert.objects.filter(product=self.product, alert_type='low').first()
        self.assertIsNotNone(alert)
        self.assertFalse(alert.resolved)
    
    def test_out_of_stock_alert(self):
        """Test out of stock alert creation"""
        self.client.login(username='admin', password='admin')
        
        # Set stock to zero
        url = reverse('store:update_stock', args=[self.product.id])
        self.client.post(url, {'quantity': 0})
        
        # Check if alert was created
        alert = StockAlert.objects.filter(product=self.product, alert_type='out').first()
        self.assertIsNotNone(alert)
        self.assertFalse(alert.resolved)
    
    def test_check_stock(self):
        """Test checking stock availability"""
        StockLevel.objects.create(product=self.product, quantity=100)
        
        url = reverse('store:check_stock', args=[self.product.id])
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['in_stock'])
        self.assertEqual(data['quantity'], 100)
    
    def test_pre_order_creation(self):
        """Test creating a pre-order"""
        url = reverse('store:pre_order', args=[self.product.id])
        response = self.client.post(url, {
            'email': 'test@example.com',
            'name': 'Test User',
            'quantity': 2
        })
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['success'])
        
        # Verify pre-order was created
        pre_order = PreOrder.objects.filter(product=self.product).first()
        self.assertIsNotNone(pre_order)
        self.assertEqual(pre_order.customer_email, 'test@example.com')
        self.assertEqual(pre_order.quantity, 2)
    
    def test_back_in_stock_notification(self):
        """Test signing up for back in stock notification"""
        url = reverse('store:back_in_stock', args=[self.product.id])
        response = self.client.post(url, {'email': 'test@example.com'})
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertTrue(data['success'])
        
        # Verify notification was created
        notification = BackInStockNotification.objects.filter(
            product=self.product, 
            email='test@example.com'
        ).first()
        self.assertIsNotNone(notification)
        self.assertFalse(notification.notified)
    
    def test_low_stock_alert_view(self):
        """Test viewing low stock alerts"""
        self.client.login(username='admin', password='admin')
        
        # Create a low stock situation
        StockLevel.objects.create(product=self.product, quantity=5)
        StockAlert.objects.create(product=self.product, alert_type='low')
        
        url = reverse('store:low_stock_alerts')
        response = self.client.get(url)
        
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('alerts', data)
        self.assertEqual(len(data['alerts']), 1)
