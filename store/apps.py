from django.apps import AppConfig


class StoreConfig(AppConfig):
    default_auto_field = 'django.db.models.BigAutoField'
    name = 'store'
    verbose_name = 'Cửa hàng'

    def ready(self):
        # Import signals to wire up Order notifications
        from . import signals  # noqa: F401
