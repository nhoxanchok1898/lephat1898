"""Temporary middleware to log Host header and related info for debugging 400 errors.

Install only for local development; remove after diagnosis.
"""
from django.utils.deprecation import MiddlewareMixin


class DebugHostMiddleware(MiddlewareMixin):
    def process_request(self, request):
        try:
            host = request.get_host()
        except Exception:
            host = '<could-not-get-host>'
        http_host = request.META.get('HTTP_HOST')
        remote = request.META.get('REMOTE_ADDR')
        print(f"[DebugHost] REQUEST host={host!r} HTTP_HOST={http_host!r} REMOTE_ADDR={remote!r}")
        return None
