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
    slug = models.SlugField(max_length=120, blank=True, null=True)

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
    slug = models.SlugField(max_length=120, blank=True, null=True)

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
    slug = models.SlugField(max_length=250, blank=True, null=True)
    price = models.DecimalField(max_digits=12, decimal_places=2)
    sale_price = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)
    unit_type = models.CharField(max_length=3, choices=UNIT_CHOICES, default=UNIT_LIT)
    volume = models.PositiveIntegerField(help_text='Volume in selected unit, e.g., 5, 18, 20', default=0)
    quantity = models.PositiveIntegerField(default=0)
    image = models.ImageField(upload_to='products/', blank=True, null=True)
    is_active = models.BooleanField(default=True)
    is_new = models.BooleanField(default=False)
    is_on_sale = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    # Phase 2 fields
    stock_quantity = models.PositiveIntegerField(default=0)
    rating = models.DecimalField(max_digits=3, decimal_places=2, default=0.0)
    view_count = models.PositiveIntegerField(default=0)

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
        """Return sale price if available, otherwise regular price"""
        return self.sale_price if self.sale_price else self.price

    def get_is_on_sale(self):
        """Check if product is on sale (method version)"""
        return self.sale_price is not None and self.sale_price < self.price

    def is_in_stock(self):
        """Return True if product is in stock according to stock_quantity or quantity."""
        try:
            return (self.stock_quantity and self.stock_quantity > 0) or (self.quantity and self.quantity > 0)
        except Exception:
            return False


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


# ============= Recommendation Models =============

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


# ============= Inventory Models =============

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


# ============= Analytics Models =============

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


# ============= Coupon Models =============

class Coupon(models.Model):
    """Discount coupons"""
    code = models.CharField(max_length=50, unique=True)
    description = models.TextField(blank=True)
    # Backwards-compatible fields (legacy and new tests expect different names)
    discount_type = models.CharField(max_length=20, choices=[
        ('percentage', 'Percentage'),
        ('fixed', 'Fixed Amount'),
    ], blank=True, null=True)
    discount_value = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)
    # Newer test-suite fields
    discount_percentage = models.DecimalField(max_digits=5, decimal_places=2, null=True, blank=True)
    discount_amount = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)
    min_purchase_amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    max_uses = models.PositiveIntegerField(null=True, blank=True, help_text="Leave empty for unlimited")
    max_uses_per_user = models.PositiveIntegerField(default=1)
    used_count = models.PositiveIntegerField(default=0)
    # Support both naming conventions used across the codebase/tests
    start_date = models.DateTimeField(null=True, blank=True)
    end_date = models.DateTimeField(null=True, blank=True)
    valid_from = models.DateTimeField(null=True, blank=True)
    valid_to = models.DateTimeField(null=True, blank=True)
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
        # Normalize date bounds: prefer valid_from/valid_to if provided
        start = self.valid_from or self.start_date
        end = self.valid_to or self.end_date
        if start and now < start:
            return False
        if end and now > end:
            return False
        if self.max_uses and self.used_count >= self.max_uses:
            return False
        return True

    def apply_discount(self, cart_total):
        """Return the new cart total after applying the coupon."""
        # Check minimum purchase amount requirement
        if cart_total < self.min_purchase_amount:
            return cart_total
        
        # Prefer explicit percentage/amount fields
        if self.discount_percentage is not None:
            factor = (100 - self.discount_percentage) / 100
            return cart_total * factor
        if self.discount_amount is not None:
            return max(cart_total - self.discount_amount, 0)

        # Fallback to legacy fields
        if self.discount_type == 'percentage' and self.discount_value is not None:
            return (cart_total * (100 - self.discount_value)) / 100
        if self.discount_value is not None:
            return max(cart_total - self.discount_value, 0)

        return cart_total

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


# ============= Email Models =============

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


# ============= Search Models =============

