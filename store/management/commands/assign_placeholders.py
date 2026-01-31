from django.core.management.base import BaseCommand
from django.conf import settings
from django.core.files import File
from pathlib import Path
from store.models import Product


class Command(BaseCommand):
    help = 'Assign product-placeholder.svg to products without an image'

    def handle(self, *args, **options):
        placeholder_path = Path(settings.BASE_DIR) / 'static' / 'images' / 'product-placeholder.svg'
        if not placeholder_path.exists():
            self.stdout.write(self.style.ERROR(f'Placeholder not found: {placeholder_path}'))
            return

        assigned = 0
        for p in Product.objects.all():
            if not p.image:
                with open(placeholder_path, 'rb') as fh:
                    filename = f'product_{p.pk}_placeholder.svg'
                    p.image.save(filename, File(fh), save=True)
                    assigned += 1

        self.stdout.write(self.style.SUCCESS(f'Assigned placeholder to {assigned} products'))
