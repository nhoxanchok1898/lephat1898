import os, traceback
os.environ.setdefault('DJANGO_SETTINGS_MODULE','paint_store.settings')
import django
from django.test import RequestFactory
from django.contrib.sitemaps.views import sitemap
from store.sitemaps import ProductSitemap, StaticViewSitemap

django.setup()
req = RequestFactory().get('/sitemap.xml')
try:
    resp = sitemap(req, sitemaps={'products': ProductSitemap(), 'static': StaticViewSitemap()})
    print('STATUS', getattr(resp, 'status_code', None))
    ct = getattr(resp, 'content', b'').decode('utf-8', errors='replace')
    print('\n---BODY START---\n', ct[:8000], '\n---BODY END---')
except Exception:
    traceback.print_exc()
