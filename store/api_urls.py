from django.urls import path, include
from rest_framework.routers import DefaultRouter
from . import api_views

router = DefaultRouter()
router.register(r'products', api_views.ProductViewSet, basename='product')
router.register(r'categories', api_views.CategoryViewSet, basename='category')
router.register(r'brands', api_views.BrandViewSet, basename='brand')
router.register(r'orders', api_views.OrderViewSet, basename='order')

urlpatterns = [
    path('', include(router.urls)),
]
