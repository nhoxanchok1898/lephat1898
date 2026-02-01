#!/usr/bin/env python
"""
Auto-diagnosis script for VSCode AI
Checks all errors and generates report
"""
import os
import sys
import subprocess

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
try:
    import django
    django.setup()
except Exception:
    # If django is not available or setup fails, we'll continue and let imports report errors
    pass

print("=" * 80)
print("DJANGO PROJECT DIAGNOSIS")
print("=" * 80)

# Test 1: Django Check
print("\n1) Running Django Check...")
print("-" * 80)
result = subprocess.run([sys.executable, 'manage.py', 'check'], capture_output=True, text=True)
print(result.stdout)
if result.stderr:
    print("STDERR:", result.stderr)
print("Status:", "PASS" if result.returncode == 0 else "FAIL")

# Test 2: Python Syntax
print("\n2) Checking Python Syntax...")
print("-" * 80)
result = subprocess.run([sys.executable, '-m', 'compileall', 'store', 'paint_store', '-q'], capture_output=True, text=True)
if result.returncode == 0:
    print("All Python files compile successfully")
else:
    print("Syntax errors found:")
    print(result.stdout)
    print(result.stderr)

# Test 3: Import Check
print("\n3) Checking Critical Imports...")
print("-" * 80)
imports = [
    "from store.search import ProductSearch, SearchAnalytics",
    "from store.models import LoginAttempt, SuspiciousActivity, UserProfile",
    "from store import views",
    "from store import auth_views",
    "from store import order_views",
]

for imp in imports:
    try:
        exec(imp)
        print(f"OK: {imp}")
    except Exception as e:
        print(f"ERROR: {imp}")
        print(f"   Error: {str(e)}")

# Test 4: File Check
print("\n4) Checking for Debug Files...")
print("-" * 80)
debug_files = ['response.html', 'tools/home_response.html']
for f in debug_files:
    if os.path.exists(f):
        print(f"FOUND debug file: {f}")
    else:
        print(f"{f} - not found (good)")

print("\n" + "=" * 80)
print("DIAGNOSIS COMPLETE")
print("=" * 80)
