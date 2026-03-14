from rest_framework import viewsets, status, filters
from rest_framework.decorators import api_view, action, permission_classes, authentication_classes
from rest_framework.response import Response
from rest_framework.permissions import IsAuthenticatedOrReadOnly, IsAuthenticated, AllowAny
from django.shortcuts import get_object_or_404
from .models import Product, Order, ProductView, Brand, Category, Cart, CartItem, Coupon
from .serializers import (
    ProductSerializer, OrderSerializer, ProductViewSerializer,
    CartSerializer, CouponSerializer, BrandSerializer, CategorySerializer
)
from .recommendation_views import (
    get_also_viewed, get_also_bought, get_similar_products
)


def _parse_int(value, field_name, default=None):
    try:
        return int(value)
    except (TypeError, ValueError):
        if default is not None:
            return default
        raise ValueError(f'{field_name} must be an integer')


def _session_cart_total_items(cart):
    total = 0
    for quantity in cart.values():
        try:
            total += int(quantity)
        except (TypeError, ValueError):
            continue
    return total


class ProductViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint for products
    GET /api/products/ - List all products
    GET /api/products/<id>/ - Get product details
    """
    queryset = Product.with_effective_stock(
        Product.objects.filter(is_active=True).select_related('brand', 'category')
    )
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
    queryset = Order.objects.all().order_by('-created_at')
    serializer_class = OrderSerializer
    permission_classes = [IsAuthenticated]
    
    def get_queryset(self):
        # In a real app, filter by user's email or user relationship
        if self.request.user.is_staff:
            return self.queryset
        return Order.objects.filter(user=self.request.user).order_by('-created_at')


@api_view(['GET'])
@authentication_classes([])
@permission_classes([AllowAny])
def cart_view_api(request):
    """Get current cart contents"""
    cart = request.session.get('cart', {})
    items = []
    total = 0
    cart_changed = False
    
    for product_id, quantity in cart.items():
        try:
            product = Product.with_effective_stock(
                Product.objects.filter(is_active=True).select_related('brand', 'category')
            ).get(pk=int(product_id))
        except (Product.DoesNotExist, ValueError, TypeError):
            cart_changed = True
            continue

        stock_state = product.get_stock_state()
        bounded_quantity = int(quantity)
        if stock_state['has_defined_stock']:
            bounded_quantity = min(max(0, bounded_quantity), stock_state['stock_available'])
            if bounded_quantity <= 0:
                cart_changed = True
                continue
            if bounded_quantity != quantity:
                cart_changed = True

        try:
            item_total = product.price * bounded_quantity
            total += item_total
            items.append({
                'product': ProductSerializer(product).data,
                'quantity': bounded_quantity,
                'item_total': str(item_total)
            })
        except Exception:
            cart_changed = True
            continue

    if cart_changed:
        request.session['cart'] = {
            str(item['product']['id']): int(item['quantity'])
            for item in items
        }
        request.session.modified = True
    
    return Response({
        'items': items,
        'total': str(total)
    })


@api_view(['POST'])
@authentication_classes([])
@permission_classes([AllowAny])
def cart_add_api(request):
    """Add item to cart"""
    """Add item to cart (session-backed, public)."""
    try:
        product_id = _parse_int(request.data.get('product_id'), 'product_id')
    except ValueError as exc:
        return Response({'error': str(exc)}, status=status.HTTP_400_BAD_REQUEST)

    quantity = _parse_int(request.data.get('quantity', 1), 'quantity', default=1)
    if quantity <= 0:
        quantity = 1
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.with_effective_stock(
            Product.objects.filter(is_active=True).select_related('brand', 'category')
        ).get(pk=product_id)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Ensure session key exists
    if not request.session.session_key:
        request.session.create()
    
    cart = request.session.get('cart', {})
    product_key = str(product_id)

    stock_state = product.get_stock_state()
    current_quantity = int(cart.get(product_key, 0))

    if stock_state['has_defined_stock'] and stock_state['stock_available'] <= 0:
        return Response({'success': False, 'error': 'out_of_stock'}, status=status.HTTP_409_CONFLICT)

    if stock_state['has_defined_stock']:
        cart[product_key] = min(current_quantity + quantity, stock_state['stock_available'])
    else:
        cart[product_key] = current_quantity + quantity
    
    request.session['cart'] = cart
    request.session.modified = True
    total_items = _session_cart_total_items(cart)
    
    return Response({
        'success': True,
        'product': ProductSerializer(product).data,
        'quantity': cart[product_key],
        'total_items': total_items
    })
    


@api_view(['DELETE'])
@authentication_classes([])
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
@authentication_classes([])
@permission_classes([AllowAny])
def recommendations_api(request):
    """Get personalized recommendations"""
    product_id = request.GET.get('product_id')
    
    if product_id:
        try:
            product = Product.with_effective_stock(
                Product.objects.filter(is_active=True).select_related('brand', 'category')
            ).get(pk=product_id)
            similar = get_similar_products(product)[:10]
            return Response({
                'recommendations': ProductSerializer(similar, many=True).data
            })
        except Product.DoesNotExist:
            return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Return trending products
    trending = Product.with_effective_stock(
        Product.objects.filter(is_active=True).select_related('brand', 'category')
    ).order_by('-created_at')[:10]
    return Response({
        'recommendations': ProductSerializer(trending, many=True).data
    })


# Cart API endpoints for testing
@api_view(['POST'])
@permission_classes([IsAuthenticated])
def cart_add_item_api(request):
    """Add item to cart (API for tests)"""
    try:
        product_id = _parse_int(request.data.get('product_id'), 'product_id')
    except ValueError as exc:
        return Response({'error': str(exc)}, status=status.HTTP_400_BAD_REQUEST)

    quantity = _parse_int(request.data.get('quantity', 1), 'quantity', default=1)
    if quantity <= 0:
        quantity = 1
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.with_effective_stock(
            Product.objects.filter(is_active=True).select_related('brand', 'category')
        ).get(pk=product_id)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    stock_state = product.get_stock_state()
    if stock_state['has_defined_stock'] and stock_state['stock_available'] <= 0:
        return Response({'success': False, 'error': 'out_of_stock'}, status=status.HTTP_409_CONFLICT)

    cart, _ = Cart.objects.get_or_create(user=request.user)
    cart_item = CartItem.objects.filter(cart=cart, product=product).first()
    current_quantity = cart_item.quantity if cart_item else 0

    bounded_quantity = current_quantity + quantity
    if stock_state['has_defined_stock']:
        bounded_quantity = min(bounded_quantity, stock_state['stock_available'])

    if cart_item is None:
        cart_item = CartItem.objects.create(
            cart=cart,
            product=product,
            quantity=bounded_quantity,
            price=product.get_price(),
        )
    else:
        cart_item.quantity = bounded_quantity
        cart_item.price = product.get_price()
        cart_item.save(update_fields=['quantity', 'price'])
    
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
    try:
        product_id = _parse_int(request.data.get('product_id'), 'product_id')
    except ValueError as exc:
        return Response({'error': str(exc)}, status=status.HTTP_400_BAD_REQUEST)

    quantity = _parse_int(request.data.get('quantity', 1), 'quantity', default=1)
    
    if not product_id:
        return Response({'error': 'product_id is required'}, status=status.HTTP_400_BAD_REQUEST)
    
    try:
        product = Product.with_effective_stock(
            Product.objects.filter(is_active=True).select_related('brand', 'category')
        ).get(pk=product_id)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=status.HTTP_404_NOT_FOUND)
    
    # Get cart
    try:
        cart = Cart.objects.get(user=request.user)
        cart_item = CartItem.objects.get(cart=cart, product=product)
    except (Cart.DoesNotExist, CartItem.DoesNotExist):
        return Response({'error': 'Item not in cart'}, status=status.HTTP_404_NOT_FOUND)

    if quantity <= 0:
        cart_item.delete()
        total_items = sum(item.quantity for item in cart.items.all())
        subtotal = sum(item.get_total_price() for item in cart.items.all())
        return Response({
            'success': True,
            'quantity': 0,
            'total_items': total_items,
            'subtotal': subtotal
        }, status=status.HTTP_200_OK)

    stock_state = product.get_stock_state()
    bounded_quantity = quantity
    if stock_state['has_defined_stock']:
        if stock_state['stock_available'] <= 0:
            cart_item.delete()
            return Response({'success': False, 'error': 'out_of_stock'}, status=status.HTTP_409_CONFLICT)
        bounded_quantity = min(quantity, stock_state['stock_available'])

    cart_item.quantity = bounded_quantity
    cart_item.price = product.get_price()
    cart_item.save(update_fields=['quantity', 'price'])
    
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
