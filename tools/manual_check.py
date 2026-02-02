import urllib.request
import urllib.error

HOST = 'http://127.0.0.1:8000'
paths = ['/', '/store/', '/store/products/', '/store/products/1/', '/store/cart/', '/store/login/', '/store/contact/']
results = []
for p in paths:
    url = HOST + p
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'manual-check/1.0'})
        with urllib.request.urlopen(req, timeout=5) as r:
            status = r.getcode()
            content = r.read(4096).decode('utf-8', errors='replace')
            ok = status == 200
            snippet = content[:4000].replace('\n',' ')
    except urllib.error.HTTPError as e:
        status = e.code
        snippet = getattr(e, 'read', lambda: b'')()[:4000].decode('utf-8', errors='replace')
        ok = False
    except Exception as e:
        status = None
        snippet = str(e)
        ok = False
    results.append((p, url, status, ok, snippet))

for p, url, status, ok, snippet in results:
    print(f"{p} -> {status} {'OK' if ok else 'FAIL'}")
    if not ok:
        print('  snippet:', snippet)

# Exit 0 if all OK, else 2
import sys
if all(r[3] for r in results):
    sys.exit(0)
else:
    sys.exit(2)
