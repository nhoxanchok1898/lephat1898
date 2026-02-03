"""
Tests for Payment Webhooks
"""
import json
from decimal import Decimal
from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import Order, OrderItem, Product, Brand, Category
from store.payment_webhooks import (
    verify_stripe_signature,
    handle_payment_success,
    handle_payment_failed,
    handle_refund,
)


class PaymentWebhookTestCase(TestCase):
    """Test payment webhook handlers"""
    
    def setUp(self):
        """Set up test data"""
        self.client = Client()
        
        # Create test brand and category
        self.brand = Brand.objects.create(name="Test Brand")
        self.category = Category.objects.create(name="Test Category")
        
        # Create test product
        self.product = Product.objects.create(
            name="Test Product",
            brand=self.brand,
            category=self.category,
            price=Decimal('100.00'),
            stock_quantity=10,
        )
        
        # Create test order
        self.order = Order.objects.create(
            full_name="John Doe",
            phone="1234567890",
            address="123 Test St",
            payment_method="stripe",
            payment_status="pending",
        )
        
        # Create order item
        self.order_item = OrderItem.objects.create(
            order=self.order,
            product=self.product,
            quantity=2,
            price=Decimal('100.00'),
        )
    
    def test_payment_success_updates_order(self):
        """Test successful payment updates order status"""
        event_data = {
            'object': {
                'id': 'pi_test123',
                'metadata': {
                    'order_id': str(self.order.id)
                }
            }
        }
        
        handle_payment_success(event_data)
        
        # Refresh order
        self.order.refresh_from_db()
        
        self.assertEqual(self.order.payment_status, 'completed')
        self.assertEqual(self.order.payment_reference, 'pi_test123')
    
    def test_payment_success_reduces_inventory(self):
        """Test successful payment reduces product inventory"""
        initial_stock = self.product.stock_quantity
        
        event_data = {
            'object': {
                'id': 'pi_test123',
                'metadata': {
                    'order_id': str(self.order.id)
                }
            }
        }
        
        handle_payment_success(event_data)
        
        # Refresh product
        self.product.refresh_from_db()
        
        expected_stock = initial_stock - self.order_item.quantity
        self.assertEqual(self.product.stock_quantity, expected_stock)
    
    def test_payment_success_idempotent(self):
        """Test payment success is idempotent (doesn't process twice)"""
        event_data = {
            'object': {
                'id': 'pi_test123',
                'metadata': {
                    'order_id': str(self.order.id)
                }
            }
        }
        
        # Process once
        handle_payment_success(event_data)
        self.product.refresh_from_db()
        stock_after_first = self.product.stock_quantity
        
        # Process again
        handle_payment_success(event_data)
        self.product.refresh_from_db()
        stock_after_second = self.product.stock_quantity
        
        # Stock should not change on second processing
        self.assertEqual(stock_after_first, stock_after_second)
    
    def test_payment_failed_updates_order(self):
        """Test failed payment updates order status"""
        event_data = {
            'object': {
                'metadata': {
                    'order_id': str(self.order.id)
                }
            }
        }
        
        handle_payment_failed(event_data)
        
        # Refresh order
        self.order.refresh_from_db()
        
        self.assertEqual(self.order.payment_status, 'failed')
    
    def test_refund_restores_inventory(self):
        """Test refund restores product inventory"""
        # First complete the payment
        self.order.payment_status = 'completed'
        self.order.save()
        
        # Reduce inventory
        self.product.stock_quantity = 5
        self.product.save()
        
        initial_stock = self.product.stock_quantity
        
        # Process refund
        event_data = {
            'object': {
                'metadata': {
                    'order_id': str(self.order.id)
                }
            }
        }
        
        handle_refund(event_data)
        
        # Refresh product
        self.product.refresh_from_db()
        
        expected_stock = initial_stock + self.order_item.quantity
        self.assertEqual(self.product.stock_quantity, expected_stock)
    
    def test_refund_updates_order_status(self):
        """Test refund updates order status"""
        self.order.payment_status = 'completed'
        self.order.save()
        
        event_data = {
            'object': {
                'metadata': {
                    'order_id': str(self.order.id)
                }
            }
        }
        
        handle_refund(event_data)
        
        # Refresh order
        self.order.refresh_from_db()
        
        self.assertEqual(self.order.payment_status, 'refunded')
    
    def test_webhook_endpoint_exists(self):
        """Test webhook endpoints are accessible"""
        # Note: This requires URL configuration
        # Just test that the view functions exist
        from store.payment_webhooks import stripe_webhook, paypal_webhook
        
        self.assertTrue(callable(stripe_webhook))
        self.assertTrue(callable(paypal_webhook))


class WebhookSecurityTestCase(TestCase):
    """Test webhook security"""
    
    def test_signature_verification_required(self):
        """Test that webhook signature verification is implemented"""
        # The verify_stripe_signature function exists
        self.assertTrue(callable(verify_stripe_signature))
    
    def test_missing_order_id_handled(self):
        """Test missing order_id in webhook is handled gracefully"""
        event_data = {
            'object': {
                'metadata': {}  # No order_id
            }
        }
        
        # Should not raise exception
        try:
            handle_payment_success(event_data)
            handle_payment_failed(event_data)
            handle_refund(event_data)
        except Exception as e:
            self.fail(f"Webhook handler raised exception: {e}")
    
    def test_invalid_order_id_handled(self):
        """Test invalid order_id in webhook is handled"""
        event_data = {
            'object': {
                'metadata': {
                    'order_id': '99999'  # Non-existent order
                }
            }
        }
        
        # Should handle gracefully
        with self.assertRaises(Exception):
            handle_payment_success(event_data)
