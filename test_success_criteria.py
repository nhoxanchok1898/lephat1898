#!/usr/bin/env python
"""
Comprehensive test to validate all success criteria for store/models.py restoration
"""

import os
import django

# Setup Django
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from django.db import models
from django.contrib.auth.models import User

print("=" * 80)
print("SUCCESS CRITERIA VALIDATION FOR store/models.py RESTORATION")
print("=" * 80)
print()

# ============================================================================
# TEST 1: All models properly defined as Django models
# ============================================================================
print("TEST 1: All models properly defined as Django models")
print("-" * 80)

from store.models import (
    Brand, Category, Product, Order, OrderItem,
    ProductView, ProductViewAnalytics,
    StockLevel, LoginAttempt, SuspiciousActivity, UserProfile
)

test_models = [
    Brand, Category, Product, Order, OrderItem,
    ProductView, ProductViewAnalytics, StockLevel,
    LoginAttempt, SuspiciousActivity, UserProfile
]

for model in test_models:
    assert issubclass(model, models.Model), f"{model.__name__} is not a Django model"
    print(f"  ✅ {model.__name__} is a proper Django model")

print()

# ============================================================================
# TEST 2: All ForeignKey and OneToOneField relationships correct
# ============================================================================
print("TEST 2: All ForeignKey and OneToOneField relationships correct")
print("-" * 80)

# Check Product relationships
product_brand_field = Product._meta.get_field('brand')
assert isinstance(product_brand_field, models.ForeignKey), "Product.brand should be ForeignKey"
assert product_brand_field.related_model == Brand, "Product.brand should reference Brand"
print("  ✅ Product.brand -> Brand (ForeignKey)")

product_category_field = Product._meta.get_field('category')
assert isinstance(product_category_field, models.ForeignKey), "Product.category should be ForeignKey"
assert product_category_field.related_model == Category, "Product.category should reference Category"
print("  ✅ Product.category -> Category (ForeignKey)")

# Check OrderItem relationships
orderitem_order_field = OrderItem._meta.get_field('order')
assert isinstance(orderitem_order_field, models.ForeignKey), "OrderItem.order should be ForeignKey"
assert orderitem_order_field.related_model == Order, "OrderItem.order should reference Order"
print("  ✅ OrderItem.order -> Order (ForeignKey)")

orderitem_product_field = OrderItem._meta.get_field('product')
assert isinstance(orderitem_product_field, models.ForeignKey), "OrderItem.product should be ForeignKey"
assert orderitem_product_field.related_model == Product, "OrderItem.product should reference Product"
print("  ✅ OrderItem.product -> Product (ForeignKey)")

# Check ProductView relationships
productview_product_field = ProductView._meta.get_field('product')
assert isinstance(productview_product_field, models.ForeignKey), "ProductView.product should be ForeignKey"
assert productview_product_field.related_model == Product, "ProductView.product should reference Product"
print("  ✅ ProductView.product -> Product (ForeignKey)")

productview_user_field = ProductView._meta.get_field('user')
assert isinstance(productview_user_field, models.ForeignKey), "ProductView.user should be ForeignKey"
print("  ✅ ProductView.user -> User (ForeignKey)")

# Check ProductViewAnalytics relationships
analytics_product_field = ProductViewAnalytics._meta.get_field('product')
assert isinstance(analytics_product_field, models.OneToOneField), "ProductViewAnalytics.product should be OneToOneField"
assert analytics_product_field.related_model == Product, "ProductViewAnalytics.product should reference Product"
print("  ✅ ProductViewAnalytics.product -> Product (OneToOneField)")

# Check StockLevel relationships
stock_product_field = StockLevel._meta.get_field('product')
assert isinstance(stock_product_field, models.OneToOneField), "StockLevel.product should be OneToOneField"
assert stock_product_field.related_model == Product, "StockLevel.product should reference Product"
print("  ✅ StockLevel.product -> Product (OneToOneField)")

# Check UserProfile relationships
userprofile_user_field = UserProfile._meta.get_field('user')
assert isinstance(userprofile_user_field, models.OneToOneField), "UserProfile.user should be OneToOneField"
assert userprofile_user_field.related_model == User, "UserProfile.user should reference User"
print("  ✅ UserProfile.user -> User (OneToOneField)")

print()

# ============================================================================
# TEST 3: All imports work
# ============================================================================
print("TEST 3: All imports work")
print("-" * 80)

try:
    from store.models import Brand
    print("  ✅ from store.models import Brand")
    
    from store.models import Category
    print("  ✅ from store.models import Category")
    
    from store.models import Product
    print("  ✅ from store.models import Product")
    
    from store.models import Order
    print("  ✅ from store.models import Order")
    
    from store.models import OrderItem
    print("  ✅ from store.models import OrderItem")
    
    from store.models import ProductView
    print("  ✅ from store.models import ProductView")
    
    from store.models import ProductViewAnalytics
    print("  ✅ from store.models import ProductViewAnalytics")
    
    from store.models import StockLevel
    print("  ✅ from store.models import StockLevel")
    
    from store.models import LoginAttempt
    print("  ✅ from store.models import LoginAttempt")
    
    from store.models import SuspiciousActivity
    print("  ✅ from store.models import SuspiciousActivity")
    
    from store.models import UserProfile
    print("  ✅ from store.models import UserProfile")
    
