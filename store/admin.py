from decimal import Decimal, InvalidOperation
from django.contrib import admin
from django.contrib.admin import SimpleListFilter
from django import forms
from django.utils.html import format_html
from django.utils.safestring import mark_safe
from django.utils.translation import gettext_lazy as _
from django.utils.formats import number_format
from .models import (
    Brand, Category, Product, Order, OrderItem,
    ProductView, ProductViewAnalytics,
    StockLevel, StockAlert, PreOrder, BackInStockNotification,
    OrderAnalytics, UserAnalytics, ProductPerformance,
    Coupon, AppliedCoupon,
    EmailTemplate, EmailQueue, NewsletterSubscription
)
from django.db import models


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
        self.fields['price'].label = 'Giá (VND)'
        self.fields['sale_price'].label = 'Giá khuyến mãi (VND)'
        # Hiển thị giá với dấu chấm ngăn cách nghìn và bỏ spinner số thập phân
        for fname in ('price', 'sale_price'):
            field = self.fields[fname]
            field.widget = forms.TextInput(attrs={'class': 'vTextField text-right', 'inputmode': 'numeric', 'pattern': '[0-9\\.]*'})
            field.localize = True
        if self.instance and getattr(self.instance, 'price', None) is not None:
            self.initial['price'] = f"{int(self.instance.price):,}".replace(',', '.')
        if self.instance and getattr(self.instance, 'sale_price', None):
            self.initial['sale_price'] = f"{int(self.instance.sale_price):,}".replace(',', '.')
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

    def _parse_money(self, field_name):
        raw = self.data.get(field_name)
        if raw in (None, ''):
            return None
        normalized = raw.replace('.', '').replace(',', '')
        try:
            return Decimal(normalized)
        except InvalidOperation:
            raise forms.ValidationError("Vui lòng nhập số tiền hợp lệ, ví dụ: 1.250.000")

    def clean_price(self):
        value = self._parse_money('price')
        if value is None:
            raise forms.ValidationError("Giá không được để trống")
        return value

    def clean_sale_price(self):
        value = self._parse_money('sale_price')
        return value


@admin.register(Brand)
class BrandAdmin(admin.ModelAdmin):
    list_display = ('id', 'name')
    search_fields = ('name',)
    verbose_name = 'Thương hiệu'
    verbose_name_plural = 'Thương hiệu'


@admin.register(Category)
class CategoryAdmin(admin.ModelAdmin):
    list_display = ('id', 'name')
    search_fields = ('name',)
    verbose_name = 'Danh mục'
    verbose_name_plural = 'Danh mục'


class ProductTypeFilter(SimpleListFilter):
    title = 'Nhóm sản phẩm'
    parameter_name = 'product_group'

    def lookups(self, request, model_admin):
        return [
            ('interior', 'Sơn nội thất'),
            ('exterior', 'Sơn ngoại thất'),
            ('waterproof', 'Chống thấm'),
            ('putty', 'Bột/Bả'),
            ('other', 'Khác'),
        ]

    def queryset(self, request, queryset):
        val = self.value()
        if not val:
            return queryset
        key_map = {
            'interior': ['nội thất'],
            'exterior': ['ngoại thất'],
            'waterproof': ['chống thấm'],
            'putty': ['bột', 'bả'],
        }
        keywords = key_map.get(val, [])
        if keywords:
            q = models.Q()
            for kw in keywords:
                q |= models.Q(category__name__icontains=kw)
            return queryset.filter(q)
        return queryset


@admin.register(Product)
class ProductAdmin(admin.ModelAdmin):
    form = ProductAdminForm
    # Gọn danh sách: chỉ giữ các cột chính
    list_display = ('id', 'name', 'brand', 'category', 'price_vnd', 'sale_price_vnd', 'volume_with_unit', 'stock_quantity', 'is_active')
    list_filter = ('brand', 'category', ProductTypeFilter, 'unit_type', 'is_active')
    search_fields = ('name', 'brand__name')
    readonly_fields = ('thumbnail', 'view_count', 'rating', 'is_on_sale')
    ordering = ('-id',)
    list_editable = ('is_active',)
    fieldsets = (
        ('Thông tin chính', {
            'fields': ('name', 'slug', 'brand', 'category', 'description')
        }),
        ('Giá & khối lượng', {
            'fields': (('price', 'sale_price'), ('unit_type', 'volume')),
        }),
        ('Kho & hiển thị', {
            'fields': ('quantity', 'stock_quantity', 'is_active', 'is_new', 'is_on_sale', 'image')
        }),
        ('Thống kê', {
            'classes': ('collapse',),
            'fields': ('view_count', 'rating')
        })
    )

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

    def volume_with_unit(self, obj):
        unit = 'L' if obj.unit_type == Product.UNIT_LIT else 'Kg'
        return f"{obj.volume} {unit}"
    volume_with_unit.short_description = 'Khối lượng'

    @admin.display(ordering='price', description='Giá (VND)')
    def price_vnd(self, obj):
        return f"{number_format(obj.price, decimal_pos=0, force_grouping=True)} ₫"

    @admin.display(ordering='sale_price', description='KM (VND)')
    def sale_price_vnd(self, obj):
        return f"{number_format(obj.sale_price, decimal_pos=0, force_grouping=True)} ₫" if obj.sale_price else ''


@admin.register(Order)
class OrderAdmin(admin.ModelAdmin):
    list_display = ('id', 'full_name', 'phone', 'status', 'payment_method', 'payment_status', 'is_paid', 'paid_at', 'created_at')
    readonly_fields = ('created_at', 'paid_at')
    list_filter = ('status', 'payment_status', 'payment_method', 'is_paid')
    search_fields = ('full_name', 'phone', 'payment_reference')
    list_editable = ('status', 'is_paid')
    actions = ['export_orders_csv', 'mark_as_paid', 'cancel_orders']

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

    def save_model(self, request, obj, form, change):
        # If staff marks is_paid true, set payment_status to paid
        if 'is_paid' in form.changed_data and obj.is_paid:
            obj.payment_status = Order.PAYMENT_STATUS_PAID
        super().save_model(request, obj, form, change)

    def mark_as_paid(self, request, queryset):
        updated = 0
        for order in queryset:
            if not order.is_paid:
                order.is_paid = True
                order.payment_status = Order.PAYMENT_STATUS_PAID
                order.save()
                updated += 1
        self.message_user(request, f"Marked {updated} order(s) as paid.")
    mark_as_paid.short_description = 'Mark selected orders as paid'

    @admin.action(description="Cancel selected orders")
    def cancel_orders(self, request, queryset):
        canceled = 0
        for order in queryset:
            if order.status != Order.STATUS_CANCELED:
                order.status = Order.STATUS_CANCELED
                order.save()
                canceled += 1
        if canceled:
            self.message_user(request, f"Canceled {canceled} order(s).")
        else:
            self.message_user(request, "No orders were canceled (already canceled).")


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

# Tiêu đề admin tiếng Việt, gọn gàng
admin.site.site_header = "Quản trị Đại lý Sơn Phát Tấn"
admin.site.site_title = "Quản trị"
admin.site.index_title = "Bảng điều khiển"
