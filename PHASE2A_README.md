# Phase 2A - Advanced E-commerce Features API

## Overview

This document describes the Phase 2A advanced e-commerce features implemented in the Le Phat paint store platform.

## Key Features Implemented

### 1. Advanced Search & Filtering System ✅
- **Full-text search** with relevance scoring across product names, brands, and descriptions
- **Faceted search** with filters:
  - Brand filter
  - Category filter
  - Price range (min/max)
  - Rating filter
  - Stock availability
  - Sale status
- **Search autocomplete** via `/api/v1/products/search_suggestions/`
- **Search analytics** - All searches are tracked for analysis
- **Advanced filters**:
  - In stock only
  - On sale products
  - Price range filtering
  - Minimum rating

### 2. Shopping Cart Enhancements ✅
- **Persistent cart** - Saved to database for both authenticated and anonymous users
- **Session-based cart** for anonymous users
- **User-based cart** for logged-in users
- **Cart API endpoints**:
  - Add items to cart
  - Update item quantities
  - Remove items
  - Clear cart
- **Coupon code support**:
  - Percentage-based discounts
  - Fixed amount discounts
  - Minimum purchase requirements
  - Expiration dates
  - Usage limits

### 3. Performance Optimization ✅
- **Database query optimization**:
  - `select_related()` for foreign keys
  - `prefetch_related()` for many-to-many relationships
  - Custom indexes on frequently queried fields
- **Caching strategy**:
  - Redis support configured
  - LocalMem cache fallback
  - Cache-ready infrastructure
- **Database indexing**:
  - Composite indexes on (name, brand)
  - Index on (is_active, created_at)
  - Index on (category, is_active)
  - Indexes on ProductView for analytics
- **Pagination** on all list endpoints (20 items per page)

### 4. Security Enhancements ✅
- **API rate limiting**:
  - Anonymous: 100 requests/hour
  - Authenticated: 1000 requests/hour
- **Token authentication** for API access
- **HTTPS enforcement** (production mode)
- **Security headers**:
  - HSTS (HTTP Strict Transport Security)
  - CSP (Content Security Policy)
  - X-Frame-Options: DENY
  - XSS Protection
  - Content Type No-Sniff
- **Built-in Django security**:
  - CSRF protection
  - SQL injection protection
  - XSS protection

### 5. Product Recommendations Engine ✅
- **View tracking** - All product views are tracked
- **Recommendations endpoint** - `/api/v1/products/{id}/recommendations/`
- **Trending products** - `/api/v1/products/trending/` (configurable time window)
- **Collaborative filtering** based on user viewing patterns

### 6. Analytics & Reporting ✅
- **Product view tracking** - Every product view is logged
- **Search analytics** - All searches tracked with result counts
- **Analytics overview** - `/api/v1/analytics/overview/`
  - Total products
  - Total orders
  - Total revenue
  - Top products by views
  - Recent searches

### 7. Additional Features ✅
- **Product Reviews** - Users can rate and review products
- **Wishlist** - Save products for later
- **Enhanced admin interface** with better filtering and search
- **Inventory management** - Stock quantity tracking

## API Documentation

### Base URL
```
http://localhost:8000/api/v1/
```

### Authentication

To use authenticated endpoints, first obtain a token:

```bash
curl -X POST http://localhost:8000/api/v1/auth/token/ \
  -H "Content-Type: application/json" \
  -d '{"username": "demo", "password": "demo123"}'
```

Response:
```json
{
  "token": "your-auth-token-here"
}
```

Use the token in subsequent requests:
```bash
curl http://localhost:8000/api/v1/cart/ \
  -H "Authorization: Token your-auth-token-here"
```

### Quick Start Examples

#### 1. List Products with Filters
```bash
# Get products on sale with minimum rating of 4
curl "http://localhost:8000/api/v1/products/?is_on_sale=true&min_rating=4"

# Search products
curl "http://localhost:8000/api/v1/products/?search=paint"

# Filter by price range
curl "http://localhost:8000/api/v1/products/?price_min=50&price_max=200"
```

#### 2. Get Search Suggestions
```bash
curl "http://localhost:8000/api/v1/products/search_suggestions/?q=son"
```

#### 3. Add Item to Cart
```bash
curl -X POST http://localhost:8000/api/v1/cart/add_item/ \
  -H "Authorization: Token your-token" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 2}'
```

#### 4. Apply Coupon Code
```bash
curl -X POST http://localhost:8000/api/v1/cart/apply_coupon/ \
  -H "Authorization: Token your-token" \
  -H "Content-Type: application/json" \
  -d '{"code": "SAVE20"}'
```

#### 5. Get Trending Products
```bash
curl "http://localhost:8000/api/v1/products/trending/?days=7"
```

#### 6. Create Product Review
```bash
curl -X POST http://localhost:8000/api/v1/reviews/ \
  -H "Authorization: Token your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "product": 1,
    "rating": 5,
    "comment": "Excellent product!"
  }'
```

#### 7. Add to Wishlist
```bash
curl -X POST http://localhost:8000/api/v1/wishlist/add_product/ \
  -H "Authorization: Token your-token" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1}'
```

## Testing

Run the test suite:
```bash
python manage.py test store
```

Current test coverage:
- 31 tests total
- 27 Phase 2A-specific tests
- 4 legacy tests
- All tests passing ✅

## Seeding Demo Data

To populate the database with demo data:
```bash
python manage.py seed_phase2a
```

This creates:
- Demo user (username: `demo`, password: `demo123`)
- Sample coupons (WELCOME10, SAVE20, BIGSALE)
- Product reviews
- Wishlist items

## Performance Metrics

- **Database indexes**: 6+ custom indexes
- **Query optimization**: All list views use select_related/prefetch_related
- **API pagination**: 20 items per page (configurable)
- **Rate limiting**: Prevents API abuse
- **Caching**: Redis-ready infrastructure

## Security Features

- Token-based authentication
- Rate limiting (100/hour anonymous, 1000/hour authenticated)
- HTTPS enforcement in production
- Security headers (HSTS, CSP, X-Frame-Options)
- CSRF protection
- SQL injection protection
- XSS protection

## Next Steps (Phase 2B/C)

- [ ] Email notifications (cart abandonment, order updates)
- [ ] SMS notifications
- [ ] Customer loyalty program
- [ ] Advanced admin dashboard with charts
- [ ] Google Analytics integration
- [ ] SEO enhancements (JSON-LD, Open Graph)
- [ ] Internationalization
- [ ] Two-factor authentication

## API Reference

Full API documentation available at:
```
http://localhost:8000/api/v1/
```

The endpoint returns comprehensive JSON documentation of all available endpoints, parameters, and response formats.
