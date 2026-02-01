from django.db import models
from django.core.exceptions import ValidationError
from django.urls import reverse
from django.db.models.signals import post_save
from django.dispatch import receiver
from django.contrib.auth.models import User
from django.utils import timezone
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

    name = models.CharField(max_length=250, db_index=True)
    brand = models.ForeignKey(Brand, on_delete=models.CASCADE, db_index=True)
    category = models.ForeignKey(Category, on_delete=models.SET_NULL, null=True, blank=True, db_index=True)
    price = models.DecimalField(max_digits=12, decimal_places=2, db_index=True)
    unit_type = models.CharField(max_length=3, choices=UNIT_CHOICES, default=UNIT_LIT)
    volume = models.PositiveIntegerField(help_text='Volume in selected unit, e.g., 5, 18, 20')
    image = models.ImageField(upload_to='products/', blank=True, null=True)
    is_active = models.BooleanField(default=True, db_index=True)
    created_at = models.DateTimeField(auto_now_add=True, db_index=True)
    updated_at = models.DateTimeField(auto_now=True)
    # New fields for Phase 2
    stock_quantity = models.PositiveIntegerField(default=0)
    is_on_sale = models.BooleanField(default=False)
    sale_price = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)
    rating = models.DecimalField(max_digits=3, decimal_places=2, default=0.0)
    view_count = models.PositiveIntegerField(default=0)
    description = models.TextField(blank=True, null=True)

    class Meta:
        ordering = ['-created_at']
        indexes = [
            models.Index(fields=['name', 'brand']),
            models.Index(fields=['is_active', 'created_at']),
            models.Index(fields=['category', 'is_active']),
        ]

    def __str__(self):
        return self.name

    def get_price(self):
        """Return sale price if on sale, otherwise regular price"""
        if self.is_on_sale and self.sale_price:
            return self.sale_price
        return self.price

    def is_in_stock(self):
        """Check if product is in stock"""
        return self.stock_quantity > 0


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


# Phase 2A: Advanced Features Models

class Cart(models.Model):
    """Persistent cart model for logged-in users"""
    user = models.ForeignKey(User, on_delete=models.CASCADE, null=True, blank=True)
    session_key = models.CharField(max_length=40, null=True, blank=True, db_index=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-updated_at']

    def __str__(self):
        if self.user:
            return f"Cart for {self.user.username}"
        return f"Anonymous cart {self.session_key}"


class CartItem(models.Model):
    """Cart items for persistent cart"""
    cart = models.ForeignKey(Cart, related_name='items', on_delete=models.CASCADE)
    product = models.ForeignKey(Product, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField(default=1)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        unique_together = ('cart', 'product')

    def __str__(self):
        return f"{self.product.name} x {self.quantity}"

    def get_total_price(self):
        return self.product.get_price() * self.quantity


class Coupon(models.Model):
    """Coupon/discount code model"""
    code = models.CharField(max_length=50, unique=True, db_index=True)
    discount_percentage = models.DecimalField(max_digits=5, decimal_places=2, null=True, blank=True)
    discount_amount = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)
    min_purchase_amount = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    valid_from = models.DateTimeField()
    valid_to = models.DateTimeField()
    is_active = models.BooleanField(default=True)
    max_uses = models.PositiveIntegerField(null=True, blank=True)
    used_count = models.PositiveIntegerField(default=0)

    def __str__(self):
        return self.code

    def is_valid(self):
        """Check if coupon is currently valid"""
        now = timezone.now()
        if not self.is_active:
            return False
        if now < self.valid_from or now > self.valid_to:
            return False
        if self.max_uses and self.used_count >= self.max_uses:
            return False
        return True

    def apply_discount(self, amount):
        """Apply discount to given amount"""
        if not self.is_valid():
            return amount
        if amount < self.min_purchase_amount:
            return amount
        
        if self.discount_percentage:
            discount = amount * (self.discount_percentage / 100)
            return amount - discount
        elif self.discount_amount:
            return max(0, amount - self.discount_amount)
        return amount


class SearchQuery(models.Model):
    """Track search queries for analytics"""
    query = models.CharField(max_length=255, db_index=True)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    session_key = models.CharField(max_length=40, null=True, blank=True)
    results_count = models.PositiveIntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True, db_index=True)

    class Meta:
        ordering = ['-created_at']
        indexes = [
            models.Index(fields=['query', '-created_at']),
        ]

    def __str__(self):
        return f"{self.query} ({self.results_count} results)"


class SavedSearch(models.Model):
    """Saved searches for logged-in users"""
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    name = models.CharField(max_length=100)
    query = models.CharField(max_length=255)
    filters = models.JSONField(default=dict)  # Store filter parameters
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-created_at']
        unique_together = ('user', 'name')

    def __str__(self):
        return f"{self.user.username} - {self.name}"


class ProductView(models.Model):
    """Track product views for analytics and recommendations"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='views')
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    session_key = models.CharField(max_length=40, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True, db_index=True)

    class Meta:
        ordering = ['-created_at']
        indexes = [
            models.Index(fields=['product', '-created_at']),
            models.Index(fields=['user', '-created_at']),
        ]

    def __str__(self):
        return f"{self.product.name} viewed at {self.created_at}"


class Wishlist(models.Model):
    """Wishlist for users"""
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    products = models.ManyToManyField(Product, related_name='wishlisted_by')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Wishlist for {self.user.username}"


class Review(models.Model):
    """Product reviews"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='reviews')
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    rating = models.PositiveIntegerField(choices=[(i, i) for i in range(1, 6)])
    comment = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-created_at']
        unique_together = ('product', 'user')

    def __str__(self):
        return f"{self.user.username} - {self.product.name} ({self.rating}/5)"
