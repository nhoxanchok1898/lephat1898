"""
Order Views
Handles order history and order details
"""
from django.shortcuts import render, get_object_or_404, redirect
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.db.models import Sum, F
from django.db.models import Q
from django.views.decorators.http import require_POST
from .models import Order, OrderItem


@login_required
def order_history(request):
    """
    Display user's order history
    Lists all orders associated with the user
    """
    orders = Order.objects.filter(
        Q(user=request.user) | Q(full_name__icontains=request.user.username)
    ).prefetch_related('items__product').order_by('-created_at')
    
    # Alternative: if you want to show all orders for testing
    # You can modify this to show orders based on email or other criteria
    
    # Calculate total spent
    total_spent = 0
    for order in orders:
        order_total = sum(
            item.price * item.quantity 
            for item in order.items.all()
        )
        order.total = order_total
        total_spent += order_total
    
    context = {
        'orders': orders,
        'total_spent': total_spent,
        'order_count': orders.count(),
    }
    
    return render(request, 'orders/order_history.html', context)


@login_required
def order_detail(request, order_id):
    """
    Display detailed information for a specific order
    """
    # Get the order
    order = get_object_or_404(Order, id=order_id)
    
    # Security check: verify the order belongs to the current user
    if order.user and order.user != request.user and not request.user.is_staff:
        messages.error(request, 'You do not have permission to view this order.')
        return redirect('store:order_history')
    elif not order.user:
        if not order.full_name.lower().startswith(request.user.username.lower()) and not request.user.is_staff:
            messages.error(request, 'You do not have permission to view this order.')
            return redirect('store:order_history')
    
    # Get order items with product details
    order_items = order.items.select_related('product').all()
    
    # Calculate order totals
    subtotal = sum(item.price * item.quantity for item in order_items)
    
    # Calculate tax (assume 10% for example)
    tax_rate = 0.10
    tax = subtotal * tax_rate
    
    # Calculate shipping (free for orders over $100)
    shipping = 0 if subtotal > 100 else 10
    
    # Total
    total = subtotal + tax + shipping
    
    context = {
        'order': order,
        'order_items': order_items,
        'subtotal': subtotal,
        'tax': tax,
        'tax_rate': tax_rate * 100,
        'shipping': shipping,
        'total': total,
    }
    
    return render(request, 'orders/order_detail.html', context)


@login_required
@require_POST
def order_cancel(request, order_id):
    order = get_object_or_404(Order, id=order_id)

    # Access control: owner or staff
    if not (request.user.is_staff or (order.user and order.user == request.user)):
        messages.error(request, 'Bạn không có quyền hủy đơn này.')
        return redirect('store:order_detail', order_id=order_id)

    # Only allow cancel in pending or processing
    if order.status not in (Order.STATUS_PENDING, Order.STATUS_PROCESSING):
        messages.info(request, 'Đơn hàng không thể hủy ở trạng thái hiện tại.')
        return redirect('store:order_detail', order_id=order_id)

    # Idempotent: if already canceled, no change
    if order.status == Order.STATUS_CANCELED:
        messages.info(request, 'Đơn hàng đã được hủy trước đó.')
        return redirect('store:order_detail', order_id=order_id)

    order.status = Order.STATUS_CANCELED
    order.save()
    messages.success(request, 'Đơn hàng đã được hủy.')
    return redirect('store:order_detail', order_id=order_id)
