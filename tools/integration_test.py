import os, sys
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()
from django.test import Client
from store.models import Product, Order

c = Client()
# Use a Host header that matches `ALLOWED_HOSTS` when possible to avoid 400
from django.conf import settings
preferred_host = os.getenv('TEST_HTTP_HOST') or (settings.ALLOWED_HOSTS[0] if settings.ALLOWED_HOSTS else '127.0.0.1:8888')
c.defaults['HTTP_HOST'] = preferred_host
print('Integration test: add to cart -> checkout')
# pick an active product
p = Product.objects.filter(is_active=True).first()
if not p:
    print('No active products found; abort')
    sys.exit(1)
print('Using product', p.pk, p.name)
# add to cart
r = c.post(f'/cart/add/{p.pk}/', {'quantity': 2}, follow=True)
print('POST /cart/add ->', r.status_code)
# view cart
r = c.get('/cart/')
print('/cart ->', r.status_code)
# checkout GET
r = c.get('/checkout/')
print('/checkout GET ->', r.status_code)
# checkout POST
data = {'name': 'Test User', 'phone': '0123456789', 'address': 'Test Address'}
r = c.post('/checkout/', data, follow=True)
print('/checkout POST ->', r.status_code)
# verify order created
orders = Order.objects.order_by('-id')[:3]
print('Recent orders count:', orders.count())
if orders:
    print('Latest order id:', orders[0].id, 'full_name:', orders[0].full_name)
print('Integration test complete')
