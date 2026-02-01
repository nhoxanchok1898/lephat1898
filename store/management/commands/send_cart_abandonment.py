from django.core.management.base import BaseCommand
from store.email_utils import send_cart_abandonment_emails


class Command(BaseCommand):
    help = 'Send cart abandonment emails for carts older than 24 hours'

    def handle(self, *args, **options):
        send_cart_abandonment_emails()
        self.stdout.write(
            self.style.SUCCESS('Cart abandonment emails sent')
        )
