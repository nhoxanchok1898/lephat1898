"""
API URLs for Phase 2A REST API
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from rest_framework.authtoken.views import obtain_auth_token

from .api_views import (
    BrandViewSet, CategoryViewSet, ProductViewSet,
    OrderViewSet, CartViewSet, ReviewViewSet, WishlistViewSet,
    analytics_overview, track_search
)

# Create router for viewsets
router = DefaultRouter()
router.register(r'brands', BrandViewSet, basename='brand')
router.register(r'categories', CategoryViewSet, basename='category')
router.register(r'products', ProductViewSet, basename='product')
router.register(r'orders', OrderViewSet, basename='order')
router.register(r'cart', CartViewSet, basename='cart')
router.register(r'reviews', ReviewViewSet, basename='review')
router.register(r'wishlist', WishlistViewSet, basename='wishlist')

urlpatterns = [
    # Authentication
    path('auth/token/', obtain_auth_token, name='api_token_auth'),
    
    # Analytics
    path('analytics/overview/', analytics_overview, name='analytics_overview'),
    path('search/track/', track_search, name='track_search'),
    
    # Include router URLs
    path('', include(router.urls)),
]
