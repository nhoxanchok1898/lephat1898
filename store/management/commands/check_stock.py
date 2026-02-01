from django.core.management.base import BaseCommand
from django.utils import timezone
from store.models import StockLevel, StockAlert


class Command(BaseCommand):
    help = 'Check stock levels and create alerts for low stock'

    def handle(self, *args, **options):
        alerts_created = 0
        
        # Check all stock levels
        for stock in StockLevel.objects.all():
            if stock.is_low_stock() and not stock.is_out_of_stock():
                # Check if alert already exists
                existing_alert = StockAlert.objects.filter(
                    product=stock.product,
                    is_resolved=False
                ).exists()
                
                if not existing_alert:
                    StockAlert.objects.create(
                        product=stock.product,
                        message=f'Low stock: Only {stock.available_quantity()} items left'
                    )
                    alerts_created += 1
            
            elif stock.is_out_of_stock():
                # Check if alert already exists
                existing_alert = StockAlert.objects.filter(
                    product=stock.product,
                    is_resolved=False,
                    message__contains='Out of stock'
                ).exists()
                
                if not existing_alert:
                    StockAlert.objects.create(
                        product=stock.product,
                        message='Out of stock'
                    )
                    alerts_created += 1
        
        self.stdout.write(
            self.style.SUCCESS(f'Created {alerts_created} stock alerts')
        )
