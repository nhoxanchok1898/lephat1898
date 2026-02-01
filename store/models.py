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

    name = models.CharField(max_length=250)
    description = models.TextField(blank=True, default='')
    brand = models.ForeignKey(Brand, on_delete=models.CASCADE)
    category = models.ForeignKey(Category, on_delete=models.SET_NULL, null=True, blank=True)
    price = models.DecimalField(max_digits=12, decimal_places=2)
    sale_price = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)
    unit_type = models.CharField(max_length=3, choices=UNIT_CHOICES, default=UNIT_LIT)
    volume = models.PositiveIntegerField(help_text='Volume in selected unit, e.g., 5, 18, 20')
    image = models.ImageField(upload_to='products/', blank=True, null=True)
    is_active = models.BooleanField(default=True)
    is_new = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return self.name

    def get_price(self):
        """Return sale price if available, otherwise regular price"""
        return self.sale_price if self.sale_price else self.price

    def is_on_sale(self):
        """Check if product is on sale"""
        return self.sale_price is not None and self.sale_price < self.price


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


# ===== Advanced Search System =====
class SearchQuery(models.Model):
    """Track user searches for analytics"""
    query = models.CharField(max_length=255)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    results_count = models.IntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)
    ip_address = models.GenericIPAddressField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']
        verbose_name_plural = 'Search Queries'

    def __str__(self):
        return f"{self.query} ({self.results_count} results)"


class SearchFilter(models.Model):
    """Saved search filters for users"""
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    name = models.CharField(max_length=100)
    filters = models.JSONField(default=dict)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.user.username} - {self.name}"


class ProductView(models.Model):
    """Track product views for recommendations"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='views')
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    session_key = models.CharField(max_length=255, blank=True)
    viewed_at = models.DateTimeField(auto_now_add=True)
    ip_address = models.GenericIPAddressField(null=True, blank=True)

    class Meta:
        ordering = ['-viewed_at']

    def __str__(self):
        return f"{self.product.name} viewed at {self.viewed_at}"


# ===== Shopping Cart Database Model =====
class CartSession(models.Model):
    """Persist cart across sessions for logged-in users"""
    user = models.OneToOneField(User, on_delete=models.CASCADE, related_name='cart_session')
    session_key = models.CharField(max_length=255, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    def __str__(self):
        return f"Cart for {self.user.username}"

    def get_total(self):
        return sum(item.get_subtotal() for item in self.items.all())


class CartItem(models.Model):
    """Items in cart with quantity"""
    cart = models.ForeignKey(CartSession, on_delete=models.CASCADE, related_name='items')
    product = models.ForeignKey(Product, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField(default=1)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        unique_together = ['cart', 'product']

    def __str__(self):
        return f"{self.product.name} x {self.quantity}"

    def get_subtotal(self):
        return self.product.get_price() * self.quantity


class Coupon(models.Model):
    """Coupon codes for discounts"""
    code = models.CharField(max_length=50, unique=True)
    discount_percent = models.DecimalField(max_digits=5, decimal_places=2, default=0)
    discount_amount = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    valid_from = models.DateTimeField()
    valid_to = models.DateTimeField()
    active = models.BooleanField(default=True)
    max_uses = models.IntegerField(null=True, blank=True)
    used_count = models.IntegerField(default=0)

    def __str__(self):
        return self.code

    def is_valid(self):
        now = timezone.now()
        if not self.active:
            return False
        if now < self.valid_from or now > self.valid_to:
            return False
        if self.max_uses and self.used_count >= self.max_uses:
            return False
        return True


# ===== Product Recommendations =====
class ProductRating(models.Model):
    """Track user ratings separate from reviews"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='ratings')
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    rating = models.IntegerField(choices=[(i, i) for i in range(1, 6)])
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ['product', 'user']

    def __str__(self):
        return f"{self.product.name} - {self.rating} stars by {self.user.username}"


# ===== Inventory Management System =====
class StockLevel(models.Model):
    """Track inventory per product"""
    product = models.OneToOneField(Product, on_delete=models.CASCADE, related_name='stock')
    quantity = models.IntegerField(default=0)
    reserved = models.IntegerField(default=0)  # Reserved for pending orders
    low_stock_threshold = models.IntegerField(default=10)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.product.name} - {self.quantity} in stock"

    def available_quantity(self):
        return max(0, self.quantity - self.reserved)

    def is_low_stock(self):
        return self.available_quantity() <= self.low_stock_threshold

    def is_out_of_stock(self):
        return self.available_quantity() <= 0


