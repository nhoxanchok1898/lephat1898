# Le Phat E-Commerce Platform 🎨

[![CI/CD](https://github.com/nhoxanchok1898/lephat1898/workflows/CI/CD%20Pipeline/badge.svg)](https://github.com/nhoxanchok1898/lephat1898/actions)
[![Python](https://img.shields.io/badge/python-3.12-blue.svg)](https://www.python.org/)
[![Django](https://img.shields.io/badge/django-4.2-green.svg)](https://www.djangoproject.com/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Enterprise-grade e-commerce platform** for paint products with advanced features including payment processing, real-time analytics, recommendation engine, and comprehensive security.

## 🌟 Features

### Core E-Commerce
- ✅ Product catalog with categories, brands, and variants
- ✅ Shopping cart with persistent storage
- ✅ Checkout with multiple payment options
- ✅ Order management and tracking
- ✅ Product reviews and ratings
- ✅ Wishlist functionality
- ✅ Coupon and discount system

### Advanced Features
- ✅ **Payment Integration**: Stripe and PayPal with webhook support
- ✅ **Admin Dashboard**: Real-time KPIs, charts, and analytics
- ✅ **Recommendation Engine**: AI-powered product suggestions
- ✅ **Search**: Full-text search with autocomplete
- ✅ **Caching**: Redis-based caching layer for performance
- ✅ **Security**: 2FA, rate limiting, input validation, HTTPS enforcement
- ✅ **Monitoring**: Sentry integration, health checks, structured logging
- ✅ **Email System**: Automated notifications for orders, cart abandonment, etc.

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/nhoxanchok1898/lephat1898.git
cd lephat1898

# Install dependencies
pip install -r requirements.txt

# Run migrations
python manage.py migrate

# Run development server
python manage.py runserver
```

## 📚 Documentation

- **[Setup Guide](SETUP.md)** - Local development setup
- **[Deployment Guide](DEPLOYMENT.md)** - Production deployment
- **[API Documentation](API.md)** - Complete API reference
- **[Security Guide](SECURITY.md)** - Security best practices
- **[Website Content Playbook](WEBSITE_CONTENT_PLAYBOOK.md)** - Cấu trúc nội dung, CTA và backlog landing/blog cho site
- **[Catalog Operations SOP](CATALOG_OPERATIONS_SOP.md)** - Quy trình quản lý sản phẩm, giá và ảnh qua Catalog QA
- **[Content Execution Backlog](CONTENT_EXECUTION_BACKLOG.md)** - Brief chi tiết cho 10 bài blog và 5 landing page ưu tiên
- **[Page Copy Templates](PAGE_COPY_TEMPLATES.md)** - Mẫu copy nhanh cho sản phẩm, landing page, FAQ và CTA

## 🧪 Testing

### Quick smoke tests (chạy trước khi commit)
```bash
python manage.py test tests.smoke
# hoặc
./run_tests.sh   # Linux/macOS
run_tests.bat    # Windows
```

### WordPress storefront smoke test
```bash
python tools/wp_route_smoke_test.py
# hoặc chỉ định site khác
python tools/wp_route_smoke_test.py --base-url http://localhost:8080/
```

### Toàn bộ test
```bash
python manage.py test
```

## 🏗️ Tech Stack

- **Backend**: Django 4.2, Django REST Framework
- **Database**: PostgreSQL 15
- **Cache**: Redis 7
- **Web Server**: Gunicorn + Nginx
- **Payments**: Stripe, PayPal
- **Monitoring**: Sentry
- **Deployment**: Docker, Docker Compose
- **CI/CD**: GitHub Actions

## 🔒 Security

- ✅ HTTPS enforcement with HSTS
- ✅ Two-Factor Authentication (2FA)
- ✅ Rate limiting on all endpoints
- ✅ Input validation and sanitization
- ✅ Security headers (CSP, X-Frame-Options, etc.)

## Status Information
Current Status: Active  
Last Updated: 2026-02-01

---

**Built with ❤️ for enterprise e-commerce**
