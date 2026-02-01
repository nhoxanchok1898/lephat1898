"""
Advanced Search System - Django ORM based
Full-text search, faceted filtering, autocomplete, and analytics
"""
from django.db.models import Q, Count, Avg
from django.core.paginator import Paginator
from .models import Product, SearchQuery, Brand, Category
from django.contrib.auth.models import User


class ProductSearch:
    """Advanced product search with filtering and analytics"""
    
    def __init__(self):
        self.results = Product.objects.filter(is_active=True)
    
    def search(self, query_text, filters=None, sort_by=None, page=1, per_page=20):
        """
        Full-text search with filters
        
        Args:
            query_text: Search query string
            filters: Dict of filters (category, brand, price_min, price_max, rating, in_stock, on_sale)
            sort_by: Sort option (price_asc, price_desc, rating, newest, bestseller)
            page: Page number for pagination
            per_page: Results per page
        
        Returns:
            dict with results, pagination, facets
        """
        # Full-text search
        if query_text:
            self.results = self._full_text_search(query_text)
        
        # Apply filters
        if filters:
            self.results = self._apply_filters(filters)
        
        # Apply sorting
        if sort_by:
            self.results = self._apply_sorting(sort_by)
        
        # Get facets for filtering UI
        facets = self._get_facets()
        
        # Paginate results
        paginator = Paginator(self.results, per_page)
        page_obj = paginator.get_page(page)
        
        return {
            'results': list(page_obj.object_list),
            'page': page,
            'total_pages': paginator.num_pages,
            'total_results': paginator.count,
            'has_next': page_obj.has_next(),
            'has_previous': page_obj.has_previous(),
            'facets': {
                'categories': list(facets.get('categories') or []),
                'brands': list(facets.get('brands') or []),
                'price_ranges': facets.get('price_ranges'),
                'ratings': facets.get('ratings'),
            },
        }
    
    def _full_text_search(self, query):
        """Full-text search across name, description, brand"""
        return self.results.filter(
            Q(name__icontains=query) |
            Q(description__icontains=query) |
            Q(brand__name__icontains=query)
        ).distinct()
    
    def _apply_filters(self, filters):
        """Apply faceted filters"""
        queryset = self.results
        
        # Category filter
        if filters.get('category'):
            queryset = queryset.filter(category_id=filters['category'])
        
        # Brand filter
        if filters.get('brand'):
            queryset = queryset.filter(brand_id=filters['brand'])
        
        # Price range filter
        if filters.get('price_min'):
            queryset = queryset.filter(price__gte=filters['price_min'])
        if filters.get('price_max'):
            queryset = queryset.filter(price__lte=filters['price_max'])
        
        # Rating filter
        if filters.get('min_rating'):
            queryset = queryset.filter(rating__gte=filters['min_rating'])
        
        # In stock filter
        if filters.get('in_stock'):
            queryset = queryset.filter(stock_quantity__gt=0)
        
        # On sale filter
        if filters.get('on_sale'):
            queryset = queryset.filter(is_on_sale=True)
        
        # New arrivals filter
        if filters.get('new_arrivals'):
            queryset = queryset.filter(is_new=True)
        
        return queryset
    
    def _apply_sorting(self, sort_by):
        """Apply sorting to results"""
        sort_options = {
            'price_asc': 'price',
            'price_desc': '-price',
            'rating': '-rating',
            'newest': '-created_at',
            'name': 'name',
            'bestseller': '-view_count',  # Using view_count as proxy for bestseller
        }
        
        order = sort_options.get(sort_by, '-created_at')
        return self.results.order_by(order)
    
    def _get_facets(self):
        """Get available facets for filtering"""
        return {
            'categories': Category.objects.filter(
                product__in=self.results
            ).distinct().values('id', 'name'),
            'brands': Brand.objects.filter(
                product__in=self.results
            ).distinct().values('id', 'name'),
            'price_ranges': self._get_price_ranges(),
            'ratings': [5, 4, 3, 2, 1],
        }
    
    def _get_price_ranges(self):
        """Get price range buckets"""
        return [
            {'min': 0, 'max': 100000, 'label': 'Dưới 100k'},
            {'min': 100000, 'max': 500000, 'label': '100k - 500k'},
            {'min': 500000, 'max': 1000000, 'label': '500k - 1tr'},
            {'min': 1000000, 'max': None, 'label': 'Trên 1tr'},
        ]
    
    def autocomplete(self, prefix, limit=10):
        """
        Get autocomplete suggestions
        
        Args:
            prefix: Search prefix
            limit: Max number of suggestions
        
        Returns:
            List of suggestion dicts
        """
        if not prefix or len(prefix) < 2:
            return []
        
        # Search in product names and brands
        products = Product.objects.filter(
            Q(name__istartswith=prefix) |
            Q(brand__name__istartswith=prefix),
            is_active=True
        ).values('id', 'name', 'brand__name')[:limit]
        
        suggestions = []
        for product in products:
            suggestions.append({
                'id': product['id'],
                'text': product['name'],
                'brand': product['brand__name'],
                'type': 'product'
            })
        
        return suggestions
    
    def track_search(self, query, user=None, session_key=None, result_count=0):
        """
        Track search query for analytics
        
        Args:
            query: Search query text
            user: User instance if logged in
            session_key: Session key for anonymous users
            result_count: Number of results returned
        """
        try:
            SearchQuery.objects.create(
                query=query,
                user=user,
                session_key=session_key,
                result_count=result_count
            )
        except Exception as e:
            # Log error but don't fail the search
            print(f"Error tracking search: {e}")
    
    def get_popular_searches(self, limit=10, days=7):
        """
        Get popular search queries
        
        Args:
            limit: Max number of searches to return
            days: Look back period in days
        
        Returns:
            List of popular search queries
        """
        from django.utils import timezone
        from datetime import timedelta
        
        since = timezone.now() - timedelta(days=days)
        
        return SearchQuery.objects.filter(
            created_at__gte=since
        ).values('query').annotate(
            count=Count('id')
        ).order_by('-count')[:limit]
    
    def get_trending_searches(self, limit=10):
        """Get trending searches (increasing in popularity)"""
        from django.utils import timezone
        from datetime import timedelta
        
        # Last 24 hours
        yesterday = timezone.now() - timedelta(days=1)
        
        return SearchQuery.objects.filter(
            created_at__gte=yesterday
        ).values('query').annotate(
            count=Count('id')
        ).order_by('-count')[:limit]


