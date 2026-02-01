# Phase 2: Advanced E-commerce Features - Quick Start Guide

## Overview

This release includes 20+ new models and features to transform the basic e-commerce platform into a production-ready advanced system.

## What's New

✅ **Advanced Search System** - Full-text search with filters, sorting, and analytics  
✅ **Product Recommendations** - Collaborative filtering and trending products  
✅ **Inventory Management** - Stock tracking, alerts, and pre-orders  
✅ **Analytics Dashboard** - KPIs, sales tracking, and conversion metrics  
✅ **Security Enhancements** - Rate limiting, login tracking, and suspicious activity alerts  
✅ **Email System** - Queue-based email with templates and automation  
✅ **REST API** - Complete API with DRF for mobile/external integrations  

## Quick Setup

### 1. Install Dependencies

```bash
pip install -r requirements.txt
```

### 2. Run Migrations

```bash
python manage.py migrate
```

### 3. Update Analytics (Optional)

```bash
python manage.py update_analytics
```

### 4. Check System

```bash
python manage.py check
```

### 5. Run Tests

```bash
python manage.py test store.tests_cart
```

### 6. Start Server

```bash
python manage.py runserver
```

## Key Features

### Advanced Search
- URL: `/products/?q=paint&min_price=10&max_price=50&sort=price_low`
- Filters: category, brand, price range, on_sale, new_arrivals, in_stock
- Sorting: price, rating, newest, popular

### Analytics Dashboard
- URL: `/dashboard/` (requires staff permission)
- Shows: Today/week/month revenue, orders, top products, conversion rate

### REST API
- Base URL: `/api/`
- Endpoints:
  - `/api/products/` - All products with filtering
  - `/api/products/trending/` - Trending products
  - `/api/products/on_sale/` - Sale items
  - `/api/categories/` - All categories
  - `/api/brands/` - All brands

### Management Commands

```bash
# Update daily analytics
python manage.py update_analytics

# Send queued emails
python manage.py send_emails --max 50

# Check stock levels
python manage.py check_stock

# Send cart abandonment emails
python manage.py send_cart_abandonment
```

## Admin Interface

All new models are registered in Django admin:

1. **Search & Analytics** - SearchQuery, ProductView, Analytics
2. **Cart & Coupons** - CartSession, CartItem, Coupon
3. **Inventory** - StockLevel, StockAlert, PreOrder
4. **Email** - EmailTemplate, EmailQueue
5. **Security** - LoginAttempt, SuspiciousActivity

Access at: `http://localhost:8000/admin/`

## API Usage Examples

### Get All Products
```bash
curl http://localhost:8000/api/products/
```

### Search Products
```bash
curl http://localhost:8000/api/products/?search=paint
```

### Get Trending Products
```bash
curl http://localhost:8000/api/products/trending/
```

### Filter by Category
```bash
curl http://localhost:8000/api/products/?category=1
```

## Database Models

### New Models (20+)

**Search:**
- SearchQuery, SearchFilter, ProductView

**Analytics:**
- ProductViewAnalytics, OrderAnalytics, UserAnalytics

**Cart:**
- CartSession, CartItem, Coupon, CartAbandonment

**Recommendations:**
- ProductRating

**Inventory:**
- StockLevel, StockAlert, PreOrder, BackInStockNotification

**Email:**
- EmailTemplate, EmailQueue

**Security:**
- LoginAttempt, SuspiciousActivity

**Enhanced Product Model:**
- Added: description, sale_price, is_new fields

## Production Deployment

### Environment Variables

```bash
# Django
SECRET_KEY=your-secret-key
DEBUG=False
ALLOWED_HOSTS=yourdomain.com

# Database
DATABASE_URL=postgresql://user:pass@host:port/dbname

# Email (for production)
EMAIL_BACKEND=django.core.mail.backends.smtp.EmailBackend
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USE_TLS=True
EMAIL_HOST_USER=your-email@gmail.com
EMAIL_HOST_PASSWORD=your-password

# Stripe (optional)
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Scheduled Tasks

Set up cron jobs or use Celery for:

```bash
# Every hour
0 * * * * cd /path/to/project && python manage.py send_emails
0 * * * * cd /path/to/project && python manage.py check_stock