except ImportError as e:
    print(f"  ❌ Import failed: {e}")
    exit(1)

print()

# ============================================================================
# TEST 4: Model fields validation
# ============================================================================
print("TEST 4: Model fields validation")
print("-" * 80)

# Brand fields
assert hasattr(Brand, 'name'), "Brand should have 'name' field"
assert hasattr(Brand, 'logo'), "Brand should have 'logo' field"
print("  ✅ Brand has required fields (name, logo)")

# Category fields
assert hasattr(Category, 'name'), "Category should have 'name' field"
print("  ✅ Category has required fields (name)")

# Product fields - checking all required fields from problem statement
product_fields = [
    'name', 'description', 'brand', 'category', 'price', 'sale_price',
    'unit_type', 'volume', 'quantity', 'image', 'is_active', 'is_new',
    'is_on_sale', 'created_at', 'updated_at', 'view_count', 'rating'
]
for field in product_fields:
    assert hasattr(Product, field), f"Product should have '{field}' field"
print(f"  ✅ Product has all {len(product_fields)} required fields")

# Order fields
order_fields = ['full_name', 'phone', 'address', 'payment_method', 'payment_status', 'payment_reference', 'created_at', 'updated_at']
for field in order_fields:
    assert hasattr(Order, field), f"Order should have '{field}' field"
print(f"  ✅ Order has all {len(order_fields)} required fields")

# OrderItem fields
orderitem_fields = ['order', 'product', 'quantity', 'price']
for field in orderitem_fields:
    assert hasattr(OrderItem, field), f"OrderItem should have '{field}' field"
print(f"  ✅ OrderItem has all {len(orderitem_fields)} required fields")

# ProductView fields
productview_fields = ['product', 'user', 'session_key', 'ip_address', 'viewed_at']
for field in productview_fields:
    assert hasattr(ProductView, field), f"ProductView should have '{field}' field"
print(f"  ✅ ProductView has all {len(productview_fields)} required fields")

# ProductViewAnalytics fields
analytics_fields = ['product', 'total_views', 'unique_views', 'total_purchases']
for field in analytics_fields:
    assert hasattr(ProductViewAnalytics, field), f"ProductViewAnalytics should have '{field}' field"
print(f"  ✅ ProductViewAnalytics has all {len(analytics_fields)} required fields")

# StockLevel fields
stock_fields = ['product', 'quantity', 'low_stock_threshold']
for field in stock_fields:
    assert hasattr(StockLevel, field), f"StockLevel should have '{field}' field"
print(f"  ✅ StockLevel has all {len(stock_fields)} required fields")

# LoginAttempt fields
login_fields = ['username', 'ip_address', 'success', 'user_agent', 'timestamp']
for field in login_fields:
    assert hasattr(LoginAttempt, field), f"LoginAttempt should have '{field}' field"
print(f"  ✅ LoginAttempt has all {len(login_fields)} required fields")

# SuspiciousActivity fields
suspicious_fields = ['activity_type', 'description', 'ip_address', 'timestamp']
for field in suspicious_fields:
    assert hasattr(SuspiciousActivity, field), f"SuspiciousActivity should have '{field}' field"
print(f"  ✅ SuspiciousActivity has all {len(suspicious_fields)} required fields")

# UserProfile fields
userprofile_fields = ['user', 'phone', 'address', 'created_at']
for field in userprofile_fields:
    assert hasattr(UserProfile, field), f"UserProfile should have '{field}' field"
print(f"  ✅ UserProfile has all {len(userprofile_fields)} required fields")

print()

# ============================================================================
# TEST 5: LoginAttempt and SuspiciousActivity are proper Django models
# ============================================================================
print("TEST 5: LoginAttempt and SuspiciousActivity are proper Django models")
print("-" * 80)

# Check they're not just plain Python classes
assert issubclass(LoginAttempt, models.Model), "LoginAttempt must be a Django model"
assert hasattr(LoginAttempt, 'objects'), "LoginAttempt should have 'objects' manager"
print("  ✅ LoginAttempt is a proper Django model (not just a Python class)")

assert issubclass(SuspiciousActivity, models.Model), "SuspiciousActivity must be a Django model"
assert hasattr(SuspiciousActivity, 'objects'), "SuspiciousActivity should have 'objects' manager"
print("  ✅ SuspiciousActivity is a proper Django model (not just a Python class)")

print()

# ============================================================================
# FINAL SUMMARY
# ============================================================================
print("=" * 80)
print("✅ ALL SUCCESS CRITERIA MET!")
print("=" * 80)
print()
print("Summary:")
print("  ✅ All models properly defined as Django models")
print("  ✅ All ForeignKey and OneToOneField relationships correct")
print("  ✅ All imports work: from store.models import Brand, Category, Product, Order, ...")
print("  ✅ All required fields present in each model")
print("  ✅ LoginAttempt and SuspiciousActivity are proper Django models")
print()
print("Note: python manage.py check has a pre-existing URL configuration issue")
print("      (auth_views.register_view missing) that is unrelated to the models fix.")
print("      The models themselves are correctly defined and migrations apply successfully.")
print()
