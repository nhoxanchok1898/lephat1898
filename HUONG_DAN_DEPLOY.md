# Hướng dẫn Triển khai Production / Production Deployment Guide

**Ngày cập nhật / Last Updated:** 2026-02-01  
**Phiên bản / Version:** 1.0

---

## 📌 Tóm tắt / Summary

Tài liệu này hướng dẫn chi tiết cách triển khai Le Phat E-Commerce Platform lên môi trường production.

This document provides step-by-step instructions for deploying Le Phat E-Commerce Platform to production.

---

## 🎯 Các phương thức triển khai / Deployment Options

### Option 1: Render (Khuyến nghị - Easiest)
- ✅ Miễn phí cho web services nhỏ / Free tier available
- ✅ Tự động deploy từ GitHub / Auto-deploy from GitHub
- ✅ SSL miễn phí / Free SSL
- ✅ Dễ setup / Easy setup

### Option 2: Railway
- ✅ Miễn phí $5 tháng đầu / $5 free credits
- ✅ Deploy nhanh / Fast deployment
- ✅ Tích hợp GitHub / GitHub integration

### Option 3: VPS (DigitalOcean, AWS, Linode)
- ✅ Kiểm soát hoàn toàn / Full control
- ⚠️ Cần kiến thức server / Requires server knowledge
- ✅ Mở rộng tốt / Good scalability

### Option 4: Docker (Any Platform)
- ✅ Portable / Di động
- ✅ Consistent environments / Môi trường nhất quán
- ✅ Dễ scale / Easy to scale

---

## 🚀 Option 1: Deploy trên Render (Recommended)

### Bước 1: Chuẩn bị

1. **Tạo tài khoản Render:** https://render.com
2. **Push code lên GitHub** (đã có sẵn)
3. **Chuẩn bị thông tin:**
   - Database name, user, password
   - Email SMTP credentials
   - Payment gateway keys (Stripe/PayPal)

### Bước 2: Tạo PostgreSQL Database

1. Trong Render Dashboard, chọn **New → PostgreSQL**
2. Cấu hình:
   - Name: `lephat-db`
   - Database: `lephat`
   - User: `lephat_user`
   - Region: Singapore (gần Việt Nam nhất)
   - Plan: Free
3. Click **Create Database**
4. Lưu lại **Internal Database URL** (dạng `postgres://...`)

### Bước 3: Tạo Redis Instance

1. Chọn **New → Redis**
2. Cấu hình:
   - Name: `lephat-redis`
   - Region: Singapore
   - Plan: Free
3. Click **Create Redis**
4. Lưu lại **Internal Redis URL** (dạng `redis://...`)

### Bước 4: Tạo Web Service

1. Chọn **New → Web Service**
2. Connect repository: `nhoxanchok1898/lephat1898`
3. Cấu hình:
   - Name: `lephat-ecommerce`
   - Region: Singapore
   - Branch: `main`
   - Runtime: Python 3
   - Build Command: `pip install -r requirements.txt && python manage.py collectstatic --noinput`
   - Start Command: `gunicorn paint_store.wsgi:application --bind 0.0.0.0:$PORT`
   - Plan: Free

### Bước 5: Cấu hình Environment Variables

Trong tab **Environment**, thêm các biến:

```
# Django Settings
DJANGO_SECRET_KEY=<generate-random-50-chars>
DJANGO_SETTINGS_MODULE=paint_store.settings_production
DEBUG=False
ALLOWED_HOSTS=your-app.onrender.com
SITE_URL=https://your-app.onrender.com
PORT=10000

# Database (copy từ Render PostgreSQL)
DATABASE_URL=postgres://lephat_user:password@hostname:5432/lephat

# Redis (copy từ Render Redis)
REDIS_URL=redis://hostname:6379

# Email Configuration
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USE_TLS=True
EMAIL_HOST_USER=your-email@gmail.com
EMAIL_HOST_PASSWORD=<your-app-password>
DEFAULT_FROM_EMAIL=noreply@lephat.com

# Payment Gateway (Production)
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_secret
PAYPAL_MODE=live

# Monitoring (Optional)
SENTRY_DSN=https://...@sentry.io/...
ENVIRONMENT=production

# Admin
ADMIN_EMAIL=admin@yourdomain.com
```

