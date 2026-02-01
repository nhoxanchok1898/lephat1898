#!/bin/bash
# Production Initialization Script for Le Phat E-Commerce
# Script khởi tạo môi trường production

set -e  # Exit on error

echo "=========================================="
echo "Le Phat E-Commerce - Production Setup"
echo "Khởi tạo môi trường Production"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}ERROR: File .env không tồn tại!${NC}"
    echo "Vui lòng tạo file .env từ .env.example:"
    echo "  cp .env.example .env"
    echo "  nano .env  # Và cấu hình các giá trị"
    exit 1
fi

echo -e "${GREEN}✓ File .env đã tồn tại${NC}"

# Check if SECRET_KEY has been changed
if grep -q "your-secret-key-here" .env; then
    echo -e "${RED}ERROR: SECRET_KEY vẫn đang dùng giá trị mặc định!${NC}"
    echo "Vui lòng thay đổi SECRET_KEY trong file .env"
    echo "Tạo random key bằng lệnh:"
    echo "  python -c 'from django.core.management.utils import get_random_secret_key; print(get_random_secret_key())'"
    exit 1
fi

echo -e "${GREEN}✓ SECRET_KEY đã được cấu hình${NC}"

# Check if DEBUG is False
if grep -q "DEBUG=True" .env; then
    echo -e "${YELLOW}WARNING: DEBUG=True trong production!${NC}"
    echo "Khuyến nghị đặt DEBUG=False cho production"
    read -p "Tiếp tục? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Install Python dependencies
echo ""
echo "Đang cài đặt Python dependencies..."
pip install -r requirements.txt --quiet

# Check database connection
echo ""
echo "Kiểm tra kết nối database..."
python manage.py check --database default

echo -e "${GREEN}✓ Database connection OK${NC}"

# Run migrations
echo ""
echo "Đang chạy database migrations..."
python manage.py migrate

echo -e "${GREEN}✓ Migrations completed${NC}"

# Collect static files
echo ""
echo "Đang thu thập static files..."
python manage.py collectstatic --noinput

echo -e "${GREEN}✓ Static files collected${NC}"

# Create cache table
echo ""
echo "Đang tạo cache table..."
python manage.py createcachetable || true

# Check for superuser
echo ""
echo "Kiểm tra admin user..."
ADMIN_EXISTS=$(python manage.py shell -c "from django.contrib.auth import get_user_model; User = get_user_model(); print('yes' if User.objects.filter(is_superuser=True).exists() else 'no')")

if [ "$ADMIN_EXISTS" = "no" ]; then
    echo -e "${YELLOW}Chưa có admin user. Tạo superuser:${NC}"
    python manage.py createsuperuser
else
    echo -e "${GREEN}✓ Admin user đã tồn tại${NC}"
fi

# Run system check
echo ""
echo "Chạy Django system check..."
python manage.py check --deploy

# Final summary
echo ""
echo "=========================================="
echo -e "${GREEN}Production Setup Hoàn thành!${NC}"
echo "=========================================="
echo ""
echo "Các bước tiếp theo:"
echo "1. Kiểm tra health endpoint: curl http://localhost:8000/health/"
echo "2. Khởi động server: gunicorn paint_store.wsgi:application --bind 0.0.0.0:8000"
echo "3. Hoặc dùng Docker: docker-compose up -d"
echo "4. Truy cập admin: http://your-domain.com/admin/"
echo ""
echo "Monitoring:"
echo "- Logs: tail -f logs/django.log"
echo "- Health check: /health/"
echo "- Admin: /admin/"
echo ""
echo -e "${YELLOW}Nhớ cấu hình:${NC}"
echo "- SSL certificate (certbot)"
echo "- Firewall rules"
echo "- Database backups"
echo "- Monitoring alerts"
echo ""
