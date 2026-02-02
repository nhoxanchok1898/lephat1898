from django.db import models
from django.contrib.auth.models import User
from django.utils.text import slugify
from django.dispatch import receiver
from django.db.models.signals import post_save


class Brand(models.Model):
    """Brand model for product manufacturers"""
    name = models.CharField(max_length=120)
    logo = models.ImageField(upload_to='brands/', blank=True, null=True)
    slug = models.SlugField(max_length=120, blank=True, null=True)

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.name)
        super().save(*args, **kwargs)


class Category(models.Model):
    """Category model for product organization"""
    name = models.CharField(max_length=120)
    slug = models.SlugField(max_length=120, blank=True, null=True)

    class Meta:
        verbose_name_plural = 'Categories'

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.name)
        super().save(*args, **kwargs)


class Product(models.Model):
    """Main product model"""
    UNIT_LIT = 'LIT'
    UNIT_KG = 'KG'
    UNIT_CHOICES = [
        (UNIT_LIT, 'Lít'),
        (UNIT_KG, 'Kilogram'),
    ]

    name = models.CharField(max_length=250)
    description = models.TextField(blank=True, default='')
    brand = models.ForeignKey(Brand, on_delete=models.CASCADE)
    category = models.ForeignKey(Category, on_delete=models.SET_NULL, blank=True, null=True)
    price = models.DecimalField(max_digits=12, decimal_places=2)
    sale_price = models.DecimalField(max_digits=12, decimal_places=2, blank=True, null=True)
    unit_type = models.CharField(max_length=3, choices=UNIT_CHOICES, default=UNIT_LIT)
    volume = models.PositiveIntegerField(default=1, help_text='Volume in selected unit, e.g., 5, 18, 20')
    quantity = models.PositiveIntegerField(default=0)
    stock_quantity = models.PositiveIntegerField(default=0)
    image = models.ImageField(upload_to='products/', blank=True, null=True)
    slug = models.SlugField(max_length=250, blank=True, null=True)
    is_active = models.BooleanField(default=True)
    is_new = models.BooleanField(default=False)
    is_on_sale = models.BooleanField(default=False)
    view_count = models.PositiveIntegerField(default=0)
    rating = models.DecimalField(max_digits=3, decimal_places=2, default=0.0)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-created_at']
        indexes = [
            models.Index(fields=['name', 'brand']),
            models.Index(fields=['is_active', 'created_at']),
            models.Index(fields=['category', 'is_active']),
        ]

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.name)
        # Auto-set is_on_sale if sale_price is set and less than price
        if self.sale_price and self.sale_price < self.price:
            self.is_on_sale = True
        else:
            self.is_on_sale = False
        super().save(*args, **kwargs)
    
    def get_price(self):
        """Get the effective price (sale price if available, otherwise regular price)"""
        return self.sale_price if self.sale_price else self.price
    
    def is_in_stock(self):
        """Check if product is in stock"""
        return self.stock_quantity > 0


class Order(models.Model):
    """Order model for customer orders"""
    full_name = models.CharField(max_length=200)
    phone = models.CharField(max_length=30)
    address = models.TextField()
    payment_method = models.CharField(max_length=30, default='offline')
    payment_status = models.CharField(max_length=30, default='pending')
    payment_reference = models.CharField(max_length=255, blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Order #{self.id} - {self.full_name}"


class OrderItem(models.Model):
    """Order item model for individual products in an order"""
    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name='items')
    product = models.ForeignKey(Product, on_delete=models.PROTECT)
    quantity = models.PositiveIntegerField()
    price = models.DecimalField(max_digits=12, decimal_places=2)

    def __str__(self):
        return f"{self.product.name} x {self.quantity}"