class SearchAnalytics:
    """Search analytics and insights"""
    
    @staticmethod
    def get_search_stats(days=30):
        """Get search statistics"""
        from django.utils import timezone
        from datetime import timedelta
        
        since = timezone.now() - timedelta(days=days)
        
        total_searches = SearchQuery.objects.filter(created_at__gte=since).count()
        unique_queries = SearchQuery.objects.filter(
            created_at__gte=since
        ).values('query').distinct().count()
        
        zero_result_searches = SearchQuery.objects.filter(
            created_at__gte=since,
            result_count=0
        ).count()
        
        avg_results = SearchQuery.objects.filter(
            created_at__gte=since
        ).aggregate(Avg('result_count'))['result_count__avg'] or 0
        
        return {
            'total_searches': total_searches,
            'unique_queries': unique_queries,
            'zero_result_searches': zero_result_searches,
            'zero_result_rate': (zero_result_searches / total_searches * 100) if total_searches > 0 else 0,
            'avg_results_per_search': round(avg_results, 2),
        }
    
    @staticmethod
    def get_failed_searches(limit=20, days=7):
        """Get searches with zero results (for improvement)"""
        from django.utils import timezone
        from datetime import timedelta
        
        since = timezone.now() - timedelta(days=days)
        
        return SearchQuery.objects.filter(
            created_at__gte=since,
            result_count=0
        ).values('query').annotate(
            count=Count('id')
        ).order_by('-count')[:limit]
