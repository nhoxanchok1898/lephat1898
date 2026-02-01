import os
os.environ.setdefault('DJANGO_SETTINGS_MODULE','ecommerce.settings')
import django
django.setup()
from django.urls import get_resolver
names = [k for k in get_resolver().reverse_dict.keys() if isinstance(k,str)]
print('TOTAL_NAMED_URLS:', len(names))
print('HAS_wishlist:', 'wishlist' in names)
print('NAMES_WITH_wish:', [n for n in names if 'wish' in n.lower()])