class ProductView(models.Model):
    """Track product views for analytics"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='views')
    user = models.ForeignKey(User, on_delete=models.CASCADE, blank=True, null=True)
    session_key = models.CharField(max_length=40, blank=True, null=True)
    ip_address = models.GenericIPAddressField(blank=True, null=True)
    viewed_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-viewed_at']
        indexes = [
            models.Index(fields=['product', '-viewed_at']),
            models.Index(fields=['user', '-viewed_at']),
        ]

    def __str__(self):
        return f"{self.product.name} viewed at {self.viewed_at}"


class ProductViewAnalytics(models.Model):
    """Analytics for product views"""
    product = models.OneToOneField(Product, on_delete=models.CASCADE, related_name='analytics')
    total_views = models.PositiveIntegerField(default=0)
    unique_views = models.PositiveIntegerField(default=0)
    total_purchases = models.PositiveIntegerField(default=0)
    last_viewed = models.DateTimeField(blank=True, null=True)
    last_purchased = models.DateTimeField(blank=True, null=True)

    class Meta:
        verbose_name_plural = 'Product View Analytics'

    def __str__(self):
        return f"Analytics for {self.product.name}"
    
    def update_view_count(self):
        """Update view count and last viewed timestamp"""
        from django.utils import timezone
        self.total_views += 1
        self.last_viewed = timezone.now()
        self.save()


class StockLevel(models.Model):
    """Inventory management for products"""
    product = models.OneToOneField(Product, on_delete=models.CASCADE, related_name='stock')
    quantity = models.PositiveIntegerField(default=0)
    low_stock_threshold = models.PositiveIntegerField(default=10)
    last_restocked = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Stock for {self.product.name}: {self.quantity}"

    @property
    def is_low_stock(self):
        return self.quantity <= self.low_stock_threshold

    @property
    def is_out_of_stock(self):
        return self.quantity == 0


class StockAlert(models.Model):
    """Alerts for low or out of stock products"""
    ALERT_LOW = 'low'
    ALERT_OUT = 'out'
    ALERT_CHOICES = [
        (ALERT_LOW, 'Low Stock'),
        (ALERT_OUT, 'Out of Stock'),
    ]

    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='stock_alerts')
    alert_type = models.CharField(max_length=20, choices=ALERT_CHOICES)
    created_at = models.DateTimeField(auto_now_add=True)
    resolved = models.BooleanField(default=False)
    resolved_at = models.DateTimeField(blank=True, null=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.get_alert_type_display()} - {self.product.name}"


class PreOrder(models.Model):
    """Pre-orders for out of stock products"""
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
        return f"PreOrder for {self.product.name} by {self.customer_name}"


class BackInStockNotification(models.Model):
    """Notifications for when products are back in stock"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='back_in_stock_requests')
    email = models.EmailField()
    created_at = models.DateTimeField(auto_now_add=True)
    notified = models.BooleanField(default=False)
    notified_at = models.DateTimeField(blank=True, null=True)

    class Meta:
        ordering = ['-created_at']
        unique_together = [('product', 'email')]

    def __str__(self):
        return f"Back in stock request for {self.product.name}"


class OrderAnalytics(models.Model):
    """Daily order analytics"""
    date = models.DateField(unique=True)
    total_orders = models.PositiveIntegerField(default=0)
    total_revenue = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    avg_order_value = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    total_items_sold = models.PositiveIntegerField(default=0)

    class Meta:
        verbose_name_plural = 'Order Analytics'
        ordering = ['-date']

    def __str__(self):
        return f"Order Analytics for {self.date}"


class UserAnalytics(models.Model):
    """Daily user analytics"""
    date = models.DateField(unique=True)
    total_users = models.PositiveIntegerField(default=0)
    new_users = models.PositiveIntegerField(default=0)
    active_users = models.PositiveIntegerField(default=0)

    class Meta:
        verbose_name_plural = 'User Analytics'
        ordering = ['-date']

    def __str__(self):
        return f"User Analytics for {self.date}"


