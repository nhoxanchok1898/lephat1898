from django.contrib import admin
from django import forms
from django.utils.html import format_html
from django.utils.safestring import mark_safe
from .models import (
    Brand, Category, Product, Order, OrderItem,
    ProductView, ProductViewAnalytics,
    StockLevel, StockAlert, PreOrder, BackInStockNotification,
    OrderAnalytics, UserAnalytics, ProductPerformance,
    Coupon, AppliedCoupon,
    EmailTemplate, EmailQueue, NewsletterSubscription
)


class ProductAdminForm(forms.ModelForm):
    class Meta:
        model = Product
        fields = '__all__'

    # extra fields for admin UX
    volume_l = forms.IntegerField(required=False, label='Volume (LIT)')
    volume_kg = forms.IntegerField(required=False, label='Volume (KG)')

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Optionally, adjust form display
        self.fields['unit_type'].widget.attrs.update({'style': 'width:140px'})
        # Adjust volume label according to unit_type when form is initialized
        unit = None
        if self.instance and getattr(self.instance, 'unit_type', None):
            unit = self.instance.unit_type
        elif 'unit_type' in self.initial:
            unit = self.initial.get('unit_type')
        if unit == Product.UNIT_KG:
            self.fields['volume'].label = 'Volume (KG)'
            # set extra fields initial
            self.fields['volume_kg'].initial = getattr(self.instance, 'volume', None)
        else:
            self.fields['volume'].label = 'Volume (LIT)'
            self.fields['volume_l'].initial = getattr(self.instance, 'volume', None)


@admin.register(Brand)
class BrandAdmin(admin.ModelAdmin):
    list_display = ('id', 'name')
    search_fields = ('name',)


@admin.register(Category)
class CategoryAdmin(admin.ModelAdmin):
    list_display = ('id', 'name')
    search_fields = ('name',)


@admin.register(Product)
class ProductAdmin(admin.ModelAdmin):
    form = ProductAdminForm
    list_display = ('id', 'thumbnail', 'name', 'brand', 'category', 'price', 'sale_price', 'stock_quantity', 'unit_type', 'volume', 'is_active', 'is_on_sale')
    list_filter = ('brand', 'category', 'unit_type', 'is_active', 'is_on_sale')
    search_fields = ('name', 'brand__name')
    readonly_fields = ('thumbnail', 'view_count', 'rating')
    ordering = ('-id',)
    list_editable = ('is_active', 'is_on_sale')

    def thumbnail(self, obj):
        if obj.image:
            return format_html('<img src="{}" style="height:40px;object-fit:cover;border-radius:4px"/>', obj.image.url)
        return ''
    thumbnail.short_description = 'Image'

    class Media:
        js = ('/static/js/admin_store.js',)

    def save_model(self, request, obj, form, change):
        # map volume_l / volume_kg back to obj.volume
        v_l = form.cleaned_data.get('volume_l')
        v_kg = form.cleaned_data.get('volume_kg')
        if obj.unit_type == Product.UNIT_KG:
            if v_kg:
                obj.volume = v_kg
        else:
            if v_l:
                obj.volume = v_l
        super().save_model(request, obj, form, change)


@admin.register(Order)
class OrderAdmin(admin.ModelAdmin):
    list_display = ('id', 'full_name', 'phone', 'payment_status', 'created_at')
    readonly_fields = ('created_at',)
    list_filter = ('payment_status', 'payment_method')
    search_fields = ('full_name', 'phone', 'payment_reference')
    actions = ['export_orders_csv']

    def export_orders_csv(self, request, queryset):
        import csv
        from django.http import HttpResponse

        meta = self.model._meta
        field_names = ['id', 'full_name', 'phone', 'address', 'created_at']

        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = 'attachment; filename=orders.csv'
        writer = csv.writer(response)
        writer.writerow(field_names + ['items'])
        for obj in queryset:
            items = []
            for it in obj.items.all():
                items.append(f"{it.product.name} x{it.quantity} @{it.price}")
            writer.writerow([getattr(obj, f) for f in field_names] + ["; ".join(items)])
        return response
    export_orders_csv.short_description = 'Export selected orders to CSV'


@admin.register(OrderItem)
class OrderItemAdmin(admin.ModelAdmin):
    list_display = ('id', 'order', 'product', 'quantity', 'price')