### Bước 6: Deploy

1. Click **Create Web Service**
2. Render sẽ tự động build và deploy
3. Đợi 5-10 phút cho lần deploy đầu tiên

### Bước 7: Chạy Migrations

1. Vào **Shell** tab trong Render dashboard
2. Chạy lệnh:
```bash
python manage.py migrate
python manage.py createsuperuser
```

### Bước 8: Test

1. Truy cập: `https://your-app.onrender.com`
2. Test health check: `https://your-app.onrender.com/health/`
3. Login admin: `https://your-app.onrender.com/admin/`

---

## 🐳 Option 2: Deploy với Docker

### Bước 1: Cập nhật docker-compose.yml

```yaml
# Chỉnh sửa file docker-compose.yml với thông tin thực tế
version: '3.9'

services:
  db:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: lephat_production
      POSTGRES_USER: lephat_user
      POSTGRES_PASSWORD: <strong-password>
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: always

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    restart: always

  web:
    build: .
    command: gunicorn paint_store.wsgi:application --bind 0.0.0.0:8000 --workers 4
    volumes:
      - static_volume:/app/staticfiles
      - media_volume:/app/media
    environment:
      - DJANGO_SETTINGS_MODULE=paint_store.settings_production
      - DATABASE_URL=postgres://lephat_user:<password>@db:5432/lephat_production
      - REDIS_URL=redis://redis:6379/0
      - DJANGO_SECRET_KEY=<your-secret-key>
      - DEBUG=False
      - ALLOWED_HOSTS=yourdomain.com,www.yourdomain.com
    depends_on:
      - db
      - redis
    restart: always

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - static_volume:/app/staticfiles:ro
      - media_volume:/app/media:ro
      - /etc/letsencrypt:/etc/letsencrypt:ro  # SSL certificates
    depends_on:
      - web
    restart: always

volumes:
  postgres_data:
  redis_data:
  static_volume:
  media_volume:
```

### Bước 2: Deploy

```bash
# Build và start containers
docker-compose up -d --build

# Chạy migrations
docker-compose exec web python manage.py migrate

# Tạo superuser
docker-compose exec web python manage.py createsuperuser

# Collect static files
docker-compose exec web python manage.py collectstatic --noinput
```

### Bước 3: Setup SSL với Let's Encrypt

```bash
# Install certbot
sudo apt-get install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 🖥️ Option 3: Deploy trên VPS (Ubuntu)

### Bước 1: Chuẩn bị Server

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install dependencies
sudo apt-get install -y python3-pip python3-venv postgresql postgresql-contrib nginx redis-server git

# Create user
sudo useradd -m -s /bin/bash lephat
sudo usermod -aG sudo lephat
```

### Bước 2: Setup Database

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE lephat_production;
CREATE USER lephat_user WITH PASSWORD 'your-strong-password';
GRANT ALL PRIVILEGES ON DATABASE lephat_production TO lephat_user;
\q
```

### Bước 3: Clone và Setup Application

```bash
# Switch to app user
sudo su - lephat

# Clone repository
git clone https://github.com/nhoxanchok1898/lephat1898.git
cd lephat1898

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Create .env file
cp .env.example .env
nano .env  # Edit with production values
```

### Bước 4: Configure .env

```env
DJANGO_SECRET_KEY=<generate-random-50-chars>
DJANGO_SETTINGS_MODULE=paint_store.settings_production
DEBUG=False
ALLOWED_HOSTS=yourdomain.com,www.yourdomain.com
SITE_URL=https://www.yourdomain.com

DB_ENGINE=django.db.backends.postgresql
DB_NAME=lephat_production
DB_USER=lephat_user
DB_PASSWORD=your-strong-password
DB_HOST=localhost
DB_PORT=5432

REDIS_URL=redis://127.0.0.1:6379/1

# Email, Payment gateway, etc...
```

### Bước 5: Run Migrations và Collect Static

```bash
python manage.py migrate
python manage.py collectstatic --noinput
python manage.py createsuperuser
```

### Bước 6: Setup Gunicorn với Systemd

```bash
# Create systemd service file
sudo nano /etc/systemd/system/lephat.service
```

Nội dung file:
```ini
[Unit]
Description=Le Phat E-Commerce Gunicorn daemon
After=network.target

