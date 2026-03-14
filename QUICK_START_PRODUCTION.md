# Quick Start - Production Deployment
# Hướng dẫn nhanh triển khai Production

## TL;DR - Những gì bạn cần / What You Need

✅ **Code:** Đã sẵn sàng (This repository)  
⚠️ **Configuration:** Cần setup (Need to setup)  
⚠️ **Hosting:** Chưa có (Not yet)  

---

## 🎯 Câu trả lời nhanh / Quick Answer

**"Website đã đủ điều kiện để đưa vào hoạt động chưa?"**

### ✅ Về mặt kỹ thuật: CÓ
- Code hoàn chỉnh
- Features đầy đủ
- Docker ready
- CI/CD ready

### ⚠️ Về mặt thực tế: CHƯA
Cần làm thêm 5 việc:

1. **Tạo file .env** (1 phút)
2. **Chọn hosting** (5 phút)
3. **Setup database** (10 phút)
4. **Deploy** (15 phút)
5. **Test** (10 phút)

**Tổng thời gian: ~1 giờ**

---

## 🚀 Cách nhanh nhất: Deploy lên Render

### 1️⃣ Tạo tài khoản (2 phút)
- Vào https://render.com
- Sign up (miễn phí)

### 2️⃣ Tạo database (3 phút)
- New → PostgreSQL
- Name: `lephat-db`
- Plan: Free
- Lưu lại Internal Database URL

### 3️⃣ Tạo web service (5 phút)
- Cách nhanh nhất: dùng Blueprint với file `render.yaml` đã có sẵn trong repo
- Trên Render: New → Blueprint → connect GitHub repo `nhoxanchok1898/lephat1898`
- Render sẽ tự tạo web service + PostgreSQL từ `render.yaml`
- Nếu không dùng Blueprint, tạo Web Service thủ công với:
  - Build Command: `bash build.sh`
  - Start Command: `gunicorn paint_store.wsgi:application --bind 0.0.0.0:$PORT --workers 3`

### 4️⃣ Setup Environment Variables (10 phút)
Copy vào Render Environment tab:

```env
SECRET_KEY=django-insecure-change-this-to-50-random-characters-for-production
DJANGO_SETTINGS_MODULE=paint_store.settings_production
DEBUG=False
SITE_URL=https://your-app.onrender.com
PORT=10000
DATABASE_URL=<copy-from-render-postgres>
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USE_TLS=True
EMAIL_HOST_USER=your-email@gmail.com
EMAIL_HOST_PASSWORD=your-app-password
USE_MANIFEST_STATICFILES=1
```

Ghi chú:
- Nếu dùng Blueprint `render.yaml`, `SECRET_KEY` và `DATABASE_URL` được tạo/gắn tự động.
- `ALLOWED_HOSTS` không bắt buộc khi chạy trên Render vì app tự nhận `RENDER_EXTERNAL_HOSTNAME`.
- `REDIS_URL` là tùy chọn; nếu chưa có Redis, app sẽ fallback an toàn sang session database + local cache.

### 5️⃣ Deploy (10 phút)
- Click "Create Web Service"
- Đợi build (5-10 phút)
- Vào Shell tab, chạy:
  ```bash
  python manage.py migrate
  python manage.py createsuperuser
  ```

### 6️⃣ Test
- Truy cập: `https://your-app.onrender.com`
- Admin: `https://your-app.onrender.com/admin/`

---

## 📋 Checklist trước khi Go Live

### Bắt buộc (MUST HAVE)
- [ ] File .env created with production values
- [ ] SECRET_KEY changed from default
- [ ] DEBUG=False
- [ ] Database (PostgreSQL) setup
- [ ] Migrations run
- [ ] Admin user created
- [ ] SSL/HTTPS working
- [ ] Domain configured (or using .onrender.com)

### Nên có (SHOULD HAVE)
- [ ] Email notifications working
- [ ] Payment gateway (Stripe/PayPal) with live keys
- [ ] Backup strategy planned
- [ ] Monitoring (Sentry) configured
- [ ] Custom domain (not *.onrender.com)

### Tốt nếu có (NICE TO HAVE)
- [ ] CDN for static files
- [ ] Redis cache working
- [ ] Error tracking active
- [ ] Performance monitoring

---

## 📞 Nếu gặp vấn đề / If You Need Help

### Tài liệu chi tiết / Detailed Documentation
- **Vietnamese:** `HUONG_DAN_DEPLOY.md` (Hướng dẫn chi tiết từng bước)
- **English:** `DEPLOYMENT.md`
- **Checklist:** `PRODUCTION_READINESS.md`

### Quick Links
- Setup: `SETUP.md`
- Security: `SECURITY.md`
- API Docs: `API.md`
- Troubleshooting: `TROUBLESHOOTING.md`

---

## 🎓 Học thêm / Learn More

### Video tutorials (Search on YouTube)
- "Deploy Django to Render"
- "Django production deployment"
- "Docker Django deployment"

### Official Documentation
- Django: https://docs.djangoproject.com/en/4.2/howto/deployment/
- Render: https://render.com/docs/deploy-django
- Docker: https://docs.docker.com/

---

## 💡 Tips

### Để test local như production:
```bash
# 1. Tạo .env từ template
cp .env.example .env

# 2. Chỉnh sửa .env
nano .env

# 3. Dùng production settings
export DJANGO_SETTINGS_MODULE=paint_store.settings_production

# 4. Run migrations
python manage.py migrate

# 5. Collect static
python manage.py collectstatic

# 6. Run server
gunicorn paint_store.wsgi:application
```

### Hoặc dùng Docker (dễ hơn):
```bash
# 1. Build
docker-compose up --build

# 2. Migrate
docker-compose exec web python manage.py migrate

# 3. Create admin
docker-compose exec web python manage.py createsuperuser
```

---

## ⏱️ Timeline dự kiến / Expected Timeline

### Nếu có sẵn:
- Domain name
- Email service (Gmail)
- Payment accounts (Stripe)

**→ Thời gian: 1-2 giờ**

### Nếu chưa có:
- Cần đăng ký domain: +1 ngày
- Setup payment gateway: +1 ngày
- Testing kỹ: +1 ngày

**→ Thời gian: 2-3 ngày**

---

**🎯 Kết luận / Conclusion:**

Website **SẴN SÀNG về mặt kỹ thuật**, chỉ cần **CÀI ĐẶT và CẤU HÌNH** là có thể đưa vào hoạt động.

The website is **TECHNICALLY READY**, just needs **SETUP and CONFIGURATION** to go live.

**Action Items ngay bây giờ / Immediate Next Steps:**

1. ✅ Đọc `PRODUCTION_READINESS.md` - Hiểu rõ cần gì
2. ✅ Đọc `HUONG_DAN_DEPLOY.md` - Làm theo hướng dẫn
3. ✅ Chọn Render hoặc VPS - Tạo tài khoản
4. ✅ Deploy theo 6 bước trên - Bắt đầu!

**Chúc bạn thành công! 🚀**
