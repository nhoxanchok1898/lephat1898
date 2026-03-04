import os
import sys
from datetime import UTC, datetime
import traceback

try:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass

# Ensure project root is on sys.path so imports like `paint_store` work
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)

# Ensure Django settings are configured when running as a script
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()

from django.test import Client
from django.conf import settings

c = Client()
preferred_host = os.getenv('TEST_HTTP_HOST') or (settings.ALLOWED_HOSTS[0] if settings.ALLOWED_HOSTS else 'testserver')
c.defaults['HTTP_HOST'] = preferred_host

def safe_print(text=''):
    try:
        print(text)
    except UnicodeEncodeError:
        # Console on Windows may not support all Unicode chars from paths/tracebacks.
        sys.stdout.buffer.write((str(text) + '\n').encode('utf-8', errors='replace'))

def now_ts():
    return datetime.now(UTC).strftime('%d/%b/%Y %H:%M:%S')

paths = ['/', '/products/', '/products/1/', '/contact/', '/login/', '/cart/']
for p in paths:
    try:
        r = c.get(p)
    except Exception:
        ts = now_ts()
        safe_print(f'[{ts}] "GET {p} HTTP/1.1" EXCEPTION')
        safe_print('---TRACEBACK---')
        safe_print(traceback.format_exc())
        safe_print('---END---\n')
        continue

    ts = now_ts()
    safe_print(f'[{ts}] "GET {p} HTTP/1.1" {r.status_code} {len(r.content)}')
    safe_print('---SNIPPET---')
    try:
        safe_print(r.content[:2000].decode('utf-8', errors='replace'))
    except Exception:
        safe_print(repr(r.content[:2000]))
    safe_print('---END---\n')
