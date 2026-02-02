import os
import sys
from datetime import datetime
import traceback

# Ensure project root is on sys.path so imports like `paint_store` work
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)

# Ensure Django settings are configured when running as a script
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()

from django.test import Client

c = Client()
paths = ['/', '/products/', '/products/1/', '/contact/', '/login/', '/cart/']
for p in paths:
    try:
        r = c.get(p)
    except Exception:
        ts = datetime.utcnow().strftime('%d/%b/%Y %H:%M:%S')
        print(f'[{ts}] "GET {p} HTTP/1.1" EXCEPTION')
        print('---TRACEBACK---')
        print(traceback.format_exc())
        print('---END---\n')
        continue

    ts = datetime.utcnow().strftime('%d/%b/%Y %H:%M:%S')
    print(f'[{ts}] "GET {p} HTTP/1.1" {r.status_code} {len(r.content)}')
    print('---SNIPPET---')
    try:
        print(r.content[:2000].decode('utf-8', errors='replace'))
    except Exception:
        print(repr(r.content[:2000]))
    print('---END---\n')
