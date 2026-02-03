from django.shortcuts import get_object_or_404
from django.http import JsonResponse
from django.views.decorators.http import require_http_methods, require_GET, require_POST
from django.contrib.admin.views.decorators import staff_member_required
from django.utils import timezone
from .models import Product, StockLevel, StockAlert, PreOrder, BackInStockNotification


@staff_member_required
@require_POST
def update_stock(request, product_id):
    """Update stock level for a product"""
    product = get_object_or_404(Product, pk=product_id)
    quantity = int(request.POST.get('quantity', 0))
    
    stock, created = StockLevel.objects.get_or_create(product=product)
    old_quantity = stock.quantity
    stock.quantity = quantity
    
    # Check if restocked
    if quantity > old_quantity:
        stock.last_restocked = timezone.now()
        
        # Notify people waiting for back in stock
        if old_quantity == 0 and quantity > 0:
            notify_back_in_stock(product)
    
    stock.save()
    
    # Create alerts if needed
    if stock.is_out_of_stock:
        StockAlert.objects.get_or_create(
            product=product,
            alert_type='out',
            resolved=False
        )
    elif stock.is_low_stock:
        StockAlert.objects.get_or_create(
            product=product,
            alert_type='low',
            resolved=False
        )
    else:
        # Resolve existing alerts
        StockAlert.objects.filter(
            product=product,
            resolved=False
        ).update(resolved=True, resolved_at=timezone.now())
    
    return JsonResponse({
        'success': True,
        'product': product.name,
        'quantity': stock.quantity,
        'is_low_stock': stock.is_low_stock,
        'is_out_of_stock': stock.is_out_of_stock,
    })


@require_GET
def check_stock(request, product_id):
    """Check stock availability for a product"""
    product = get_object_or_404(Product, pk=product_id, is_active=True)
    
    try:
        stock = product.stock
        return JsonResponse({
            'product': product.name,
            'in_stock': stock.quantity > 0,
            'quantity': stock.quantity,
            'is_low_stock': stock.is_low_stock,
        })
    except StockLevel.DoesNotExist:
        return JsonResponse({
            'product': product.name,
            'in_stock': True,  # Assume in stock if not tracked
            'quantity': None,
            'is_low_stock': False,
        })


@staff_member_required
@require_GET
def low_stock_alert_view(request):
    """View all low stock and out of stock alerts"""
    alerts = StockAlert.objects.filter(resolved=False).select_related('product')
    
    alert_data = []
    for alert in alerts:
        try:
            stock = alert.product.stock
            alert_data.append({
                'product_id': alert.product.id,
                'product_name': alert.product.name,
                'alert_type': alert.get_alert_type_display(),
                'current_stock': stock.quantity,
                'created_at': alert.created_at.isoformat(),
            })
        except StockLevel.DoesNotExist:
            continue
    
    return JsonResponse({'alerts': alert_data})


@require_POST
def pre_order_create(request, product_id):
    """Create a pre-order for out of stock product"""
    product = get_object_or_404(Product, pk=product_id, is_active=True)
    
    customer_email = request.POST.get('email')
    customer_name = request.POST.get('name')
    quantity = int(request.POST.get('quantity', 1))
    
    if not customer_email or not customer_name:
        return JsonResponse({'error': 'Email and name are required'}, status=400)
    
    pre_order = PreOrder.objects.create(
        product=product,
        customer_email=customer_email,
        customer_name=customer_name,
        quantity=quantity
    )
    
    return JsonResponse({
        'success': True,
        'pre_order_id': pre_order.id,
        'message': 'Pre-order created successfully. You will be notified when the product is available.'
    })


@require_POST
def back_in_stock_notification(request, product_id):
    """Sign up for back in stock notification"""
    product = get_object_or_404(Product, pk=product_id, is_active=True)
    email = request.POST.get('email')
    
    if not email:
        return JsonResponse({'error': 'Email is required'}, status=400)
    
    notification, created = BackInStockNotification.objects.get_or_create(
        product=product,
        email=email
    )
    
    if created:
        message = 'You will be notified when this product is back in stock.'
    else:
        message = 'You are already subscribed to notifications for this product.'
    
    return JsonResponse({
        'success': True,
        'message': message
    })


def notify_back_in_stock(product):
    """Notify users waiting for product to be back in stock"""
    notifications = BackInStockNotification.objects.filter(
        product=product,
        notified=False
    )
    
    for notification in notifications:
        # In production, send actual email
        # For now, just mark as notified
        notification.notified = True
        notification.notified_at = timezone.now()
        notification.save()
    
    return notifications.count()
