from django.db import models
from django.contrib.auth.models import User
from .models import Product


class ProductView(models.Model):
    """Track product views for recommendation engine"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='views')
    user = models.ForeignKey(User, on_delete=models.CASCADE, null=True, blank=True)
    session_key = models.CharField(max_length=40, null=True, blank=True)
    viewed_at = models.DateTimeField(auto_now_add=True)
    ip_address = models.GenericIPAddressField(null=True, blank=True)

    class Meta:
        ordering = ['-viewed_at']
        indexes = [
            models.Index(fields=['product', '-viewed_at']),
            models.Index(fields=['user', '-viewed_at']),
        ]

    def __str__(self):
        return f"{self.product.name} viewed at {self.viewed_at}"


class ProductViewAnalytics(models.Model):
    """Aggregated analytics for products"""
    product = models.OneToOneField(Product, on_delete=models.CASCADE, related_name='analytics')
    total_views = models.PositiveIntegerField(default=0)
    unique_views = models.PositiveIntegerField(default=0)
    total_purchases = models.PositiveIntegerField(default=0)
    last_viewed = models.DateTimeField(null=True, blank=True)
    last_purchased = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name_plural = "Product View Analytics"

    def __str__(self):
        return f"{self.product.name} - {self.total_views} views"

    def update_view_count(self):
        """Update view counts from ProductView"""
        self.total_views = self.product.views.count()
        self.unique_views = self.product.views.values('user').distinct().count()
        latest_view = self.product.views.first()
        if latest_view:
            self.last_viewed = latest_view.viewed_at
        self.save()
from django.db import models
from .models import Product


class StockLevel(models.Model):
    """Track stock levels for each product"""
    product = models.OneToOneField(Product, on_delete=models.CASCADE, related_name='stock')
    quantity = models.PositiveIntegerField(default=0)
    low_stock_threshold = models.PositiveIntegerField(default=10)
    last_restocked = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.product.name} - {self.quantity} units"

    @property
    def is_low_stock(self):
        return self.quantity <= self.low_stock_threshold

    @property
    def is_out_of_stock(self):
        return self.quantity == 0


class StockAlert(models.Model):
    """Alert for low stock items"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='stock_alerts')
    alert_type = models.CharField(max_length=20, choices=[
        ('low', 'Low Stock'),
        ('out', 'Out of Stock'),
    ])
    created_at = models.DateTimeField(auto_now_add=True)
    resolved = models.BooleanField(default=False)
    resolved_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.get_alert_type_display()} - {self.product.name}"


class PreOrder(models.Model):
    """Handle pre-orders for out of stock products"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='pre_orders')
    customer_email = models.EmailField()
    customer_name = models.CharField(max_length=200)
    quantity = models.PositiveIntegerField(default=1)
    created_at = models.DateTimeField(auto_now_add=True)
    notified = models.BooleanField(default=False)
    fulfilled = models.BooleanField(default=False)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"Pre-order: {self.customer_name} - {self.product.name}"


class BackInStockNotification(models.Model):
    """Notification requests for when products are back in stock"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='back_in_stock_requests')
    email = models.EmailField()
    created_at = models.DateTimeField(auto_now_add=True)
    notified = models.BooleanField(default=False)
    notified_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']
        unique_together = ['product', 'email']

    def __str__(self):
        return f"Back in stock request: {self.email} - {self.product.name}"
from django.db import models
from django.contrib.auth.models import User
from .models import Order, Product


class OrderAnalytics(models.Model):
    """Daily aggregated order analytics"""
    date = models.DateField(unique=True)
    total_orders = models.PositiveIntegerField(default=0)
    total_revenue = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    avg_order_value = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    total_items_sold = models.PositiveIntegerField(default=0)

    class Meta:
        ordering = ['-date']
        verbose_name_plural = "Order Analytics"

    def __str__(self):
        return f"{self.date} - ${self.total_revenue}"


class UserAnalytics(models.Model):
    """Daily aggregated user analytics"""
    date = models.DateField(unique=True)
    total_users = models.PositiveIntegerField(default=0)
    new_users = models.PositiveIntegerField(default=0)
    active_users = models.PositiveIntegerField(default=0)

    class Meta:
        ordering = ['-date']
        verbose_name_plural = "User Analytics"

    def __str__(self):
        return f"{self.date} - {self.new_users} new users"


