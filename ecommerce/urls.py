"""
URL configuration for ecommerce project.

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/6.0/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.contrib import admin
from django.contrib.auth import views as auth_views
from django.urls import path, include
from store import wishlist_views
from store import views as store_views
from store import auth_views as store_auth_views
from store import search_views
from store import api_views
from store import review_views
from store import order_views
from django.contrib.sitemaps.views import sitemap
from django.views.generic import TemplateView
from store.sitemaps import ProductSitemap, StaticViewSitemap


def trigger_error(request):
    # Endpoint to force an exception for testing Sentry integration
    division_by_zero = 1 / 0


urlpatterns = [
    # Built-in auth endpoints for templates that use `{% url 'login' %}`.
    path('login/', auth_views.LoginView.as_view(template_name='auth/login.html'), name='login'),
    path('logout/', auth_views.LogoutView.as_view(next_page='login'), name='logout'),
    path('auth/register/', store_auth_views.register_view, name='auth_register_root'),
    path('auth/login/', store_auth_views.login_view, name='auth_login_root'),
    # Root aliases for key storefront pages (for tests/legacy)
    path('products/', store_views.product_list, name='products_root'),
    path('products/<int:pk>/', store_views.product_detail, name='product_detail_root'),
    path('products/<int:pk>/reviews/create/', review_views.review_create, name='review_create_root'),
    path('wishlist/add/<int:pk>/', wishlist_views.wishlist_add, name='wishlist_add_root'),
    path('cart/add/<int:pk>/', store_views.cart_add, name='cart_add_root'),
    path('cart/', store_views.cart_view, name='cart_root'),
    path('checkout/', store_views.checkout_view, name='checkout_root'),
    path('orders/history/', order_views.order_history, name='orders_history_root'),
    path('auth/profile/', store_auth_views.profile_view, name='auth_profile_root'),
    # Redirect /accounts/profile/ (default Django auth) về hồ sơ người dùng trong store
    path('accounts/profile/', store_auth_views.profile_view, name='accounts_profile_redirect'),
    path('admin/', admin.site.urls),
    path('', include('home.urls')),
    path('store/', include(('store.urls', 'store'), namespace='store')),
    # Root alias for wishlist to avoid NoReverseMatch from legacy templates
    path('wishlist/', wishlist_views.wishlist_view, name='wishlist'),
    # Root-level API aliases to support tests and legacy callers
    path('api/cart/add/public/', store_views.api_cart_add_public, name='api_cart_add_public'),
    path('api/products/', api_views.ProductViewSet.as_view({'get': 'list'}), name='product-list'),
    # Global aliases for cart actions (no namespace)
    path('api/cart/add/', api_views.cart_add_api, name='cart-add-item'),
    path('api/cart/update/', api_views.cart_update_item_api, name='cart-update-item'),
    path('api/cart/remove/', api_views.cart_remove_item_api, name='cart-remove-item'),
    path('api/cart/clear/', api_views.cart_clear_api, name='cart-clear'),
    path('api/cart/apply-coupon/', api_views.cart_apply_coupon_api, name='cart-apply-coupon'),
    path('sentry-debug/', trigger_error),
    path('sitemap.xml', sitemap, {'sitemaps': {'products': ProductSitemap, 'static': StaticViewSitemap}}, name='sitemap'),
    path('robots.txt', TemplateView.as_view(template_name="robots.txt", content_type="text/plain"), name='robots_txt'),
    # Root aliases for search endpoints (tests expect /search/* without /store prefix)
    path('search/', search_views.product_search_view, name='search_root'),
    path('search/autocomplete/', search_views.autocomplete_view, name='search_autocomplete_root'),
    path('search/popular/', search_views.popular_searches_view, name='search_popular_root'),
    path('search/analytics/', search_views.search_analytics_view, name='search_analytics_root'),
]
