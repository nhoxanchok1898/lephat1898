"""
Production Settings for Paint Store E-commerce
Optimized for security, performance, and scalability
"""
import os
from urllib.parse import unquote, urlparse
from .settings import *


def _parse_database_url(url):
    parsed = urlparse(url)
    scheme = parsed.scheme.lower()

    if scheme in {'postgres', 'postgresql', 'pgsql', 'postgresql_psycopg2'}:
        engine = 'django.db.backends.postgresql'
    elif scheme == 'sqlite':
        engine = 'django.db.backends.sqlite3'
    else:
        raise ValueError(f'Unsupported DATABASE_URL scheme: {scheme}')

    if engine == 'django.db.backends.sqlite3':
        if parsed.path in ('', '/'):
            name = ':memory:'
        else:
            raw_path = parsed.netloc + parsed.path
            if raw_path.startswith('/') and len(raw_path) > 3 and raw_path[2] == ':':
                raw_path = raw_path.lstrip('/')
            name = unquote(raw_path)
        return {
            'ENGINE': engine,
            'NAME': name,
        }

    return {
        'ENGINE': engine,
        'NAME': unquote(parsed.path.lstrip('/')),
        'USER': unquote(parsed.username or ''),
        'PASSWORD': unquote(parsed.password or ''),
        'HOST': parsed.hostname or '',
        'PORT': str(parsed.port or ''),
        'CONN_MAX_AGE': 600,
        'OPTIONS': {
            'sslmode': os.environ.get('DB_SSLMODE', 'require'),
        },
    }


def _build_trusted_origins(hosts, site_url):
    origins = set()
    for host in hosts:
        host = (host or '').strip()
        if not host or host == '*':
            continue
        origins.add(f'https://{host}')
        if DEBUG:
            origins.add(f'http://{host}')
    if site_url and site_url.startswith(('http://', 'https://')):
        origins.add(site_url.rstrip('/'))
    return sorted(origins)

# SECURITY WARNING: keep the secret key used in production secret!
SECRET_KEY = os.environ.get('SECRET_KEY')
if not SECRET_KEY:
    raise ValueError('SECRET_KEY environment variable must be set in production')

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = False

RENDER_EXTERNAL_HOSTNAME = os.environ.get('RENDER_EXTERNAL_HOSTNAME', '').strip()

ALLOWED_HOSTS = [host.strip() for host in os.environ.get('ALLOWED_HOSTS', '').split(',') if host.strip()]
if RENDER_EXTERNAL_HOSTNAME and RENDER_EXTERNAL_HOSTNAME not in ALLOWED_HOSTS:
    ALLOWED_HOSTS.append(RENDER_EXTERNAL_HOSTNAME)
if not ALLOWED_HOSTS:
    raise ValueError('ALLOWED_HOSTS or RENDER_EXTERNAL_HOSTNAME must be set in production')

if not os.environ.get('SITE_URL') and RENDER_EXTERNAL_HOSTNAME:
    SITE_URL = f'https://{RENDER_EXTERNAL_HOSTNAME}'

# Database
DATABASE_URL = os.environ.get('DATABASE_URL', '').strip()
if DATABASE_URL:
    DATABASES = {
        'default': _parse_database_url(DATABASE_URL)
    }
else:
    db_name = os.environ.get('DB_NAME', '').strip()
    db_user = os.environ.get('DB_USER', '').strip()
    db_password = os.environ.get('DB_PASSWORD', '').strip()
    db_host = os.environ.get('DB_HOST', '').strip()
    db_port = os.environ.get('DB_PORT', '5432').strip()
    if not any([db_name, db_user, db_password, db_host]):
        raise ValueError('DATABASE_URL or DB_* variables must be set in production')
    DATABASES = {
        'default': {
            'ENGINE': 'django.db.backends.postgresql',
            'NAME': db_name or 'lephat',
            'USER': db_user or 'postgres',
            'PASSWORD': db_password,
            'HOST': db_host or 'localhost',
            'PORT': db_port,
            'CONN_MAX_AGE': 600,
            'OPTIONS': {
                'sslmode': os.environ.get('DB_SSLMODE', 'require'),
            },
        }
    }

