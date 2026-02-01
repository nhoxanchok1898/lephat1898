from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.http import JsonResponse
from django.views.decorators.http import require_POST

from .models import Wishlist, Product


@login_required
def wishlist_view(request):
    """Display user's wishlist"""
    wishlist_items = Wishlist.objects.filter(user=request.user).select_related('product')
    
    # Calculate total value
    total_value = sum(item.product.price for item in wishlist_items)
    
    return render(request, 'wishlist/wishlist.html', {
        'wishlist_items': wishlist_items,
        'total_value': total_value
    })


@login_required
@require_POST
def wishlist_add(request, pk):
    """Add product to wishlist"""
    product = get_object_or_404(Product, pk=pk, is_active=True)
    
    wishlist_item, created = Wishlist.objects.get_or_create(
        user=request.user,
        product=product
    )
    
    if created:
        messages.success(request, f'{product.name} added to your wishlist!')
    else:
        messages.info(request, f'{product.name} is already in your wishlist.')
    
    # Return JSON for AJAX requests
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'created': created,
            'message': f'{product.name} added to wishlist' if created else 'Already in wishlist'
        })
    
    return redirect('store:product_detail', pk=pk)


@login_required
@require_POST
def wishlist_remove(request, pk):
    """Remove product from wishlist"""
    product = get_object_or_404(Product, pk=pk)
    
    deleted_count, _ = Wishlist.objects.filter(
        user=request.user,
        product=product
    ).delete()
    
    if deleted_count > 0:
        messages.success(request, f'{product.name} removed from your wishlist.')
    
    # Return JSON for AJAX requests
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'removed': deleted_count > 0
        })
    
    return redirect('store:wishlist')


@login_required
def wishlist_share(request):
    """Generate shareable link for wishlist"""
    wishlist_items = Wishlist.objects.filter(user=request.user).select_related('product')
    
    if request.method == 'POST':
        # In a real application, you would generate a unique token and store it
        # For now, we'll just show the items
        share_url = request.build_absolute_uri(f'/wishlist/shared/{request.user.username}/')
        messages.success(request, f'Shareable link: {share_url}')
        return redirect('store:wishlist')
    
    return render(request, 'wishlist/share.html', {'wishlist_items': wishlist_items})


def wishlist_shared_view(request, username):
    """Public view of a shared wishlist"""
    from django.contrib.auth.models import User
    
    user = get_object_or_404(User, username=username)
    wishlist_items = Wishlist.objects.filter(user=user).select_related('product')
    
    return render(request, 'wishlist/shared.html', {
        'owner': user,
        'wishlist_items': wishlist_items
    })


@login_required
def wishlist_check(request, pk):
    """Check if product is in user's wishlist (AJAX)"""
    in_wishlist = Wishlist.objects.filter(
        user=request.user,
        product_id=pk
    ).exists()
    
    return JsonResponse({'in_wishlist': in_wishlist})
