from django.contrib import admin
from django import forms
from django.utils.html import format_html
from django.utils.safestring import mark_safe
from .models import (
    Brand, Category, Product, Order, OrderItem,
    SearchQuery, SearchFilter, ProductView,
    CartSession, CartItem, Coupon,
    ProductRating, StockLevel, StockAlert, PreOrder, BackInStockNotification,
    ProductViewAnalytics, OrderAnalytics, UserAnalytics,
    EmailTemplate, EmailQueue, CartAbandonment
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
    list_display = ('id', 'thumbnail', 'name', 'brand', 'category', 'price', 'unit_type', 'volume', 'is_active')
    list_filter = ('brand', 'category', 'unit_type', 'is_active')
    search_fields = ('name', 'brand__name')
    readonly_fields = ('thumbnail',)
    ordering = ('-id',)

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
    list_display = ('id', 'full_name', 'phone', 'created_at')
    readonly_fields = ('created_at',)
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


# ===== Advanced Search System =====
@admin.register(SearchQuery)
class SearchQueryAdmin(admin.ModelAdmin):
    list_display = ('query', 'user', 'results_count', 'created_at', 'ip_address')
    list_filter = ('created_at',)
    search_fields = ('query', 'user__username')
    readonly_fields = ('created_at',)


@admin.register(SearchFilter)
class SearchFilterAdmin(admin.ModelAdmin):
    list_display = ('name', 'user', 'created_at')
    list_filter = ('created_at',)
    search_fields = ('name', 'user__username')


@admin.register(ProductView)
class ProductViewAdmin(admin.ModelAdmin):
    list_display = ('product', 'user', 'session_key', 'viewed_at', 'ip_address')
    list_filter = ('viewed_at',)
    search_fields = ('product__name', 'user__username')
    readonly_fields = ('viewed_at',)


# ===== Shopping Cart =====
@admin.register(CartSession)
class CartSessionAdmin(admin.ModelAdmin):
    list_display = ('user', 'created_at', 'updated_at', 'get_total')
    readonly_fields = ('created_at', 'updated_at')


@admin.register(CartItem)
class CartItemAdmin(admin.ModelAdmin):
    list_display = ('cart', 'product', 'quantity', 'get_subtotal')
    list_filter = ('created_at',)


@admin.register(Coupon)
class CouponAdmin(admin.ModelAdmin):
    list_display = ('code', 'discount_percent', 'discount_amount', 'valid_from', 'valid_to', 'active', 'used_count', 'max_uses')
    list_filter = ('active', 'valid_from', 'valid_to')
    search_fields = ('code',)


# ===== Product Recommendations =====
@admin.register(ProductRating)
class ProductRatingAdmin(admin.ModelAdmin):
    list_display = ('product', 'user', 'rating', 'created_at')
    list_filter = ('rating', 'created_at')
    search_fields = ('product__name', 'user__username')


# ===== Inventory Management =====
@admin.register(StockLevel)
class StockLevelAdmin(admin.ModelAdmin):
    list_display = ('product', 'quantity', 'reserved', 'available_quantity', 'low_stock_threshold', 'is_low_stock', 'updated_at')
    list_filter = ('updated_at',)
    search_fields = ('product__name',)


@admin.register(StockAlert)
class StockAlertAdmin(admin.ModelAdmin):
    list_display = ('product', 'message', 'is_resolved', 'created_at', 'resolved_at')
    list_filter = ('is_resolved', 'created_at')
    search_fields = ('product__name', 'message')


@admin.register(PreOrder)
class PreOrderAdmin(admin.ModelAdmin):
    list_display = ('product', 'user', 'quantity', 'status', 'expected_date', 'created_at')
    list_filter = ('status', 'created_at')
    search_fields = ('product__name', 'user__username')


@admin.register(BackInStockNotification)
class BackInStockNotificationAdmin(admin.ModelAdmin):
    list_display = ('product', 'email', 'notified', 'created_at', 'notified_at')
    list_filter = ('notified', 'created_at')
    search_fields = ('product__name', 'email')


# ===== Analytics =====
@admin.register(ProductViewAnalytics)
class ProductViewAnalyticsAdmin(admin.ModelAdmin):
    list_display = ('product', 'date', 'view_count')
    list_filter = ('date',)
    search_fields = ('product__name',)


@admin.register(OrderAnalytics)
class OrderAnalyticsAdmin(admin.ModelAdmin):
    list_display = ('date', 'order_count', 'revenue', 'items_sold')
    list_filter = ('date',)


@admin.register(UserAnalytics)
class UserAnalyticsAdmin(admin.ModelAdmin):
    list_display = ('date', 'new_users', 'active_users', 'total_users')
    list_filter = ('date',)


# ===== Email System =====
@admin.register(EmailTemplate)
class EmailTemplateAdmin(admin.ModelAdmin):
    list_display = ('name', 'subject', 'template_type', 'created_at', 'updated_at')
    list_filter = ('template_type', 'created_at')
    search_fields = ('name', 'subject')


@admin.register(EmailQueue)
class EmailQueueAdmin(admin.ModelAdmin):
    list_display = ('to_email', 'subject', 'status', 'retry_count', 'created_at', 'sent_at')
    list_filter = ('status', 'created_at')
    search_fields = ('to_email', 'subject')


@admin.register(CartAbandonment)
class CartAbandonmentAdmin(admin.ModelAdmin):
    list_display = ('email', 'total_amount', 'notified', 'created_at', 'notified_at')
    list_filter = ('notified', 'created_at')
    search_fields = ('email', 'session_key')