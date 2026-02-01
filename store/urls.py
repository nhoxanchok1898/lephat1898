from django.urls import path, include
from rest_framework.routers import DefaultRouter
from . import views
from . import recommendation_views
from . import inventory_views
from . import analytics_views
from . import api_views
from . import coupon_views

app_name = 'store'

# API Router
router = DefaultRouter()
router.register(r'products', api_views.ProductViewSet, basename='api-product')
router.register(r'orders', api_views.OrderViewSet, basename='api-order')

urlpatterns = [
    # Existing URLs
    path('', views.home_view, name='home'),
    path('products/', views.product_list, name='product_list'),
    path('products/<int:pk>/', views.product_detail, name='product_detail'),
    path('cart/add/<int:pk>/', views.cart_add, name='cart_add'),
    path('cart/', views.cart_view, name='cart_view'),
    path('cart/update/<int:pk>/', views.cart_update, name='cart_update'),
    path('cart/ajax/update/<int:pk>/', views.cart_update_ajax, name='cart_update_ajax'),
    path('cart/ajax/summary/', views.cart_summary_ajax, name='cart_summary_ajax'),
    path('cart/ajax/remove/<int:pk>/', views.cart_remove_ajax, name='cart_remove_ajax'),
    path('cart/remove/<int:pk>/', views.cart_remove, name='cart_remove'),
    path('checkout/', views.checkout_view, name='checkout'),
    path('checkout/success/', views.checkout_success, name='checkout_success'),
    path('payments/stripe/create/', views.stripe_create_session, name='stripe_create'),
    path('payments/stripe/success/', views.stripe_success, name='stripe_success'),
    path('payments/stripe/webhook/', views.stripe_webhook, name='stripe_webhook'),
    path('contact/', views.contact_view, name='contact'),
    path('ajax/search_suggestions/', views.search_suggestions, name='search_suggestions'),
    
    # Recommendation URLs
    path('recommendations/<int:product_id>/', recommendation_views.get_recommendations, name='recommendations'),
    path('recommendations/trending/', recommendation_views.get_trending_products, name='trending'),
    path('recommendations/personalized/', recommendation_views.get_personalized_recommendations, name='personalized'),
    path('products/<int:product_id>/track-view/', recommendation_views.product_view_tracker, name='track_view'),
    
    # Inventory URLs
    path('inventory/stock/update/<int:product_id>/', inventory_views.update_stock, name='update_stock'),
    path('inventory/stock/check/<int:product_id>/', inventory_views.check_stock, name='check_stock'),
    path('inventory/alerts/', inventory_views.low_stock_alert_view, name='low_stock_alerts'),
    path('inventory/pre-order/<int:product_id>/', inventory_views.pre_order_create, name='pre_order'),
    path('inventory/notify/<int:product_id>/', inventory_views.back_in_stock_notification, name='back_in_stock'),
    
    # Analytics URLs
    path('admin/dashboard/', analytics_views.admin_dashboard, name='admin_dashboard'),
    path('admin/analytics/data/', analytics_views.analytics_data, name='analytics_data'),
    path('admin/analytics/sales/', analytics_views.sales_chart_data, name='sales_chart'),
    path('admin/analytics/performance/', analytics_views.product_performance, name='product_performance'),
    
    # Coupon URLs
    path('coupons/apply/', coupon_views.apply_coupon, name='apply_coupon'),
    path('coupons/remove/', coupon_views.remove_coupon, name='remove_coupon'),
    path('admin/coupons/', coupon_views.coupon_list_admin, name='coupon_list_admin'),
    path('admin/coupons/create/', coupon_views.coupon_create_admin, name='coupon_create_admin'),
    path('admin/coupons/<int:coupon_id>/edit/', coupon_views.coupon_edit_admin, name='coupon_edit_admin'),
    path('admin/coupons/<int:coupon_id>/delete/', coupon_views.coupon_delete_admin, name='coupon_delete_admin'),
    
    # REST API URLs
    path('api/', include(router.urls)),
    path('api/cart/', api_views.cart_view_api, name='api_cart'),
    path('api/cart/add/', api_views.cart_add_api, name='api_cart_add'),
    path('api/cart/remove/<int:product_id>/', api_views.cart_remove_api, name='api_cart_remove'),
    path('api/recommendations/', api_views.recommendations_api, name='api_recommendations'),
]