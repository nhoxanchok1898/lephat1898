import os
os.environ.setdefault('DJANGO_SETTINGS_MODULE','paint_store.settings')
import os
import sys

# Ensure project root is on sys.path so Django settings module imports correctly
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()
from django.test import Client

c = Client()
for path in ['/', '/sitemap.xml']:
    r = c.get(path)
    print('\n===', path, 'STATUS', r.status_code, '===')
    print(r.content.decode('utf-8', errors='replace')[:12000])
