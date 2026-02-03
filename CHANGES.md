Changelog (summary of work completed)

2026-01-29
- Completed development QA and integration testing for Paint Store.
- Implemented sitemaps with product images and absolute URL generation via `SITE_URL`.
- Fixed multiple template issues and validated all public routes return 200 via `tools/http_qa.py`.
- Added environment configuration via `.env` + `python-dotenv` and `.env.example`.
- Added Dockerfile + docker-compose for production-like setup; added `gunicorn` to requirements.
- Added GitHub Actions CI workflow to run migrations and tests on push/PR.
- Added `RUNBOOK.md` with deployment steps (systemd, nginx, certbot), `README.md`, and helper scripts in `tools/`.
- Ran and passed unit tests and integration checkout test.

Notes:
- Before production: set real `DJANGO_SECRET_KEY`, update `ALLOWED_HOSTS`, secure the DB credentials, and configure static/media hosting.
