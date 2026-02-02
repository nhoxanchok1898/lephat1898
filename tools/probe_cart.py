import os
import sys
os.environ.setdefault('DJANGO_SETTINGS_MODULE','ecommerce.settings')
sys.path.insert(0, os.getcwd())
import django
django.setup()
from django.test import Client
c=Client()
resp=c.post('/store/api/cart/add/', '{"product_id": 1, "quantity": 1}', content_type='application/json')
print('status', resp.status_code)
print('content', resp.content.decode('utf-8'))