# Daily
0 0 * * * cd /path/to/project && python manage.py update_analytics
0 2 * * * cd /path/to/project && python manage.py send_cart_abandonment
```

### Celery Setup (Recommended)

```python
# celery.py
from celery import Celery
from celery.schedules import crontab

app = Celery('paint_store')
app.config_from_object('django.conf:settings', namespace='CELERY')

app.conf.beat_schedule = {
    'send-emails-hourly': {
        'task': 'store.tasks.send_queued_emails',
        'schedule': crontab(minute=0),
    },
    'update-analytics-daily': {
        'task': 'store.tasks.update_analytics',
        'schedule': crontab(hour=0, minute=0),
    },
    'check-stock-hourly': {
        'task': 'store.tasks.check_stock',
        'schedule': crontab(minute=0),
    },
    'send-cart-abandonment-daily': {
        'task': 'store.tasks.send_cart_abandonment',
        'schedule': crontab(hour=2, minute=0),
    },
}
```

## Security Checklist

✅ Rate limiting on login (5 attempts/15min)  
✅ CSRF protection enabled  
✅ Secure password hashing  
✅ Login attempt logging  
✅ Suspicious activity alerts  
⚠️ HTTPS enforcement (configure in production)  
⚠️ SECRET_KEY (change from default)  
⚠️ DEBUG=False in production  

## Testing

```bash
# Run all tests
python manage.py test

# Run specific test
python manage.py test store.tests_cart

# Check for issues
python manage.py check --deploy
```

## Troubleshooting

### Issue: Migrations not applying
```bash
python manage.py migrate --run-syncdb
```

### Issue: Static files not loading
```bash
python manage.py collectstatic
```

### Issue: Email not sending
- Check EMAIL_BACKEND in settings
- For development, emails print to console
- For production, configure SMTP settings

### Issue: API returning 404
- Ensure REST_FRAMEWORK in INSTALLED_APPS
- Check URL configuration: `/api/` prefix

## Documentation

- **Full Documentation**: See `PHASE2_FEATURES.md`
- **API Documentation**: Available at `/api/` when browsing
- **Admin Help**: Django admin has built-in documentation

## Support

For issues or questions:
1. Check `PHASE2_FEATURES.md` for detailed documentation
2. Review Django logs for errors
3. Check model admin interfaces for data

## Performance Tips

1. **Database Indexing**: Add indexes to frequently queried fields
2. **Caching**: Use Redis/Memcached for session and view caching
3. **CDN**: Serve static files via CDN
4. **Query Optimization**: Use select_related() and prefetch_related()
5. **API Pagination**: Already implemented, ensure clients use it

## Upgrade Notes

### From Phase 1 to Phase 2:

1. **Database**: Run all migrations (0006, 0007)
2. **Dependencies**: Install new packages (DRF, django-filter, django-ratelimit)
3. **Settings**: Add REST_FRAMEWORK configuration
4. **URLs**: API endpoints added at `/api/`
5. **Product Model**: New fields added (description, sale_price, is_new)

**No breaking changes** - all existing functionality preserved.

## Next Steps

1. **Create Email Templates**: Design HTML templates in admin
2. **Add Sample Data**: Create products, categories, brands
3. **Configure Cron Jobs**: Set up automated tasks
4. **Test API**: Use Postman or curl to test endpoints
5. **Monitor Analytics**: Check dashboard daily
6. **Review Security Alerts**: Monitor LoginAttempt and SuspiciousActivity

---

**Version**: 2.0  
**Release Date**: February 2026  
**Status**: Production Ready ✅  
**Compatibility**: Django 4.2+, Python 3.8+
