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

    def test_cart_update(self):
        """Test updating cart quantity via form-based update"""
        # Add item to cart first
        session = self.client.session
        session['cart'] = {str(self.product.pk): 5}
        session.save()

        # Update quantity
        url = reverse('store:cart_update', args=[self.product.pk])
        resp = self.client.post(url, {'quantity': 3}, follow=True)
        self.assertEqual(resp.status_code, 200)
        
        session = self.client.session
        cart = session.get('cart', {})
        self.assertEqual(cart[str(self.product.pk)], 3)

    def test_cart_update_remove_zero_quantity(self):
        """Test that updating with quantity 0 removes the item"""
        session = self.client.session
        session['cart'] = {str(self.product.pk): 5}
        session.save()

        url = reverse('store:cart_update', args=[self.product.pk])
        resp = self.client.post(url, {'quantity': 0}, follow=True)
        self.assertEqual(resp.status_code, 200)
        
        session = self.client.session
        cart = session.get('cart', {})
        self.assertNotIn(str(self.product.pk), cart)

    def test_cart_update_ajax(self):
        """Test AJAX cart update endpoint"""
        session = self.client.session
        session['cart'] = {str(self.product.pk): 2}
        session.save()

        url = reverse('store:cart_update_ajax', args=[self.product.pk])
        resp = self.client.post(url, {'quantity': 4})
        self.assertEqual(resp.status_code, 200)
        
        data = resp.json()
        self.assertEqual(data['pk'], self.product.pk)
        self.assertEqual(data['quantity'], 4)
        self.assertAlmostEqual(float(data['subtotal']), 400.00, places=2)  # 4 * 100
        self.assertAlmostEqual(float(data['total']), 400.00, places=2)

    def test_cart_summary_ajax(self):
        """Test AJAX cart summary endpoint"""
        session = self.client.session
        session['cart'] = {str(self.product.pk): 2}
        session.save()

        url = reverse('store:cart_summary_ajax')
        resp = self.client.get(url)
        self.assertEqual(resp.status_code, 200)
        
        data = resp.json()
        self.assertEqual(len(data['items']), 1)
        self.assertEqual(data['items'][0]['pk'], self.product.pk)
        self.assertEqual(data['items'][0]['qty'], 2)
        self.assertAlmostEqual(float(data['total']), 200.00, places=2)

    def test_cart_remove_ajax(self):
        """Test AJAX cart remove endpoint"""
        session = self.client.session
        session['cart'] = {str(self.product.pk): 3}
        session.save()

        url = reverse('store:cart_remove_ajax', args=[self.product.pk])
        resp = self.client.post(url)
        self.assertEqual(resp.status_code, 200)
        
        data = resp.json()
        self.assertEqual(data['removed'], self.product.pk)
        self.assertEqual(len(data['items']), 0)
        self.assertEqual(data['total'], 0)

    def test_cart_remove(self):
        """Test regular cart remove endpoint"""
        session = self.client.session
        session['cart'] = {str(self.product.pk): 3}
        session.save()

        url = reverse('store:cart_remove', args=[self.product.pk])
        resp = self.client.get(url, follow=True)
        self.assertEqual(resp.status_code, 200)
        
        session = self.client.session
        cart = session.get('cart', {})
        self.assertNotIn(str(self.product.pk), cart)

    def test_search_suggestions(self):
        """Test search suggestions AJAX endpoint"""
        url = reverse('store:search_suggestions')
        resp = self.client.get(url, {'q': 'Test'})
        self.assertEqual(resp.status_code, 200)
        
        data = resp.json()
        self.assertEqual(len(data['results']), 1)
        self.assertEqual(data['results'][0]['name'], 'Test Paint')

    def test_contact_view(self):
        """Test contact page renders"""
        url = reverse('store:contact')
        resp = self.client.get(url)
        self.assertEqual(resp.status_code, 200)

    def test_checkout_success_view(self):
        """Test checkout success page renders"""
        url = reverse('store:checkout_success')
        resp = self.client.get(url)
        self.assertEqual(resp.status_code, 200)

    def test_product_get_absolute_url(self):
        """Test Product.get_absolute_url() returns correct URL"""
        url = self.product.get_absolute_url()
        expected = reverse('store:product_detail', args=[self.product.pk])
        self.assertEqual(url, expected)

    def test_brand_get_absolute_url(self):
        """Test Brand.get_absolute_url() returns product list with brand filter"""
        url = self.brand.get_absolute_url()
        expected = reverse('store:product_list') + f'?brand={self.brand.pk}'
        self.assertEqual(url, expected)

