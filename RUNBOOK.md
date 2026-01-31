Runbook — Paint Store (development)

Quick start (development):

1. Create virtualenv and install dependencies

```
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

2. Run migrations & seed data

```
python manage.py makemigrations
python manage.py migrate
python manage.py seed_store
python manage.py assign_placeholders
```

3. Run development server (keep single instance)

```
python manage.py runserver 0.0.0.0:8888
```

Notes for production / deployment:

- Set `DEBUG = False` and configure `ALLOWED_HOSTS`.
- Configure `STATIC_ROOT` and run `python manage.py collectstatic`.
- Configure `MEDIA_ROOT` and serve media files from your webserver or cloud storage.
- Provide a `SITE_URL` setting (e.g. `https://www.example.com`) so sitemap images and absolute links are generated correctly.
- Use a WSGI/ASGI server (Gunicorn/Uvicorn) behind a reverse proxy.
- Ensure `django.contrib.sitemaps` is in `INSTALLED_APPS` (already present in development settings).
- For SEO: enable HTTPS and canonical host, generate sitemap at `/sitemap.xml` and ensure `robots.txt` points to it.

Deployment checklist (concrete steps):

- Set environment variables (example `.env` below) and export them for the process that runs the app.
- Turn off debug and set `ALLOWED_HOSTS` to your domain(s):

```py
DEBUG = False
ALLOWED_HOSTS = ['your-domain.com', 'www.your-domain.com']
```

- Configure static & media serving:

```powershell
python manage.py collectstatic --noinput
# Serve files from nginx/Caddy/IIS or cloud storage (S3)
```

- Ensure `SITE_URL` is set in the environment to your canonical site (used by sitemaps and JSON-LD):

```env
# .env.example
SITE_URL=https://www.your-domain.com
DJANGO_SECRET_KEY=replace-with-secret
DEBUG=False
ALLOWED_HOSTS=your-domain.com,www.your-domain.com
```

- Start your production server (example using Gunicorn on Linux):
- Monitor logs and cron/backup for database and media backups.

Example deployment snippets (minimal, for Linux servers)

1) Install runtime & dependencies

```bash
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

2) Collect static files

```bash
python manage.py migrate --noinput
python manage.py collectstatic --noinput
```

3) Gunicorn (example systemd service)

Create `/etc/systemd/system/paint_store.service` with:

```ini
[Unit]
Description=Paint Store Gunicorn
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/srv/paint_store
EnvironmentFile=/srv/paint_store/.env
ExecStart=/srv/paint_store/.venv/bin/gunicorn paint_store.wsgi:application \
	--workers 3 --bind unix:/run/paint_store.sock

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now paint_store
```

4) Nginx reverse proxy (basic)

Create an nginx site config `/etc/nginx/sites-available/paint_store`:

```nginx
server {
	listen 80;
	server_name your-domain.com www.your-domain.com;

	location = /favicon.ico { access_log off; log_not_found off; }
	location /static/ { alias /srv/paint_store/staticfiles/; }
	location /media/ { alias /srv/paint_store/media/; }

	location / {
		include proxy_params;
		proxy_pass http://unix:/run/paint_store.sock;
	}
}
```

Enable and test:

```bash
sudo ln -s /etc/nginx/sites-available/paint_store /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

5) HTTPS with Let's Encrypt (certbot)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

6) Notes

- Ensure `EnvironmentFile` or system environment provides `DJANGO_SECRET_KEY`, `SITE_URL`, and `ALLOWED_HOSTS` appropriately. Use the `.env.example` as a template.
- Use a process manager (systemd) and a reverse proxy (nginx) to serve static/media and terminate TLS.
- Monitor logs (`journalctl -u paint_store`, nginx logs) and rotate backups for database/media.

Monitor logs and cron/backup for database and media backups.

Troubleshooting tips:

- If you see template errors about `{% extends %}` must be first, ensure child templates begin with `{% extends 'store/base.html' %}` and do not call `{% load static %}` before extends.
- If sitemap images are missing, add `SITE_URL` to settings or configure production host.
- If server hangs, check running `runserver` instances and stop duplicates; see how to find and kill by PID on Windows using `netstat -ano` and `taskkill`.

Contact: repository maintainer (project owner) for additional configuration.