class ProductPerformance(models.Model):
    """Daily product performance analytics"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='performance')
    date = models.DateField()
    views = models.PositiveIntegerField(default=0)
    cart_additions = models.PositiveIntegerField(default=0)
    purchases = models.PositiveIntegerField(default=0)
    revenue = models.DecimalField(max_digits=12, decimal_places=2, default=0)

    class Meta:
        ordering = ['-date']
        unique_together = [('product', 'date')]

    def __str__(self):
        return f"Performance for {self.product.name} on {self.date}"

    @property
    def conversion_rate(self):
        if self.views > 0:
            return (self.purchases / self.views) * 100
        return 0


class Coupon(models.Model):
    """Coupon model for discounts"""
    DISCOUNT_PERCENTAGE = 'percentage'
    DISCOUNT_FIXED = 'fixed'
    DISCOUNT_CHOICES = [
        (DISCOUNT_PERCENTAGE, 'Percentage'),
        (DISCOUNT_FIXED, 'Fixed Amount'),
    ]

    code = models.CharField(max_length=50, unique=True)
    description = models.TextField(blank=True)
    discount_type = models.CharField(max_length=20, choices=DISCOUNT_CHOICES, blank=True, null=True)
    discount_value = models.DecimalField(max_digits=10, decimal_places=2, blank=True, null=True)
    discount_percentage = models.DecimalField(max_digits=5, decimal_places=2, blank=True, null=True)
    discount_amount = models.DecimalField(max_digits=12, decimal_places=2, blank=True, null=True)
    min_purchase_amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    max_uses = models.PositiveIntegerField(blank=True, null=True, help_text='Leave empty for unlimited')
    max_uses_per_user = models.PositiveIntegerField(default=1)
    used_count = models.PositiveIntegerField(default=0)
    start_date = models.DateTimeField(blank=True, null=True)
    end_date = models.DateTimeField(blank=True, null=True)
    valid_from = models.DateTimeField(blank=True, null=True)
    valid_to = models.DateTimeField(blank=True, null=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    allowed_users = models.ManyToManyField(User, blank=True, related_name='allowed_coupons')
    allowed_products = models.ManyToManyField(Product, blank=True, related_name='allowed_coupons')

    def __str__(self):
        return self.code
    
    def is_valid(self):
        """Check if coupon is valid and can be used"""
        from django.utils import timezone
        now = timezone.now()
        
        # Check if active
        if not self.is_active:
            return False
        
        # Check date range (start_date and end_date)
        if self.start_date and now < self.start_date:
            return False
        if self.end_date and now > self.end_date:
            return False
        
        # Check date range (valid_from and valid_to)
        if self.valid_from and now < self.valid_from:
            return False
        if self.valid_to and now > self.valid_to:
            return False
        
        # Check usage limit
        if self.max_uses and self.used_count >= self.max_uses:
            return False
        
        return True
    
    def calculate_discount(self, cart_total):
        """Calculate discount amount based on cart total"""
        from decimal import Decimal
        
        if not self.is_valid():
            return Decimal('0')
        
        # Check minimum purchase requirement
        if cart_total < self.min_purchase_amount:
            return Decimal('0')
        
        # Use discount_value if available (new unified field)
        if self.discount_value:
            if self.discount_type == self.DISCOUNT_PERCENTAGE:
                return (cart_total * self.discount_value / Decimal('100'))
            elif self.discount_type == self.DISCOUNT_FIXED:
                return min(self.discount_value, cart_total)
        
        # Fallback to legacy fields
        if self.discount_percentage:
            return (cart_total * self.discount_percentage / Decimal('100'))
        elif self.discount_amount:
            return min(self.discount_amount, cart_total)
        
        return Decimal('0')
    
    def apply_discount(self, cart_total):
        """Apply discount to cart total - returns final price after discount"""
        discount_amount = self.calculate_discount(cart_total)
        return cart_total - discount_amount


class AppliedCoupon(models.Model):
    """Track coupon applications"""
    coupon = models.ForeignKey(Coupon, on_delete=models.CASCADE, related_name='applications')
    user = models.ForeignKey(User, on_delete=models.CASCADE, blank=True, null=True)
    session_key = models.CharField(max_length=40, blank=True, null=True)
    discount_amount = models.DecimalField(max_digits=10, decimal_places=2)
    applied_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.coupon.code} applied"


class EmailTemplate(models.Model):
    """Email templates for various email types"""
    EMAIL_WELCOME = 'welcome'
    EMAIL_ORDER_CONFIRMATION = 'order_confirmation'
    EMAIL_SHIPPING = 'shipping'
    EMAIL_CART_ABANDONMENT = 'cart_abandonment'
    EMAIL_BACK_IN_STOCK = 'back_in_stock'
    EMAIL_REVIEW_APPROVED = 'review_approved'
    EMAIL_PASSWORD_RESET = 'password_reset'
    EMAIL_NEWSLETTER = 'newsletter'
    EMAIL_CHOICES = [
        (EMAIL_WELCOME, 'Welcome Email'),
        (EMAIL_ORDER_CONFIRMATION, 'Order Confirmation'),
        (EMAIL_SHIPPING, 'Shipping Notification'),
        (EMAIL_CART_ABANDONMENT, 'Cart Abandonment'),
        (EMAIL_BACK_IN_STOCK, 'Back in Stock'),
        (EMAIL_REVIEW_APPROVED, 'Review Approved'),
        (EMAIL_PASSWORD_RESET, 'Password Reset'),
        (EMAIL_NEWSLETTER, 'Newsletter'),
    ]

    name = models.CharField(max_length=100, unique=True)
    email_type = models.CharField(max_length=30, choices=EMAIL_CHOICES)
    subject = models.CharField(max_length=200)
    html_content = models.TextField()
    text_content = models.TextField(blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return self.name


class EmailQueue(models.Model):
    """Queue for sending emails"""
    STATUS_PENDING = 'pending'
    STATUS_SENT = 'sent'
    STATUS_FAILED = 'failed'
    STATUS_CHOICES = [
        (STATUS_PENDING, 'Pending'),
        (STATUS_SENT, 'Sent'),
        (STATUS_FAILED, 'Failed'),
    ]

    template = models.ForeignKey(EmailTemplate, on_delete=models.SET_NULL, blank=True, null=True)
    to_email = models.EmailField()
    subject = models.CharField(max_length=200)
    html_content = models.TextField()
    text_content = models.TextField(blank=True)
    context_data = models.JSONField(default=dict, blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default=STATUS_PENDING)
    error_message = models.TextField(blank=True)
    retry_count = models.PositiveIntegerField(default=0)
    max_retries = models.PositiveIntegerField(default=3)
    created_at = models.DateTimeField(auto_now_add=True)
    sent_at = models.DateTimeField(blank=True, null=True)
    scheduled_for = models.DateTimeField(blank=True, null=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"Email to {self.to_email} - {self.status}"


class NewsletterSubscription(models.Model):
    """Newsletter subscription model"""
    email = models.EmailField(unique=True)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, blank=True, null=True)
    is_active = models.BooleanField(default=True)
    subscribed_at = models.DateTimeField(auto_now_add=True)
    unsubscribed_at = models.DateTimeField(blank=True, null=True)

    def __str__(self):
        return self.email


class Cart(models.Model):
    """Shopping cart model"""
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='carts')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Cart for {self.user.username}"


class CartItem(models.Model):
    """Cart item model"""
    cart = models.ForeignKey(Cart, on_delete=models.CASCADE, related_name='items')
    product = models.ForeignKey(Product, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField(default=1)
    price = models.DecimalField(max_digits=12, decimal_places=2, default=0)

    def __str__(self):
        return f"{self.product.name} x {self.quantity}"
    
    def get_total_price(self):
        """Get total price for this cart item"""
        return self.product.get_price() * self.quantity


class UserProfile(models.Model):
    """Extended user profile"""
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
    """Wishlist model"""
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='wishlist')
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='wishlisted_by')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = [('user', 'product')]

    def __str__(self):
        return f"{self.user.username}'s wishlist - {self.product.name}"


class Review(models.Model):
    """Product review model"""
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name='reviews')
    user = models.ForeignKey(User, on_delete=models.SET_NULL, blank=True, null=True)
    rating = models.PositiveSmallIntegerField(default=5)
    comment = models.TextField(blank=True, default='')
    verified_purchase = models.BooleanField(default=False)
    is_approved = models.BooleanField(default=False)
    helpful_count = models.IntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        user_name = self.user.username if self.user else 'Anonymous'
        return f"Review by {user_name} for {self.product.name} - {self.rating} stars"


class ReviewImage(models.Model):
    """Images for reviews"""
    review = models.ForeignKey(Review, on_delete=models.CASCADE, related_name='images')
    image = models.ImageField(upload_to='review_images/', blank=True, null=True)

    def __str__(self):
        return f"Image for review #{self.review.id}"


class ReviewHelpful(models.Model):
    """Track helpful votes on reviews"""
    review = models.ForeignKey(Review, on_delete=models.CASCADE, related_name='helpful_votes')
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = [('review', 'user')]

    def __str__(self):
        return f"{self.user.username} found review #{self.review.id} helpful"


class PaymentLog(models.Model):
    """Payment transaction logs"""
    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name='payments')
    transaction_id = models.CharField(max_length=255, blank=True, null=True)
    amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    status = models.CharField(max_length=50, default='pending')
    payment_method = models.CharField(max_length=50, blank=True)
    raw_response = models.TextField(blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Payment {self.transaction_id} for Order #{self.order.id}"


class EmailLog(models.Model):
    """Log of sent emails"""
    recipient = models.EmailField()
    subject = models.CharField(max_length=255)
    template_name = models.CharField(max_length=255, blank=True)
    status = models.CharField(max_length=50, default='pending')
    error = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Email to {self.recipient} - {self.subject}"


class SavedSearch(models.Model):
    """Saved searches for users"""
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='saved_searches')
    query = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.user.username}'s search: {self.query}"


class SearchQuery(models.Model):
    """Track search queries"""
    user = models.ForeignKey(User, on_delete=models.SET_NULL, blank=True, null=True)
    query = models.CharField(max_length=255)
    session_key = models.CharField(max_length=40, blank=True, null=True)
    result_count = models.PositiveIntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name_plural = 'Search Queries'
        ordering = ['-created_at']

    def __str__(self):
        return f"Search: {self.query}"


class LoginAttempt(models.Model):
    """Track login attempts for security"""
    username = models.CharField(max_length=150)
    ip_address = models.GenericIPAddressField()
    success = models.BooleanField(default=False)
    user_agent = models.TextField(blank=True)
    timestamp = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-timestamp']

    def __str__(self):
        status = 'successful' if self.success else 'failed'
        return f"{status} login attempt for {self.username} from {self.ip_address}"


class SuspiciousActivity(models.Model):
    """Track suspicious activity for security monitoring"""
    activity_type = models.CharField(max_length=100)
    description = models.TextField()
    ip_address = models.GenericIPAddressField()
    user_agent = models.TextField(blank=True)
    timestamp = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name_plural = 'Suspicious Activities'
        ordering = ['-timestamp']

    def __str__(self):
        return f"{self.activity_type} from {self.ip_address} at {self.timestamp}"
