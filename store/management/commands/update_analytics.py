from django.core.management.base import BaseCommand
from django.utils import timezone
from django.db.models import Sum, Count
from django.contrib.auth.models import User
from store.models import Order, OrderItem, OrderAnalytics, UserAnalytics


class Command(BaseCommand):
    help = 'Update daily analytics data'

    def handle(self, *args, **options):
        today = timezone.now().date()
        
        # Update Order Analytics
        orders_today = Order.objects.filter(created_at__date=today)
        order_count = orders_today.count()
        
        # Calculate revenue
        revenue = 0
        items_sold = 0
        for order in orders_today:
            for item in order.items.all():
                revenue += item.price * item.quantity
                items_sold += item.quantity
        
        OrderAnalytics.objects.update_or_create(
            date=today,
            defaults={
                'order_count': order_count,
                'revenue': revenue,
                'items_sold': items_sold,
            }
        )
        
        # Update User Analytics
        new_users_today = User.objects.filter(date_joined__date=today).count()
        total_users = User.objects.count()
        # For demo purposes, set active users to 0 since orders aren't linked to users
        # In production, link orders to users and count distinct users who placed orders
        active_users = 0
        
        UserAnalytics.objects.update_or_create(
            date=today,
            defaults={
                'new_users': new_users_today,
                'active_users': active_users,
                'total_users': total_users,
            }
        )
        
        self.stdout.write(self.style.SUCCESS(f'Successfully updated analytics for {today}'))
