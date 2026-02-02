# DEPRECATED: This file is no longer used.
# Admin registrations have been moved to store/admin.py.
# The models in paint_store.models are legacy and should not be used.
# All admin configuration is now in the store app.
#
# Keeping this file to avoid import errors, but all registrations are disabled.
# See store/admin.py for the current admin configuration.

from django.contrib import admin

# Legacy admin code commented out to prevent duplicate registrations.
# All admin registrations are now in store/admin.py
#
# from django import forms
# from .models import Product, Brand
#
# class ProductAdminForm(forms.ModelForm):
#     class Meta:
#         model = Product
#         fields = '__all__'
#
#     def clean(self):
#         cleaned_data = super().clean()
#         category = cleaned_data.get('category')
#         unit = cleaned_data.get('unit')
#         if category == 'putty' and unit != 'KG':
#             self.add_error('unit', 'Bột trét phải sử dụng đơn vị KG.')
#         if category in ['paint', 'waterproof'] and unit != 'L':
#             self.add_error('unit', 'Sơn và Chống thấm phải sử dụng đơn vị Lít.')
#         return cleaned_data
#
# class ProductAdmin(admin.ModelAdmin):
#     form = ProductAdminForm
#     list_display = ('name', 'brand', 'price', 'category', 'unit', 'quantity_value')
#     list_filter = ('brand', 'category', 'unit')
#     search_fields = ('name', 'brand__name')
#
# admin.site.register(Brand)
# admin.site.register(Product, ProductAdmin)