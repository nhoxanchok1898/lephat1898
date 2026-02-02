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
    # Cart API endpoints
    path('cart/add/', api_views.cart_add_item_api, name='cart-add-item'),
    path('cart/update/', api_views.cart_update_item_api, name='cart-update-item'),
    path('cart/remove/', api_views.cart_remove_item_api, name='cart-remove-item'),
    path('cart/clear/', api_views.cart_clear_api, name='cart-clear'),
    path('cart/apply-coupon/', api_views.cart_apply_coupon_api, name='cart-apply-coupon'),
]
