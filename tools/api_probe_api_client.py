import os
import django
import json
import sys
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
# Use paint_store.settings as the canonical settings module
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from rest_framework.test import APIClient
from store.models import Brand, Category, Product

b = Brand.objects.create(name='ProbeBrand2')
c = Category.objects.create(name='ProbeCat2')
try:
    unit = Product.UNIT_LIT
except Exception:
    unit = 0
p = Product.objects.create(name='Probe2', brand=b, category=c, price=10.0, unit_type=unit, volume=1, is_active=True)

client = APIClient()
resp = client.post('/api/cart/add/', {'product_id': p.id, 'quantity': 2}, format='json')
print('status:', resp.status_code)
print('data:', getattr(resp, 'data', resp.content))
