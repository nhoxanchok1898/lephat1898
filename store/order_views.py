from django.shortcuts import render, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.http import JsonResponse
from store.models import Order, OrderItem


@login_required
def order_history(request):
    """View user's order history"""
    # Get orders by matching the full_name with the user's username or email
    # In a real app, you'd have a proper foreign key to User
    orders = Order.objects.filter(
        full_name__icontains=request.user.username
    ).order_by('-created_at')
    
    return render(request, 'orders/order_history.html', {
        'orders': orders
    })


@login_required
def order_detail(request, order_id):
    """View a specific order detail"""
    order = get_object_or_404(Order, pk=order_id)
    
    # Calculate total
    total = sum(item.quantity * item.price for item in order.items.all())
    
    return render(request, 'orders/order_detail.html', {
        'order': order,
        'total': total
    })


@login_required
def order_status_api(request, order_id):
    """API endpoint to check order status"""
    order = get_object_or_404(Order, pk=order_id)
    
    return JsonResponse({
        'order_id': order.pk,
        'status': order.payment_status,
        'payment_method': order.payment_method,
        'created_at': order.created_at.isoformat(),
        'updated_at': order.updated_at.isoformat()
    })
