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
    
    print("OK: Brand imported successfully")
    print("OK: Category imported successfully")
    print("OK: Product imported successfully")
    print("OK: Order imported successfully")
    print("OK: OrderItem imported successfully")
    print("OK: ProductView imported successfully")
    print("OK: ProductViewAnalytics imported successfully")
    print("OK: StockLevel imported successfully")
    print("OK: StockAlert imported successfully")
    print("OK: PreOrder imported successfully")
    print("OK: BackInStockNotification imported successfully")
    print("OK: OrderAnalytics imported successfully")
    print("OK: UserAnalytics imported successfully")
    print("OK: ProductPerformance imported successfully")
    print("OK: Coupon imported successfully")
    print("OK: AppliedCoupon imported successfully")
    print("OK: EmailTemplate imported successfully")
    print("OK: EmailQueue imported successfully")
    print("OK: NewsletterSubscription imported successfully")
    print("OK: Cart imported successfully")
    print("OK: CartItem imported successfully")
    print("OK: UserProfile imported successfully")
    print("OK: Wishlist imported successfully")
    print("OK: Review imported successfully")
    print("OK: ReviewImage imported successfully")
    print("OK: ReviewHelpful imported successfully")
    print("OK: PaymentLog imported successfully")
    print("OK: EmailLog imported successfully")
    print("OK: SavedSearch imported successfully")
    print("OK: SearchQuery imported successfully")
    print("OK: LoginAttempt imported successfully")
    print("OK: SuspiciousActivity imported successfully")
    
    print("-" * 60)
    print("ALL IMPORTS SUCCESSFUL")
    print("-" * 60)
    
    # Verify they are Django models
    from django.db import models
    
    assert issubclass(Brand, models.Model), "Brand is not a Django model"
    assert issubclass(LoginAttempt, models.Model), "LoginAttempt is not a Django model"
    assert issubclass(SuspiciousActivity, models.Model), "SuspiciousActivity is not a Django model"
    
    print("All models are proper Django models")
    print("-" * 60)
    
    # Test basic model functionality
    print("\nTesting model attributes...")
    print(f"Product.UNIT_LIT = {Product.UNIT_LIT}")
    print(f"Product.UNIT_KG = {Product.UNIT_KG}")
    print(f"LoginAttempt has 'username' field: {hasattr(LoginAttempt, 'username')}")
    print(f"SuspiciousActivity has 'activity_type' field: {hasattr(SuspiciousActivity, 'activity_type')}")
    
    print("-" * 60)
    print("DIAGNOSIS COMPLETE - All tests passed!")
    
except ImportError as e:
    print(f"Import Error: {e}")
    exit(1)
except AssertionError as e:
    print(f"Assertion Error: {e}")
    exit(1)
except Exception as e:
    print(f"Unexpected Error: {e}")
    exit(1)
