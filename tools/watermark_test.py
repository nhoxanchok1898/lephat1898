import os
import django

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'ecommerce.settings')
django.setup()

from store.models import Product
from store.watermark import watermark_product_image


def main():
    qs = Product.objects.exclude(image__isnull=True).exclude(image__exact='')
    print('Products with image:', qs.count())
    if not qs.exists():
        print('No product images found to test.')
        return

    p = qs.first()
    print('Testing product:', p.pk, getattr(p.image, 'name', None))
    saved = watermark_product_image(p, 'image')
    print('watermark saved as:', saved)


if __name__ == '__main__':
    main()
