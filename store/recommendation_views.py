from django.shortcuts import get_object_or_404
from django.http import JsonResponse
from django.views.decorators.http import require_http_methods, require_GET
from django.db.models import Count, Q, F
from django.utils import timezone
from datetime import timedelta
from .models import Product, ProductView, ProductViewAnalytics, Order, OrderItem


@require_GET
def get_recommendations(request, product_id):
    """Get product recommendations based on viewing and purchase patterns"""
    product = get_object_or_404(Product, pk=product_id, is_active=True)
    
    # People who viewed this also viewed
    also_viewed = get_also_viewed(product)
    
    # People who bought this also bought
    also_bought = get_also_bought(product)
    
    # Similar products (same category/brand)
    similar = get_similar_products(product)
    
    return JsonResponse({
        'also_viewed': [{'id': p.id, 'name': p.name, 'price': str(p.price)} for p in also_viewed[:5]],
        'also_bought': [{'id': p.id, 'name': p.name, 'price': str(p.price)} for p in also_bought[:5]],
        'similar': [{'id': p.id, 'name': p.name, 'price': str(p.price)} for p in similar[:5]],
    })


def get_also_viewed(product):
    """Products viewed by users who also viewed this product"""
    # Get users who viewed this product
    viewers = ProductView.objects.filter(product=product).values_list('user_id', flat=True).distinct()
    
    # Get other products viewed by these users
    also_viewed = Product.objects.filter(
        views__user_id__in=viewers,
        is_active=True
    ).exclude(id=product.id).annotate(
        views_count=Count('views')
    ).order_by('-views_count')[:10]
    
    return also_viewed


def get_also_bought(product):
    """Products bought by users who also bought this product"""
    # Get orders that contain this product
    orders_with_product = OrderItem.objects.filter(product=product).values_list('order_id', flat=True)
    
    # Get other products in those orders
    also_bought = Product.objects.filter(
        orderitem__order_id__in=orders_with_product,
        is_active=True
    ).exclude(id=product.id).annotate(
        purchase_count=Count('orderitem')
    ).order_by('-purchase_count')[:10]
    
    return also_bought


def get_similar_products(product):
    """Products in same category or brand"""
    similar = Product.objects.filter(
        Q(category=product.category) | Q(brand=product.brand),
        is_active=True
    ).exclude(id=product.id).order_by('-created_at')[:10]
    
    return similar


@require_GET
def get_trending_products(request):
    """Get trending products based on recent views and sales"""
    days = int(request.GET.get('days', 7))
    limit = int(request.GET.get('limit', 10))
    
    since = timezone.now() - timedelta(days=days)
    
    # Most viewed products
    most_viewed = Product.objects.filter(
        views__viewed_at__gte=since,
        is_active=True
    ).annotate(
        recent_views=Count('views')
    ).order_by('-recent_views')[:limit]
    
    # Most sold products
    most_sold = Product.objects.filter(
        orderitem__order__created_at__gte=since,
        is_active=True
    ).annotate(
        recent_sales=Count('orderitem')
    ).order_by('-recent_sales')[:limit]
    
    return JsonResponse({
        'most_viewed': [{'id': p.id, 'name': p.name, 'price': str(p.price), 'views': p.recent_views} 
                       for p in most_viewed],
        'most_sold': [{'id': p.id, 'name': p.name, 'price': str(p.price), 'sales': p.recent_sales} 
                     for p in most_sold],
    })


@require_GET
def get_personalized_recommendations(request):
    """Get personalized recommendations based on user's browsing history"""
    if not request.user.is_authenticated:
        return JsonResponse({'error': 'Authentication required'}, status=401)
    
    # Get user's recently viewed products
    recent_views = ProductView.objects.filter(
        user=request.user
    ).order_by('-viewed_at')[:10]
    
    if not recent_views:
        # Return trending if no history
        return get_trending_products(request)
    
    # Get categories and brands user is interested in
    viewed_products = [v.product for v in recent_views]
    categories = set(p.category for p in viewed_products if p.category)
    brands = set(p.brand for p in viewed_products)
    
    # Recommend products from same categories/brands
    recommendations = Product.objects.filter(
        Q(category__in=categories) | Q(brand__in=brands),
        is_active=True
    ).exclude(
        id__in=[p.id for p in viewed_products]
    ).order_by('-created_at')[:10]
    
    return JsonResponse({
        'recommendations': [{'id': p.id, 'name': p.name, 'price': str(p.price)} 
                          for p in recommendations],
    })


@require_http_methods(["POST"])
def product_view_tracker(request, product_id):
    """Track product views for analytics"""
    product = get_object_or_404(Product, pk=product_id)
    
    user = request.user if request.user.is_authenticated else None
    session_key = request.session.session_key if not user else None
    
    # Get client IP
    x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
    if x_forwarded_for:
        ip_address = x_forwarded_for.split(',')[0]
    else:
        ip_address = request.META.get('REMOTE_ADDR')
    
    # Create view record
    ProductView.objects.create(
        product=product,
        user=user,
        session_key=session_key,
        ip_address=ip_address
    )
    
    # Update analytics
    analytics, created = ProductViewAnalytics.objects.get_or_create(product=product)
    analytics.update_view_count()
    
    return JsonResponse({'success': True})
