"""Print sitemap entries (locations and image URLs) using the Sitemap classes.

This avoids making an HTTP request and uses the project's settings directly.
"""
import os
import sys
import django
from pathlib import Path

# Ensure project root is on sys.path (handles spaces/non-ascii paths)
project_root = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(project_root))
# Allow overriding SITE_URL via command-line arg for testing:
if len(sys.argv) > 1:
    os.environ['SITE_URL'] = sys.argv[1]
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from store.sitemaps import ProductSitemap, StaticViewSitemap
from django.conf import settings


def print_product_entries():
    ps = ProductSitemap()
    for obj in ps.items():
        loc = ps.location(obj)
        lastmod = ps.lastmod(obj)
        imgs = ps.images(obj)
        print('LOC:', loc)
        if lastmod:
            print('  lastmod:', lastmod)
        for img in imgs:
            print('  IMAGE:', img.get('loc'))


def print_static_entries():
    ss = StaticViewSitemap()
    for view in ss.items():
        loc = ss.location(view)
        print('LOC:', loc)


if __name__ == '__main__':
    print('SITE_URL:', getattr(settings, 'SITE_URL', None))
    print('\nStatic entries:')
    print_static_entries()
    print('\nProduct entries:')
    print_product_entries()
