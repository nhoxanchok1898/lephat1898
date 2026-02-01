from django.core.mail import send_mail
from django.template.loader import render_to_string
from django.conf import settings
from django.utils import timezone
from django.db.models import Q, F
from .models import EmailTemplate, EmailQueue, Product, Order


def send_welcome_email(user):
    """Send welcome email to new user"""
    try:
        template = EmailTemplate.objects.get(email_type='welcome', is_active=True)
        subject = template.subject
        html_content = template.html_content.format(
            user_name=user.username,
            site_url=settings.SITE_URL
        )
    except EmailTemplate.DoesNotExist:
        subject = "Welcome to Our Store!"
        html_content = f"<p>Hello {user.username},</p><p>Welcome to our store!</p>"
    
    # Queue email
    EmailQueue.objects.create(
        to_email=user.email,
        subject=subject,
        html_content=html_content,
        context_data={'user_id': user.id}
    )
    
    # Send immediately (in production, use Celery)
    try:
        send_mail(
            subject,
            html_content,
            settings.DEFAULT_FROM_EMAIL,
            [user.email],
            html_message=html_content,
            fail_silently=False,
        )
        return True
    except Exception as e:
        print(f"Error sending welcome email: {e}")
        return False


def send_order_confirmation(order):
    """Send order confirmation email"""
    try:
        template = EmailTemplate.objects.get(email_type='order_confirmation', is_active=True)
        subject = template.subject.format(order_id=order.id)
        
        # Prepare order items
        items_html = ""
        for item in order.items.all():
            items_html += f"<li>{item.product.name} x {item.quantity} - ${item.price * item.quantity}</li>"
        
        html_content = template.html_content.format(
            customer_name=order.full_name,
            order_id=order.id,
            order_items=items_html,
            order_total=sum(item.price * item.quantity for item in order.items.all()),
            site_url=settings.SITE_URL
        )
    except EmailTemplate.DoesNotExist:
        subject = f"Order Confirmation #{order.id}"
        html_content = f"<p>Thank you for your order #{order.id}!</p>"
    
    # Queue email
    email_queue = EmailQueue.objects.create(
        to_email=order.full_name,  # In production, use customer email field
        subject=subject,
        html_content=html_content,
        context_data={'order_id': order.id}
    )
    
    # Send immediately
    try:
        send_mail(
            subject,
            html_content,
            settings.DEFAULT_FROM_EMAIL,
            [order.full_name],  # Use actual email
            html_message=html_content,
            fail_silently=False,
        )
        email_queue.status = 'sent'
        email_queue.sent_at = timezone.now()
        email_queue.save()
        return True
    except Exception as e:
        email_queue.status = 'failed'
        email_queue.error_message = str(e)
        email_queue.save()
        return False


def send_cart_abandonment(user, cart_items):
    """Send cart abandonment email after 24 hours"""
    try:
        template = EmailTemplate.objects.get(email_type='cart_abandonment', is_active=True)
        subject = template.subject
        
        # Prepare cart items
        items_html = ""
        for item in cart_items:
            items_html += f"<li>{item['product'].name} x {item['quantity']} - ${item['product'].price * item['quantity']}</li>"
        
        html_content = template.html_content.format(
            user_name=user.username,
            cart_items=items_html,
            site_url=settings.SITE_URL
        )
    except EmailTemplate.DoesNotExist:
        subject = "You left items in your cart"
        html_content = "<p>Don't forget about your cart!</p>"
    
    # Queue email for later (24 hours)
    EmailQueue.objects.create(
        to_email=user.email,
        subject=subject,
        html_content=html_content,
        context_data={'user_id': user.id},
        scheduled_for=timezone.now() + timezone.timedelta(hours=24)
    )


def send_back_in_stock(user, product):
    """Send notification when product is back in stock"""
    try:
        template = EmailTemplate.objects.get(email_type='back_in_stock', is_active=True)
        subject = template.subject.format(product_name=product.name)
        html_content = template.html_content.format(
            user_name=user.username if user else 'Customer',
            product_name=product.name,
            product_url=f"{settings.SITE_URL}/products/{product.id}/",
            site_url=settings.SITE_URL
        )
    except EmailTemplate.DoesNotExist:
        subject = f"{product.name} is back in stock!"
        html_content = f"<p>Good news! {product.name} is back in stock.</p>"
    
    user_email = user.email if user else None
    if not user_email:
        return False
    
    # Send immediately
    try:
        send_mail(
            subject,
            html_content,
            settings.DEFAULT_FROM_EMAIL,
            [user_email],
            html_message=html_content,
            fail_silently=False,
        )
        return True
    except Exception as e:
        print(f"Error sending back in stock email: {e}")
        return False


def send_review_notification(admin_email, review):
    """Notify admin of new review"""
    subject = f"New Review for {review.product.name}"
    html_content = f"""
    <h3>New Review Submitted</h3>
    <p><strong>Product:</strong> {review.product.name}</p>
    <p><strong>Rating:</strong> {review.rating}/5</p>
    <p><strong>Review:</strong> {review.comment}</p>
    """
    
    try:
        send_mail(
            subject,
            html_content,
            settings.DEFAULT_FROM_EMAIL,
            [admin_email],
            html_message=html_content,
            fail_silently=True,
        )
        return True
    except Exception as e:
        print(f"Error sending review notification: {e}")
        return False


def process_email_queue():
    """Process pending emails in queue (call from Celery task)"""
    now = timezone.now()
    pending_emails = EmailQueue.objects.filter(
        status='pending',
        retry_count__lt=F('max_retries')
    ).filter(
        Q(scheduled_for__isnull=True) | Q(scheduled_for__lte=now)
    )[:50]  # Process 50 at a time
    
    for email in pending_emails:
        try:
            send_mail(
                email.subject,
                email.text_content or email.html_content,
                settings.DEFAULT_FROM_EMAIL,
                [email.to_email],
                html_message=email.html_content,
                fail_silently=False,
            )
            email.status = 'sent'
            email.sent_at = timezone.now()
            email.save()
        except Exception as e:
            email.status = 'failed'
            email.error_message = str(e)
            email.retry_count += 1
            email.save()
