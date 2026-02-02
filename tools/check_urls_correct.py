import urllib.request

BASE = 'http://127.0.0.1:8000'
PATHS = ['/', '/products/', '/products/1/', '/cart/', '/login/', '/contact/']

for p in PATHS:
    url = BASE + p
    try:
        with urllib.request.urlopen(url, timeout=5) as r:
            print(f"{p} -> {r.getcode()} OK")
    except Exception as e:
        code = getattr(e, 'code', '')
        body = b''
        try:
            body = e.read()[:20000]
        except Exception:
            pass
        text = ''
        try:
            text = body.decode('utf-8', errors='replace')
        except Exception:
            text = str(body)
        msg = getattr(e, 'reason', str(e))
        print(f"{p} -> ERROR {code} {msg}\n--- response snippet ---\n{text}\n------------------------")