# Recommendation Models
@admin.register(ProductView)
class ProductViewAdmin(admin.ModelAdmin):
    list_display = ('product', 'user', 'session_key', 'viewed_at', 'ip_address')
    list_filter = ('viewed_at',)
    search_fields = ('product__name', 'user__username')
    date_hierarchy = 'viewed_at'


@admin.register(ProductViewAnalytics)
class ProductViewAnalyticsAdmin(admin.ModelAdmin):
    list_display = ('product', 'total_views', 'unique_views', 'total_purchases', 'last_viewed')
    readonly_fields = ('total_views', 'unique_views', 'total_purchases', 'last_viewed', 'last_purchased')
    search_fields = ('product__name',)


# Inventory Models
@admin.register(StockLevel)
class StockLevelAdmin(admin.ModelAdmin):
    list_display = ('product', 'quantity', 'low_stock_threshold', 'is_low_stock', 'is_out_of_stock', 'last_restocked')
    list_filter = ('last_restocked',)
    search_fields = ('product__name',)


@admin.register(StockAlert)
class StockAlertAdmin(admin.ModelAdmin):
    list_display = ('product', 'alert_type', 'created_at', 'resolved', 'resolved_at')
    list_filter = ('alert_type', 'resolved', 'created_at')
    search_fields = ('product__name',)


@admin.register(PreOrder)
class PreOrderAdmin(admin.ModelAdmin):
    list_display = ('product', 'customer_name', 'customer_email', 'quantity', 'created_at', 'notified', 'fulfilled')
    list_filter = ('notified', 'fulfilled', 'created_at')
    search_fields = ('product__name', 'customer_name', 'customer_email')


@admin.register(BackInStockNotification)
class BackInStockNotificationAdmin(admin.ModelAdmin):
    list_display = ('product', 'email', 'created_at', 'notified', 'notified_at')
    list_filter = ('notified', 'created_at')
    search_fields = ('product__name', 'email')


# Analytics Models
@admin.register(OrderAnalytics)
class OrderAnalyticsAdmin(admin.ModelAdmin):
    list_display = ('date', 'total_orders', 'total_revenue', 'avg_order_value', 'total_items_sold')
    date_hierarchy = 'date'


@admin.register(UserAnalytics)
class UserAnalyticsAdmin(admin.ModelAdmin):
    list_display = ('date', 'total_users', 'new_users', 'active_users')
    date_hierarchy = 'date'


@admin.register(ProductPerformance)
class ProductPerformanceAdmin(admin.ModelAdmin):
    list_display = ('product', 'date', 'views', 'cart_additions', 'purchases', 'revenue', 'conversion_rate')
    list_filter = ('date',)
    search_fields = ('product__name',)
    date_hierarchy = 'date'


# Coupon Models
@admin.register(Coupon)
class CouponAdmin(admin.ModelAdmin):
    list_display = ('code', 'discount_type', 'discount_value', 'used_count', 'max_uses', 'start_date', 'end_date', 'is_active')
    list_filter = ('discount_type', 'is_active', 'start_date', 'end_date')
    search_fields = ('code', 'description')
    filter_horizontal = ('allowed_users', 'allowed_products')


@admin.register(AppliedCoupon)
class AppliedCouponAdmin(admin.ModelAdmin):
    list_display = ('coupon', 'user', 'discount_amount', 'applied_at')
    list_filter = ('applied_at',)
    search_fields = ('coupon__code', 'user__username')


# Email Models
@admin.register(EmailTemplate)
class EmailTemplateAdmin(admin.ModelAdmin):
    list_display = ('name', 'email_type', 'subject', 'is_active', 'created_at', 'updated_at')
    list_filter = ('email_type', 'is_active', 'created_at')
    search_fields = ('name', 'subject')


@admin.register(EmailQueue)
class EmailQueueAdmin(admin.ModelAdmin):
    list_display = ('to_email', 'subject', 'status', 'retry_count', 'created_at', 'sent_at')
    list_filter = ('status', 'created_at', 'sent_at')
    search_fields = ('to_email', 'subject')
    readonly_fields = ('created_at', 'sent_at')


@admin.register(NewsletterSubscription)
class NewsletterSubscriptionAdmin(admin.ModelAdmin):
    list_display = ('email', 'user', 'is_active', 'subscribed_at', 'unsubscribed_at')
    list_filter = ('is_active', 'subscribed_at')
    search_fields = ('email', 'user__username')
