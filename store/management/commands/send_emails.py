from django.core.management.base import BaseCommand
from store.email_utils import send_queued_emails


class Command(BaseCommand):
    help = 'Send queued emails'

    def add_arguments(self, parser):
        parser.add_argument(
            '--max',
            type=int,
            default=50,
            help='Maximum number of emails to send in one batch'
        )

    def handle(self, *args, **options):
        max_emails = options['max']
        sent, failed = send_queued_emails(max_emails=max_emails)
        
        self.stdout.write(
            self.style.SUCCESS(
                f'Sent {sent} emails, {failed} failed'
            )
        )
