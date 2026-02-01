from django.db import models
from django.core.exceptions import ValidationError
from django.urls import reverse
from django.db.models.signals import post_save
from django.dispatch import receiver
from .watermark import watermark_product_image


class Brand(models.Model):
    name = models.CharField(max_length=120)
    logo = models.ImageField(upload_to='brands/', blank=True, null=True)

    def __str__(self):
        return self.name

    def get_absolute_url(self):
        return reverse('store:product_detail', args=[self.pk])



class Order(models.Model):
    full_name = models.CharField(max_length=200)
    phone = models.CharField(max_length=30)
    address = models.TextField()
    # payment fields
    payment_method = models.CharField(max_length=30, default='offline')
    payment_status = models.CharField(max_length=30, default='pending')
    payment_reference = models.CharField(max_length=255, blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    # Static type hints for static analyzers (Pylance/django-stubs helps, but add these
    # to reduce false-positive warnings about dynamic related attributes)
    items: 'models.Manager'  # type: ignore
    id: int  # type: ignore

    def __str__(self):
        return f"Order #{self.pk} - {self.full_name}"


class OrderItem(models.Model):
    order = models.ForeignKey(Order, related_name='items', on_delete=models.CASCADE)
    product = models.ForeignKey('Product', on_delete=models.PROTECT)
    quantity = models.PositiveIntegerField()
    price = models.DecimalField(max_digits=12, decimal_places=2)

    def __str__(self):
        return f"{self.product.name} x {self.quantity}"



class Category(models.Model):
    name = models.CharField(max_length=120)

    def __str__(self):
        return self.name


class Product(models.Model):
    UNIT_LIT = 'LIT'
    UNIT_KG = 'KG'
    UNIT_CHOICES = [
        (UNIT_LIT, 'Lít'),
        (UNIT_KG, 'Kilogram'),
    ]

    name = models.CharField(max_length=250)
    brand = models.ForeignKey(Brand, on_delete=models.CASCADE)
    category = models.ForeignKey(Category, on_delete=models.SET_NULL, null=True, blank=True)
    price = models.DecimalField(max_digits=12, decimal_places=2)
    unit_type = models.CharField(max_length=3, choices=UNIT_CHOICES, default=UNIT_LIT)
    volume = models.PositiveIntegerField(help_text='Volume in selected unit, e.g., 5, 18, 20')
    image = models.ImageField(upload_to='products/', blank=True, null=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return self.name


@receiver(post_save, sender=Product)
def apply_product_image_watermark(sender, instance: Product, created, **kwargs):
    # Only proceed if there is an image and it hasn't already been watermarked
    try:
        image_field = instance.image
    except Exception:
        return

    if not image_field:
        return

    name = getattr(image_field, 'name', '') or ''
    if not name:
        return

    # skip if filename already indicates watermark applied
    lower = name.lower()
    if '_wm' in lower:
        return

    # perform watermarking; watermark_product_image handles PIL import lazily
    try:
        new_name = watermark_product_image(instance, field_name='image')
        if new_name:
            instance.image.name = new_name
            instance.save(update_fields=['image'])
    except Exception:
        # Do not crash the request on image processing errors; silently skip.
        return
