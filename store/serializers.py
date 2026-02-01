"""
Django REST Framework serializers for Phase 2A API
"""
from rest_framework import serializers
from .models import (
    Brand, Category, Product, Order, OrderItem,
    Cart, CartItem, Coupon, Review, Wishlist
)


class BrandSerializer(serializers.ModelSerializer):
    class Meta:
        model = Brand
        fields = ['id', 'name', 'logo']


class CategorySerializer(serializers.ModelSerializer):
    class Meta:
        model = Category
        fields = ['id', 'name']


class ProductListSerializer(serializers.ModelSerializer):
    """Lightweight serializer for product lists"""
    brand_name = serializers.CharField(source='brand.name', read_only=True)
    category_name = serializers.CharField(source='category.name', read_only=True)
    current_price = serializers.DecimalField(source='get_price', max_digits=12, decimal_places=2, read_only=True)
    in_stock = serializers.BooleanField(source='is_in_stock', read_only=True)

    class Meta:
        model = Product
        fields = [
            'id', 'name', 'brand', 'brand_name', 'category', 'category_name',
            'price', 'sale_price', 'current_price', 'is_on_sale',
            'stock_quantity', 'in_stock', 'rating', 'view_count',
            'image', 'created_at'
        ]


class ProductDetailSerializer(serializers.ModelSerializer):
    """Detailed serializer for single product view"""
    brand = BrandSerializer(read_only=True)
    category = CategorySerializer(read_only=True)
    current_price = serializers.DecimalField(source='get_price', max_digits=12, decimal_places=2, read_only=True)
    in_stock = serializers.BooleanField(source='is_in_stock', read_only=True)

    class Meta:
        model = Product
        fields = [
            'id', 'name', 'brand', 'category', 'description',
            'price', 'sale_price', 'current_price', 'is_on_sale',
            'unit_type', 'volume', 'stock_quantity', 'in_stock',
            'rating', 'view_count', 'image', 'is_active',
            'created_at', 'updated_at'
        ]


class OrderItemSerializer(serializers.ModelSerializer):
    product_name = serializers.CharField(source='product.name', read_only=True)

    class Meta:
        model = OrderItem
        fields = ['id', 'product', 'product_name', 'quantity', 'price']


class OrderSerializer(serializers.ModelSerializer):
    items = OrderItemSerializer(many=True, read_only=True)
    total_amount = serializers.SerializerMethodField()

    class Meta:
        model = Order
        fields = [
            'id', 'full_name', 'phone', 'address',
            'payment_method', 'payment_status', 'payment_reference',
            'items', 'total_amount', 'created_at', 'updated_at'
        ]
        read_only_fields = ['payment_status', 'payment_reference']

    def get_total_amount(self, obj):
        return sum(item.price * item.quantity for item in obj.items.all())


class CartItemSerializer(serializers.ModelSerializer):
    product = ProductListSerializer(read_only=True)
    product_id = serializers.IntegerField(write_only=True)
    total_price = serializers.DecimalField(source='get_total_price', max_digits=12, decimal_places=2, read_only=True)

    class Meta:
        model = CartItem
        fields = ['id', 'product', 'product_id', 'quantity', 'total_price', 'created_at']


class CartSerializer(serializers.ModelSerializer):
    items = CartItemSerializer(many=True, read_only=True)
    total_amount = serializers.SerializerMethodField()
    total_items = serializers.SerializerMethodField()

    class Meta:
        model = Cart
        fields = ['id', 'user', 'items', 'total_amount', 'total_items', 'created_at', 'updated_at']
        read_only_fields = ['user']

    def get_total_amount(self, obj):
        return sum(item.get_total_price() for item in obj.items.all())

    def get_total_items(self, obj):
        return sum(item.quantity for item in obj.items.all())


class CouponSerializer(serializers.ModelSerializer):
    class Meta:
        model = Coupon
        fields = [
            'id', 'code', 'discount_percentage', 'discount_amount',
            'min_purchase_amount', 'valid_from', 'valid_to',
            'is_active', 'max_uses', 'used_count'
        ]
        read_only_fields = ['used_count']


class ReviewSerializer(serializers.ModelSerializer):
    user_name = serializers.CharField(source='user.username', read_only=True)

    class Meta:
        model = Review
        fields = ['id', 'product', 'user', 'user_name', 'rating', 'comment', 'created_at', 'updated_at']
        read_only_fields = ['user']


class WishlistSerializer(serializers.ModelSerializer):
    products = ProductListSerializer(many=True, read_only=True)
    product_ids = serializers.ListField(
        child=serializers.IntegerField(),
        write_only=True,
        required=False
    )

    class Meta:
        model = Wishlist
        fields = ['id', 'user', 'products', 'product_ids', 'created_at', 'updated_at']
        read_only_fields = ['user']
