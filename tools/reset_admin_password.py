#!/usr/bin/env python3
import os
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'paint_store.settings')
import django
django.setup()
from django.contrib.auth import get_user_model
User = get_user_model()

USERNAME = 'lephat1898'
NEW_PASSWORD = 'ChangeMeNow!2026'
EMAIL = 'admin@example.com'

u = User.objects.filter(username=USERNAME).first()
if u:
    u.set_password(NEW_PASSWORD)
    u.is_staff = True
    u.is_superuser = True
    u.save()
    print('PASSWORD_SET')
else:
    User.objects.create_superuser(USERNAME, EMAIL, NEW_PASSWORD)
    print('USER_CREATED')
print('Username:', USERNAME)
print('New temporary password:', NEW_PASSWORD)
print('Please change this password immediately after logging in.')
