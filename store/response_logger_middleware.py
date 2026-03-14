from pathlib import Path

from django.conf import settings
from django.utils.deprecation import MiddlewareMixin

class ResponseLoggerMiddleware(MiddlewareMixin):
    def process_response(self, request, response):
        try:
            log_path = Path(settings.BASE_DIR) / "logs" / "last_response.txt"
            log_path.parent.mkdir(parents=True, exist_ok=True)
            with log_path.open("w", encoding="utf-8") as f:
                f.write(f"PATH: {getattr(request, 'path', '')}\n")
                f.write(f"STATUS: {getattr(response, 'status_code', '')}\n")
                try:
                    content = getattr(response, 'content', b'')
                    if isinstance(content, bytes):
                        content = content.decode('utf-8', errors='replace')
                    f.write(f"BODY: {content}\n")
                except Exception:
                    f.write("BODY: <unreadable>\n")
        except Exception:
            pass
        return response
