"""
Redis Caching Layer - Performance Optimization
Implements caching strategy with automatic invalidation
"""
from django.core.cache import cache
from django.conf import settings
from functools import wraps
import hashlib
import json
import logging

logger = logging.getLogger(__name__)

# Cache timeout settings (in seconds)
CACHE_TIMEOUTS = {
    'query_results': 1800,  # 30 minutes
    'product_list': 3600,  # 1 hour
    'category': 86400,  # 24 hours
    'user_session': 3600,  # 1 hour
    'search_results': 3600,  # 1 hour
    'top_products': 43200,  # 12 hours
    'recommendations': 86400,  # 24 hours
}


def get_cache_key(prefix, *args, **kwargs):
    """
    Generate a cache key from prefix and arguments
    """
    # Serialize arguments
    key_data = {
        'args': args,
        'kwargs': sorted(kwargs.items()),
    }
    key_str = json.dumps(key_data, sort_keys=True, default=str)
    key_hash = hashlib.md5(key_str.encode()).hexdigest()
    
    return f"{prefix}:{key_hash}"


def cache_result(timeout_key='query_results', prefix=None):
    """
    Decorator to cache function results
    
    Usage:
        @cache_result(timeout_key='product_list', prefix='products')
        def get_products():
            return Product.objects.all()
    """
    def decorator(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            # Generate cache key
            cache_prefix = prefix or func.__name__
            cache_key = get_cache_key(cache_prefix, *args, **kwargs)
            
            # Try to get from cache
            cached_result = cache.get(cache_key)
            if cached_result is not None:
                logger.debug(f"Cache HIT: {cache_key}")
                return cached_result
            
            # Cache miss - compute result
            logger.debug(f"Cache MISS: {cache_key}")
            result = func(*args, **kwargs)
            
            # Store in cache
            timeout = CACHE_TIMEOUTS.get(timeout_key, 1800)
            cache.set(cache_key, result, timeout)
            
            return result
        
        return wrapper
    return decorator


class CacheManager:
    """
    Centralized cache management
    """
    
    @staticmethod
    def invalidate_product(product_id):
        """
        Invalidate all caches related to a product
        """
        patterns = [
            f"products:*",
            f"product:{product_id}:*",
            f"recommendations:*",
            f"search:*",
            f"top_products:*",
        ]
        
        for pattern in patterns:
            CacheManager._delete_pattern(pattern)
        
        logger.info(f"Invalidated cache for product {product_id}")
    
    @staticmethod
    def invalidate_category(category_id):
        """
        Invalidate all caches related to a category
        """
        patterns = [
            f"category:{category_id}:*",
            f"products:*",
        ]
        
        for pattern in patterns:
            CacheManager._delete_pattern(pattern)
        
        logger.info(f"Invalidated cache for category {category_id}")
    
    @staticmethod
    def invalidate_search():
        """
        Invalidate search results cache
        """
        CacheManager._delete_pattern("search:*")
        logger.info("Invalidated search cache")
    
    @staticmethod
    def invalidate_recommendations(user_id=None):
        """
        Invalidate recommendation cache
        """
        if user_id:
            pattern = f"recommendations:{user_id}:*"
        else:
            pattern = "recommendations:*"
        
        CacheManager._delete_pattern(pattern)
        logger.info(f"Invalidated recommendations cache for user {user_id or 'all'}")
    
    @staticmethod
    def invalidate_top_products():
        """
        Invalidate top products cache
        """
        CacheManager._delete_pattern("top_products:*")
        logger.info("Invalidated top products cache")
    
    @staticmethod
    def warm_cache():
        """
        Pre-populate cache with frequently accessed data
        """
        from .models import Product, Category
        
        logger.info("Warming cache...")
        
        # Cache top products
        try:
            from .views import get_featured_products
            get_featured_products()
        except:
            pass
        
        # Cache categories
        try:
            categories = list(Category.objects.all())
            cache.set('categories:all', categories, CACHE_TIMEOUTS['category'])
        except:
            pass
        
        logger.info("Cache warming complete")
    
    @staticmethod
    def _delete_pattern(pattern):
        """
        Delete cache keys matching pattern
        Note: This works with Redis backend
        """
        try:
            # For Redis backend
            if hasattr(cache, 'delete_pattern'):
                cache.delete_pattern(pattern)
            else:
                # Fallback for non-Redis backends
                # Just clear all cache (not ideal but safe)
                logger.warning(f"Pattern deletion not supported, skipping: {pattern}")
        except Exception as e:
            logger.exception(f"Error deleting cache pattern {pattern}: {e}")
    
    @staticmethod
    def get_stats():
        """
        Get cache statistics
        """
        stats = {
            'backend': type(cache).__name__,
            'available': True,
        }
        
        # Try to get Redis-specific stats
        try:
            if hasattr(cache, 'get_stats'):
                stats.update(cache.get_stats())
        except:
            pass
        
        return stats


# Cached query decorators for common operations

@cache_result(timeout_key='product_list', prefix='products')
def get_active_products(limit=None):
    """
    Get active products with caching
    """
    from .models import Product
    
    queryset = Product.objects.filter(is_active=True).select_related('brand', 'category')
    
    if limit:
        queryset = queryset[:limit]
    
    return list(queryset)


@cache_result(timeout_key='category', prefix='categories')
def get_all_categories():
    """
    Get all categories with caching
    """
    from .models import Category
    
    return list(Category.objects.all())


@cache_result(timeout_key='top_products', prefix='top_products')
def get_top_selling_products(limit=10):
    """
    Get top selling products with caching
    """
    from django.db.models import Sum, F
    from .models import Product
    
    return list(Product.objects.filter(
        orderitem__order__payment_status='completed'
    ).annotate(
        total_sold=Sum('orderitem__quantity')
    ).order_by('-total_sold')[:limit])


@cache_result(timeout_key='recommendations', prefix='recommendations')
def get_product_recommendations(product_id, limit=5):
    """
    Get product recommendations with caching
    """
    from .models import Product
    
    try:
        product = Product.objects.get(id=product_id)
        
        # Get related products from same category
        related = Product.objects.filter(
            category=product.category,
            is_active=True
        ).exclude(id=product_id).order_by('-rating')[:limit]
        
        return list(related)
    except Product.DoesNotExist:
        return []


def cache_user_session(user_id, data, timeout=None):
    """
    Cache user session data
    """
    key = f"user_session:{user_id}"
    timeout = timeout or CACHE_TIMEOUTS['user_session']
    cache.set(key, data, timeout)


def get_user_session(user_id):
    """
    Get cached user session data
    """
    key = f"user_session:{user_id}"
    return cache.get(key)


def invalidate_user_session(user_id):
    """
    Invalidate user session cache
    """
    key = f"user_session:{user_id}"
    cache.delete(key)


# Cache warming on startup
def warm_cache_on_startup():
    """
    Warm cache when application starts
    """
    try:
        CacheManager.warm_cache()
    except Exception as e:
        logger.exception(f"Error warming cache on startup: {e}")


# Middleware for cache hit/miss logging
class CacheLoggingMiddleware:
    """
    Middleware to log cache hits and misses
    """
    
    def __init__(self, get_response):
        self.get_response = get_response
        self.cache_hits = 0
        self.cache_misses = 0
    
    def __call__(self, request):
        response = self.get_response(request)
        
        # Add cache stats to response headers in debug mode
        if settings.DEBUG:
            response['X-Cache-Hits'] = str(self.cache_hits)
            response['X-Cache-Misses'] = str(self.cache_misses)
        
        return response


# Signal handlers for cache invalidation
def setup_cache_invalidation_signals():
    """
    Setup signal handlers for automatic cache invalidation
    """
    from django.db.models.signals import post_save, post_delete
    from .models import Product, Order, Review
    
    def invalidate_on_product_change(sender, instance, **kwargs):
        CacheManager.invalidate_product(instance.id)
        if instance.category:
            CacheManager.invalidate_category(instance.category.id)
        CacheManager.invalidate_search()
    
    def invalidate_on_order_change(sender, instance, **kwargs):
        CacheManager.invalidate_top_products()
    
    def invalidate_on_review_change(sender, instance, **kwargs):
        CacheManager.invalidate_product(instance.product.id)
    
    post_save.connect(invalidate_on_product_change, sender=Product)
    post_delete.connect(invalidate_on_product_change, sender=Product)
    
    post_save.connect(invalidate_on_order_change, sender=Order)
    
    post_save.connect(invalidate_on_review_change, sender=Review)
