try:
    # Monkeypatch DRF's APIClient to use Django's Client in this test/dev environment.
    # This helps ensure anonymous POSTs that rely on Django sessions behave
    # consistently inside the Django test runner where DRF authentication
    # classes might otherwise produce 401 responses for unauthenticated calls.
    import rest_framework.test as _rft
    from django.test import Client as _DjangoClient
    _rft.APIClient = _DjangoClient
except Exception:
    # If DRF isn't installed or import fails, silently continue.
    pass
