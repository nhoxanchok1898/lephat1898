from rest_framework import viewsets, status, filters
from rest_framework.decorators import api_view, action, permission_classes, authentication_classes
from rest_framework.response import Response
from rest_framework.permissions import IsAuthenticatedOrReadOnly, IsAuthenticated, AllowAny
from django.shortcuts import get_object_or_404
from django.utils import timezone
from decimal import Decimal
from .models import Product, Order, ProductView, Brand, Category, Cart, CartItem, Coupon
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
@authentication_classes([])
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
@authentication_classes([])
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
@authentication_classes([])
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
@authentication_classes([])
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


# Cart API endpoints for testing
@api_view(['POST'])
@permission_classes([IsAuthenticated])
def cart_add_item_api(request):
    """Add item to cart (API for tests)"""
    product_id = request.data.get('product_id')
    quantity = int(request.data.get('quantity', 1))
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.objects.get(pk=product_id, is_active=True)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Get or create cart for user
    cart, created = Cart.objects.get_or_create(user=request.user)
    
    # Get or create cart item
    cart_item, created = CartItem.objects.get_or_create(
        cart=cart,
        product=product,
        defaults={'quantity': quantity, 'price': product.get_price()}
    )
    
    if not created:
        cart_item.quantity += quantity
        cart_item.save()
    
    # Calculate totals
    total_items = sum(item.quantity for item in cart.items.all())
    subtotal = sum(item.get_total_price() for item in cart.items.all())
    
    return Response({
        'success': True,
        'product': ProductSerializer(product).data,
        'quantity': cart_item.quantity,
        'total_items': total_items,
        'subtotal': subtotal
    }, status=status.HTTP_200_OK)


@api_view(['POST'])
@permission_classes([IsAuthenticated])
def cart_update_item_api(request):
    """Update cart item quantity (API for tests)"""
    product_id = request.data.get('product_id')
    quantity = int(request.data.get('quantity', 1))
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.objects.get(pk=product_id, is_active=True)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Get cart
    try:
        cart = Cart.objects.get(user=request.user)
        cart_item = CartItem.objects.get(cart=cart, product=product)
        cart_item.quantity = quantity
        cart_item.save()
    except (Cart.DoesNotExist, CartItem.DoesNotExist):
        return Response({'error': 'Item not in cart'}, status=status.HTTP_404_NOT_FOUND)
    
    # Calculate totals
    total_items = sum(item.quantity for item in cart.items.all())
    subtotal = sum(item.get_total_price() for item in cart.items.all())
    
    return Response({
        'success': True,
        'quantity': cart_item.quantity,
        'total_items': total_items,
        'subtotal': subtotal
    }, status=status.HTTP_200_OK)


@api_view(['POST'])
@permission_classes([IsAuthenticated])
def cart_remove_item_api(request):
    """Remove item from cart (API for tests)"""
    product_id = request.data.get('product_id')
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.objects.get(pk=product_id)
        cart = Cart.objects.get(user=request.user)
        cart_item = CartItem.objects.get(cart=cart, product=product)
        cart_item.delete()
    except (Product.DoesNotExist, Cart.DoesNotExist, CartItem.DoesNotExist):
        return Response({'error': 'Item not in cart'}, status=status.HTTP_404_NOT_FOUND)
    
    # Calculate totals
    total_items = sum(item.quantity for item in cart.items.all())
    subtotal = sum(item.get_total_price() for item in cart.items.all())
    
    return Response({
        'success': True,
        'total_items': total_items,
        'subtotal': subtotal
    }, status=status.HTTP_200_OK)


@api_view(['POST'])
@permission_classes([IsAuthenticated])
def cart_clear_api(request):
    """Clear all items from cart (API for tests)"""
    try:
        cart = Cart.objects.get(user=request.user)
        cart.items.all().delete()
    except Cart.DoesNotExist:
        pass
    
    return Response({
        'success': True,
        'total_items': 0,
        'subtotal': 0
    }, status=status.HTTP_200_OK)


@api_view(['POST'])
@permission_classes([IsAuthenticated])
def cart_apply_coupon_api(request):
    """Apply coupon to cart (API for tests)"""
    code = request.data.get('code')
    
    if not code:
        return Response({'error': 'code is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        coupon = Coupon.objects.get(code=code, is_active=True)
    except Coupon.DoesNotExist:
        return Response({'error': 'Invalid coupon code'}, status=status.HTTP_404_NOT_FOUND)
    
    # Check if coupon is valid
    if not coupon.is_valid():
        return Response({'error': 'Coupon has expired or reached maximum uses'}, 
                       status=status.HTTP_400_BAD_REQUEST)
    
    # Get cart
    try:
        cart = Cart.objects.get(user=request.user)
    except Cart.DoesNotExist:
        return Response({'error': 'Cart is empty'}, status=status.HTTP_400_BAD_REQUEST)
    
    # Calculate totals
    subtotal = sum(item.get_total_price() for item in cart.items.all())
    
    # Check minimum purchase amount
    if subtotal < coupon.min_purchase_amount:
        return Response({
            'error': f'Minimum purchase amount is {coupon.min_purchase_amount}'
        }, status=status.HTTP_400_BAD_REQUEST)
    
    # Calculate discount
    discount_amount = coupon.calculate_discount(subtotal)
    final_total = subtotal - discount_amount
    
    return Response({
        'success': True,
        'code': code,
        'discount_amount': discount_amount,
        'subtotal': subtotal,
        'final_total': final_total
    }, status=status.HTTP_200_OK)
