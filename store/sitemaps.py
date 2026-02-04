from django.contrib.sitemaps import Sitemap
from django.urls import reverse
from .models import Product


class ProductSitemap(Sitemap):
    changefreq = "weekly"
    priority = 0.8

    def items(self):
        return Product.objects.filter(is_active=True)

    def lastmod(self, obj):
        return obj.updated_at

    def location(self, obj):
        return reverse("store:product_detail", args=[obj.pk])


class StaticViewSitemap(Sitemap):
    priority = 1.0
    changefreq = "daily"

    def items(self):
        return [
            "home:index",
            "store:product_list",
            "store:contact",
        ]

    def location(self, item):
        return reverse(item)
