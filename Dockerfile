FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    DJANGO_SETTINGS_MODULE=paint_store.settings

WORKDIR /app

# System deps (add build-essential if wheels are missing)
RUN apt-get update && apt-get install -y --no-install-recommends \
    netcat-traditional \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt /app/
RUN pip install --no-cache-dir --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

COPY . /app

# Collect static files at build time (uses env; set SECRET_KEY during build if needed)
ARG SECRET_KEY=build-secret-key
ENV SECRET_KEY=${SECRET_KEY}
RUN python manage.py collectstatic --noinput

EXPOSE 8000

CMD ["gunicorn", "paint_store.wsgi:application", "--bind", "0.0.0.0:8000", "--workers", "3"]
