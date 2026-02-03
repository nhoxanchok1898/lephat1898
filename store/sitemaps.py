from django.contrib.sitemaps import Sitemap
from django.urls import reverse
from urllib.parse import quote as urlquote
from django.conf import settings

from .models import Product


class ProductSitemap(Sitemap):
    changefreq = 'weekly'
    priority = 0.6

    def items(self):
        return Product.objects.filter(is_active=True)

    def lastmod(self, obj):
        # prefer updated_at when available
        return getattr(obj, 'updated_at', getattr(obj, 'created_at', None))

    def location(self, obj):
        try:
            return obj.get_absolute_url()
        except Exception:
            return reverse('store:product_detail', args=[obj.pk])

    def images(self, obj):
        """Return a list of image dicts for sitemap image tags.

        Each dict may contain: 'loc', 'caption', 'title', 'license'
        We only provide the image URL (loc) when the product has an image.
        """
        imgs = []
        if obj.image:
            try:
                # prefer an explicit SITE_URL setting if provided
                base = getattr(settings, 'SITE_URL', None)
                if not base:
                    # fall back to localhost dev address when DEBUG
                    if getattr(settings, 'DEBUG', False):
                        base = 'http://127.0.0.1:8888'
                    else:
                        base = 'https://example.com'
                img_url = obj.image.url
                # ensure single leading slash
                if img_url.startswith('/'):
                    img_url = img_url[1:]
                full = f"{base.rstrip('/')}/{urlquote(img_url)}"
                imgs.append({'loc': full})
            except Exception:
                pass
        return imgs


class StaticViewSitemap(Sitemap):
    changefreq = 'daily'
    priority = 0.5

    def items(self):
        return ['store:home', 'store:product_list', 'store:contact']

    def location(self, item):
        # item is a view name
        return reverse(item)
