# Phase 2 Implementation - Advanced E-commerce Features

## Overview
This document describes the Phase 2 implementation of advanced e-commerce features for the paint store application.

## New Features

### 1. Recommendation Engine
**Files:** `store/recommendation_views.py`, models in `store/models.py`

**Features:**
- Track product views with ProductView model
- Aggregate analytics with ProductViewAnalytics
- "People who viewed this also viewed" recommendations
- "People who bought this also bought" recommendations
- Trending products (most viewed, most sold)
- Personalized recommendations based on browsing history
- Similar products by category/brand

**Endpoints:**
- `GET /recommendations/<product_id>/` - Get recommendations for a product
- `GET /recommendations/trending/` - Get trending products
- `GET /recommendations/personalized/` - Get personalized recommendations (requires auth)
- `POST /products/<product_id>/track-view/` - Track product view

### 2. Inventory Management
**Files:** `store/inventory_views.py`, models in `store/models.py`

**Features:**
- Real-time stock tracking with StockLevel model
- Automatic low stock alerts (< 10 items)
- Out of stock alerts
- Pre-order functionality for out-of-stock items
- Back in stock notification subscriptions
- Stock level API for frontend integration

**Endpoints:**
- `POST /inventory/stock/update/<product_id>/` - Update stock (staff only)
- `GET /inventory/stock/check/<product_id>/` - Check stock availability
- `GET /inventory/alerts/` - View low stock alerts (staff only)
- `POST /inventory/pre-order/<product_id>/` - Create pre-order
- `POST /inventory/notify/<product_id>/` - Subscribe to back-in-stock notifications

### 3. Analytics Dashboard
**Files:** `store/analytics_views.py`, models in `store/models.py`

**Features:**
- Daily aggregated order analytics
- User analytics (total, new, active users)
- Product performance metrics
- Sales trends (today, week, month)
- Top selling products
- Conversion rate tracking
- Revenue reporting

**Endpoints:**
- `GET /dashboard/` - Admin dashboard (staff only)
- `GET /analytics/data/` - Analytics data API (staff only)
- `GET /analytics/sales/` - Sales chart data (staff only)
- `GET /analytics/performance/` - Product performance data (staff only)

### 4. REST API
**Files:** `store/api_views.py`, `store/serializers.py`

**Features:**
- RESTful API with Django REST Framework
- Token and session authentication
- Product CRUD operations (read-only for non-staff)
- Order management (staff only)
- Cart operations (session-based)
- Recommendation endpoints
- Pagination (20 items per page)
- Search and filtering
- Rate limiting (100/hour anonymous, 1000/hour authenticated)
- CORS support for mobile apps

**Endpoints:**
- `GET /api/products/` - List products
- `GET /api/products/<id>/` - Product details
- `GET /api/products/<id>/recommendations/` - Product recommendations
- `POST /api/products/<id>/track_view/` - Track view
- `GET /api/orders/` - List orders (authenticated)
- `GET /api/orders/<id>/` - Order details (authenticated)
- `GET /api/cart/` - View cart
- `POST /api/cart/add/` - Add to cart
- `DELETE /api/cart/remove/<id>/` - Remove from cart
- `GET /api/recommendations/` - General recommendations

### 5. Coupon System
**Files:** `store/coupon_views.py`, models in `store/models.py`

**Features:**
- Percentage and fixed amount discounts
- Usage limits (total and per user)
- Date range restrictions
- User-specific coupons
- Product-specific coupons
- Minimum purchase requirements
- Admin management interface

**Endpoints:**
- `POST /coupons/apply/` - Apply coupon to cart
- `POST /coupons/remove/` - Remove coupon from cart
- `GET /coupons/admin/` - List coupons (staff only)
- `GET/POST /coupons/admin/create/` - Create coupon (staff only)
- `GET/POST /coupons/admin/<id>/edit/` - Edit coupon (staff only)
- `POST /coupons/admin/<id>/delete/` - Delete coupon (staff only)

### 6. Email System
**Files:** `store/email_views.py`, models in `store/models.py`

**Features:**
- Email templates for various notifications
- Email queue with retry logic
- Welcome emails for new users
- Order confirmation emails
- Cart abandonment reminders (24h delay)
- Back in stock notifications
- Newsletter subscription management

**Email Types:**
- Welcome email
- Order confirmation
- Shipping notification
- Cart abandonment
- Back in stock
- Review approved
- Password reset
- Newsletter

## Database Models

### New Models (16 total)

**Recommendation:**
- ProductView - Individual product view tracking
- ProductViewAnalytics - Aggregated view analytics

**Inventory:**
- StockLevel - Current stock levels
- StockAlert - Low/out of stock alerts
- PreOrder - Pre-order requests
- BackInStockNotification - Notification subscriptions

**Analytics:**
- OrderAnalytics - Daily order statistics
- UserAnalytics - Daily user statistics
- ProductPerformance - Product performance metrics

**Coupon:**
- Coupon - Discount codes
- AppliedCoupon - Coupon usage tracking