# Cache Configuration (Redis when available, safe fallback otherwise)
REDIS_URL = os.environ.get('REDIS_URL', '').strip()
if REDIS_URL:
    CACHES = {
        'default': {
            'BACKEND': 'django.core.cache.backends.redis.RedisCache',
            'LOCATION': REDIS_URL,
            'KEY_PREFIX': 'lephat',
            'TIMEOUT': 300,
        }
    }
else:
    CACHES = {
        'default': {
            'BACKEND': 'django.core.cache.backends.locmem.LocMemCache',
            'LOCATION': 'production-fallback',
            'TIMEOUT': 300,
        }
    }

# Session Configuration
SESSION_ENGINE = 'django.contrib.sessions.backends.cache' if REDIS_URL else 'django.contrib.sessions.backends.db'
SESSION_CACHE_ALIAS = 'default'

# Security Settings
SECURE_SSL_REDIRECT = True
SECURE_HSTS_SECONDS = 31536000  # 1 year
SECURE_HSTS_INCLUDE_SUBDOMAINS = True
SECURE_HSTS_PRELOAD = True
SECURE_CONTENT_TYPE_NOSNIFF = True
SECURE_BROWSER_XSS_FILTER = True
X_FRAME_OPTIONS = 'DENY'

# Cookie Security
SESSION_COOKIE_SECURE = True
SESSION_COOKIE_HTTPONLY = True
SESSION_COOKIE_SAMESITE = 'Strict'
CSRF_COOKIE_SECURE = True
CSRF_COOKIE_HTTPONLY = True
CSRF_COOKIE_SAMESITE = 'Strict'
SECURE_PROXY_SSL_HEADER = ('HTTP_X_FORWARDED_PROTO', 'https')
USE_X_FORWARDED_HOST = True
CSRF_TRUSTED_ORIGINS = _build_trusted_origins(ALLOWED_HOSTS, SITE_URL)

# Static Files (WhiteNoise)
STATICFILES_STORAGE = 'whitenoise.storage.CompressedManifestStaticFilesStorage'
STATIC_ROOT = os.path.join(BASE_DIR, 'staticfiles')
WHITENOISE_MANIFEST_STRICT = False

# Media Files (Use S3 or similar in production)
# DEFAULT_FILE_STORAGE = 'storages.backends.s3boto3.S3Boto3Storage'
# AWS_ACCESS_KEY_ID = os.environ.get('AWS_ACCESS_KEY_ID')
# AWS_SECRET_ACCESS_KEY = os.environ.get('AWS_SECRET_ACCESS_KEY')
# AWS_STORAGE_BUCKET_NAME = os.environ.get('AWS_STORAGE_BUCKET_NAME')
# AWS_S3_REGION_NAME = os.environ.get('AWS_S3_REGION_NAME', 'us-east-1')

# Email Configuration
EMAIL_BACKEND = 'django.core.mail.backends.smtp.EmailBackend'
EMAIL_HOST = os.environ.get('EMAIL_HOST', 'smtp.gmail.com')
EMAIL_PORT = int(os.environ.get('EMAIL_PORT', 587))
EMAIL_USE_TLS = os.environ.get('EMAIL_USE_TLS', 'True') == 'True'
EMAIL_HOST_USER = os.environ.get('EMAIL_HOST_USER')
EMAIL_HOST_PASSWORD = os.environ.get('EMAIL_HOST_PASSWORD')
DEFAULT_FROM_EMAIL = os.environ.get('DEFAULT_FROM_EMAIL', 'noreply@lephat.com')

