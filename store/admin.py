from django.contrib import admin
from django import forms
from django.utils.html import format_html
from django.utils.safestring import mark_safe
from .models import Brand, Category, Product


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

from .models import Order, OrderItem


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