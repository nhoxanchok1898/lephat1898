"""
Advanced Search Views
Full-text search, filtering, autocomplete, and analytics
"""
from django.http import JsonResponse
from django.views.decorators.http import require_GET
from django.contrib.auth.decorators import login_required
from .search import ProductSearch, SearchAnalytics
from .models import Product


@require_GET
def product_search_view(request):
    """
    Advanced product search with filters
    GET /search/?q=query&category=1&brand=2&price_min=100&price_max=1000&sort=price_asc&page=1
    """
    query = request.GET.get('q', '').strip()
    
    # Get filters from request
    filters = {}
    if request.GET.get('category'):
        filters['category'] = request.GET.get('category')
    if request.GET.get('brand'):
        filters['brand'] = request.GET.get('brand')
    if request.GET.get('price_min'):
        filters['price_min'] = float(request.GET.get('price_min'))
    if request.GET.get('price_max'):
        filters['price_max'] = float(request.GET.get('price_max'))
    if request.GET.get('min_rating'):
        filters['min_rating'] = float(request.GET.get('min_rating'))
    if request.GET.get('in_stock'):
        filters['in_stock'] = request.GET.get('in_stock') == 'true'
    if request.GET.get('on_sale'):
        filters['on_sale'] = request.GET.get('on_sale') == 'true'
    if request.GET.get('new_arrivals'):
        filters['new_arrivals'] = request.GET.get('new_arrivals') == 'true'
    
    # Get sorting and pagination
    sort_by = request.GET.get('sort', 'newest')
    page = int(request.GET.get('page', 1))
    per_page = int(request.GET.get('per_page', 20))
    
    # Perform search
    searcher = ProductSearch()
    results = searcher.search(
        query_text=query,
        filters=filters,
        sort_by=sort_by,
        page=page,
        per_page=per_page
    )
    
    # Track search
    if query:
        user = request.user if request.user.is_authenticated else None
        session_key = request.session.session_key if not user else None
        searcher.track_search(
            query=query,
            user=user,
            session_key=session_key,
            result_count=results['total_results']
        )
    
    # Convert products to dict for JSON response
    products_data = []
    for product in results['results']:
        products_data.append({
            'id': product.id,
            'name': product.name,
            'price': float(product.price),
            'sale_price': float(product.sale_price) if product.sale_price else None,
            'brand': product.brand.name,
            'category': product.category.name if product.category else None,
            'rating': float(product.rating),
            'stock_quantity': product.stock_quantity,
            'is_on_sale': product.is_on_sale,
            'is_new': product.is_new,
            'image_url': product.image.url if product.image else None,
        })
    
    return JsonResponse({
        'success': True,
        'query': query,
        'results': products_data,
        'pagination': {
            'page': results['page'],
            'total_pages': results['total_pages'],
            'total_results': results['total_results'],
            'has_next': results['has_next'],
            'has_previous': results['has_previous'],
        },
        'facets': results['facets'],
    })


@require_GET
def autocomplete_view(request):
    """
    Autocomplete suggestions
    GET /search/autocomplete/?q=prefix
    """
    prefix = request.GET.get('q', '').strip()
    limit = int(request.GET.get('limit', 10))
    
    if not prefix or len(prefix) < 2:
        return JsonResponse({
            'success': True,
            'suggestions': []
        })
    
    searcher = ProductSearch()
    suggestions = searcher.autocomplete(prefix, limit)
    
    return JsonResponse({
        'success': True,
        'suggestions': suggestions
    })


@require_GET
@login_required
def search_analytics_view(request):
    """
    Search analytics (admin/staff only)
    GET /search/analytics/
    """
    if not request.user.is_staff:
        return JsonResponse({
            'success': False,
            'error': 'Permission denied'
        }, status=403)
    
    days = int(request.GET.get('days', 30))
    
    # Get statistics
    stats = SearchAnalytics.get_search_stats(days)
    
    # Get popular and failed searches
    searcher = ProductSearch()
    popular = list(searcher.get_popular_searches(limit=20, days=days))
    trending = list(searcher.get_trending_searches(limit=10))
    failed = list(SearchAnalytics.get_failed_searches(limit=20, days=days))
    
    return JsonResponse({
        'success': True,
        'stats': stats,
        'popular_searches': popular,
        'trending_searches': trending,
        'failed_searches': failed,
    })


@require_GET
def popular_searches_view(request):
    """
    Get popular searches (public)
    GET /search/popular/
    """
    limit = int(request.GET.get('limit', 10))
    days = int(request.GET.get('days', 7))
    
    searcher = ProductSearch()
    popular = list(searcher.get_popular_searches(limit=limit, days=days))
    
    return JsonResponse({
        'success': True,
        'popular_searches': popular
    })