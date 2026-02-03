import os
from importlib import import_module

# Determine base settings module — import project base explicitly to avoid
# recursive import when this file is used as the settings module.
base = import_module('ecommerce.settings')

# Copy uppercase settings from base module
for name in dir(base):
    if name.isupper():
        globals()[name] = getattr(base, name)

# Ensure BASE_DIR exists
BASE_DIR = globals().get('BASE_DIR', os.path.dirname(os.path.abspath(__file__)))

# Override DATABASES to use local SQLite
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': os.path.join(BASE_DIR, 'db.sqlite3'),
    }
}
# For local development: allow all hosts and enable debug
DEBUG = True
ALLOWED_HOSTS = ['*']

# Silence the admin template-engine check locally; keep admin installed so
# URLs like /admin/ still import correctly. This avoids the admin.E403
# SystemCheck while developing locally.
SILENCED_SYSTEM_CHECKS = ['admin.E403']
