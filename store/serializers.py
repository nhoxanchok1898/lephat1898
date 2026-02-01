from rest_framework import serializers
from .models import Product, Category, Brand, Order, OrderItem


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
    current_price = serializers.SerializerMethodField()
    is_on_sale = serializers.SerializerMethodField()
    
    class Meta:
        model = Product
        fields = [
            'id', 'name', 'description', 'brand', 'category',
            'price', 'sale_price', 'current_price', 'is_on_sale',
            'unit_type', 'volume', 'image', 'is_active', 'is_new',
            'created_at', 'updated_at'
        ]
    
    def get_current_price(self, obj):
        return obj.get_price()
    
    def get_is_on_sale(self, obj):
        return obj.is_on_sale()


class ProductListSerializer(serializers.ModelSerializer):
    """Lightweight serializer for product lists"""
    brand_name = serializers.CharField(source='brand.name', read_only=True)
    category_name = serializers.CharField(source='category.name', read_only=True)
    current_price = serializers.SerializerMethodField()
    
    class Meta:
        model = Product
        fields = [
            'id', 'name', 'brand_name', 'category_name',
            'price', 'sale_price', 'current_price',
            'image', 'is_new'
        ]
    
    def get_current_price(self, obj):
        return obj.get_price()


class OrderItemSerializer(serializers.ModelSerializer):
    product = ProductListSerializer(read_only=True)
    
    class Meta:
        model = OrderItem
        fields = ['id', 'product', 'quantity', 'price']


class OrderSerializer(serializers.ModelSerializer):
    items = OrderItemSerializer(many=True, read_only=True)
    
    class Meta:
        model = Order
        fields = [
            'id', 'full_name', 'phone', 'address',
            'payment_method', 'payment_status', 'payment_reference',
            'created_at', 'updated_at', 'items'
        ]
        read_only_fields = ['payment_reference', 'created_at', 'updated_at']
