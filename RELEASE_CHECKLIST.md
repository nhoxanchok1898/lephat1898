# Production Release Checklist (v1.0.0 baseline)

Pre-release verification
- Smoke tests pass: `python manage.py test tests.smoke`
- HTTPS active (nginx + valid cert)
- `DEBUG=False` in production `.env`
- `ALLOWED_HOSTS` set to production domains

Tagging release
- Ensure working tree is clean
- `git tag v1.0.0`
- `git push origin v1.0.0`

Backups (before deploy/tag)
- Database (logical dump):
  - Postgres example: `pg_dump -h <host> -U <user> -Fc <db> > backup_$(date +%Y%m%d).dump`
  - SQLite example (if used): `cp db.sqlite3 db.sqlite3.backup.$(date +%Y%m%d)`
- Media/static (if stored locally):
  - `tar -czf media_backup_$(date +%Y%m%d).tar.gz media/`
  - `tar -czf static_backup_$(date +%Y%m%d).tar.gz staticfiles/`

Deployment sanity checks
- `docker compose up -d` (web) and `docker compose -f docker-compose.override.yml up -d nginx`
- Access `/` and `/admin` over **HTTPS**
- Review logs for errors (`docker compose logs --tail=200 web nginx`)

Post-release
- Record backup location and tag in change log / release notes.
