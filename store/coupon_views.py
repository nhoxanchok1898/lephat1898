from django.shortcuts import render, get_object_or_404, redirect
from django.http import JsonResponse
from django.views.decorators.http import require_POST, require_GET
from django.contrib.admin.views.decorators import staff_member_required
from django.contrib import messages
from django.utils import timezone
from .models import Coupon, AppliedCoupon


@require_POST
def apply_coupon(request):
    """Apply a coupon code to the cart"""
    coupon_code = request.POST.get('coupon_code', '').strip().upper()
    
    if not coupon_code:
        return JsonResponse({'error': 'Coupon code is required'}, status=400)
    
    try:
        coupon = Coupon.objects.get(code=coupon_code)
    except Coupon.DoesNotExist:
        return JsonResponse({'error': 'Invalid coupon code'}, status=404)
    
    # Validate coupon
    if not coupon.is_valid():
        return JsonResponse({'error': 'This coupon is not valid or has expired'}, status=400)
    
    # Calculate cart total
    cart = request.session.get('cart', {})
    cart_total = 0
    
    from .models import Product
    for product_id, quantity in cart.items():
        try:
            product = Product.objects.get(pk=int(product_id), is_active=True)
            cart_total += product.price * quantity
        except Product.DoesNotExist:
            continue
    
    # Check minimum purchase amount
    if cart_total < coupon.min_purchase_amount:
        return JsonResponse({
            'error': f'Minimum purchase amount is ${coupon.min_purchase_amount}'
        }, status=400)
    
    # Check user-specific coupons
    if request.user.is_authenticated and coupon.allowed_users.exists():
        if request.user not in coupon.allowed_users.all():
            return JsonResponse({'error': 'This coupon is not available for your account'}, status=403)
    
    # Check per-user usage limit
    if request.user.is_authenticated:
        user_usage = AppliedCoupon.objects.filter(
            coupon=coupon,
            user=request.user
        ).count()
        if user_usage >= coupon.max_uses_per_user:
            return JsonResponse({'error': 'You have already used this coupon'}, status=400)
    
    # Calculate discount
    discount = coupon.calculate_discount(cart_total)
    final_total = max(cart_total - discount, 0)
    
    # Store coupon in session
    request.session['applied_coupon'] = {
        'code': coupon.code,
        'discount': str(discount),
        'discount_type': coupon.discount_type,
    }
    request.session.modified = True
    
    return JsonResponse({
        'success': True,
        'coupon_code': coupon.code,
        'discount': str(discount),
        'cart_total': str(cart_total),
        'final_total': str(final_total),
        'discount_type': coupon.get_discount_type_display(),
    })


@require_POST
def remove_coupon(request):
    """Remove applied coupon from cart"""
    if 'applied_coupon' in request.session:
        del request.session['applied_coupon']
        request.session.modified = True
        return JsonResponse({'success': True, 'message': 'Coupon removed'})
    
    return JsonResponse({'error': 'No coupon applied'}, status=400)


def validate_coupon(coupon_code, cart_total):
    """Validate a coupon for given cart total"""
    try:
        coupon = Coupon.objects.get(code=coupon_code.upper())
        
        if not coupon.is_valid():
            return None, 'Coupon is not valid or has expired'
        
        if cart_total < coupon.min_purchase_amount:
            return None, f'Minimum purchase amount is ${coupon.min_purchase_amount}'
        
        return coupon, None
    except Coupon.DoesNotExist:
        return None, 'Invalid coupon code'


@staff_member_required
@require_GET
def coupon_list_admin(request):
    """List all coupons (admin view)"""
    coupons = Coupon.objects.all().order_by('-created_at')
    return render(request, 'admin/coupon_list.html', {'coupons': coupons})


@staff_member_required
def coupon_create_admin(request):
    """Create a new coupon (admin view)"""
    if request.method == 'POST':
        code = request.POST.get('code', '').strip().upper()
        description = request.POST.get('description', '')
        discount_type = request.POST.get('discount_type')
        discount_value = request.POST.get('discount_value')
        min_purchase_amount = request.POST.get('min_purchase_amount', 0)
        max_uses = request.POST.get('max_uses') or None
        max_uses_per_user = request.POST.get('max_uses_per_user', 1)
        start_date = request.POST.get('start_date')
        end_date = request.POST.get('end_date')
        
        try:
            coupon = Coupon.objects.create(
                code=code,
                description=description,
                discount_type=discount_type,
                discount_value=discount_value,
                min_purchase_amount=min_purchase_amount,
                max_uses=max_uses,
                max_uses_per_user=max_uses_per_user,
                start_date=start_date,
                end_date=end_date,
            )
            messages.success(request, f'Coupon {code} created successfully')
            return redirect('store:coupon_list_admin')
        except Exception as e:
            messages.error(request, f'Error creating coupon: {str(e)}')
    
    return render(request, 'admin/coupon_form.html', {'action': 'Create'})


@staff_member_required
def coupon_edit_admin(request, coupon_id):
    """Edit a coupon (admin view)"""
    coupon = get_object_or_404(Coupon, pk=coupon_id)
    
    if request.method == 'POST':
        coupon.description = request.POST.get('description', '')
        coupon.discount_type = request.POST.get('discount_type')
        coupon.discount_value = request.POST.get('discount_value')
        coupon.min_purchase_amount = request.POST.get('min_purchase_amount', 0)
        coupon.max_uses = request.POST.get('max_uses') or None
        coupon.max_uses_per_user = request.POST.get('max_uses_per_user', 1)
        coupon.start_date = request.POST.get('start_date')
        coupon.end_date = request.POST.get('end_date')
        coupon.is_active = request.POST.get('is_active') == 'on'
        
        try:
            coupon.save()
            messages.success(request, f'Coupon {coupon.code} updated successfully')
            return redirect('store:coupon_list_admin')
        except Exception as e:
            messages.error(request, f'Error updating coupon: {str(e)}')
    
    return render(request, 'admin/coupon_form.html', {
        'action': 'Edit',
        'coupon': coupon
    })


@staff_member_required
@require_POST
def coupon_delete_admin(request, coupon_id):
    """Delete a coupon (admin view)"""
    coupon = get_object_or_404(Coupon, pk=coupon_id)
    code = coupon.code
    coupon.delete()
    messages.success(request, f'Coupon {code} deleted successfully')
    return redirect('store:coupon_list_admin')
