from django.core.management.base import BaseCommand
from store.models import Brand, Category, Product


class Command(BaseCommand):
    help = 'Seed store with sample brands, categories and products'

    def handle(self, *args, **options):
        Brand.objects.all().delete()
        Category.objects.all().delete()
        Product.objects.all().delete()

        b1 = Brand.objects.create(name='Dulux')
        b2 = Brand.objects.create(name='Nippon')
        b3 = Brand.objects.create(name='Jotun')

        c1 = Category.objects.create(name='Sơn nước')
        c2 = Category.objects.create(name='Bột bả')
        c3 = Category.objects.create(name='Chống thấm')

        Product.objects.create(name='Dulux Interior 5L', brand=b1, category=c1, price=250000.00, unit_type=Product.UNIT_LIT, volume=5, is_active=True)
        Product.objects.create(name='Dulux Exterior 18L', brand=b1, category=c1, price=750000.00, unit_type=Product.UNIT_LIT, volume=18, is_active=True)
        Product.objects.create(name='Nippon Powder 25KG', brand=b2, category=c2, price=400000.00, unit_type=Product.UNIT_KG, volume=25, is_active=True)
        Product.objects.create(name='Jotun Waterproof 20L', brand=b3, category=c3, price=900000.00, unit_type=Product.UNIT_LIT, volume=20, is_active=True)

        self.stdout.write(self.style.SUCCESS('Sample store data created.'))
