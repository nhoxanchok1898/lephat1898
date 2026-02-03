from django.core.paginator import Paginator
from django.utils import timezone
from datetime import timedelta
from django.db.models import Count, Avg
from .models import Product, SearchQuery


class ProductSearch:
    def __init__(self, products=None):
        # Accept an iterable or a queryset; default to all active products
        if products is None:
            self.products = Product.objects.filter(is_active=True)
        else:
            self.products = products

    def _as_queryset(self):
        if hasattr(self.products, 'filter'):
            return self.products
        # assume iterable of model instances
        ids = [p.id for p in self.products]
        return Product.objects.filter(id__in=ids)

    def search(self, query=None, query_text=None, page=1, per_page=10, filters=None, sort_by=None):
        # support both parameter names for compatibility
        if query_text is not None:
            query = query_text

        qs = self._as_queryset()

        if query:
            qs = qs.filter(name__icontains=query) | qs.filter(description__icontains=query)

        # apply filters
        filters = filters or {}
        if 'brand' in filters:
            qs = qs.filter(brand_id=filters['brand'])
        if 'category' in filters:
            qs = qs.filter(category_id=filters['category'])
        if 'price_min' in filters:
            qs = qs.filter(price__gte=filters['price_min'])
        if 'price_max' in filters:
            qs = qs.filter(price__lte=filters['price_max'])
        if filters.get('in_stock'):
            qs = qs.filter(stock_quantity__gt=0)
        if filters.get('on_sale'):
            qs = qs.filter(is_on_sale=True)

        # sorting
        if sort_by == 'price_asc':
            qs = qs.order_by('price')
        elif sort_by == 'price_desc':
            qs = qs.order_by('-price')
        elif sort_by == 'rating':
            qs = qs.order_by('-rating')
        else:
            qs = qs.order_by('-created_at')

        # facets (basic)
        facets = {
            'categories': list(qs.values('category__id', 'category__name').annotate(count=Count('id'))),
            'brands': list(qs.values('brand__id', 'brand__name').annotate(count=Count('id'))),
            'price_ranges': [],
            'ratings': []
        }

        paginator = Paginator(qs, per_page)
        page_obj = paginator.get_page(page)

        return {
            'results': list(page_obj.object_list),
            'page': page_obj.number,
            'total_pages': paginator.num_pages,
            'total_results': paginator.count,
            'has_next': page_obj.has_next(),
            'has_previous': page_obj.has_previous(),
            'facets': facets
        }

    def autocomplete(self, prefix, limit=10):
        if not prefix or len(prefix) < 2:
            return []
        qs = Product.objects.filter(name__icontains=prefix)[:limit]
        return [{'text': p.name} for p in qs]

    def track_search(self, query, user=None, session_key=None, result_count=0):
        SearchQuery.objects.create(user=user, query=query, session_key=session_key, result_count=result_count)

    def get_popular_searches(self, limit=10, days=7):
        since = timezone.now() - timedelta(days=days)
        qs = (
            SearchQuery.objects.filter(created_at__gte=since)
            .values('query')
            .annotate(count=Count('id'))
            .order_by('-count')[:limit]
        )
        return list(qs)

    def get_trending_searches(self, limit=10):
        # simple trending: recent distinct queries
        qs = (
            SearchQuery.objects.order_by('-created_at')
            .values('query')
            .distinct()[:limit]
        )
        return list(qs)


class SearchAnalytics:
    @classmethod
    def get_search_stats(cls, days=30):
        since = timezone.now() - timedelta(days=days)
        qs = SearchQuery.objects.filter(created_at__gte=since)
        total_searches = qs.count()
        unique_queries = qs.values('query').distinct().count()
        zero_result_searches = qs.filter(result_count=0).count()
        avg_results = qs.aggregate(avg=Avg('result_count'))['avg'] or 0

        return {
            'total_searches': total_searches,
            'unique_queries': unique_queries,
            'zero_result_searches': zero_result_searches,
            'zero_result_rate': (zero_result_searches / total_searches) if total_searches else 0,
            'avg_results_per_search': float(avg_results)
        }

    @classmethod
    def get_failed_searches(cls, limit=10, days=7):
        since = timezone.now() - timedelta(days=days)
        qs = (
            SearchQuery.objects.filter(created_at__gte=since, result_count=0)
            .values('query')
            .annotate(count=Count('id'))
            .order_by('-count')[:limit]
        )
        return list(qs)