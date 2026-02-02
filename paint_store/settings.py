from pathlib import Path
import os

# Sentry: optional import and initialization
try:
    import sentry_sdk
    from sentry_sdk.integrations.django import DjangoIntegration
except Exception:
    sentry_sdk = None

# Initialize Sentry only if a valid DSN is provided via environment variable
SENTRY_DSN = os.environ.get('SENTRY_DSN', '')

if sentry_sdk and SENTRY_DSN:
    try:
        sentry_sdk.init(
            dsn=SENTRY_DSN,
            integrations=[DjangoIntegration()],
            traces_sample_rate=1.0,
            send_default_pii=False,
        )
    except Exception:
        # Silently ignore Sentry initialization errors (e.g., invalid DSN)
        pass
try:
    from dotenv import load_dotenv  # type: ignore[reportMissingImports]
except Exception:
    # dotenv isn't required at runtime; if not installed, skip loading .env
    def load_dotenv(*args, **kwargs):
        return False

# Load .env from project root if present
load_dotenv(dotenv_path=Path(__file__).resolve().parent.parent / '.env')

# Base dir
BASE_DIR = Path(__file__).resolve().parent.parent

# SECURITY (development defaults)
SECRET_KEY = os.getenv('DJANGO_SECRET_KEY', 'change-me-in-production')
# Read DEBUG from environment (useful for production deployment)
DEBUG = os.getenv('DEBUG', 'True').lower() in ('1', 'true', 'yes')
# Read ALLOWED_HOSTS from comma-separated env var, fallback to localhost when
# running in production without configuration
raw_hosts = os.getenv('ALLOWED_HOSTS', '')
if raw_hosts:
    ALLOWED_HOSTS = [h.strip() for h in raw_hosts.split(',') if h.strip()]
else:
    ALLOWED_HOSTS = ['*'] if DEBUG else ['www.your-domain.com']

# When developing locally ensure common local hostnames are allowed so
# requests from browsers (127.0.0.1 / localhost) don't get rejected by
# Django's host header check. We only add these when DEBUG is True to
# avoid loosening production settings.
# Always allow local loopback hosts to ease development and testing on the machine.
for _host in ('127.0.0.1', 'localhost'):
    if _host not in ALLOWED_HOSTS:
        ALLOWED_HOSTS.append(_host)

# Applications
INSTALLED_APPS = [
    'django.contrib.admin',
    'django.contrib.auth',
    'django.contrib.contenttypes',
    'django.contrib.sessions',
    'django.contrib.messages',
    'django.contrib.staticfiles',
    'django.contrib.sitemaps',
    'rest_framework',
    'rest_framework.authtoken',
    'corsheaders',
    'store.apps.StoreConfig',
]

# URLs and templates
ROOT_URLCONF = 'paint_store.urls'

MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
    'corsheaders.middleware.CorsMiddleware',
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.common.CommonMiddleware',
    'django.middleware.csrf.CsrfViewMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
]

TEMPLATES = [
    {
        'BACKEND': 'django.template.backends.django.DjangoTemplates',
        'DIRS': [BASE_DIR / 'templates'],
        'APP_DIRS': True,
        'OPTIONS': {
            'context_processors': [
                'django.template.context_processors.debug',
                'django.template.context_processors.request',
                'django.contrib.auth.context_processors.auth',
                'django.contrib.messages.context_processors.messages',
                'django.template.context_processors.static',
            ],
        },
    },
]

# WSGI
WSGI_APPLICATION = 'paint_store.wsgi.application'

# Database (sqlite for easy start)
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': BASE_DIR / 'db.sqlite3',
    }
}

# Static & media
STATIC_URL = '/static/'
STATICFILES_DIRS = [BASE_DIR / 'static']
MEDIA_URL = '/media/'
MEDIA_ROOT = BASE_DIR / 'media'

# Absolute site URL used for generating full links (sitemaps, JSON-LD)
# Prefer explicit environment variable in production. When not provided:
# - use the local runserver address during DEBUG
# - otherwise use a safe production placeholder (override this for your site)
SITE_URL = os.getenv('SITE_URL')
if not SITE_URL:
    SITE_URL = 'http://127.0.0.1:8888' if DEBUG else 'https://www.your-domain.com'

# Directory for `collectstatic` output (dev/test staging)
STATIC_ROOT = BASE_DIR / 'staticfiles'

# Internationalization
LANGUAGE_CODE = 'en-us'
TIME_ZONE = 'UTC'
USE_I18N = True
USE_L10N = True
USE_TZ = True

# Default primary key field
DEFAULT_AUTO_FIELD = 'django.db.models.BigAutoField'
# Development email backend (prints emails to console)
EMAIL_BACKEND = 'django.core.mail.backends.console.EmailBackend'
DEFAULT_FROM_EMAIL = 'noreply@example.com'

# REST Framework Configuration
REST_FRAMEWORK = {
    'DEFAULT_AUTHENTICATION_CLASSES': [
        'rest_framework.authentication.TokenAuthentication',
        'rest_framework.authentication.SessionAuthentication',
    ],
    'DEFAULT_PERMISSION_CLASSES': [
        'rest_framework.permissions.IsAuthenticatedOrReadOnly',
    ],
    'DEFAULT_PAGINATION_CLASS': 'rest_framework.pagination.PageNumberPagination',
    'PAGE_SIZE': 20,
    'DEFAULT_THROTTLE_CLASSES': [
        'rest_framework.throttling.AnonRateThrottle',
        'rest_framework.throttling.UserRateThrottle'
    ],
    'DEFAULT_THROTTLE_RATES': {
        'anon': '100/hour',
        'user': '1000/hour'
    },
    'DEFAULT_FILTER_BACKENDS': [
        'rest_framework.filters.SearchFilter',
        'rest_framework.filters.OrderingFilter',
    ],
}

# CORS Configuration for mobile apps
CORS_ALLOWED_ORIGINS = [
    "http://localhost:3000",
    "http://127.0.0.1:3000",
]
CORS_ALLOW_CREDENTIALS = True

# Stripe Configuration (existing from context)
STRIPE_PUBLIC_KEY = os.getenv('STRIPE_PUBLIC_KEY', '')
STRIPE_SECRET_KEY = os.getenv('STRIPE_SECRET_KEY', '')
STRIPE_WEBHOOK_SECRET = os.getenv('STRIPE_WEBHOOK_SECRET', '')

# Celery Configuration (for email queue - optional)
CELERY_BROKER_URL = os.getenv('CELERY_BROKER_URL', 'redis://localhost:6379/0')
CELERY_RESULT_BACKEND = os.getenv('CELERY_RESULT_BACKEND', 'redis://localhost:6379/0')
CELERY_ACCEPT_CONTENT = ['json']
CELERY_TASK_SERIALIZER = 'json'
CELERY_RESULT_SERIALIZER = 'json'
CELERY_TIMEZONE = TIME_ZONE

# Load runtime admin compatibility shim to guard against contexts lacking
# a ``template`` attribute in some dev environments. This is a best-effort
# development-time fallback and will not raise if the shim fails to import.
try:
    import importlib
    importlib.import_module('paint_store.admin_compat')
except Exception:
    # Don't fail settings import on shim errors.
    pass
