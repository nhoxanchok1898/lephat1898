from django.utils.deprecation import MiddlewareMixin
from pathlib import Path


class ResponseLoggerMiddleware(MiddlewareMixin):
    def process_response(self, request, response):
        # Only log in DEBUG mode and use a cross-platform path
        from django.conf import settings
        if not getattr(settings, 'DEBUG', False):
            return response

        try:
            # Use a cross-platform path relative to BASE_DIR
            base = getattr(
                settings,
                'BASE_DIR',
                Path(__file__).resolve().parent.parent
            )
            log_path = base / 'tmp_final_response.txt'
            with open(log_path, "w", encoding="utf-8") as f:
                f.write(f"PATH: {getattr(request, 'path', '')}\n")
                f.write(f"STATUS: {getattr(response, 'status_code', '')}\n")
                try:
                    content = getattr(response, 'content', b'')
                    if isinstance(content, bytes):
                        content = content.decode('utf-8', errors='replace')
                    # Only write first 10000 chars to avoid huge files
                    f.write(f"BODY: {content[:10000]}\n")
                except Exception:
                    f.write("BODY: <unreadable>\n")
        except Exception:
            # Don't crash if logging fails
            pass
        return response
