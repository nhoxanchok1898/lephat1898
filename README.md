# Paint Store — Local Development

Quick notes to run the project locally for development:

1. Create & activate virtualenv (Windows PowerShell):

```powershell
python -m venv .venv
Set-ExecutionPolicy -Scope Process -ExecutionPolicy RemoteSigned -Force
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt
```

2. Apply migrations and run the dev server:

```powershell
python manage.py migrate --noinput
python manage.py runserver 127.0.0.1:8888
```

3. If `Pillow` is required but your environment blocks network, install from wheels placed in `wheels/`:

```powershell
python -m pip install --no-index --find-links=./wheels Pillow
```

4. Collect static assets for production-like static serving:

```powershell
python manage.py collectstatic --noinput
```

Notes:
- I updated templates and styles for a modern, responsive UI and added a minimal Vietnamese translations file at `locale/vi/LC_MESSAGES/django.po`.
- CI workflow at `.github/workflows/ci.yml` runs lightweight checks and the `tools/http_qa.py` script.
# Paint Store

Local Django development site for a small paint e‑commerce demo.

Quick start (development)

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
python manage.py migrate
python manage.py seed_store
python manage.py assign_placeholders
python manage.py runserver 0.0.0.0:8888
```

Admin

```bash
python manage.py createsuperuser
```

Production (examples)

- Provide an `.env` file from `.env.example` with `DJANGO_SECRET_KEY`, `SITE_URL`, `ALLOWED_HOSTS`.
- Build and run with Docker Compose (example):

```bash
docker-compose build
docker-compose up -d
```

- Or deploy using Gunicorn + Nginx (see `RUNBOOK.md` for systemd/nginx snippets).

CI

- A GitHub Actions workflow is included at `.github/workflows/ci.yml` to run migrations and tests on push/PR to `main`.

Testing

```bash
python manage.py test
```

Notes
- The project includes simple QA scripts under `tools/` and a `RUNBOOK.md` with deployment guidance.
- Before production, ensure `DEBUG=False`, secure `DJANGO_SECRET_KEY`, and restrict `ALLOWED_HOSTS`.
