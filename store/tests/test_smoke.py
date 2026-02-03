from decimal import Decimal

from django.contrib.auth import get_user_model
from django.test import TestCase
from django.urls import reverse

from store.models import Brand, Product


class SmokeTests(TestCase):
    @classmethod
    def setUpTestData(cls):
        # Minimal data for pages that require objects
        cls.brand = Brand.objects.create(name="Test Brand")
        cls.product = Product.objects.create(
            name="Test Product",
            brand=cls.brand,
            price=Decimal("10.00"),
            stock_quantity=5,
        )
        # Admin user
        User = get_user_model()
        cls.admin_username = "admin"
        cls.admin_password = "adminpass"
        cls.admin = User.objects.create_superuser(
            username=cls.admin_username,
            email="admin@example.com",
            password=cls.admin_password,
        )

    def test_homepage_ok(self):
        resp = self.client.get(reverse("home:index"))
        self.assertEqual(resp.status_code, 200)

    def test_product_list_ok(self):
        resp = self.client.get(reverse("store:product_list"))
        self.assertEqual(resp.status_code, 200)

    def test_product_detail_ok(self):
        resp = self.client.get(reverse("store:product_detail", args=[self.product.id]))
        self.assertEqual(resp.status_code, 200)

    def test_cart_ok(self):
        resp = self.client.get(reverse("store:cart_view"))
        self.assertEqual(resp.status_code, 200)

    def test_admin_login_page_ok(self):
        resp = self.client.get(reverse("admin:login"))
        self.assertEqual(resp.status_code, 200)

    def test_admin_requires_login_redirect(self):
        url = reverse("admin:store_product_changelist")
        resp = self.client.get(url)
        self.assertEqual(resp.status_code, 302)
        self.assertIn(reverse("admin:login"), resp.url)

    def test_admin_access_after_login(self):
        self.client.login(username=self.admin_username, password=self.admin_password)
        resp = self.client.get(reverse("admin:store_product_changelist"))
        self.assertEqual(resp.status_code, 200)