# Logging Configuration
LOGGING = {
    'version': 1,
    'disable_existing_loggers': False,
    'formatters': {
        'verbose': {
            'format': '{levelname} {asctime} {module} {process:d} {thread:d} {message}',
            'style': '{',
        },
        'json': {
            'format': '{"time": "%(asctime)s", "level": "%(levelname)s", "module": "%(module)s", "message": "%(message)s"}',
        },
    },
    'filters': {
        'require_debug_false': {
            '()': 'django.utils.log.RequireDebugFalse',
        },
    },
    'handlers': {
        'console': {
            'level': 'INFO',
            'class': 'logging.StreamHandler',
            'formatter': 'verbose',
        },
        'file': {
            'level': 'INFO',
            'class': 'logging.handlers.RotatingFileHandler',
            'filename': os.path.join(BASE_DIR, 'logs', 'django.log'),
            'maxBytes': 1024 * 1024 * 10,  # 10MB
            'backupCount': 5,
            'formatter': 'json',
        },
        'error_file': {
            'level': 'ERROR',
            'class': 'logging.handlers.RotatingFileHandler',
            'filename': os.path.join(BASE_DIR, 'logs', 'error.log'),
            'maxBytes': 1024 * 1024 * 10,  # 10MB
            'backupCount': 5,
            'formatter': 'json',
        },
    },
    'root': {
        'handlers': ['console', 'file'],
        'level': 'INFO',
    },
    'loggers': {
        'django': {
            'handlers': ['console', 'file'],
            'level': 'INFO',
            'propagate': False,
        },
        'django.request': {
            'handlers': ['error_file'],
            'level': 'ERROR',
            'propagate': False,
        },
        'store': {
            'handlers': ['console', 'file'],
            'level': 'INFO',
            'propagate': False,
        },
    },
}

# Sentry Error Tracking
SENTRY_DSN = os.environ.get('SENTRY_DSN')
if SENTRY_DSN:
    import sentry_sdk
    from sentry_sdk.integrations.django import DjangoIntegration
    
    sentry_sdk.init(
        dsn=SENTRY_DSN,
        integrations=[DjangoIntegration()],
        traces_sample_rate=0.1,
        send_default_pii=False,
        environment=os.environ.get('ENVIRONMENT', 'production'),
    )

# Performance Optimization
CONN_MAX_AGE = 600
DATA_UPLOAD_MAX_MEMORY_SIZE = 5242880  # 5MB

# Rate Limiting
RATELIMIT_ENABLE = True
RATELIMIT_USE_CACHE = 'default'

# Celery Configuration (if using)
CELERY_BROKER_URL = os.environ.get('CELERY_BROKER_URL', REDIS_URL)
CELERY_RESULT_BACKEND = os.environ.get('CELERY_RESULT_BACKEND', REDIS_URL)
CELERY_ACCEPT_CONTENT = ['json']
CELERY_TASK_SERIALIZER = 'json'
CELERY_RESULT_SERIALIZER = 'json'
CELERY_TIMEZONE = 'Asia/Ho_Chi_Minh'

# Payment Gateway Configuration
STRIPE_PUBLIC_KEY = os.environ.get('STRIPE_PUBLIC_KEY') or os.environ.get('STRIPE_PUBLISHABLE_KEY')
STRIPE_SECRET_KEY = os.environ.get('STRIPE_SECRET_KEY')
STRIPE_WEBHOOK_SECRET = os.environ.get('STRIPE_WEBHOOK_SECRET')

PAYPAL_CLIENT_ID = os.environ.get('PAYPAL_CLIENT_ID')
PAYPAL_CLIENT_SECRET = os.environ.get('PAYPAL_CLIENT_SECRET')
PAYPAL_MODE = os.environ.get('PAYPAL_MODE', 'live')  # 'sandbox' or 'live'

# Admin Configuration
ADMINS = [
    ('Admin', os.environ.get('ADMIN_EMAIL', 'admin@lephat.com')),
]
MANAGERS = ADMINS

# Create logs directory if it doesn't exist
LOGS_DIR = os.path.join(BASE_DIR, 'logs')
os.makedirs(LOGS_DIR, exist_ok=True)
