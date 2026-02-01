import os

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "ecommerce.settings_debug")

import django  # noqa: E402
from django.core.management import call_command  # noqa: E402

django.setup()

# Ensure the test database has required tables before tests run.
call_command("migrate", run_syncdb=True, interactive=False, verbosity=0)
