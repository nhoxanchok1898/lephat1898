#!/usr/bin/env python
"""Simulated browser smoke checks for mini-cart UI elements.
Uses Django test Client to fetch pages and verifies expected HTML snippets and static files.
"""
import os
import sys
import django

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from django.test import Client


def file_exists(path):
    return os.path.exists(os.path.join(ROOT, path.replace('/', os.sep).lstrip(os.sep)))


def run():
    client = Client()
    r = client.get('/')
    print('GET / ->', r.status_code)
    html = r.content.decode('utf-8', errors='ignore')

    checks = []
    checks.append(('cart.js included', 'cart.js' in html))
    checks.append(('offcanvas present', 'id="miniCartOffcanvas"' in html))
    checks.append(('mini-cart partial included', 'mini-cart-body' in html))
    checks.append(('add-to-cart buttons present (data-attr or onclick)', ('data-add-to-cart' in html) or ('addToCart(' in html) or ('.btn-add-to-cart-redesign' in html)))
    checks.append(('cart badge present', 'mini-cart-count' in html or 'cart-badge' in html))

    # static files on disk
    checks.append(('static/js/cart.js exists', file_exists('static/js/cart.js')))
    checks.append(('static/css/cart.css exists', file_exists('static/css/cart.css')))
    checks.append(('template partial exists', os.path.exists(os.path.join(ROOT, 'templates', 'store', 'partials', 'mini_cart.html'))))

    for name, ok in checks:
        print(f'{name}:', 'OK' if ok else 'MISSING')

    # Quick sanity: ensure no <script> tag referencing missing file (simple heuristic)
    missing_static = []
    for tag in ['cart.js', 'cart.css']:
        if tag in html and not file_exists(f'static/{"js" if tag.endswith(".js") else "css"}/{tag}'):
            missing_static.append(tag)
    if missing_static:
        print('Missing referenced static files:', missing_static)
    else:
        print('All referenced static files present on disk (heuristic).')


if __name__ == '__main__':
    run()
