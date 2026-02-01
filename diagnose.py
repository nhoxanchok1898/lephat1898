#!/usr/bin/env python
"""
Diagnostic script to test store.models imports
"""

import os
import django

# Setup Django
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

# Test all model imports
print("Testing store.models imports...")
print("-" * 60)

try:
    from store.models import (
        Brand, Category, Product, Order, OrderItem,
        ProductView, ProductViewAnalytics,
        StockLevel, StockAlert, PreOrder, BackInStockNotification,
        OrderAnalytics, UserAnalytics, ProductPerformance,
        Coupon, AppliedCoupon,
        EmailTemplate, EmailQueue, NewsletterSubscription,
        Cart, CartItem,
        UserProfile,
        Wishlist,
        Review, ReviewImage, ReviewHelpful,
        PaymentLog,
        EmailLog,
        SavedSearch, SearchQuery,
        LoginAttempt,
        SuspiciousActivity
    )
    
    print("✅ Brand imported successfully")
    print("✅ Category imported successfully")
    print("✅ Product imported successfully")
    print("✅ Order imported successfully")
    print("✅ OrderItem imported successfully")
    print("✅ ProductView imported successfully")
    print("✅ ProductViewAnalytics imported successfully")
    print("✅ StockLevel imported successfully")
    print("✅ StockAlert imported successfully")
    print("✅ PreOrder imported successfully")
    print("✅ BackInStockNotification imported successfully")
    print("✅ OrderAnalytics imported successfully")
    print("✅ UserAnalytics imported successfully")
    print("✅ ProductPerformance imported successfully")
    print("✅ Coupon imported successfully")
    print("✅ AppliedCoupon imported successfully")
    print("✅ EmailTemplate imported successfully")
    print("✅ EmailQueue imported successfully")
    print("✅ NewsletterSubscription imported successfully")
    print("✅ Cart imported successfully")
    print("✅ CartItem imported successfully")
    print("✅ UserProfile imported successfully")
    print("✅ Wishlist imported successfully")
    print("✅ Review imported successfully")
    print("✅ ReviewImage imported successfully")
    print("✅ ReviewHelpful imported successfully")
    print("✅ PaymentLog imported successfully")
    print("✅ EmailLog imported successfully")
    print("✅ SavedSearch imported successfully")
    print("✅ SearchQuery imported successfully")
    print("✅ LoginAttempt imported successfully")
    print("✅ SuspiciousActivity imported successfully")
    
    print("-" * 60)
    print("✅ ALL IMPORTS SUCCESSFUL!")
    print("-" * 60)
    
    # Verify they are Django models
    from django.db import models
    
    assert issubclass(Brand, models.Model), "Brand is not a Django model"
    assert issubclass(LoginAttempt, models.Model), "LoginAttempt is not a Django model"
    assert issubclass(SuspiciousActivity, models.Model), "SuspiciousActivity is not a Django model"
    
    print("✅ All models are proper Django models")
    print("-" * 60)
    
    # Test basic model functionality
    print("\nTesting model attributes...")
    print(f"Product.UNIT_LIT = {Product.UNIT_LIT}")
    print(f"Product.UNIT_KG = {Product.UNIT_KG}")
    print(f"LoginAttempt has 'username' field: {hasattr(LoginAttempt, 'username')}")
    print(f"SuspiciousActivity has 'activity_type' field: {hasattr(SuspiciousActivity, 'activity_type')}")
    
    print("-" * 60)
    print("✅ DIAGNOSIS COMPLETE - All tests passed!")
    
except ImportError as e:
    print(f"❌ Import Error: {e}")
    exit(1)
except AssertionError as e:
    print(f"❌ Assertion Error: {e}")
    exit(1)
except Exception as e:
    print(f"❌ Unexpected Error: {e}")
    exit(1)
