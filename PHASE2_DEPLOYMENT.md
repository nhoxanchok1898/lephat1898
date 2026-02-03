# Phase 2 - Production Deployment Checklist

## Pre-Deployment

### 1. Code & Dependencies
- [ ] All code committed to repository
- [ ] All tests passing (`python manage.py test`)
- [ ] Code review completed
- [ ] Security scan completed (CodeQL)
- [ ] Dependencies installed (`pip install -r requirements.txt`)

### 2. Database
- [ ] All migrations created (`python manage.py makemigrations`)
- [ ] All migrations applied (`python manage.py migrate`)
- [ ] Database backup created
- [ ] Database indexes created for performance

### 3. Configuration
- [ ] Copy `settings_production_template.py` to `settings_production.py`
- [ ] Set `SECRET_KEY` (50+ random characters)
- [ ] Set `DEBUG = False`
- [ ] Configure `ALLOWED_HOSTS`
- [ ] Configure database credentials
- [ ] Configure email settings (SMTP)
- [ ] Configure Stripe keys (if using)

### 4. Security Settings
- [ ] `SECURE_SSL_REDIRECT = True`
- [ ] `SESSION_COOKIE_SECURE = True`
- [ ] `CSRF_COOKIE_SECURE = True`
- [ ] `SECURE_HSTS_SECONDS = 31536000`
- [ ] `X_FRAME_OPTIONS = 'DENY'`
- [ ] `SECURE_BROWSER_XSS_FILTER = True`
- [ ] `SECURE_CONTENT_TYPE_NOSNIFF = True`

### 5. Static & Media Files
- [ ] Run `python manage.py collectstatic`
- [ ] Configure static files serving (nginx/Apache)
- [ ] Configure media files serving
- [ ] Set up CDN (optional)

## Deployment

### 6. Server Setup
- [ ] Python 3.8+ installed
- [ ] PostgreSQL installed and configured
- [ ] Redis installed (for cache/Celery)
- [ ] Web server configured (nginx/Apache)
- [ ] WSGI server configured (gunicorn/uWSGI)
- [ ] SSL certificate installed
- [ ] Firewall configured

### 7. Application Deployment
- [ ] Clone repository to server
- [ ] Create virtual environment
- [ ] Install dependencies
- [ ] Copy production settings
- [ ] Run migrations
- [ ] Collect static files
- [ ] Create superuser (`python manage.py createsuperuser`)
- [ ] Test application starts

### 8. Background Tasks
Set up cron jobs for management commands:
```bash
# /etc/cron.d/django-tasks
0 * * * * /path/to/venv/bin/python /path/to/manage.py send_emails
0 * * * * /path/to/venv/bin/python /path/to/manage.py check_stock
0 0 * * * /path/to/venv/bin/python /path/to/manage.py update_analytics
0 2 * * * /path/to/venv/bin/python /path/to/manage.py send_cart_abandonment
```

### 9. Post-Deployment Testing
- [ ] Test product search
- [ ] Test API endpoints
- [ ] Test email sending
- [ ] Test admin dashboard
- [ ] Run `python manage.py check --deploy`

---

For complete deployment guide, see `PHASE2_FEATURES.md`
