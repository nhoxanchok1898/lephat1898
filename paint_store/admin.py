from django.contrib import admin
from django import forms
from .models import Product, Brand

class ProductAdminForm(forms.ModelForm):
    class Meta:
        model = Product
        fields = '__all__'

    def clean(self):
        cleaned_data = super().clean()
        category = cleaned_data.get('category')
        unit = cleaned_data.get('unit')
        if category == 'putty' and unit != 'KG':
            self.add_error('unit', 'Bột trét phải sử dụng đơn vị KG.')
        if category in ['paint', 'waterproof'] and unit != 'L':
            self.add_error('unit', 'Sơn và Chống thấm phải sử dụng đơn vị Lít.')
        return cleaned_data

class ProductAdmin(admin.ModelAdmin):
    form = ProductAdminForm
    list_display = ('name', 'brand', 'price', 'category', 'unit', 'quantity_value')
    list_filter = ('brand', 'category', 'unit')
    search_fields = ('name', 'brand__name')

admin.site.register(Brand)
admin.site.register(Product, ProductAdmin)