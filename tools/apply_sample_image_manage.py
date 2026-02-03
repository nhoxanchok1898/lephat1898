from store.models import Product
from store.watermark import watermark_product_image
from django.core.files.storage import default_storage

p = Product.objects.first()
print('product:', p.pk if p else None)
if p:
    p.image.name = 'products/sample_product.jpg'
    p.save(update_fields=['image'])
    print('assigned', p.image.name)
    saved = watermark_product_image(p, 'image')
    print('watermark ->', saved)
    print('exists:', default_storage.exists(saved) if saved else None)
