from django.utils.deprecation import MiddlewareMixin

class ResponseLoggerMiddleware(MiddlewareMixin):
    def process_response(self, request, response):
        try:
            with open(r"C:\Users\letan\Desktop\lephat1898\tmp_final_response.txt", "w", encoding="utf-8") as f:
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
