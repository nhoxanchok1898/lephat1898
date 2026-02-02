import os
import sys
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()
from django.contrib.auth import get_user_model
from django.test import Client
from django.conf import settings

User = get_user_model()
username = 'ci_admin'
password = 'ci_admin_pass'

if not User.objects.filter(username=username).exists():
    print('Creating superuser...')
    User.objects.create_superuser(username=username, email='ci@example.com', password=password)

c = Client()
# Ensure test client uses an allowed host
if settings.ALLOWED_HOSTS:
    c.defaults['HTTP_HOST'] = settings.ALLOWED_HOSTS[0] if settings.ALLOWED_HOSTS[0] != '*' else '127.0.0.1'
logged_in = c.login(username=username, password=password)
print('Logged in:', logged_in)
try:
    r = c.get('/admin/store/product/')
    print('Status code:', r.status_code)
    print('Content length:', len(getattr(r, 'content', b'')))
    print(r.content[:2000].decode('utf-8', errors='replace'))
except Exception as e:
    import traceback
    print('EXCEPTION:')
    traceback.print_exc()
