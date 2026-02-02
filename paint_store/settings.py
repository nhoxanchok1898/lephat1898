"""
Django settings for paint_store project - Development Environment

AUTO-GENERATED: This file was auto-generated to provide minimal
development settings. For production, use paint_store/settings_production.py
and set proper environment variables.

Generated automatically during development setup to enable local testing.
See https://docs.djangoproject.com/en/stable/topics/settings/ for more
information.
"""

from pathlib import Path
import os

# Build paths inside the project like this: BASE_DIR / 'subdir'.
BASE_DIR = Path(__file__).resolve().parent.parent


# Quick-start development settings - unsuitable for production
# See https://docs.djangoproject.com/en/stable/howto/deployment/checklist/

# SECURITY WARNING: keep the secret key used in production secret!
# This is a development-only key. Override with environment variable for production.
SECRET_KEY = os.environ.get(
    'SECRET_KEY',
    'django-insecure-dev-key-paint-store-local-only-change-in-production'
)

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = os.environ.get('DEBUG', 'True').lower() in ('true', '1', 'yes')

ALLOWED_HOSTS = os.environ.get('ALLOWED_HOSTS', '127.0.0.1,localhost').split(',')


# Application definition

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
    'home',
    'store',
    # Note: do NOT register `paint_store` as an app here because it can
    # be a namespace package in some environments and Django will attempt
    # to create an AppConfig for it (causing ImproperlyConfigured errors).
    # Instead we'll import its compatibility shim directly below.
]

MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.common.CommonMiddleware',
    'django.middleware.csrf.CsrfViewMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
    'store.response_logger_middleware.ResponseLoggerMiddleware',
]

ROOT_URLCONF = 'paint_store.urls'

TEMPLATES = [
    {
        'BACKEND': 'django.template.backends.django.DjangoTemplates',
        'DIRS': [BASE_DIR / 'templates'],
        'APP_DIRS': True,
        'OPTIONS': {
            'context_processors': [
                'django.template.context_processors.request',
                'django.contrib.auth.context_processors.auth',
                'django.contrib.messages.context_processors.messages',
                'django.template.context_processors.static',
            ],
        },
    },
]

WSGI_APPLICATION = 'paint_store.wsgi.application'


# Database
# https://docs.djangoproject.com/en/stable/ref/settings/#databases

DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': BASE_DIR / 'db.sqlite3',
    }
}


# Password validation
# https://docs.djangoproject.com/en/stable/ref/settings/
# #auth-password-validators
# (see AUTH_PASSWORD_VALIDATORS)

AUTH_PASSWORD_VALIDATORS = [
    {
        'NAME': 'django.contrib.auth.password_validation.'
                'UserAttributeSimilarityValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.MinimumLengthValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.CommonPasswordValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.NumericPasswordValidator',
    },
]


# Internationalization
# https://docs.djangoproject.com/en/stable/topics/i18n/

LANGUAGE_CODE = 'en-us'

TIME_ZONE = 'UTC'

USE_I18N = True

USE_TZ = True


# Static files (CSS, JavaScript, Images)
# https://docs.djangoproject.com/en/stable/howto/static-files/

STATIC_URL = '/static/'

# Canonical site URL used for absolute links (sitemaps, JSON-LD). Update for production.
SITE_URL = os.environ.get('SITE_URL', 'http://127.0.0.1:8000')

# Directory for `collectstatic` (development/prod staging)
STATIC_ROOT = BASE_DIR / 'staticfiles'

# Media files (user uploads)
MEDIA_URL = '/media/'
MEDIA_ROOT = BASE_DIR / 'media'

# Default primary key field type
# https://docs.djangoproject.com/en/stable/ref/settings/#default-auto-field
DEFAULT_AUTO_FIELD = 'django.db.models.BigAutoField'

# REST Framework Configuration
REST_FRAMEWORK = {
    'DEFAULT_PAGINATION_CLASS': 'rest_framework.pagination.PageNumberPagination',
    'PAGE_SIZE': 10,
    'DEFAULT_AUTHENTICATION_CLASSES': [
        'rest_framework.authentication.SessionAuthentication',
        'rest_framework.authentication.TokenAuthentication',
    ],
    'DEFAULT_PERMISSION_CLASSES': [
        'rest_framework.permissions.IsAuthenticatedOrReadOnly',
    ],
    'DEFAULT_THROTTLE_RATES': {
        'anon': '100/hour',
        'user': '1000/hour',
    }
}

# Sentry configuration: initialize when a DSN is available and not in DEBUG.
try:
    import sentry_sdk
    from sentry_sdk.integrations.django import DjangoIntegration
except Exception:
    sentry_sdk = None

# Prefer environment variable `SENTRY_DSN`; use empty string if not set
# Note: Replace with your project's DSN or keep empty for local development
SENTRY_DSN = os.environ.get('SENTRY_DSN', '')

if sentry_sdk and SENTRY_DSN and not DEBUG:
    sentry_sdk.init(
        dsn=SENTRY_DSN,
        integrations=[DjangoIntegration()],
        # Add data like request headers and IP for users
        # see https://docs.sentry.io/platforms/python/data-management/
        # data-collected/ for more info
        send_default_pii=True,
    )

# Load admin compatibility shim without registering `paint_store` as
# an app. Importing the module runs the runtime monkey-patches but avoids
# AppConfig discovery problems that can occur when the package exists in
# multiple filesystem locations (namespace packages).
try:
    import importlib
    importlib.import_module('paint_store.admin_compat')
except Exception:
    # Best-effort: don't crash settings import if shim import fails.
    pass
