from django.apps import AppConfig

class PaintStoreConfig(AppConfig):
    default_auto_field = 'django.db.models.BigAutoField'
    name = 'paint_store'
    
    def ready(self):
        # Import runtime admin compatibility shim so it runs when the app is ready.
        try:
            from . import admin_compat  # noqa: F401
        except Exception:
            pass