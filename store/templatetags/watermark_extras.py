from django import template
from django.core.files.storage import default_storage
import os

register = template.Library()

@register.filter
def watermarked(field):
    """Return URL of `_wm.png` version if it exists in storage, else original url."""
    if not field:
        return ''
    name = getattr(field, 'name', None)
    if not name:
        try:
            return field.url
        except Exception:
            return ''
    base, _ext = os.path.splitext(name)
    wm_name = f"{base}_wm.png"
    try:
        if default_storage.exists(wm_name):
            return default_storage.url(wm_name)
    except Exception:
        pass
    try:
        return field.url
    except Exception:
        return ''
