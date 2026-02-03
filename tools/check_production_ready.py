"""Checks common production readiness items in Django settings.

Usage:
    python tools/check_production_ready.py [--site SITE_URL] [--output FILE]

The script prints a report and can optionally write it to a file.
"""
import argparse
import os
import sys
from pathlib import Path
from datetime import datetime

project_root = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(project_root))

parser = argparse.ArgumentParser()
parser.add_argument('--site', help='Override SITE_URL for the check')
parser.add_argument('--output', help='Write report to this file')
args = parser.parse_args()

if args.site:
    os.environ['SITE_URL'] = args.site

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')

try:
    import django
    django.setup()
except Exception as e:
    print('ERROR: could not setup Django:', e)
    sys.exit(2)

from django.conf import settings

report_lines = []

def println(s=''):
    print(s)
    report_lines.append(str(s))

issues = []
infos = []

println('--- Production Readiness Check ---')
println('Run at: ' + datetime.utcnow().isoformat() + 'Z')
println('SITE_URL: ' + str(getattr(settings, 'SITE_URL', None)))

# SECRET_KEY
if not settings.SECRET_KEY or settings.SECRET_KEY.startswith('change-me') or 'django-insecure' in settings.SECRET_KEY:
    issues.append('SECRET_KEY is insecure or not set.')
    println(' - SECRET_KEY: insecure or not set')
else:
    infos.append('SECRET_KEY set.')
    println(' - SECRET_KEY: set')

# DEBUG
if settings.DEBUG:
    issues.append('DEBUG is True. Set DEBUG=False in production.')
    println(' - DEBUG: True')
else:
    infos.append('DEBUG is False.')
    println(' - DEBUG: False')

# ALLOWED_HOSTS
if not settings.ALLOWED_HOSTS:
    issues.append('ALLOWED_HOSTS is empty.')
    println(' - ALLOWED_HOSTS: empty')
elif any(h.strip() == '*' for h in settings.ALLOWED_HOSTS):
    issues.append("ALLOWED_HOSTS contains '*'; restrict to your domains.")
    println(" - ALLOWED_HOSTS contains '*'")
else:
    infos.append(f"ALLOWED_HOSTS = {settings.ALLOWED_HOSTS}")
    println(f' - ALLOWED_HOSTS = {settings.ALLOWED_HOSTS}')

# SITE_URL
site = getattr(settings, 'SITE_URL', None)
if not site:
    issues.append('SITE_URL not configured.')
    println(' - SITE_URL: not configured')
elif 'example.com' in site or '127.0.0.1' in site or 'localhost' in site:
    infos.append(f'SITE_URL looks like a dev placeholder: {site}')
    println(f' - SITE_URL looks like dev placeholder: {site}')
else:
    infos.append(f'SITE_URL = {site}')
    println(f' - SITE_URL = {site}')

# STATIC_ROOT & collectstatic
static_root = getattr(settings, 'STATIC_ROOT', None)
if not static_root:
    issues.append('STATIC_ROOT not set.')
    println(' - STATIC_ROOT: not set')
else:
    p = Path(static_root)
    if not p.exists() or not any(p.iterdir()):
        issues.append(f'STATIC_ROOT ({p}) is empty. Run collectstatic.')
        println(f' - STATIC_ROOT ({p}) is empty or missing')
    else:
        infos.append(f'STATIC_ROOT populated ({p}).')
        println(f' - STATIC_ROOT populated ({p})')

# MEDIA_ROOT
media_root = getattr(settings, 'MEDIA_ROOT', None)
if not media_root:
    issues.append('MEDIA_ROOT not set.')
    println(' - MEDIA_ROOT: not set')
else:
    p = Path(media_root)
    if not p.exists():
        infos.append(f'MEDIA_ROOT ({p}) does not exist yet — ensure media is available in production.')
        println(f' - MEDIA_ROOT ({p}) does not exist yet')
    else:
        infos.append(f'MEDIA_ROOT exists ({p}).')
        println(f' - MEDIA_ROOT exists ({p})')

# Sitemaps
if 'django.contrib.sitemaps' not in settings.INSTALLED_APPS:
    issues.append('django.contrib.sitemaps not enabled in INSTALLED_APPS.')
    println(' - sitemaps: not enabled')
else:
    infos.append('sitemaps app enabled.')
    println(' - sitemaps: enabled')

# Additional diagnostics: dotenv presence
try:
    import dotenv  # type: ignore
    println(' - python-dotenv: installed')
except Exception:
    println(' - python-dotenv: not installed')

# Python path and environment info
println(' - Python executable: ' + sys.executable)
println(' - sys.path entries:')
for pth in sys.path[:5]:
    println('    ' + str(pth))

# Database connectivity quick check
try:
    from django.db import connections
    conn = connections['default']
    conn.ensure_connection()
    println(' - Database: connection successful')
except Exception as e:
    issues.append('Database connection failed: ' + str(e))
    println(' - Database: connection failed: ' + str(e))

# Summary
print('\nIssues:')
if issues:
    for it in issues:
        println(' - ' + it)
else:
    println(' - None')

print('\nInfo:')
for it in infos:
    println(' - ' + it)

print('\nRecommendations:')
if 'DEBUG is True. Set DEBUG=False in production.' in issues:
    print(' - Set DEBUG=False and ensure ALLOWED_HOSTS lists your domains.')
if any('SECRET_KEY' in s for s in issues):
    print(' - Set a secure SECRET_KEY via environment variable.')
if any('STATIC_ROOT' in s or 'collectstatic' in s for s in issues):
    print(' - Run `python manage.py collectstatic --noinput` and configure static file serving.')
print(' - Verify SITE_URL is set to your canonical URL for sitemaps and JSON-LD.')

# If requested, write the full report to a file
if args.output:
    try:
        with open(args.output, 'w', encoding='utf-8') as fh:
            fh.write('\n'.join(report_lines))
        print(f'Wrote report to {args.output}')
    except Exception as e:
        print('Failed to write report file:', e)