class StockAlert(models.Model):
    """Low stock notifications for admin"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='stock_alerts')
    message = models.CharField(max_length=255)
    is_resolved = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    resolved_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"Alert for {self.product.name}: {self.message}"


class PreOrder(models.Model):
    """Pre-order functionality for out-of-stock items"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='preorders')
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField(default=1)
    expected_date = models.DateField(null=True, blank=True)
    status = models.CharField(max_length=20, choices=[
        ('pending', 'Pending'),
        ('confirmed', 'Confirmed'),
        ('fulfilled', 'Fulfilled'),
        ('cancelled', 'Cancelled'),
    ], default='pending')
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"PreOrder: {self.product.name} x {self.quantity} by {self.user.username}"


class BackInStockNotification(models.Model):
    """Email notifications when product is back in stock"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='back_in_stock_notifications')
    email = models.EmailField()
    notified = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    notified_at = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return f"Notification for {self.product.name} to {self.email}"


# ===== Analytics Dashboard =====
class ProductViewAnalytics(models.Model):
    """Track product view analytics"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='view_analytics')
    date = models.DateField(auto_now_add=True)
    view_count = models.IntegerField(default=0)

    class Meta:
        unique_together = ['product', 'date']
        ordering = ['-date']

    def __str__(self):
        return f"{self.product.name} - {self.view_count} views on {self.date}"


class OrderAnalytics(models.Model):
    """Track order and revenue analytics"""
    date = models.DateField(unique=True)
    order_count = models.IntegerField(default=0)
    revenue = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    items_sold = models.IntegerField(default=0)

    class Meta:
        ordering = ['-date']
        verbose_name_plural = 'Order Analytics'

    def __str__(self):
        return f"{self.date} - {self.order_count} orders, ${self.revenue} revenue"


class UserAnalytics(models.Model):
    """Track user behavior analytics"""
    date = models.DateField(unique=True)
    new_users = models.IntegerField(default=0)
    active_users = models.IntegerField(default=0)
    total_users = models.IntegerField(default=0)

    class Meta:
        ordering = ['-date']
        verbose_name_plural = 'User Analytics'

    def __str__(self):
        return f"{self.date} - {self.new_users} new, {self.active_users} active"


# ===== Email Templates & System =====
class EmailTemplate(models.Model):
    """Store reusable email templates"""
    name = models.CharField(max_length=100, unique=True)
    subject = models.CharField(max_length=255)
    html_content = models.TextField()
    text_content = models.TextField(blank=True)
    template_type = models.CharField(max_length=50, choices=[
        ('welcome', 'Welcome Email'),
        ('order_confirmation', 'Order Confirmation'),
        ('shipping', 'Shipping Notification'),
        ('password_reset', 'Password Reset'),
        ('review_approved', 'Review Approved'),
        ('cart_abandonment', 'Cart Abandonment'),
        ('back_in_stock', 'Back in Stock'),
    ])
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.name} ({self.template_type})"


class EmailQueue(models.Model):
    """Queue emails for sending"""
    to_email = models.EmailField()
    subject = models.CharField(max_length=255)
    html_content = models.TextField()
    text_content = models.TextField(blank=True)
    status = models.CharField(max_length=20, choices=[
        ('pending', 'Pending'),
        ('sent', 'Sent'),
        ('failed', 'Failed'),
    ], default='pending')
    retry_count = models.IntegerField(default=0)
    max_retries = models.IntegerField(default=3)
    error_message = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    sent_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"Email to {self.to_email} - {self.status}"


class CartAbandonment(models.Model):
    """Track cart abandonments for email notifications"""
    user = models.ForeignKey(User, on_delete=models.CASCADE, null=True, blank=True)
    session_key = models.CharField(max_length=255)
    email = models.EmailField(blank=True)
    cart_items = models.JSONField(default=dict)
    total_amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    notified = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    notified_at = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return f"Abandoned cart - {self.email or self.session_key}"


# ===== Security =====
class LoginAttempt(models.Model):
    """Track login attempts for security monitoring"""
    username = models.CharField(max_length=150)
    ip_address = models.GenericIPAddressField()
    success = models.BooleanField(default=False)
    timestamp = models.DateTimeField(auto_now_add=True)
    user_agent = models.CharField(max_length=255, blank=True)

    class Meta:
        ordering = ['-timestamp']

    def __str__(self):
        status = 'Success' if self.success else 'Failed'
        return f"{self.username} - {status} - {self.timestamp}"


class SuspiciousActivity(models.Model):
    """Track suspicious activities for admin alerts"""
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    activity_type = models.CharField(max_length=50, choices=[
        ('multiple_failed_logins', 'Multiple Failed Logins'),
        ('unusual_location', 'Unusual Location'),
        ('rapid_requests', 'Rapid Requests'),
        ('suspicious_pattern', 'Suspicious Pattern'),
    ])
    description = models.TextField()
    ip_address = models.GenericIPAddressField()
    is_resolved = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    resolved_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']
        verbose_name_plural = 'Suspicious Activities'

    def __str__(self):
        return f"{self.activity_type} - {self.ip_address} - {self.created_at}"
