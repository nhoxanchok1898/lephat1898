from decimal import Decimal

from django.contrib.auth import get_user_model
from django.test import TestCase
from django.urls import reverse

from store.models import Brand, Product


class SmokeHttpStatusTests(TestCase):
    @classmethod
    def setUpTestData(cls):
        cls.brand = Brand.objects.create(name="Smoke Brand")
        cls.product = Product.objects.create(
            name="Smoke Product",
            brand=cls.brand,
            price=Decimal("9.99"),
            stock_quantity=3,
        )
        User = get_user_model()
        cls.admin_username = "admin"
        cls.admin_password = "adminpass"
        cls.admin = User.objects.create_superuser(
            username=cls.admin_username,
            email="admin@example.com",
            password=cls.admin_password,
        )

    def test_homepage_status_ok(self):
        resp = self.client.get(reverse("home:index"))
        self.assertEqual(resp.status_code, 200)

    def test_product_list_status_ok(self):
        resp = self.client.get(reverse("store:product_list"))
        self.assertEqual(resp.status_code, 200)

    def test_product_detail_status_ok(self):
        resp = self.client.get(reverse("store:product_detail", args=[self.product.id]))
        self.assertEqual(resp.status_code, 200)

    def test_cart_status_ok(self):
        resp = self.client.get(reverse("store:cart_view"))
        self.assertEqual(resp.status_code, 200)

    def test_admin_login_page_status_ok(self):
        resp = self.client.get(reverse("admin:login"))
        self.assertEqual(resp.status_code, 200)

    def test_admin_changelist_requires_login(self):
        url = reverse("admin:store_product_changelist")
        resp = self.client.get(url)
        self.assertEqual(resp.status_code, 302)
        self.assertIn(reverse("admin:login"), resp.url)
