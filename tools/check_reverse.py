import os
os.environ.setdefault('DJANGO_SETTINGS_MODULE','paint_store.settings')
import django
django.setup()
from django.urls import reverse, NoReverseMatch
try:
    print('reverse login ->', reverse('login'))
except Exception as e:
    print('ERROR', type(e), e)
