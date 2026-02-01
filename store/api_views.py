from rest_framework import viewsets, status, filters
from rest_framework.decorators import api_view, action, permission_classes
from rest_framework.response import Response
from rest_framework.permissions import IsAuthenticatedOrReadOnly, IsAuthenticated, AllowAny
from django.shortcuts import get_object_or_404
from .models import Product, Order, ProductView, Brand, Category
from .serializers import (
    ProductSerializer, OrderSerializer, ProductViewSerializer,
    CartSerializer, CouponSerializer, BrandSerializer, CategorySerializer
)
from .recommendation_views import (
    get_also_viewed, get_also_bought, get_similar_products
)


class ProductViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for products
    GET /api/products/ - List all products
    GET /api/products/<id>/ - Get product details
    """
    queryset = Product.objects.filter(is_active=True)
    serializer_class = ProductSerializer
    permission_classes = [IsAuthenticatedOrReadOnly]
    filter_backends = [filters.SearchFilter, filters.OrderingFilter]
    search_fields = ['name', 'brand__name', 'category__name']
    ordering_fields = ['price', 'created_at', 'name']
    ordering = ['-created_at']
    
    @action(detail=True, methods=['get'])
    def recommendations(self, request, pk=None):
        """Get recommendations for a product"""
        product = self.get_object()
        
        also_viewed = get_also_viewed(product)[:5]
        also_bought = get_also_bought(product)[:5]
        similar = get_similar_products(product)[:5]
        
        return Response({
            'also_viewed': ProductSerializer(also_viewed, many=True).data,
            'also_bought': ProductSerializer(also_bought, many=True).data,
            'similar': ProductSerializer(similar, many=True).data,
        })
    
    @action(detail=True, methods=['post'], permission_classes=[AllowAny])
    def track_view(self, request, pk=None):
        """Track product view"""
        product = self.get_object()
        user = request.user if request.user.is_authenticated else None
        
        ProductView.objects.create(
            product=product,
            user=user,
            session_key=request.session.session_key if not user else None
        )
        
        return Response({'success': True})


class CategoryViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for categories
    GET /api/categories/ - List all categories
    GET /api/categories/<id>/ - Get category details
    """
    queryset = Category.objects.all()
    serializer_class = CategorySerializer
    permission_classes = [IsAuthenticatedOrReadOnly]


class BrandViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for brands
    GET /api/brands/ - List all brands
    GET /api/brands/<id>/ - Get brand details
    """
    queryset = Brand.objects.all()
    serializer_class = BrandSerializer
    permission_classes = [IsAuthenticatedOrReadOnly]


class OrderViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for orders
    GET /api/orders/ - List user's orders
    GET /api/orders/<id>/ - Get order details
    """
    serializer_class = OrderSerializer
    permission_classes = [IsAuthenticated]
    
    def get_queryset(self):
        # In a real app, filter by user's email or user relationship
        # For now, return all orders (staff only should see this)
        if self.request.user.is_staff:
            return Order.objects.all()
        return Order.objects.none()


@api_view(['GET'])
@permission_classes([AllowAny])
def cart_view_api(request):
    """Get current cart contents"""
    cart = request.session.get('cart', {})
    items = []
    total = 0
    
    for product_id, quantity in cart.items():
        try:
            product = Product.objects.get(pk=int(product_id), is_active=True)
            item_total = product.price * quantity
            total += item_total
            items.append({
                'product': ProductSerializer(product).data,
                'quantity': quantity,
                'item_total': str(item_total)
            })
        except Product.DoesNotExist:
            continue
    
    return Response({
        'items': items,
        'total': str(total)
    })


@api_view(['POST'])
@permission_classes([AllowAny])
def cart_add_api(request):
    """Add item to cart"""
    product_id = request.data.get('product_id')
    quantity = int(request.data.get('quantity', 1))
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.objects.get(pk=product_id, is_active=True)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Ensure session key exists
    if not request.session.session_key:
        request.session.create()
    
    cart = request.session.get('cart', {})
    product_key = str(product_id)
    
    if product_key in cart:
        cart[product_key] += quantity
    else:
        cart[product_key] = quantity
    
    request.session['cart'] = cart
    request.session.modified = True
    
    return Response({
        'success': True,
        'product': ProductSerializer(product).data,
        'quantity': cart[product_key]
    })


@api_view(['DELETE'])
@permission_classes([AllowAny])
def cart_remove_api(request, product_id):
    """Remove item from cart"""
    cart = request.session.get('cart', {})
    product_key = str(product_id)
    
    if product_key in cart:
        del cart[product_key]
        request.session['cart'] = cart
        request.session.modified = True
        return Response({'success': True})
    
    return Response({'error': 'Item not in cart'}, status=status.HTTP_404_NOT_FOUND)


@api_view(['GET'])
@permission_classes([AllowAny])
def recommendations_api(request):
    """Get personalized recommendations"""
    product_id = request.GET.get('product_id')
    
    if product_id:
        try:
            product = Product.objects.get(pk=product_id, is_active=True)
            similar = get_similar_products(product)[:10]
            return Response({
                'recommendations': ProductSerializer(similar, many=True).data
            })
        except Product.DoesNotExist:
            return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Return trending products
    trending = Product.objects.filter(is_active=True).order_by('-created_at')[:10]
    return Response({
        'recommendations': ProductSerializer(trending, many=True).data
    })