[Service]
User=lephat
Group=www-data
WorkingDirectory=/home/lephat/lephat1898
Environment="PATH=/home/lephat/lephat1898/venv/bin"
EnvironmentFile=/home/lephat/lephat1898/.env
ExecStart=/home/lephat/lephat1898/venv/bin/gunicorn \
          --workers 4 \
          --bind unix:/home/lephat/lephat1898/lephat.sock \
          paint_store.wsgi:application

[Install]
WantedBy=multi-user.target
```

```bash
# Start and enable service
sudo systemctl start lephat
sudo systemctl enable lephat
sudo systemctl status lephat
```

### Bước 7: Configure Nginx

```bash
# Create nginx config
sudo nano /etc/nginx/sites-available/lephat
```

Nội dung (đã có trong file `nginx.conf`):
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    location / {
        proxy_pass http://unix:/home/lephat/lephat1898/lephat.sock;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /static/ {
        alias /home/lephat/lephat1898/staticfiles/;
    }

    location /media/ {
        alias /home/lephat/lephat1898/media/;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/lephat /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Bước 8: Setup SSL

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## ✅ Checklist sau khi Deploy

### Testing
- [ ] Website accessible: `https://yourdomain.com`
- [ ] Health check OK: `https://yourdomain.com/health/`
- [ ] Admin login works: `https://yourdomain.com/admin/`
- [ ] Test đặt hàng (order flow)
- [ ] Test thanh toán (payment)
- [ ] Email notifications working
- [ ] Static files loading (CSS, JS, images)

### Security
- [ ] HTTPS working (SSL certificate)
- [ ] DEBUG=False
- [ ] SECRET_KEY unique và secure
- [ ] Firewall configured (port 22, 80, 443)
- [ ] Database credentials strong
- [ ] ALLOWED_HOSTS correct

### Monitoring
- [ ] Sentry error tracking configured
- [ ] Log files being written
- [ ] Health check endpoint monitored
- [ ] Database backup scheduled
- [ ] Disk space monitoring

### Performance
- [ ] Static files served efficiently
- [ ] Redis caching working
- [ ] Database queries optimized
- [ ] CDN for static files (optional)

---

## 🔧 Troubleshooting / Xử lý sự cố

### Lỗi: 500 Internal Server Error

```bash
# Kiểm tra logs
docker-compose logs web  # Nếu dùng Docker
sudo journalctl -u lephat  # Nếu dùng systemd
tail -f /home/lephat/lephat1898/logs/error.log

# Chạy system check
python manage.py check --deploy
```

### Lỗi: Static files không load

```bash
# Collect static files lại
python manage.py collectstatic --clear --noinput

# Kiểm tra nginx config
sudo nginx -t

# Restart nginx
sudo systemctl restart nginx
```

### Lỗi: Database connection failed

```bash
# Kiểm tra PostgreSQL running
sudo systemctl status postgresql

# Test connection
psql -h localhost -U lephat_user -d lephat_production

# Kiểm tra .env file
cat .env | grep DB_
```

### Lỗi: Permission denied

```bash
# Fix file permissions
sudo chown -R lephat:www-data /home/lephat/lephat1898
sudo chmod -R 755 /home/lephat/lephat1898
sudo chmod -R 775 /home/lephat/lephat1898/media
sudo chmod -R 775 /home/lephat/lephat1898/staticfiles
```

---

## 📊 Monitoring & Maintenance

### Daily Checks
- Monitor error logs
- Check disk space
- Review Sentry errors
- Monitor response times

### Weekly Maintenance
- Review database performance
- Check backup status
- Update dependencies if needed
- Review security logs

### Monthly Tasks
- Security updates
- Performance optimization
- Database optimization
- Analytics review

---

## 📞 Support / Hỗ trợ

Nếu gặp vấn đề khi deploy:

1. Kiểm tra logs (xem phần Troubleshooting)
2. Đọc kỹ error messages
3. Google error messages
4. Check GitHub Issues

**Tài liệu tham khảo:**
- [Django Deployment Checklist](https://docs.djangoproject.com/en/4.2/howto/deployment/checklist/)
- [Render Documentation](https://render.com/docs)
- [Docker Documentation](https://docs.docker.com/)

---

**Chúc bạn deploy thành công! / Good luck with your deployment! 🚀**
