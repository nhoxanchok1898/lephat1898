import os
import django

# Use paint_store.settings as the canonical settings module
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from store.models import Product
from store.watermark import watermark_product_image


def main():
    qs = Product.objects.exclude(image__isnull=True).exclude(image__exact='')
    print('Products with image:', qs.count())
    for p in qs:
        name = getattr(p.image, 'name', None)
        print('\nProduct', p.pk, 'image:', name)
        try:
            saved = watermark_product_image(p, 'image')
            print(' -> watermark result:', saved)
        except Exception as e:
            print(' -> error:', type(e).__name__, str(e))


if __name__ == '__main__':
    main()
