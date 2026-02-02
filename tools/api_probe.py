import os
import django
import json
import sys

# Ensure project root is on sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

# Use paint_store.settings as the canonical settings module
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from django.test import Client
from django.conf import settings
from store.models import Brand, Category, Product

b = Brand.objects.create(name='ProbeBrand')
c = Category.objects.create(name='ProbeCat')
try:
	unit = Product.UNIT_LIT
except Exception:
	unit = 0

p = Product.objects.create(
	name='Probe',
	brand=b,
	category=c,
	price=10.0,
	unit_type=unit,
	volume=1,
	is_active=True,
)

client = Client()
# Ensure test client uses an allowed host
if settings.ALLOWED_HOSTS:
    client.defaults['HTTP_HOST'] = settings.ALLOWED_HOSTS[0] if settings.ALLOWED_HOSTS[0] != '*' else '127.0.0.1'
resp = client.post(
	'/api/cart/add/',
	data=json.dumps({'product_id': p.pk, 'quantity': 2}),
	content_type='application/json'
)

print('status:', resp.status_code)
print('content:', resp.content)
