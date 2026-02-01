from django.test import TestCase, Client
from django.urls import reverse

from .models import Brand, Category, Product, Order, OrderItem


class CheckoutIntegrationTests(TestCase):
    """Integration tests for the complete checkout flow."""

    def setUp(self):
        self.client = Client()
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product1 = Product.objects.create(
            name='Test Paint 1',
            brand=self.brand,
            category=self.category,
            price=100.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )
        self.product2 = Product.objects.create(
            name='Test Paint 2',
            brand=self.brand,
            category=self.category,
            price=200.00,
            unit_type=Product.UNIT_KG,
            volume=10,
            is_active=True
        )

    def test_complete_checkout_flow(self):
        """Test the complete flow: browse -> add to cart -> checkout -> success."""
        # 1. Browse products
        response = self.client.get(reverse('store:product_list'))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Test Paint 1')
        self.assertContains(response, 'Test Paint 2')

        # 2. View product detail
        response = self.client.get(reverse('store:product_detail', args=[self.product1.pk]))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Test Paint 1')

        # 3. Add first product to cart
        response = self.client.post(
            reverse('store:cart_add', args=[self.product1.pk]),
            {'quantity': 2},
            follow=True
        )
        self.assertEqual(response.status_code, 200)

        # 4. Add second product to cart
        response = self.client.post(
            reverse('store:cart_add', args=[self.product2.pk]),
            {'quantity': 3},
            follow=True
        )
        self.assertEqual(response.status_code, 200)

        # 5. View cart
        response = self.client.get(reverse('store:cart_view'))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Test Paint 1')
        self.assertContains(response, 'Test Paint 2')

        # Verify cart totals
        cart = self.client.session.get('cart', {})
        self.assertEqual(cart[str(self.product1.pk)], 2)
        self.assertEqual(cart[str(self.product2.pk)], 3)

        # 6. Checkout
        response = self.client.get(reverse('store:checkout'))
        self.assertEqual(response.status_code, 200)

        # 7. Submit order
        response = self.client.post(
            reverse('store:checkout'),
            {
                'name': 'John Doe',
                'phone': '0123456789',
                'address': '123 Test Street, Test City'
            },
            follow=True
        )
        self.assertEqual(response.status_code, 200)

        # 8. Verify order was created
        orders = Order.objects.all()
        self.assertEqual(orders.count(), 1)
        order = orders.first()
        self.assertEqual(order.full_name, 'John Doe')
        self.assertEqual(order.phone, '0123456789')
        self.assertEqual(order.address, '123 Test Street, Test City')

        # 9. Verify order items
        order_items = order.items.all()
        self.assertEqual(order_items.count(), 2)

        item1 = order_items.filter(product=self.product1).first()
        self.assertIsNotNone(item1)
        self.assertEqual(item1.quantity, 2)
        self.assertEqual(item1.price, self.product1.price)

        item2 = order_items.filter(product=self.product2).first()
        self.assertIsNotNone(item2)
        self.assertEqual(item2.quantity, 3)
        self.assertEqual(item2.price, self.product2.price)

        # 10. Verify cart was cleared
        cart = self.client.session.get('cart', {})
        self.assertEqual(len(cart), 0)

        # 11. Verify success page
        # Check that we're on the success page
        self.assertContains(response, 'Your order has been placed')

    def test_cart_update_flow(self):
        """Test updating cart quantities."""
        # Add product to cart
        self.client.post(
            reverse('store:cart_add', args=[self.product1.pk]),
            {'quantity': 5}
        )

        # Update quantity
        response = self.client.post(
            reverse('store:cart_update', args=[self.product1.pk]),
            {'quantity': 3},
            follow=True
        )
        self.assertEqual(response.status_code, 200)

        # Verify updated quantity
        cart = self.client.session.get('cart', {})
        self.assertEqual(cart[str(self.product1.pk)], 3)

    def test_cart_remove_flow(self):
        """Test removing items from cart."""
        # Add two products
        self.client.post(
            reverse('store:cart_add', args=[self.product1.pk]),
            {'quantity': 2}
        )
        self.client.post(
            reverse('store:cart_add', args=[self.product2.pk]),
            {'quantity': 1}
        )

        # Remove first product
        response = self.client.get(
            reverse('store:cart_remove', args=[self.product1.pk]),
            follow=True
        )
        self.assertEqual(response.status_code, 200)

        # Verify only second product remains
        cart = self.client.session.get('cart', {})
        self.assertNotIn(str(self.product1.pk), cart)
        self.assertIn(str(self.product2.pk), cart)
        self.assertEqual(cart[str(self.product2.pk)], 1)

    def test_ajax_cart_update(self):
        """Test AJAX cart update endpoint."""
        # Add product to cart
        self.client.post(
            reverse('store:cart_add', args=[self.product1.pk]),
            {'quantity': 2}
        )

        # Update via AJAX
        response = self.client.post(
            reverse('store:cart_update_ajax', args=[self.product1.pk]),
            {'quantity': 5}
        )
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertEqual(data['pk'], self.product1.pk)
        self.assertEqual(data['quantity'], 5)
        self.assertEqual(float(data['subtotal']), 500.00)  # 5 * 100
        self.assertEqual(float(data['total']), 500.00)

    def test_ajax_cart_remove(self):
        """Test AJAX cart remove endpoint."""
        # Add product to cart
        self.client.post(
            reverse('store:cart_add', args=[self.product1.pk]),
            {'quantity': 2}
        )

        # Remove via AJAX
        response = self.client.post(
            reverse('store:cart_remove_ajax', args=[self.product1.pk])
        )
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertEqual(data['removed'], self.product1.pk)
        self.assertEqual(len(data['items']), 0)
        self.assertEqual(float(data['total']), 0.00)

    def test_cart_summary_ajax(self):
        """Test AJAX cart summary endpoint."""
        # Add products to cart
        self.client.post(
            reverse('store:cart_add', args=[self.product1.pk]),
            {'quantity': 2}
        )
        self.client.post(
            reverse('store:cart_add', args=[self.product2.pk]),
            {'quantity': 1}
        )

        # Get cart summary
        response = self.client.get(reverse('store:cart_summary_ajax'))
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertEqual(len(data['items']), 2)
        self.assertEqual(float(data['total']), 400.00)  # (2*100) + (1*200)

    def test_search_suggestions(self):
        """Test search suggestions endpoint."""
        response = self.client.get(
            reverse('store:search_suggestions'),
            {'q': 'Test'}
        )
        self.assertEqual(response.status_code, 200)
        data = response.json()
        self.assertIn('results', data)
        self.assertEqual(len(data['results']), 2)

    def test_contact_view(self):
        """Test contact page renders."""
        response = self.client.get(reverse('store:contact'))
        self.assertEqual(response.status_code, 200)

    def test_empty_cart_checkout(self):
        """Test checkout with empty cart."""
        response = self.client.get(reverse('store:checkout'))
        self.assertEqual(response.status_code, 200)
        # Should still render, just with no items

    def test_product_get_absolute_url(self):
        """Test that Product model has get_absolute_url method."""
        url = self.product1.get_absolute_url()
        expected_url = reverse('store:product_detail', args=[self.product1.pk])
        self.assertEqual(url, expected_url)


