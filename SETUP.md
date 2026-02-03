# Local Development Setup Guide

## Prerequisites

- Python 3.12+
- PostgreSQL 15+
- Redis 7+
- Git

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/nhoxanchok1898/lephat1898.git
cd lephat1898
```

### 2. Create Virtual Environment

```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

### 3. Install Dependencies

```bash
pip install --upgrade pip
pip install -r requirements.txt
```

### 4. Set Up Environment Variables

Create a `.env` file in the project root:

```bash
cp .env.example .env
```

Edit `.env` with your configuration:

```
# Django settings
SECRET_KEY=your-secret-key-here
DEBUG=True
ALLOWED_HOSTS=localhost,127.0.0.1

# Database
DATABASE_URL=postgresql://user:password@localhost:5432/lephat_db

# Redis
REDIS_URL=redis://localhost:6379/0

# Email (optional for development)
EMAIL_BACKEND=django.core.mail.backends.console.EmailBackend

# Stripe (optional)
STRIPE_PUBLIC_KEY=your-stripe-public-key
STRIPE_SECRET_KEY=your-stripe-secret-key
STRIPE_WEBHOOK_SECRET=your-stripe-webhook-secret

# PayPal (optional)
PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-client-secret

# Sentry (optional)
SENTRY_DSN=your-sentry-dsn
```

### 5. Set Up Database

```bash
# Create PostgreSQL database
createdb lephat_db

# Run migrations
python manage.py migrate

# Create superuser
python manage.py createsuperuser

# Load sample data (optional)
python manage.py seed_store
```

### 6. Collect Static Files

```bash
python manage.py collectstatic
```

### 7. Run Development Server

```bash
python manage.py runserver
```

The application will be available at: http://localhost:8000

- Admin panel: http://localhost:8000/admin/
- API documentation: http://localhost:8000/api/v1/docs/

## Testing

### Run All Tests

```bash
python manage.py test
```

### Run Specific Test Module

```bash
python manage.py test store.test_payment_webhooks
python manage.py test store.test_security
python manage.py test store.test_cache
```

### Run with Coverage

```bash
pip install coverage
coverage run --source='.' manage.py test store
coverage report
coverage html
```

## Development Tools

### Code Quality

```bash
# Install development tools
pip install flake8 black isort

# Check code style
flake8 store

# Format code
black store
isort store
```

### Database Management

```bash
# Create migrations
python manage.py makemigrations

# Apply migrations
python manage.py migrate

# Reset database
python manage.py flush

# Create database backup
pg_dump lephat_db > backup.sql

# Restore database
psql lephat_db < backup.sql
```

### Management Commands

```bash
# Seed database with test data
python manage.py seed_store

# Update analytics
python manage.py update_analytics

# Send queued emails
python manage.py send_emails

# Check low stock
python manage.py check_stock

# Send cart abandonment emails
python manage.py send_cart_abandonment
```

## Docker Development

### Using Docker Compose

```bash
# Build and start services
docker-compose up --build

# Run in background
docker-compose up -d

# View logs
docker-compose logs -f web

# Stop services
docker-compose down

# Rebuild specific service
docker-compose build web
docker-compose up -d web
```

### Run Django Commands in Docker

```bash
# Run migrations
docker-compose exec web python manage.py migrate

# Create superuser
docker-compose exec web python manage.py createsuperuser

# Collect static files
docker-compose exec web python manage.py collectstatic

# Access Django shell
docker-compose exec web python manage.py shell
```

## Troubleshooting

### Common Issues

**Issue: Database connection error**
```bash
# Check PostgreSQL is running
pg_isready

# Check connection settings in .env
# Verify DATABASE_URL is correct
```

**Issue: Redis connection error**
```bash
# Check Redis is running
redis-cli ping

# Should return PONG
```

**Issue: Module not found**
```bash
# Ensure virtual environment is activated
source venv/bin/activate

# Reinstall dependencies
pip install -r requirements.txt
```

**Issue: Migration conflicts**
```bash
# Reset migrations
python manage.py migrate --fake store zero
python manage.py migrate store
```

## Development Workflow

1. Create feature branch
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make changes and test
   ```bash
   python manage.py test
   ```

3. Commit changes
   ```bash
   git add .
   git commit -m "feat: your feature description"
   ```

4. Push and create pull request
   ```bash
   git push origin feature/your-feature-name
   ```

## Next Steps

- Read [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment
- Read [API.md](API_DOCUMENTATION.md) for API documentation
- Read [SECURITY.md](SECURITY.md) for security guidelines
- Read [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines
