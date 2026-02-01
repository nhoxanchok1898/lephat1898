import os
from pathlib import Path

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "ecommerce.settings_debug")

import django  # noqa: E402
from django.core.management import call_command  # noqa: E402
import pytest  # noqa: E402

django.setup()

@pytest.fixture(scope="session", autouse=True)
def _migrate_db_once():
    call_command("migrate", run_syncdb=True, interactive=False, verbosity=0)


def pytest_ignore_collect(path=None, collection_path=None, config=None):  # noqa: D401
    """Skip helper scripts that are not pytest-friendly."""
    target = collection_path or path
    if target is None:
        return False
    return Path(target).match("tools/integration_test.py")
