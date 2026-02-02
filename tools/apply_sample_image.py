import os
import django

# Use paint_store.settings as the canonical settings module
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from django.core.files.storage import default_storage
from store.models import Product
from store.watermark import watermark_product_image


def main():
    p = Product.objects.first()
    if not p:
        print('No products found')
        return

    sample_name = 'products/sample_product.jpg'
    p.image.name = sample_name
    p.save(update_fields=['image'])
    print('Assigned image to product', p.pk, p.image.name)

    saved = watermark_product_image(p, 'image')
    print('watermark returned:', saved)
    if saved:
        print('exists in storage:', default_storage.exists(saved))


if __name__ == '__main__':
    main()
