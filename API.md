# API Reference - Complete Documentation

## Base URL
```
Development: http://localhost:8000
Production: https://your-domain.com
```

## Authentication

### Token Authentication
```http
Authorization: Token <your-token>
```

### Session Authentication
Used for web browsers with CSRF protection

## Endpoints

### Products API

#### List Products
```http
GET /api/v1/products/
```

**Query Parameters:**
- `search` - Search products by name, description, brand
- `category` - Filter by category ID
- `brand` - Filter by brand ID
- `min_price` - Minimum price filter
- `max_price` - Maximum price filter
- `is_on_sale` - Filter sale products (true/false)
- `in_stock` - Filter in-stock products (true/false)
- `ordering` - Sort by field (price, -price, rating, -rating, created_at)
- `page` - Page number for pagination
- `page_size` - Results per page (default: 20, max: 100)

**Response:**
```json
{
  "count": 100,
  "next": "http://localhost:8000/api/v1/products/?page=2",
  "previous": null,
  "results": [
    {
      "id": 1,
      "name": "Product Name",
      "description": "Product description",
      "brand": {
        "id": 1,
        "name": "Brand Name"
      },
      "category": {
        "id": 1,
        "name": "Category Name"
      },
      "price": "99.99",
      "sale_price": "79.99",
      "stock_quantity": 10,
      "rating": "4.5",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Get Product Details
```http
GET /api/v1/products/{id}/
```

**Response:** Single product object with full details

#### Search Suggestions (Autocomplete)
```http
GET /api/v1/products/search_suggestions/?q={query}
```

**Response:**
```json
{
  "suggestions": [
    {
      "id": 1,
      "name": "Product Name",
      "price": "99.99",
      "image": "/media/products/image.jpg"
    }
  ]
}
```

### Cart API

#### Get Cart
```http
GET /api/v1/cart/
```

#### Add to Cart
```http
POST /api/v1/cart/add/
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 2
}
```

#### Update Cart Item
```http
PUT /api/v1/cart/update/
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 3
}
```

#### Remove from Cart
```http
DELETE /api/v1/cart/remove/{product_id}/
```

#### Apply Coupon
```http
POST /api/v1/cart/apply_coupon/
Content-Type: application/json

{
  "code": "DISCOUNT20"
}
```

### Orders API

#### Create Order
```http
POST /api/v1/orders/
Content-Type: application/json

{
  "full_name": "John Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "address": "123 Main St, City, State 12345",
  "payment_method": "stripe"
}
```

#### List Orders
```http
GET /api/v1/orders/
```

#### Get Order Details
```http
GET /api/v1/orders/{id}/
```

### Recommendations API

#### Get Product Recommendations
```http
GET /api/v1/products/{id}/recommendations/
```

**Query Parameters:**
- `limit` - Number of recommendations (default: 5, max: 20)

#### Get Trending Products
```http
GET /api/v1/products/trending/?days={days}
```

**Query Parameters:**
- `days` - Time window in days (default: 7)

### Admin Dashboard API

#### Get Dashboard Metrics
```http
GET /admin-dashboard/api/metrics/?days={days}
```

**Response:**
```json
{
  "total_sales": 15000.00,
  "period_sales": 3000.00,
  "total_orders": 150,
  "period_orders": 30,
  "total_users": 500,
  "new_users": 50,
  "conversion_rate": 3.5,
  "avg_order_value": 100.00,
  "top_product": "Product Name"
}
```

#### Get Sales Chart Data
```http
GET /admin-dashboard/api/sales/?days={days}
```

**Response:**
```json
{
  "labels": ["2024-01-01", "2024-01-02"],
  "revenue": [1000.00, 1500.00],
  "orders": [10, 15]
}
```

#### Export Sales Report
```http
GET /admin-dashboard/export/sales/?start_date=2024-01-01&end_date=2024-01-31
```

Returns CSV file

#### Export Products Report
```http
GET /admin-dashboard/export/products/
```

Returns CSV file

### Payment Webhooks

#### Stripe Webhook
```http
POST /webhooks/stripe/
X-Stripe-Signature: {signature}
```

Handles:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `charge.refunded`
- `customer.subscription.updated`

#### PayPal Webhook
```http
POST /webhooks/paypal/
```

Handles:
- `PAYMENT.CAPTURE.COMPLETED`
- `PAYMENT.CAPTURE.DENIED`
- `BILLING.SUBSCRIPTION.CANCELLED`

### Health & Monitoring

#### Health Check
```http
GET /health/
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-01T00:00:00Z",
  "checks": {
    "database": {
      "status": "healthy",
      "response_time_ms": 10.5
    },
    "cache": {
      "status": "healthy",
      "response_time_ms": 2.1
    },
    "email": {
      "status": "healthy",
      "backend": "SMTPEmailBackend"
    }
  }
}
```

#### Readiness Check
```http
GET /readiness/
```

#### Liveness Check
```http
GET /liveness/
```

## Error Responses

### 400 Bad Request
```json
{
  "error": "Invalid request data",
  "details": {
    "field": ["Error message"]
  }
}
```

### 401 Unauthorized
```json
{
  "error": "Authentication required"
}
```

### 403 Forbidden
```json
{
  "error": "Permission denied"
}
```

### 404 Not Found
```json
{
  "error": "Resource not found"
}
```

### 429 Too Many Requests
```json
{
  "error": "Rate limit exceeded. Please try again later."
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error",
  "message": "An unexpected error occurred"
}
```

## Rate Limiting

### Anonymous Users
- 100 requests per hour

### Authenticated Users
- 1000 requests per hour

### Login Attempts
- 5 attempts per 15 minutes

## Pagination

All list endpoints support pagination:

```
?page=1&page_size=20
```

**Response includes:**
- `count` - Total number of results
- `next` - URL to next page (null if last page)
- `previous` - URL to previous page (null if first page)
- `results` - Array of objects

## Filtering & Sorting

### Filtering
```
?field=value&field2=value2
```

### Sorting
```
?ordering=field        # Ascending
?ordering=-field       # Descending
```

Multiple fields:
```
?ordering=field1,-field2
```

## Webhooks Security

### Stripe
Webhooks are verified using the `Stripe-Signature` header with the configured webhook secret.

### PayPal
Webhooks are verified using PayPal's signature verification mechanism.

## Testing

### cURL Examples

**Get Products:**
```bash
curl -X GET "http://localhost:8000/api/v1/products/" \
  -H "Accept: application/json"
```

**Create Order:**
```bash
curl -X POST "http://localhost:8000/api/v1/orders/" \
  -H "Content-Type: application/json" \
  -H "Authorization: Token YOUR_TOKEN" \
  -d '{
    "full_name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "address": "123 Main St"
  }'
```

**Health Check:**
```bash
curl -X GET "http://localhost:8000/health/"
```

## SDKs & Libraries

### JavaScript/TypeScript
```javascript
const API_BASE = 'http://localhost:8000';

async function getProducts() {
  const response = await fetch(`${API_BASE}/api/v1/products/`);
  return await response.json();
}
```

### Python
```python
import requests

API_BASE = 'http://localhost:8000'

def get_products():
    response = requests.get(f'{API_BASE}/api/v1/products/')
    return response.json()
```

## Additional Resources

- [Authentication Guide](AUTHENTICATION.md)
- [Webhook Integration](PAYMENT_INTEGRATION.md)
- [Security Best Practices](SECURITY.md)
- [Admin Dashboard Guide](ADMIN_DASHBOARD.md)
