import os
import sys
# Use paint_store.settings as the canonical settings module
os.environ.setdefault('DJANGO_SETTINGS_MODULE','paint_store.settings')
sys.path.insert(0, os.getcwd())
import django
django.setup()
from django.test import Client
from django.conf import settings
c=Client()
# Ensure test client uses an allowed host
if settings.ALLOWED_HOSTS:
    c.defaults['HTTP_HOST'] = settings.ALLOWED_HOSTS[0] if settings.ALLOWED_HOSTS[0] != '*' else '127.0.0.1'
resp=c.post('/store/api/cart/add/', '{"product_id": 1, "quantity": 1}', content_type='application/json')
print('status', resp.status_code)
print('content', resp.content.decode('utf-8'))
