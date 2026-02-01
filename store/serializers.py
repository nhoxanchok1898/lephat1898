from rest_framework import serializers
from .models import (
    Product, Brand, Category, Order, OrderItem,
    ProductView, StockLevel, Coupon
)


class BrandSerializer(serializers.ModelSerializer):
    class Meta:
        model = Brand
        fields = ['id', 'name', 'logo']


class CategorySerializer(serializers.ModelSerializer):
    class Meta:
        model = Category
        fields = ['id', 'name']


class ProductSerializer(serializers.ModelSerializer):
    brand = BrandSerializer(read_only=True)
    category = CategorySerializer(read_only=True)
    stock_quantity = serializers.SerializerMethodField()
    in_stock = serializers.SerializerMethodField()
    
    class Meta:
        model = Product
        fields = [
            'id', 'name', 'brand', 'category', 'price', 
            'unit_type', 'volume', 'image', 'is_active',
            'created_at', 'updated_at', 'stock_quantity', 'in_stock'
        ]
    
    def get_stock_quantity(self, obj):
        try:
            return obj.stock.quantity
        except StockLevel.DoesNotExist:
            return None
    
    def get_in_stock(self, obj):
        try:
            return obj.stock.quantity > 0
        except StockLevel.DoesNotExist:
            return True


class OrderItemSerializer(serializers.ModelSerializer):
    product = ProductSerializer(read_only=True)
    
    class Meta:
        model = OrderItem
        fields = ['id', 'product', 'quantity', 'price']


class OrderSerializer(serializers.ModelSerializer):
    items = OrderItemSerializer(many=True, read_only=True)
    total = serializers.SerializerMethodField()
    
    class Meta:
        model = Order
        fields = [
            'id', 'full_name', 'phone', 'address',
            'payment_method', 'payment_status', 'payment_reference',
            'created_at', 'updated_at', 'items', 'total'
        ]
        read_only_fields = ['payment_status', 'payment_reference']
    
    def get_total(self, obj):
        return sum(item.price * item.quantity for item in obj.items.all())


class ProductViewSerializer(serializers.ModelSerializer):
    product = ProductSerializer(read_only=True)
    
    class Meta:
        model = ProductView
        fields = ['id', 'product', 'viewed_at']


class CouponSerializer(serializers.ModelSerializer):
    class Meta:
        model = Coupon
        fields = [
            'id', 'code', 'description', 'discount_type', 
            'discount_value', 'min_purchase_amount', 'start_date', 
            'end_date', 'is_active'
        ]
        read_only_fields = ['used_count']


class CartItemSerializer(serializers.Serializer):
    product_id = serializers.IntegerField()
    quantity = serializers.IntegerField(min_value=1)
    
    def validate_product_id(self, value):
        try:
            Product.objects.get(pk=value, is_active=True)
        except Product.DoesNotExist:
            raise serializers.ValidationError("Product not found or inactive")
        return value


class CartSerializer(serializers.Serializer):
    items = CartItemSerializer(many=True)
    total = serializers.DecimalField(max_digits=12, decimal_places=2, read_only=True)