class CartEdgeCaseTests(TestCase):
    """Test edge cases in cart functionality."""

    def setUp(self):
        self.client = Client()
        self.brand = Brand.objects.create(name='TestBrand')
        self.product = Product.objects.create(
            name='Test Product',
            brand=self.brand,
            price=100.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )

    def test_cart_with_inactive_product(self):
        """Test cart behavior with inactive products."""
        # Add product to cart
        self.client.post(
            reverse('store:cart_add', args=[self.product.pk]),
            {'quantity': 2}
        )

        # Make product inactive
        self.product.is_active = False
        self.product.save()

        # View cart - should handle gracefully
        response = self.client.get(reverse('store:cart_view'))
        self.assertEqual(response.status_code, 200)

    def test_cart_update_zero_quantity(self):
        """Test that updating to zero quantity removes item."""
        # Add product
        self.client.post(
            reverse('store:cart_add', args=[self.product.pk]),
            {'quantity': 5}
        )

        # Update to zero
        self.client.post(
            reverse('store:cart_update', args=[self.product.pk]),
            {'quantity': 0}
        )

        # Verify item removed
        cart = self.client.session.get('cart', {})
        self.assertNotIn(str(self.product.pk), cart)

    def test_ajax_update_invalid_quantity(self):
        """Test AJAX update with invalid quantity."""
        response = self.client.post(
            reverse('store:cart_update_ajax', args=[self.product.pk]),
            {'quantity': 'invalid'}
        )
        self.assertEqual(response.status_code, 400)
        data = response.json()
        self.assertIn('error', data)
