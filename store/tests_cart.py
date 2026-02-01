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
        resp = self.client.post(url, {'quantity': 2}, follow=True)
        self.assertEqual(resp.status_code, 200)
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
        resp = self.client.post(url, {'name': 'Alice', 'phone': '012345', 'address': 'Test Address'}, follow=True)
        self.assertEqual(resp.status_code, 200)
        # order created
        orders = Order.objects.all()
        self.assertEqual(orders.count(), 1)
        order = orders.first()
        self.assertEqual(order.full_name, 'Alice')
        # session cart cleared
        session = self.client.session
        self.assertNotIn('cart', session)
