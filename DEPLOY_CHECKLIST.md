# Deploy Checklist

Required environment variables
- `SECRET_KEY` (long random string)
- `DEBUG=False`
- `ALLOWED_HOSTS` (comma-separated domains, e.g., `example.com,www.example.com`)
- Database/Redis/Sentry/Payments: set only if you use them (see `.env.example`)

Before first deploy
1. `python manage.py migrate`
2. `python manage.py collectstatic --noinput`

On every deploy
1. Apply migrations (if any).
2. Restart the app server / worker processes.
3. Monitor logs for errors.

## VPS Deployment (Docker + Nginx reverse proxy)
Prereqs (on VPS)
- Install Docker and Docker Compose
- Copy project files and `.env` to the VPS (set `SECRET_KEY`, `DEBUG=False`, `ALLOWED_HOSTS`)

Deploy steps
1. `docker compose up -d`                      # builds web (gunicorn) container
2. `docker compose -f docker-compose.override.yml up -d nginx`  # starts nginx reverse proxy
3. Verify: access `http://<VPS_IP>/` and `/admin/`

Notes
- Nginx listens on :80 and proxies to Django at 127.0.0.1:8000.
- Static files served from `/static/` via `staticfiles/` volume.
- HTTPS not automated yet; config ready for future Certbot integration.

## Enable HTTPS with Let's Encrypt (manual)
Prereqs
- Domain pointing to the VPS public IP
- Nginx running via docker-compose.override.yml

Steps (run on VPS)
1. Install certbot (system package, e.g. `sudo apt install certbot`).
2. Stop nginx container temporarily (to free :80): `docker compose stop nginx`.
3. Obtain cert (standalone): `sudo certbot certonly --standalone -d yourdomain -d www.yourdomain`
   - Cert paths will be at `/etc/letsencrypt/live/yourdomain/fullchain.pem` and `privkey.pem`.
4. Start nginx again: `docker compose -f docker-compose.override.yml up -d nginx`.
5. Reload nginx after cert renewal: `docker compose exec nginx nginx -s reload`.

Notes
- HTTP (:80) now redirects to HTTPS (:443).
- Update `nginx.conf` with your actual domain paths if they differ.
- Renewal is not automated yet; rerun certbot before expiry.

### Renewal (optional but recommended)
- Manual renew test: `sudo certbot renew --dry-run`
- Manual renew: `sudo certbot renew`
- After renewal, reload nginx to pick up new certs:
  - Docker: `docker compose exec nginx nginx -s reload`
  - Host (if running host nginx): `sudo nginx -s reload`
- Suggested (optional) automation:
  - Cron (as root): `0 3 * * * certbot renew --quiet && docker compose exec nginx nginx -s reload`
  - Or use the systemd timer installed with certbot (`certbot.timer`), plus a post-renew hook to reload nginx.
