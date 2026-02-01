from django.contrib import admin
from django import forms
from django.utils.html import format_html
from django.utils.safestring import mark_safe
from .models import (
    Brand, Category, Product, Order, OrderItem,
    Cart, CartItem, Coupon, SearchQuery, SavedSearch,
    ProductView, Wishlist, Review
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


# Phase 2A Admin Registrations

@admin.register(Cart)
class CartAdmin(admin.ModelAdmin):
    list_display = ('id', 'user', 'session_key', 'created_at', 'updated_at')
    list_filter = ('created_at',)
    search_fields = ('user__username', 'session_key')
    readonly_fields = ('created_at', 'updated_at')


@admin.register(CartItem)
class CartItemAdmin(admin.ModelAdmin):
    list_display = ('id', 'cart', 'product', 'quantity', 'created_at')
    list_filter = ('created_at',)
    search_fields = ('product__name',)


@admin.register(Coupon)
class CouponAdmin(admin.ModelAdmin):
    list_display = ('code', 'discount_percentage', 'discount_amount', 'valid_from', 'valid_to', 'is_active', 'used_count')
    list_filter = ('is_active', 'valid_from', 'valid_to')
    search_fields = ('code',)
    readonly_fields = ('used_count',)
    list_editable = ('is_active',)


@admin.register(SearchQuery)
class SearchQueryAdmin(admin.ModelAdmin):
    list_display = ('query', 'user', 'results_count', 'created_at')
    list_filter = ('created_at',)
    search_fields = ('query',)
    readonly_fields = ('created_at',)


@admin.register(SavedSearch)
class SavedSearchAdmin(admin.ModelAdmin):
    list_display = ('user', 'name', 'query', 'created_at')
    list_filter = ('created_at',)
    search_fields = ('name', 'query', 'user__username')


@admin.register(ProductView)
class ProductViewAdmin(admin.ModelAdmin):
    list_display = ('product', 'user', 'session_key', 'created_at')
    list_filter = ('created_at',)
    search_fields = ('product__name',)
    readonly_fields = ('created_at',)


@admin.register(Wishlist)
class WishlistAdmin(admin.ModelAdmin):
    list_display = ('user', 'created_at', 'updated_at')
    search_fields = ('user__username',)
    readonly_fields = ('created_at', 'updated_at')


@admin.register(Review)
class ReviewAdmin(admin.ModelAdmin):
    list_display = ('product', 'user', 'rating', 'created_at')
    list_filter = ('rating', 'created_at')
    search_fields = ('product__name', 'user__username', 'comment')
    readonly_fields = ('created_at', 'updated_at')
