import os
import sys
import django

# ensure project root is on sys.path so Django project package imports work
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)

# Use paint_store.settings as the canonical settings module
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from store.watermark import watermark_product_image
from store.models import Product
from django.core.files.storage import default_storage
from django.conf import settings

qs = Product.objects.exclude(image__isnull=True).exclude(image__exact='')
print('count', qs.count())
for p in qs:
    name = getattr(p.image, 'name', None)
    print('\nproduct', p.pk, 'name=', name)
    try:
        print('settings.MEDIA_ROOT ->', settings.MEDIA_ROOT)
    except Exception:
        pass
    try:
        print('field.storage.location ->', getattr(p.image.storage, 'location', None))
    except Exception:
        pass
    try:
        path = p.image.path
    except Exception:
        # fall back to constructing a plausible path from the probe script
        if not name:
            print('warning: image field has no name, skipping')
            continue
        debug_base = os.path.dirname(__file__)
        constructed = os.path.join(debug_base, '..', 'media', name)
        path = os.path.normpath(constructed)
        print('debug: __file__ dir ->', debug_base)
        print('debug: constructed path ->', constructed)
    print('path exists', os.path.exists(path), path)
    try:
        saved = watermark_product_image(p, 'image')
        print('watermark ->', saved)
        print('exists in storage ->', default_storage.exists(saved) if saved else None)
    except Exception as e:
        print('error', type(e).__name__, e)
