from django.shortcuts import render, get_object_or_404, redirect
from .models import Order


def order_history(request):
	"""Simple order history view (minimal implementation)."""
	orders = Order.objects.all().order_by('-created_at')[:50]
	return render(request, 'store/order_history.html', {'orders': orders})


def order_detail(request, order_id):
	order = get_object_or_404(Order, pk=order_id)
	return render(request, 'store/order_detail.html', {'order': order})


# Additional simple helpers can be added here as needed by URLs.
