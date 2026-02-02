from django.urls import path, include
from rest_framework.routers import DefaultRouter
from django.contrib.auth import views as dj_auth_views
from . import views
from . import auth_views
from . import order_views
from . import review_views
from . import recommendation_views
from . import inventory_views
from . import analytics_views
from . import api_views
from . import coupon_views
from . import payment_webhooks
from . import admin_dashboard
from . import monitoring
from . import search_views
from . import wishlist_views

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
    # Review URLs
    path('products/<int:pk>/reviews/', review_views.review_list, name='review_list'),
    path('products/<int:pk>/reviews/create/', review_views.review_create, name='review_create'),
    path('reviews/<int:pk>/approve/', review_views.review_approve, name='review_approve'),
    path('reviews/<int:pk>/helpful/', review_views.review_helpful, name='review_helpful'),
    path('reviews/<int:pk>/delete/', review_views.review_delete, name='review_delete'),
    path('reviews/moderate/', review_views.review_moderate, name='review_moderate'),
    # Cart URLs
    path('cart/add/<int:pk>/', views.cart_add, name='cart_add'),
    path('cart/', views.cart_view, name='cart_view'),
    path('cart/update/<int:pk>/', views.cart_update, name='cart_update'),
    path('cart/ajax/update/<int:pk>/', views.cart_update_ajax, name='cart_update_ajax'),
    path('cart/ajax/summary/', views.cart_summary_ajax, name='cart_summary_ajax'),
    path('cart/ajax/remove/<int:pk>/', views.cart_remove_ajax, name='cart_remove_ajax'),
    path('cart/remove/<int:pk>/', views.cart_remove, name='cart_remove'),
    path('checkout/', views.checkout_view, name='checkout'),
    # Auth views inside the `store` namespace so templates using
    # `{% url 'store:login' %}` resolve correctly.
    path('login/', auth_views.login_view, name='login'),
    path('logout/', auth_views.logout_view, name='logout'),
    # App-level auth endpoints (register, custom login/logout handlers)
    path('auth/register/', auth_views.register_view, name='register'),
    path('auth/login/', auth_views.login_view, name='auth_login'),
    path('auth/logout/', auth_views.logout_view, name='auth_logout'),
    path('auth/profile/', auth_views.profile_view, name='profile'),
    path('auth/profile/update/', auth_views.profile_update_view, name='profile_update'),
    path('auth/password-reset/', auth_views.password_reset_request_view, name='password_reset_request'),
    path('checkout/success/', views.checkout_success, name='checkout_success'),
    # Order views
    path('orders/history/', order_views.order_history, name='order_history'),
    path('orders/<int:order_id>/', order_views.order_detail, name='order_detail'),
    path('payments/stripe/create/', views.stripe_create_session, name='stripe_create'),
    path('payments/stripe/success/', views.stripe_success, name='stripe_success'),
    path('payments/stripe/webhook/', views.stripe_webhook, name='stripe_webhook'),
    path('contact/', views.contact_view, name='contact'),
    path('ajax/search_suggestions/', views.search_suggestions, name='search_suggestions'),
    
    # Advanced Search URLs
    path('search/', search_views.product_search_view, name='product_search'),
    path('search/autocomplete/', search_views.autocomplete_view, name='search_autocomplete'),
    path('search/analytics/', search_views.search_analytics_view, name='search_analytics'),
    path('search/popular/', search_views.popular_searches_view, name='popular_searches'),
    
    # Payment Webhooks
    path('webhooks/stripe/', payment_webhooks.stripe_webhook, name='webhook_stripe'),
    path('webhooks/paypal/', payment_webhooks.paypal_webhook, name='webhook_paypal'),
    
    # Admin Dashboard
    path('admin-dashboard/', admin_dashboard.admin_dashboard, name='admin_dashboard_new'),
    path('admin-dashboard/export/sales/', admin_dashboard.export_sales_report, name='export_sales'),
    path('admin-dashboard/export/products/', admin_dashboard.export_products_report, name='export_products'),
    path('admin-dashboard/api/metrics/', admin_dashboard.api_dashboard_metrics, name='api_metrics'),
    path('admin-dashboard/api/sales/', admin_dashboard.api_sales_chart, name='api_sales_chart'),
    path('admin-dashboard/performance/', admin_dashboard.performance_metrics, name='performance_metrics'),
    path('admin-dashboard/activity/', admin_dashboard.staff_activity_log, name='activity_log'),
    
    # Health Check & Monitoring
    path('health/', monitoring.health_check, name='health_check'),
    path('readiness/', monitoring.readiness_check, name='readiness_check'),
    path('liveness/', monitoring.liveness_check, name='liveness_check'),
    
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

    # Wishlist (database-backed) URLs
    path('wishlist/', wishlist_views.wishlist_view, name='wishlist'),
    path('wishlist/add/<int:pk>/', wishlist_views.wishlist_add, name='wishlist_add'),
    path('wishlist/remove/<int:pk>/', wishlist_views.wishlist_remove, name='wishlist_remove'),
    path('wishlist/check/<int:pk>/', wishlist_views.wishlist_check, name='wishlist_check'),
    path('wishlist/share/', wishlist_views.wishlist_share, name='wishlist_share'),
    path('wishlist/shared/<str:username>/', wishlist_views.wishlist_shared_view, name='wishlist_shared'),
    
    # Analytics URLs
    path('dashboard/', analytics_views.admin_dashboard, name='admin_dashboard'),
    path('analytics/data/', analytics_views.analytics_data, name='analytics_data'),
    path('analytics/sales/', analytics_views.sales_chart_data, name='sales_chart'),
    path('analytics/performance/', analytics_views.product_performance, name='product_performance'),
    
    # Coupon URLs
    path('coupons/apply/', coupon_views.apply_coupon, name='apply_coupon'),
    path('coupons/remove/', coupon_views.remove_coupon, name='remove_coupon'),
    path('coupons/admin/', coupon_views.coupon_list_admin, name='coupon_list_admin'),
    path('coupons/admin/create/', coupon_views.coupon_create_admin, name='coupon_create_admin'),
    path('coupons/admin/<int:coupon_id>/edit/', coupon_views.coupon_edit_admin, name='coupon_edit_admin'),
    path('coupons/admin/<int:coupon_id>/delete/', coupon_views.coupon_delete_admin, name='coupon_delete_admin'),
    
    # REST API URLs
    path('api/', include(router.urls)),
    path('api/cart/', api_views.cart_view_api, name='api_cart'),
    # Expose a plain Django JSON view for anonymous session-backed cart adds.
    path('api/cart/add/', views.api_cart_add_public, name='api_cart_add'),
    path('api/cart/remove/<int:product_id>/', api_views.cart_remove_api, name='api_cart_remove'),
    path('api/recommendations/', api_views.recommendations_api, name='api_recommendations'),
]