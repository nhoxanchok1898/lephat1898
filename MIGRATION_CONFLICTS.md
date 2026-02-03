# Migration Conflicts - Critical Issue

## Problem

The repository has **4 conflicting migration files** all numbered `0006`:

1. `0006_cart_cartitem_coupon_productview_review_savedsearch_and_more.py` (from PR #22)
2. `0006_coupon_emailqueue_emailtemplate_orderanalytics_and_more.py` (from PR #24)
3. `0006_emaillog_review_userprofile_reviewimage_paymentlog_and_more.py` (from PR #21)
4. `0006_emailtemplate_orderanalytics_useranalytics_and_more.py` (from PR #23)

Additionally, there is:
5. `0007_loginattempt_suspiciousactivity.py` (from PR #23)

## Impact

- **Tests cannot run:** `python manage.py test` fails with migration conflict error
- **Database migrations cannot be applied** in production
- **Development environment setup is broken**

## Root Cause

Each PR (#21-#24) was developed in parallel from the same base branch (main at different points in time). When merged sequentially, the migration files from each PR were all added to the repository, creating multiple "leaf nodes" in the migration graph.

## Resolution Options

### Option 1: Merge Migrations (Recommended)

Run Django's built-in migration merge command:

```bash
python manage.py makemigrations --merge
```

This will create a merge migration (likely `0008_merge...py`) that resolves the conflict.

**Pros:**
- Preserves migration history
- Django-recommended approach
- Can be applied to existing databases

**Cons:**
- Still references models that don't exist (UserProfile, Wishlist, etc.)
- May require manual editing

### Option 2: Delete Conflicting Migrations and Recreate

1. Delete all 0006 and 0007 migrations
2. Create a fresh migration with current models:
   ```bash
   python manage.py makemigrations
   ```

**Pros:**
- Clean migration that matches current models
- Simpler to understand

**Cons:**
- Loses migration history
- Cannot be applied to databases that ran old migrations
- **DESTRUCTIVE** - Do not use in production

### Option 3: Manual Merge of Migrations

Manually create a single `0006_comprehensive_models.py` that includes all models from all PRs.

**Pros:**
- Complete control over migration content
- Can include all models if desired

**Cons:**
- Time-consuming
- Error-prone
- Requires deep Django migrations knowledge

## Recommended Action for This Repository

Since this appears to be a development repository without production data, **Option 2** is recommended:

```bash
cd /home/runner/work/lephat1898/lephat1898
rm store/migrations/0006_*.py
rm store/migrations/0007_*.py
python manage.py makemigrations
python manage.py migrate
python manage.py test
```

However, this should only be done if:
1. There's no production database
2. All developers can recreate their databases
3. The current models in `models.py` are the desired final state

## Current Migration State

```
0001_initial.py                                  ✅ Applied
0002_order_updated_at_product_updated_at.py      ✅ Applied
0003_alter_brand_logo_alter_product_image.py     ✅ Applied
0004_alter_brand_logo_alter_product_image.py     ✅ Applied
0005_order_payment_method_order_payment_reference.py ✅ Applied
0006_cart_cartitem_coupon_productview...py       ❌ CONFLICT (PR #22)
0006_coupon_emailqueue_emailtemplate...py        ❌ CONFLICT (PR #24)
0006_emaillog_review_userprofile...py            ❌ CONFLICT (PR #21)
0006_emailtemplate_orderanalytics...py           ❌ CONFLICT (PR #23)
0007_loginattempt_suspiciousactivity.py          ❌ DEPENDS ON CONFLICT
```

## Migration Content Analysis

### 0006 from PR #21 (User-facing features)
- UserProfile
- Wishlist
- Review
- ReviewImage
- ReviewHelpful (changed to PaymentLog)
- PaymentLog
- EmailLog

### 0006 from PR #22 (Phase 2A)
- Cart
- CartItem
- Coupon
- ProductView
- Review
- SavedSearch

### 0006 from PR #23 (Phase 2 Search)
- EmailTemplate
- OrderAnalytics
- UserAnalytics
- ProductViewAnalytics
- SearchQuery
- SearchFilter
- CartSession
- CartItem
- Coupon
- ProductRating
- StockLevel
- StockAlert
- PreOrder
- BackInStockNotification
- CartAbandonment

### 0006 from PR #24 (Current State)
- Coupon
- EmailQueue
- EmailTemplate
- OrderAnalytics
- ProductPerformance
- ProductView
- ProductViewAnalytics
- UserAnalytics
- PreOrder
- StockLevel
- StockAlert
- BackInStockNotification
- AppliedCoupon
- NewsletterSubscription

## Immediate Action Required

**Before any production deployment or testing:**

1. Choose a resolution strategy
2. Implement the resolution
3. Test migrations on a clean database
4. Update this document with chosen solution
5. Run full test suite to verify

## Notes

- The migration conflict is a **blocking issue** for:
  - Running tests
  - Setting up new development environments
  - Production deployment
  - Database migrations

- This must be resolved before the PR can be merged

## Updated Resolution

Based on the current state where only 19 models exist (PR #24's models), the cleanest approach is:

1. Keep only PR #24's migration (0006_coupon_emailqueue...)
2. Delete the other three 0006 migrations
3. Delete 0007 migration
4. Run tests to verify

This aligns with accepting PR #24 as the final state and acknowledging that PR #21-#23 features need to be re-implemented if needed.
