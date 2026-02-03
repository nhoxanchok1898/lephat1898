from django.core.mail import send_mail
from django.conf import settings
from django.template.loader import render_to_string
from django.utils.html import strip_tags
from django.utils import timezone
from .models import EmailQueue, EmailTemplate


def queue_email(to_email, subject, html_content, text_content=''):
    """Add an email to the queue for sending"""
    if not text_content:
        text_content = strip_tags(html_content)
    
    EmailQueue.objects.create(
        to_email=to_email,
        subject=subject,
        html_content=html_content,
        text_content=text_content
    )


def send_queued_emails(max_emails=50):
    """Send queued emails (called by management command or celery task)"""
    pending_emails = EmailQueue.objects.filter(
        status='pending'
    ).order_by('created_at')[:max_emails]
    
    sent_count = 0
    failed_count = 0
    
    for email in pending_emails:
        try:
            send_mail(
                subject=email.subject,
                message=email.text_content,
                from_email=settings.DEFAULT_FROM_EMAIL,
                recipient_list=[email.to_email],
                html_message=email.html_content,
                fail_silently=False
            )
            email.status = 'sent'
            email.sent_at = timezone.now()
            email.save()
            sent_count += 1
        except Exception as e:
            email.retry_count += 1
            if email.retry_count >= email.max_retries:
                email.status = 'failed'
            email.error_message = str(e)
            email.save()
            failed_count += 1
    
    return sent_count, failed_count


def send_order_confirmation_email(order):
    """Send order confirmation email"""
    subject = f'Order Confirmation #{order.id}'
    
    # Try to use template if exists
    try:
        template = EmailTemplate.objects.get(template_type='order_confirmation')
        html_content = template.html_content.format(
            order_id=order.id,
            customer_name=order.full_name,
            order_date=order.created_at.strftime('%Y-%m-%d'),
        )
    except EmailTemplate.DoesNotExist:
        # Fallback to simple text
        items_text = []
        total = 0
        for item in order.items.all():
            items_text.append(f"{item.product.name} x{item.quantity} @${item.price}")
            total += item.quantity * item.price
        
        html_content = f"""
        <h2>Order Confirmation</h2>
        <p>Thank you for your order!</p>
        <p><strong>Order ID:</strong> #{order.id}</p>
        <p><strong>Name:</strong> {order.full_name}</p>
        <p><strong>Phone:</strong> {order.phone}</p>
        <p><strong>Address:</strong> {order.address}</p>
        <h3>Items:</h3>
        <ul>
        {"".join(f"<li>{item}</li>" for item in items_text)}
        </ul>
        <p><strong>Total:</strong> ${total}</p>
        """
    
    # For demo, we don't have email addresses linked to orders
    # In production, you'd get email from user account
    queue_email('customer@example.com', subject, html_content)


def send_welcome_email(user):
    """Send welcome email to new users"""
    subject = 'Welcome to Our Store!'
    
    try:
        template = EmailTemplate.objects.get(template_type='welcome')
        html_content = template.html_content.format(
            username=user.username,
        )
    except EmailTemplate.DoesNotExist:
        html_content = f"""
        <h2>Welcome to Our Store!</h2>
        <p>Hi {user.username},</p>
        <p>Thank you for registering with us!</p>
        <p>Start shopping now and enjoy great deals.</p>
        """
    
    queue_email(user.email, subject, html_content)


def send_back_in_stock_notifications(product):
    """Send notifications when product is back in stock"""
    from .models import BackInStockNotification
    
    notifications = BackInStockNotification.objects.filter(
        product=product,
        notified=False
    )
    
    subject = f'{product.name} is Back in Stock!'
    html_content = f"""
    <h2>{product.name} is Back in Stock!</h2>
    <p>The product you were waiting for is now available.</p>
    <p>Click here to view: <a href="#">View Product</a></p>
    """
    
    for notification in notifications:
        queue_email(notification.email, subject, html_content)
        notification.notified = True
        notification.notified_at = timezone.now()
        notification.save()


def send_cart_abandonment_emails():
    """Send cart abandonment emails (called by scheduled task)"""
    from .models import CartAbandonment
    from datetime import timedelta
    
    twenty_four_hours_ago = timezone.now() - timedelta(hours=24)
    
    abandoned_carts = CartAbandonment.objects.filter(
        created_at__lte=twenty_four_hours_ago,
        notified=False,
        email__isnull=False
    ).exclude(email='')
    
    subject = "Don't forget your items!"
    
    for cart in abandoned_carts:
        html_content = f"""
        <h2>You left items in your cart!</h2>
        <p>Hi there,</p>
        <p>You have items waiting in your shopping cart.</p>
        <p>Total: ${cart.total_amount}</p>
        <p><a href="#">Complete your purchase now</a></p>
        """
        
        queue_email(cart.email, subject, html_content)
        cart.notified = True
        cart.notified_at = timezone.now()
        cart.save()
