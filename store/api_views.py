from rest_framework import viewsets, filters, permissions
from rest_framework.decorators import action
from rest_framework.response import Response
from rest_framework.pagination import PageNumberPagination
from django_filters.rest_framework import DjangoFilterBackend
from django.db.models import Q, Avg, Count

from .models import Product, Category, Brand, Order, OrderItem
from .serializers import (
    ProductSerializer, ProductListSerializer,
    CategorySerializer, BrandSerializer,
    OrderSerializer
)


class StandardResultsSetPagination(PageNumberPagination):
    page_size = 12
    page_size_query_param = 'page_size'
    max_page_size = 100


class ProductViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for products.
    
    list: Get all products
    retrieve: Get a single product by ID
    search: Search products
    featured: Get featured/new products
    trending: Get trending products
    """
    queryset = Product.objects.filter(is_active=True)
    pagination_class = StandardResultsSetPagination
    filter_backends = [DjangoFilterBackend, filters.SearchFilter, filters.OrderingFilter]
    filterset_fields = ['category', 'brand', 'is_new']
    search_fields = ['name', 'description', 'brand__name']
    ordering_fields = ['price', 'created_at', 'name']
    ordering = ['-created_at']
    
    def get_serializer_class(self):
        if self.action == 'list':
            return ProductListSerializer
        return ProductSerializer
    
    @action(detail=False, methods=['get'])
    def featured(self, request):
        """Get featured/new products"""
        products = self.queryset.filter(is_new=True)[:12]
        serializer = ProductListSerializer(products, many=True)
        return Response(serializer.data)
    
    @action(detail=False, methods=['get'])
    def trending(self, request):
        """Get trending products based on views"""
        from datetime import timedelta
        from django.utils import timezone
        
        seven_days_ago = timezone.now() - timedelta(days=7)
        products = self.queryset.filter(
            views__viewed_at__gte=seven_days_ago
        ).annotate(
            view_count=Count('views')
        ).order_by('-view_count')[:12]
        
        serializer = ProductListSerializer(products, many=True)
        return Response(serializer.data)
    
    @action(detail=False, methods=['get'])
    def on_sale(self, request):
        """Get products on sale"""
        products = self.queryset.filter(sale_price__isnull=False)
        page = self.paginate_queryset(products)
        if page is not None:
            serializer = ProductListSerializer(page, many=True)
            return self.get_paginated_response(serializer.data)
        serializer = ProductListSerializer(products, many=True)
        return Response(serializer.data)
    
    @action(detail=True, methods=['get'])
    def recommendations(self, request, pk=None):
        """Get recommended products for a specific product"""
        product = self.get_object()
        
        # Get products viewed by users who viewed this product
        from .models import ProductView
        viewers = ProductView.objects.filter(product=product).exclude(
            user__isnull=True
        ).values_list('user', flat=True).distinct()
        
        recommended = Product.objects.filter(
            views__user__in=viewers,
            is_active=True
        ).exclude(pk=product.pk).annotate(
            view_count=Count('views')
        ).order_by('-view_count')[:6]
        
        serializer = ProductListSerializer(recommended, many=True)
        return Response(serializer.data)


class CategoryViewSet(viewsets.ReadOnlyModelViewSet):
    """API endpoint for categories"""
    queryset = Category.objects.all()
    serializer_class = CategorySerializer
    pagination_class = None  # No pagination for categories


class BrandViewSet(viewsets.ReadOnlyModelViewSet):
    """API endpoint for brands"""
    queryset = Brand.objects.all()
    serializer_class = BrandSerializer
    pagination_class = None  # No pagination for brands


class OrderViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for orders (read-only, requires authentication)
    """
    serializer_class = OrderSerializer
    permission_classes = [permissions.IsAuthenticated]
    pagination_class = StandardResultsSetPagination
    
    def get_queryset(self):
        # Users can only see their own orders
        # For demo purposes, we'll just return all orders
        # In production, link orders to users
        return Order.objects.all().order_by('-created_at')
