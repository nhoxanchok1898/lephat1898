"""
Order Views
Handles order history and order details
"""
from django.shortcuts import render, get_object_or_404, redirect
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.db.models import Sum, F
from .models import Order, OrderItem


@login_required
def order_history(request):
    """
    Display user's order history
    Lists all orders associated with the user
    """
    # Get orders that match the user's username in full_name field
    # (Orders are not directly linked to User model in the current implementation)
    orders = Order.objects.filter(
        full_name__icontains=request.user.username
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
    # Since orders aren't directly linked to User, we check by username in full_name
    if not order.full_name.lower().startswith(request.user.username.lower()):
        # For staff members, allow viewing any order
        if not request.user.is_staff:
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