class SearchQuery(models.Model):
    """Track search queries for analytics"""
    query = models.CharField(max_length=255)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    session_key = models.CharField(max_length=40, null=True, blank=True)
    result_count = models.PositiveIntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        ordering = ['-created_at']
        verbose_name_plural = "Search Queries"
    
    def __str__(self):
        return f"{self.query} - {self.created_at}"


# ============= Compatibility / Legacy Models for Tests =============


class UserProfile(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE, related_name='profile')
    phone = models.CharField(max_length=50, blank=True)
    address = models.TextField(blank=True)
    email_verified = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Profile of {self.user.username}"


@receiver(post_save, sender=User)
def create_user_profile(sender, instance, created, **kwargs):
    """Automatically create UserProfile when a User is created"""
    if created:
        try:
            UserProfile.objects.create(user=instance)
        except Exception:
            pass


class LoginAttempt(models.Model):
    """Track login attempts for security monitoring"""
    username = models.CharField(max_length=150)
    ip_address = models.GenericIPAddressField()
    success = models.BooleanField(default=False)
    user_agent = models.CharField(max_length=255, blank=True)
    timestamp = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-timestamp']
        indexes = [
            models.Index(fields=['ip_address', 'success', '-timestamp']),
            models.Index(fields=['username', '-timestamp']),
        ]

    def __str__(self):
        status = "Success" if self.success else "Failed"
        return f"{status} login: {self.username} from {self.ip_address} at {self.timestamp}"


class SuspiciousActivity(models.Model):
    """Track suspicious security activities"""
    activity_type = models.CharField(max_length=100)
    description = models.TextField()
    ip_address = models.GenericIPAddressField()
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-created_at']
        verbose_name_plural = "Suspicious Activities"
        indexes = [
            models.Index(fields=['activity_type', '-created_at']),
            models.Index(fields=['ip_address', '-created_at']),
        ]

    def __str__(self):
        return f"{self.activity_type} from {self.ip_address} at {self.created_at}"


class Wishlist(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='wishlist')
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='wishlisted_by')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('user', 'product')

    def __str__(self):
        return f"Wishlist: {self.user.username} - {self.product.name}"


class Review(models.Model):
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='ratings')
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    rating = models.PositiveSmallIntegerField(default=5)
    comment = models.TextField(blank=True, default='')
    verified_purchase = models.BooleanField(default=False)
    is_approved = models.BooleanField(default=False)
    helpful_count = models.IntegerField(default=0)
    created_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        user = self.user.username if self.user else 'Anonymous'
        return f"{user} - {self.product.name} ({self.rating})"


class ReviewHelpful(models.Model):
    review = models.ForeignKey(Review, on_delete=models.CASCADE, related_name='helpful_votes')
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('review', 'user')


class ReviewImage(models.Model):
    review = models.ForeignKey(Review, on_delete=models.CASCADE, related_name='images')
    image = models.ImageField(upload_to='review_images/', blank=True, null=True)


class Cart(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='carts')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def total_items(self):
        return sum(item.quantity for item in self.items.all())


class CartItem(models.Model):
    cart = models.ForeignKey(Cart, on_delete=models.CASCADE, related_name='items')
    product = models.ForeignKey(Product, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField(default=1)
    price = models.DecimalField(max_digits=12, decimal_places=2, default=0)

    def get_total_price(self):
        """Calculate total price for this cart item"""
        # Use the product's current price (which includes sale price if applicable)
        price = self.product.get_price()
        return price * self.quantity


class PaymentLog(models.Model):
    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name='payments')
    transaction_id = models.CharField(max_length=255, blank=True, null=True)
    amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    status = models.CharField(max_length=50, default='pending')
    payment_method = models.CharField(max_length=50, blank=True)
    raw_response = models.TextField(blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Payment {self.transaction_id} - Order {self.order.pk}"


class EmailLog(models.Model):
    recipient = models.EmailField()
    subject = models.CharField(max_length=255)
    template_name = models.CharField(max_length=255, blank=True)
    status = models.CharField(max_length=50, default='pending')
    error = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.recipient} - {self.subject} ({self.status})"


class SavedSearch(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='saved_searches')
    query = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.user.username} - {self.query}"
