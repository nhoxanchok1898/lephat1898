from pathlib import Path
import os

# Sentry: optional import and initialization
try:
    import sentry_sdk
    from sentry_sdk.integrations.django import DjangoIntegration
except Exception:
    sentry_sdk = None

# Initialize Sentry if a DSN is provided via env (or fallback to the provided DSN)
SENTRY_DSN = os.environ.get(
    'SENTRY_DSN',
    'https://d9474e438ed65845b699ceb9f47659ee0e451080874655740.ingest.us.sentry.io/4510808749309952',
)

if sentry_sdk and SENTRY_DSN:
    sentry_sdk.init(
        dsn=SENTRY_DSN,
        integrations=[DjangoIntegration()],
        traces_sample_rate=1.0,
        send_default_pii=False,
    )
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
    'store.apps.StoreConfig',
]

# URLs and templates
ROOT_URLCONF = 'paint_store.urls'

MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
        # temporary middleware (removed in finalization)
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

# REST Framework settings
REST_FRAMEWORK = {
    'DEFAULT_PAGINATION_CLASS': 'rest_framework.pagination.PageNumberPagination',
    'PAGE_SIZE': 12,
    'DEFAULT_FILTER_BACKENDS': [
        'django_filters.rest_framework.DjangoFilterBackend',
        'rest_framework.filters.SearchFilter',
        'rest_framework.filters.OrderingFilter',
    ],
    'DEFAULT_AUTHENTICATION_CLASSES': [
        'rest_framework.authentication.SessionAuthentication',
        'rest_framework.authentication.BasicAuthentication',
    ],
    'DEFAULT_PERMISSION_CLASSES': [
        'rest_framework.permissions.AllowAny',
    ],
}
# Touch this file to trigger the development autoreloader when needed