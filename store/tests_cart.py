from django.test import TestCase, Client
from django.urls import reverse

from .models import Brand, Category, Product, Order


class CartCheckoutTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        self.product = Product.objects.create(
            name='Test Paint', brand=self.brand, category=self.category,
            price=100.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True
        )

    def test_add_to_cart_session(self):
        url = reverse('store:cart_add', args=[self.product.pk])
        resp = self.client.post(url, {'quantity': 2}, follow=False)
        self.assertIn(resp.status_code, (302, 303, 301))
        session = self.client.session
        cart = session.get('cart', {})
        self.assertIn(str(self.product.pk), cart)
        self.assertEqual(cart[str(self.product.pk)], 2)

    def test_checkout_creates_order(self):
        # put items in session
        session = self.client.session
        session['cart'] = {str(self.product.pk): 3}
        session.save()

        url = reverse('store:checkout')
        resp = self.client.post(url, {'name': 'Alice', 'phone': '012345', 'address': 'Test Address'}, follow=False)
        self.assertIn(resp.status_code, (302, 303, 301))
        # order created
        orders = Order.objects.all()
        self.assertEqual(orders.count(), 1)
        order = orders.first()
        self.assertEqual(order.full_name, 'Alice')
        # session cart cleared
        session = self.client.session
        self.assertNotIn('cart', session)

    def test_checkout_marks_paid_for_stripe_and_paypal(self):
        session = self.client.session
        session['cart'] = {str(self.product.pk): 1}
        session.save()

        url = reverse('store:checkout')
        # Stripe
        resp = self.client.post(url, {
            'name': 'Bob', 'phone': '000', 'address': 'Addr', 'payment_method': 'stripe'
        }, follow=False)
        self.assertIn(resp.status_code, (302, 303, 301))
        order = Order.objects.latest('id')
        self.assertEqual(order.payment_method, 'stripe')
        self.assertEqual(order.payment_status, 'paid')
        self.assertTrue(order.payment_reference and 'STRIPE' in order.payment_reference.upper())

        # PayPal
        session = self.client.session
        session['cart'] = {str(self.product.pk): 2}
        session.save()
        resp = self.client.post(url, {
            'name': 'Carol', 'phone': '111', 'address': 'Addr2', 'payment_method': 'paypal'
        }, follow=False)
        self.assertIn(resp.status_code, (302, 303, 301))
        order = Order.objects.latest('id')
        self.assertEqual(order.payment_method, 'paypal')
        self.assertEqual(order.payment_status, 'paid')
        self.assertTrue(order.payment_reference and 'PAYPAL' in order.payment_reference.upper())

    def test_checkout_cod_remains_pending(self):
        session = self.client.session
        session['cart'] = {str(self.product.pk): 1}
        session.save()

        url = reverse('store:checkout')
        resp = self.client.post(url, {
            'name': 'Dave', 'phone': '222', 'address': 'Addr3', 'payment_method': 'cod'
        }, follow=False)
        self.assertIn(resp.status_code, (302, 303, 301))
        order = Order.objects.latest('id')
        self.assertEqual(order.payment_method, 'cod')
        self.assertEqual(order.payment_status, 'pending')