class ProductPerformance(models.Model):
    """Track product performance metrics"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='performance')
    date = models.DateField()
    views = models.PositiveIntegerField(default=0)
    cart_additions = models.PositiveIntegerField(default=0)
    purchases = models.PositiveIntegerField(default=0)
    revenue = models.DecimalField(max_digits=12, decimal_places=2, default=0)

    class Meta:
        ordering = ['-date']
        unique_together = ['product', 'date']

    def __str__(self):
        return f"{self.product.name} - {self.date}"

    @property
    def conversion_rate(self):
        if self.views > 0:
            return (self.purchases / self.views) * 100
        return 0
from django.db import models
from django.contrib.auth.models import User
from django.core.exceptions import ValidationError
from django.utils import timezone
from .models import Product


class Coupon(models.Model):
    """Discount coupons"""
    code = models.CharField(max_length=50, unique=True)
    description = models.TextField(blank=True)
    discount_type = models.CharField(max_length=20, choices=[
        ('percentage', 'Percentage'),
        ('fixed', 'Fixed Amount'),
    ])
    discount_value = models.DecimalField(max_digits=10, decimal_places=2)
    min_purchase_amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    max_uses = models.PositiveIntegerField(null=True, blank=True, help_text="Leave empty for unlimited")
    max_uses_per_user = models.PositiveIntegerField(default=1)
    used_count = models.PositiveIntegerField(default=0)
    start_date = models.DateTimeField()
    end_date = models.DateTimeField()
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    # Optional restrictions
    allowed_users = models.ManyToManyField(User, blank=True, related_name='allowed_coupons')
    allowed_products = models.ManyToManyField(Product, blank=True, related_name='allowed_coupons')

    def __str__(self):
        return f"{self.code} - {self.get_discount_type_display()} {self.discount_value}"

    def is_valid(self):
        """Check if coupon is currently valid"""
        now = timezone.now()
        if not self.is_active:
            return False
        if now < self.start_date or now > self.end_date:
            return False
        if self.max_uses and self.used_count >= self.max_uses:
            return False
        return True

    def calculate_discount(self, cart_total):
        """Calculate discount amount for given cart total"""
        if self.discount_type == 'percentage':
            return (cart_total * self.discount_value) / 100
        else:
            return self.discount_value

    def clean(self):
        if self.start_date and self.end_date and self.start_date >= self.end_date:
            raise ValidationError("End date must be after start date")


class AppliedCoupon(models.Model):
    """Track coupon usage"""
    coupon = models.ForeignKey(Coupon, on_delete=models.CASCADE, related_name='applications')
    user = models.ForeignKey(User, on_delete=models.CASCADE, null=True, blank=True)
    session_key = models.CharField(max_length=40, null=True, blank=True)
    discount_amount = models.DecimalField(max_digits=10, decimal_places=2)
    applied_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.coupon.code} - ${self.discount_amount}"
from django.db import models
from django.contrib.auth.models import User
from .models import Product


class EmailTemplate(models.Model):
    """Email templates for various notifications"""
    name = models.CharField(max_length=100, unique=True)
    subject = models.CharField(max_length=200)
    html_content = models.TextField()
    text_content = models.TextField(blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    EMAIL_TYPES = [
        ('welcome', 'Welcome Email'),
        ('order_confirmation', 'Order Confirmation'),
        ('shipping', 'Shipping Notification'),
        ('cart_abandonment', 'Cart Abandonment'),
        ('back_in_stock', 'Back in Stock'),
        ('review_approved', 'Review Approved'),
        ('password_reset', 'Password Reset'),
        ('newsletter', 'Newsletter'),
    ]
    email_type = models.CharField(max_length=30, choices=EMAIL_TYPES)

    def __str__(self):
        return f"{self.name} ({self.get_email_type_display()})"


class EmailQueue(models.Model):
    """Queue for emails to be sent"""
    template = models.ForeignKey(EmailTemplate, on_delete=models.SET_NULL, null=True, blank=True)
    to_email = models.EmailField()
    subject = models.CharField(max_length=200)
    html_content = models.TextField()
    text_content = models.TextField(blank=True)
    context_data = models.JSONField(default=dict, blank=True)
    
    STATUS_CHOICES = [
        ('pending', 'Pending'),
        ('sent', 'Sent'),
        ('failed', 'Failed'),
    ]
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='pending')
    error_message = models.TextField(blank=True)
    retry_count = models.PositiveIntegerField(default=0)
    max_retries = models.PositiveIntegerField(default=3)
    
    created_at = models.DateTimeField(auto_now_add=True)
    sent_at = models.DateTimeField(null=True, blank=True)
    scheduled_for = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.to_email} - {self.subject} ({self.status})"


class NewsletterSubscription(models.Model):
    """Newsletter subscriptions"""
    email = models.EmailField(unique=True)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    is_active = models.BooleanField(default=True)
    subscribed_at = models.DateTimeField(auto_now_add=True)
    unsubscribed_at = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return f"{self.email} - {'Active' if self.is_active else 'Unsubscribed'}"
