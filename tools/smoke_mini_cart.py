#!/usr/bin/env python
"""Smoke tests for AJAX mini-cart features.
Creates a sample product if none exists, then exercises cart endpoints using Django test Client.
"""
import os
import sys
import django

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
django.setup()

from django.test import Client
from store.models import Product, Brand, Category
from decimal import Decimal


def ensure_product():
    if Product.objects.exists():
        return Product.objects.first()
    b, _ = Brand.objects.get_or_create(name='SmokeBrand')
    c, _ = Category.objects.get_or_create(name='SmokeCat')
    p = Product.objects.create(name='Smoke Paint', brand=b, category=c, price=Decimal('19.90'), quantity=10, stock_quantity=10)
    return p


def pretty(jsonable):
    import json
    return json.dumps(jsonable, indent=2, ensure_ascii=False)


def run():
    client = Client()
    p = ensure_product()
    pk = p.pk
    print('Using product pk=', pk)

    # 1) summary should be empty
    r = client.get('/cart/ajax/summary/')
    print('/cart/ajax/summary/ ->', r.status_code, r.content[:200])

    # 2) add via JSON POST to /cart/add/<pk>/
    r = client.post(f'/cart/add/{pk}/', data='{"quantity":1}', content_type='application/json')
    print(f'POST /cart/add/{pk}/ ->', r.status_code)
    try:
        print('Response JSON:', pretty(r.json()))
    except Exception:
        print('Non-JSON response')

    # 3) summary now
    r = client.get('/cart/ajax/summary/')
    print('/cart/ajax/summary/ ->', r.status_code)
    print(pretty(r.json()))

    # 4) update quantity via JSON
    r = client.post(f'/cart/ajax/update/{pk}/', data='{"quantity":3}', content_type='application/json')
    print(f'POST /cart/ajax/update/{pk}/ ->', r.status_code, r.json() if r.status_code==200 else r.content)

    # 5) remove via POST
    r = client.post(f'/cart/ajax/remove/{pk}/')
    print(f'POST /cart/ajax/remove/{pk}/ ->', r.status_code)
    try:
        print('Remove JSON:', pretty(r.json()))
    except Exception:
        print('Non-JSON')

    # final summary
    r = client.get('/cart/ajax/summary/')
    print('Final summary:', r.status_code, pretty(r.json()))


if __name__ == '__main__':
    run()
