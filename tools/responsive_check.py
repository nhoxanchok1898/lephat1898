#!/usr/bin/env python
"""Automated responsive/structure check.
Checks templates and CSS for expected layout classes and responsive rules.
This is a static check (no browser rendering); it verifies presence of classes and media queries.
"""
import os
import sys
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()

from django.test import Client

def file_read(path):
    try:
        with open(os.path.join(ROOT, path), 'r', encoding='utf-8') as f:
            return f.read()
    except Exception:
        return ''

def check_css_rules():
    css = file_read('static/css/layout-fixes.css')
    checks = {}
    checks['aspect_ratio_rule'] = 'aspect-ratio' in css
    checks['product_card_min_height'] = 'min-height: 360px' in css
    checks['hero_max_height'] = 'max-height: 420px' in css
    checks['media_768'] = '@media (max-width: 768px)' in css
    checks['media_480'] = '@media (max-width: 480px)' in css
    checks['offcanvas_max_height'] = '#miniCartOffcanvas .offcanvas-body' in css
    return checks

def check_templates():
    client = Client()
    pages = ['/', '/products/']
    results = {}
    for p in pages:
        r = client.get(p)
        html = r.content.decode('utf-8', errors='ignore')
        results[p] = {
            'status': r.status_code,
            'has_product_card': 'product-card' in html or 'product-card-redesign' in html,
            'has_product_image_wrapper': 'product-image-wrapper' in html or 'product-image-wrapper-redesign' in html,
            'has_hero_visual': 'hero-visual-redesign' in html or 'hero-image-main' in html,
            'has_header_class': 'site-header-modern' in html or 'header-redesign' in html,
            'has_footer_class': 'site-footer-modern' in html,
            'has_mini_cart': 'miniCartOffcanvas' in html or 'mini-cart-body' in html,
        }
    return results

def run():
    css_checks = check_css_rules()
    tpl_checks = check_templates()

    print('Responsive Check Report (static analysis)')
    print('- CSS checks:')
    for k, v in css_checks.items():
        print(f'  - {k}:', 'OK' if v else 'MISSING')
    print('\n- Template checks:')
    for page, info in tpl_checks.items():
        print(f'  Page {page} (HTTP {info["status"]}):')
        for k, v in info.items():
            if k == 'status':
                continue
            print(f'    - {k}:', 'OK' if v else 'MISSING')

    print('\nNotes:')
    print('- This is a structural/static check; it does NOT render pages. For pixel-perfect validation, open the site in a browser and use DevTools (device toolbar).')
    print('- If any "MISSING" appears above, I can patch templates or CSS accordingly.')

if __name__ == "__main__":
    run()
