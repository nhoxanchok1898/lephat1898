import os
import sys
import django

# Ensure project root is on sys.path so `ecommerce` package can be imported
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ROOT not in sys.path:
	sys.path.insert(0, ROOT)

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'ecommerce.settings')
django.setup()

from django.contrib.sessions.models import Session

count = Session.objects.count()
Session.objects.all().delete()
print(f"Deleted {count} session(s)")