**Email:**
- EmailTemplate - Email templates
- EmailQueue - Email send queue
- NewsletterSubscription - Newsletter subscribers

## Testing

### Test Files
1. `test_recommendations.py` - 7 tests
2. `test_inventory.py` - 8 tests
3. `test_analytics.py` - 8 tests
4. `test_api.py` - 18 tests
5. `test_coupon.py` - 13 tests
6. `test_email.py` - 9 tests

**Total:** 57 tests, all passing ✅

### Running Tests
```bash
# Run all tests
python manage.py test store

# Run specific test file
python manage.py test store.test_recommendations

# Run with verbose output
python manage.py test store -v 2
```

## API Authentication

### Token Authentication
```python
from rest_framework.authtoken.models import Token

# Create token for user
token = Token.objects.create(user=user)

# Use in requests
headers = {'Authorization': f'Token {token.key}'}
```

### Session Authentication
Session authentication is automatically available for logged-in users.

## Configuration

### Settings
Key settings in `paint_store/settings.py`:

```python
# REST Framework
REST_FRAMEWORK = {
    'DEFAULT_AUTHENTICATION_CLASSES': [
        'rest_framework.authentication.TokenAuthentication',
        'rest_framework.authentication.SessionAuthentication',
    ],
    'DEFAULT_PAGINATION_CLASS': 'rest_framework.pagination.PageNumberPagination',
    'PAGE_SIZE': 20,
    'DEFAULT_THROTTLE_RATES': {
        'anon': '100/hour',
        'user': '1000/hour'
    },
}

# CORS
CORS_ALLOWED_ORIGINS = [
    "http://localhost:3000",
    "http://127.0.0.1:3000",
]

# Celery (optional)
CELERY_BROKER_URL = 'redis://localhost:6379/0'
```

### Dependencies
```
djangorestframework==3.14.0
django-cors-headers==4.3.1
celery==5.3.6 (optional)
redis==5.0.1 (optional)
```

## Admin Interface

### Registered Models
All new models are registered in Django admin:
- ProductView, ProductViewAnalytics
- StockLevel, StockAlert, PreOrder, BackInStockNotification
- OrderAnalytics, UserAnalytics, ProductPerformance
- Coupon, AppliedCoupon
- EmailTemplate, EmailQueue, NewsletterSubscription

### Custom Admin Views
- `/dashboard/` - Analytics dashboard
- `/coupons/admin/` - Coupon management

## Usage Examples

### Track Product View
```python
from store.recommendation_views import product_view_tracker

# Track view (called automatically on product page)
product_view_tracker(request, product_id)
```

### Get Recommendations
```python
from store.recommendation_views import get_also_viewed, get_similar_products

# Get related products
also_viewed = get_also_viewed(product)
similar = get_similar_products(product)
```

### Update Stock
```python
from store.models import StockLevel

# Update stock level
stock, created = StockLevel.objects.get_or_create(product=product)
stock.quantity = 100
stock.save()
```

### Apply Coupon
```python
from store.models import Coupon

# Validate and apply coupon
coupon = Coupon.objects.get(code='SAVE10')
if coupon.is_valid():
    discount = coupon.calculate_discount(cart_total)
```

### Send Email
```python
from store.email_views import send_welcome_email, send_order_confirmation

# Send welcome email
send_welcome_email(user)

# Send order confirmation
send_order_confirmation(order)
```

## Security Considerations

1. **Authentication:** Staff-only views require `@staff_member_required` decorator
2. **CSRF Protection:** All POST endpoints require CSRF token
3. **Rate Limiting:** API endpoints are rate-limited
4. **Input Validation:** All user inputs are validated
5. **SQL Injection:** Using Django ORM prevents SQL injection
6. **XSS Protection:** All templates use Django's auto-escaping

## Performance Optimization

1. **Database Indexes:** Added on frequently queried fields (product, viewed_at, user)
2. **Query Optimization:** Using select_related() and prefetch_related() where appropriate
3. **Caching:** Consider adding Redis for session storage and caching
4. **Pagination:** All list endpoints use pagination
5. **Background Tasks:** Email queue can be processed with Celery

## Future Enhancements

1. Add Chart.js for visual analytics
2. Implement Celery for async email processing
3. Add WebSocket support for real-time notifications
4. Implement search with Elasticsearch
5. Add product recommendations using ML
6. Create mobile app using REST API
7. Add internationalization (i18n)
8. Implement advanced reporting features

## Troubleshooting

### Common Issues

**Issue:** API returns 401 Unauthorized
**Solution:** Ensure you're sending authentication token or are logged in

**Issue:** Emails not sending
**Solution:** Check `EMAIL_BACKEND` setting and email configuration

**Issue:** Stock alerts not triggering
**Solution:** Ensure StockLevel objects are created for products

**Issue:** Coupons not applying
**Solution:** Check coupon validity (dates, usage limits, minimum purchase)

## Support

For issues or questions:
1. Check the test files for usage examples
2. Review the admin interface for data management
3. Check Django debug toolbar for query optimization
4. Review application logs for errors

## License

This implementation is part of the paint store e-commerce application.
