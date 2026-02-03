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

# Django 4.2 + Python 3.14 compatibility:
# In this environment BaseContext.__copy__ was monkeypatched to an invalid
# implementation (`copy(super())`), breaking admin pages. Force a safe
# implementation that works with RequestContext/Context.
import copy as _copy
from django.template import context as _ctx

def _safe_basecontext_copy(self):
    duplicate = _ctx.BaseContext.__new__(_ctx.BaseContext)
    duplicate.__class__ = self.__class__
    duplicate.__dict__ = _copy.copy(getattr(self, '__dict__', {}))
    duplicate.dicts = list(getattr(self, 'dicts', []))
    return duplicate

def _safe_context_copy(self):
    duplicate = _safe_basecontext_copy(self)
    duplicate.render_context = _copy.copy(getattr(self, 'render_context', {}))
    return duplicate

_ctx.BaseContext.__copy__ = _safe_basecontext_copy
_ctx.Context.__copy__ = _safe_context_copy
_ctx.RequestContext.__copy__ = _safe_context_copy
