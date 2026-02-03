import os
import sys
from pathlib import Path

# Ensure project root is on sys.path so Django settings module can be imported
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'ecommerce.settings')
import django
django.setup()

from django.contrib.auth import get_user_model
from django.test import Client

User = get_user_model()
username = 'admin_test'
password = 'testpass123'
if not User.objects.filter(username=username).exists():
    User.objects.create_superuser(username, 'admin_test@example.com', password)
    print('created superuser')

c = Client()
logged = c.login(username=username, password=password)
print('logged in:', logged)
try:
    # ensure at least one Brand and Category exist for FK fields
    from store.models import Brand, Category
    if not Brand.objects.exists():
        Brand.objects.create(name='Default Brand')
    if not Category.objects.exists():
        Category.objects.create(name='Default Category')

    get = c.get('/admin/store/product/add/')
    print('GET add status', get.status_code)
    content = get.content.decode('utf-8')
    import re
    m = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', content)
    csrf = m.group(1) if m else ''
    print('csrf token found:', bool(csrf))

    # prepare POST data for product creation
    brand = Brand.objects.first()
    category = Category.objects.first()
    post_data = {
        'csrfmiddlewaretoken': csrf,
        'name': 'Probe Product',
        'description': 'Created by probe',
        'brand': str(brand.pk),
        'category': str(category.pk) if category else '',
        'price': '123.45',
        'unit_type': 'LIT',
        'volume': '5',
        'quantity': '1',
        'stock_quantity': '1',
        'is_active': 'on',
    }

    post = c.post('/admin/store/product/add/', data=post_data, follow=True)
    print('POST status', post.status_code)
    # show where we ended up
    print('Final URL:', post.request.get('PATH_INFO'))
    # check product created
    from store.models import Product
    exists = Product.objects.filter(name='Probe Product').exists()
    print('Product created in DB:', exists)
except Exception:
    import traceback
    traceback.print_exc()
