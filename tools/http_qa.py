
import os, sys
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()
from django.test import Client
from django.urls import reverse
from django.conf import settings

c = Client()
# Ensure the test client sends a Host header that matches the dev server
# (prevents 400 when ALLOWED_HOSTS is restrictive or DEBUG is False)
# Prefer a host from ALLOWED_HOSTS if configured (prevents 400 from host checks)
preferred_host = os.getenv('TEST_HTTP_HOST') or (settings.ALLOWED_HOSTS[0] if settings.ALLOWED_HOSTS else '127.0.0.1:8888')
c.defaults['HTTP_HOST'] = preferred_host
print('DEBUG=', settings.DEBUG, 'ALLOWED_HOSTS=', settings.ALLOWED_HOSTS, 'CLIENT_HTTP_HOST=', c.defaults.get('HTTP_HOST'))
paths = [
    '/',
    '/products/',
    '/cart/',
    '/checkout/',
    '/checkout/success/',
    '/contact/',
    '/sitemap.xml',
    '/robots.txt',
    '/ajax/search_suggestions/',
]

print('Starting HTTP QA')
errors = []
for p in paths:
    try:
        r = c.get(p)
        status = r.status_code
        print(f'{p} -> {status}')
        if status >= 400:
            errors.append((p, status, r.content.decode('utf-8', errors='replace')[:1000]))
    except Exception as e:
        errors.append((p, 'exception', str(e)))

# probe a few product detail pages if available
from store.models import Product
qs = Product.objects.filter(is_active=True)[:5]
for prod in qs:
    p = f'/products/{prod.pk}/'
    try:
        r = c.get(p)
        print(f'{p} -> {r.status_code}')
        if r.status_code >= 400:
            errors.append((p, r.status_code, r.content.decode('utf-8', errors='replace')[:1000]))
    except Exception as e:
        errors.append((p, 'exception', str(e)))

print('\nQA complete')
if errors:
    print('\nErrors:')
    for e in errors:
        print(e[0], e[1])
else:
    print('No errors found')
