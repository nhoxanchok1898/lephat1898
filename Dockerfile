FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

RUN apt-get update && apt-get install -y build-essential libpq-dev --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt /app/
RUN pip install --no-cache-dir -r requirements.txt

COPY . /app/

# Collect static
RUN python manage.py collectstatic --noinput || true

# Run as non-root user
RUN useradd -m appuser && chown -R appuser /app
USER appuser

CMD ["gunicorn", "paint_store.wsgi:application", "--bind", "0.0.0.0:8000", "--workers", "3"]
