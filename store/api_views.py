"""
Django REST Framework API views for Phase 2A
"""
from rest_framework import viewsets, filters, status
from rest_framework.decorators import action, api_view, permission_classes
from rest_framework.response import Response
from rest_framework.permissions import IsAuthenticated, IsAuthenticatedOrReadOnly
from django_filters.rest_framework import DjangoFilterBackend
from django.db.models import Q, Count, Avg, F
from django.utils import timezone
from datetime import timedelta

from .models import (
    Brand, Category, Product, Order, OrderItem,
    Cart, CartItem, Coupon, Review, Wishlist,
    ProductView, SearchQuery
)
from .serializers import (
    BrandSerializer, CategorySerializer,
    ProductListSerializer, ProductDetailSerializer,
    OrderSerializer, CartSerializer, CartItemSerializer,
    CouponSerializer, ReviewSerializer, WishlistSerializer
)


class BrandViewSet(viewsets.ReadOnlyModelViewSet):
    """API endpoint for brands"""
    queryset = Brand.objects.all()
    serializer_class = BrandSerializer
    filter_backends = [filters.SearchFilter]
    search_fields = ['name']


class CategoryViewSet(viewsets.ReadOnlyModelViewSet):
    """API endpoint for categories"""
    queryset = Category.objects.all()
    serializer_class = CategorySerializer
    filter_backends = [filters.SearchFilter]
    search_fields = ['name']


class ProductViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for products with advanced search and filtering
    """
    queryset = Product.objects.filter(is_active=True).select_related('brand', 'category')
    filter_backends = [DjangoFilterBackend, filters.SearchFilter, filters.OrderingFilter]
    filterset_fields = ['brand', 'category', 'unit_type', 'is_on_sale']
    search_fields = ['name', 'brand__name', 'description']
    ordering_fields = ['price', 'created_at', 'rating', 'view_count']
    ordering = ['-created_at']

    def get_serializer_class(self):
        if self.action == 'retrieve':
            return ProductDetailSerializer
        return ProductListSerializer

    def get_queryset(self):
        queryset = super().get_queryset()

        # Advanced filtering
        price_min = self.request.query_params.get('price_min')
        price_max = self.request.query_params.get('price_max')
        in_stock = self.request.query_params.get('in_stock')
        min_rating = self.request.query_params.get('min_rating')

        if price_min:
            queryset = queryset.filter(price__gte=price_min)
        if price_max:
            queryset = queryset.filter(price__lte=price_max)
        if in_stock:
            queryset = queryset.filter(stock_quantity__gt=0)
        if min_rating:
            queryset = queryset.filter(rating__gte=min_rating)

        return queryset

    def retrieve(self, request, *args, **kwargs):
        """Track product views when retrieving a product"""
        instance = self.get_object()

        # Ensure session exists
        if not request.session.session_key:
            request.session.create()

        # Track view
        ProductView.objects.create(
            product=instance,
            user=request.user if request.user.is_authenticated else None,
            session_key=request.session.session_key
        )

        # Increment view count atomically to avoid race conditions
        Product.objects.filter(pk=instance.pk).update(view_count=F('view_count') + 1)

        serializer = self.get_serializer(instance)
        return Response(serializer.data)

    @action(detail=True, methods=['get'])
    def recommendations(self, request, pk=None):
        """Get product recommendations based on views and purchases"""
        product = self.get_object()

        # Get users who viewed this product
        viewer_ids = ProductView.objects.filter(
            product=product,
            user__isnull=False
        ).values_list('user_id', flat=True).distinct()

        # Get products also viewed by these users
        recommended_products = Product.objects.filter(
            views__user_id__in=viewer_ids,
            is_active=True
        ).exclude(id=product.id).annotate(
            view_count_total=Count('views')
        ).order_by('-view_count_total')[:10]

        serializer = ProductListSerializer(recommended_products, many=True)
        return Response(serializer.data)

    @action(detail=False, methods=['get'])
    def trending(self, request):
        """Get trending products based on recent views"""
        days = int(request.query_params.get('days', 7))
        since_date = timezone.now() - timedelta(days=days)

        trending_products = Product.objects.filter(
            is_active=True,
            views__created_at__gte=since_date
        ).annotate(
            recent_views=Count('views')
        ).order_by('-recent_views')[:20]

        serializer = ProductListSerializer(trending_products, many=True)
        return Response(serializer.data)

    @action(detail=False, methods=['get'])
    def search_suggestions(self, request):
        """Autocomplete suggestions for search"""
        query = request.query_params.get('q', '')
        if len(query) < 2:
            return Response([])

        # Get product name suggestions
        products = Product.objects.filter(
            Q(name__icontains=query) | Q(brand__name__icontains=query),
            is_active=True
        ).values('name', 'id')[:10]

        return Response(list(products))


class OrderViewSet(viewsets.ModelViewSet):
    """API endpoint for orders"""
    serializer_class = OrderSerializer
    permission_classes = [IsAuthenticated]

    def get_queryset(self):
        # Users can only see their own orders
        # For simplicity, we'll match by session or user
        return Order.objects.all().prefetch_related('items__product')

    @action(detail=True, methods=['get'])
    def tracking(self, request, pk=None):
        """Get order tracking information"""
        order = self.get_object()
        return Response({
            'order_id': order.id,
            'status': order.payment_status,
            'created_at': order.created_at,
            'updated_at': order.updated_at,
        })


class CartViewSet(viewsets.ModelViewSet):
    """API endpoint for shopping cart"""
    serializer_class = CartSerializer
    permission_classes = [IsAuthenticatedOrReadOnly]

    def get_queryset(self):
        if self.request.user.is_authenticated:
            return Cart.objects.filter(user=self.request.user).prefetch_related('items__product')
        else:
            # Ensure session exists
            if not self.request.session.session_key:
                self.request.session.create()
            session_key = self.request.session.session_key
            return Cart.objects.filter(session_key=session_key).prefetch_related('items__product')

    def get_or_create_cart(self):
        """Get or create cart for current user/session"""
        if self.request.user.is_authenticated:
            cart, created = Cart.objects.get_or_create(user=self.request.user)
        else:
            # Ensure session exists
            if not self.request.session.session_key:
                self.request.session.create()
            session_key = self.request.session.session_key
            cart, created = Cart.objects.get_or_create(session_key=session_key)
        return cart

    @action(detail=False, methods=['post'])
    def add_item(self, request):
        """Add item to cart"""
        cart = self.get_or_create_cart()
        product_id = request.data.get('product_id')
        quantity = int(request.data.get('quantity', 1))

        try:
            product = Product.objects.get(id=product_id, is_active=True)
        except Product.DoesNotExist:
            return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)

        cart_item, created = CartItem.objects.get_or_create(
            cart=cart,
            product=product,
            defaults={'quantity': quantity}
        )

        if not created:
            cart_item.quantity += quantity
            cart_item.save()

        serializer = CartSerializer(cart)
        return Response(serializer.data)

    @action(detail=False, methods=['post'])
    def update_item(self, request):
        """Update cart item quantity"""
        cart = self.get_or_create_cart()
        product_id = request.data.get('product_id')
        quantity = int(request.data.get('quantity', 1))

        try:
            cart_item = CartItem.objects.get(cart=cart, product_id=product_id)
            if quantity > 0:
                cart_item.quantity = quantity
                cart_item.save()
            else:
                cart_item.delete()
        except CartItem.DoesNotExist:
            return Response({'error': 'Item not found in cart'}, status=status.HTTP_404_NOT_FOUND)

        serializer = CartSerializer(cart)
        return Response(serializer.data)

    @action(detail=False, methods=['post'])
    def remove_item(self, request):
        """Remove item from cart"""
        cart = self.get_or_create_cart()
        product_id = request.data.get('product_id')

        try:
            cart_item = CartItem.objects.get(cart=cart, product_id=product_id)
            cart_item.delete()
        except CartItem.DoesNotExist:
            return Response({'error': 'Item not found in cart'}, status=status.HTTP_404_NOT_FOUND)

        serializer = CartSerializer(cart)
        return Response(serializer.data)

    @action(detail=False, methods=['post'])
    def clear(self, request):
        """Clear all items from cart"""
        cart = self.get_or_create_cart()
        cart.items.all().delete()
        serializer = CartSerializer(cart)
        return Response(serializer.data)

    @action(detail=False, methods=['post'])
    def apply_coupon(self, request):
        """Apply coupon code to cart"""
        cart = self.get_or_create_cart()
        coupon_code = request.data.get('code')

        try:
            coupon = Coupon.objects.get(code=coupon_code)
            if not coupon.is_valid():
                return Response({'error': 'Invalid or expired coupon'}, status=status.HTTP_400_BAD_REQUEST)

            total = sum(item.get_total_price() for item in cart.items.all())
            discounted_total = coupon.apply_discount(total)
            discount_amount = total - discounted_total

            return Response({
                'original_total': total,
                'discount_amount': discount_amount,
                'final_total': discounted_total,
                'coupon_code': coupon.code
            })
        except Coupon.DoesNotExist:
            return Response({'error': 'Coupon not found'}, status=status.HTTP_404_NOT_FOUND)


class ReviewViewSet(viewsets.ModelViewSet):
    """API endpoint for product reviews"""
    serializer_class = ReviewSerializer
    permission_classes = [IsAuthenticatedOrReadOnly]
    filter_backends = [DjangoFilterBackend, filters.OrderingFilter]
    filterset_fields = ['product', 'rating']
    ordering_fields = ['created_at', 'rating']
    ordering = ['-created_at']

    def get_queryset(self):
        return Review.objects.all().select_related('product', 'user')

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class WishlistViewSet(viewsets.ModelViewSet):
    """API endpoint for wishlist"""
    serializer_class = WishlistSerializer
    permission_classes = [IsAuthenticated]

    def get_queryset(self):
        return Wishlist.objects.filter(user=self.request.user).prefetch_related('products')

    def get_or_create_wishlist(self):
        wishlist, created = Wishlist.objects.get_or_create(user=self.request.user)
        return wishlist

    @action(detail=False, methods=['post'])
    def add_product(self, request):
        """Add product to wishlist"""
        wishlist = self.get_or_create_wishlist()
        product_id = request.data.get('product_id')

        try:
            product = Product.objects.get(id=product_id, is_active=True)
            wishlist.products.add(product)
            serializer = WishlistSerializer(wishlist)
            return Response(serializer.data)
        except Product.DoesNotExist:
            return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)

    @action(detail=False, methods=['post'])
    def remove_product(self, request):
        """Remove product from wishlist"""
        wishlist = self.get_or_create_wishlist()
        product_id = request.data.get('product_id')

        try:
            product = Product.objects.get(id=product_id)
            wishlist.products.remove(product)
            serializer = WishlistSerializer(wishlist)
            return Response(serializer.data)
        except Product.DoesNotExist:
            return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)


@api_view(['GET'])
def analytics_overview(request):
    """Get analytics overview"""
    total_products = Product.objects.filter(is_active=True).count()
    total_orders = Order.objects.count()
    total_revenue = sum(
        item.price * item.quantity
        for order in Order.objects.all()
        for item in order.items.all()
    )

    # Top selling products
    top_products = Product.objects.filter(is_active=True).order_by('-view_count')[:10]

    # Recent searches
    recent_searches = SearchQuery.objects.order_by('-created_at')[:10]

    return Response({
        'total_products': total_products,
        'total_orders': total_orders,
        'total_revenue': total_revenue,
        'top_products': ProductListSerializer(top_products, many=True).data,
        'recent_searches': [{'query': sq.query, 'results': sq.results_count} for sq in recent_searches],
    })


@api_view(['POST'])
@permission_classes([IsAuthenticated])
def track_search(request):
    """Track search queries for analytics"""
    query = request.data.get('query', '')
    results_count = request.data.get('results_count', 0)

    if query:
        SearchQuery.objects.create(
            query=query,
            user=request.user if request.user.is_authenticated else None,
            session_key=request.session.session_key,
            results_count=results_count
        )

    return Response({'status': 'tracked'})
