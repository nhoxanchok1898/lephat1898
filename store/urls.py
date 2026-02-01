from django.urls import path
from . import views
from . import auth_views, wishlist_views, review_views, order_views

app_name = 'store'

urlpatterns = [
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
    
    # Authentication URLs
    path('auth/register/', auth_views.register_view, name='register'),
    path('auth/login/', auth_views.login_view, name='login'),
    path('auth/logout/', auth_views.logout_view, name='logout'),
    path('auth/profile/', auth_views.profile_view, name='profile'),
    path('auth/profile/update/', auth_views.profile_update, name='profile_update'),
    path('auth/password-reset/', auth_views.password_reset_request, name='password_reset_request'),
    path('auth/password-reset/<uidb64>/<token>/', auth_views.password_reset_confirm, name='password_reset_confirm'),
    
    # Wishlist URLs
    path('wishlist/', wishlist_views.wishlist_view, name='wishlist'),
    path('wishlist/add/<int:pk>/', wishlist_views.wishlist_add, name='wishlist_add'),
    path('wishlist/remove/<int:pk>/', wishlist_views.wishlist_remove, name='wishlist_remove'),
    path('wishlist/share/', wishlist_views.wishlist_share, name='wishlist_share'),
    path('wishlist/shared/<str:username>/', wishlist_views.wishlist_shared_view, name='wishlist_shared'),
    path('wishlist/check/<int:pk>/', wishlist_views.wishlist_check, name='wishlist_check'),
    
    # Review URLs
    path('reviews/create/<int:pk>/', review_views.review_create, name='review_create'),
    path('reviews/list/<int:pk>/', review_views.review_list, name='review_list'),
    path('reviews/approve/<int:pk>/', review_views.review_approve, name='review_approve'),
    path('reviews/helpful/<int:pk>/', review_views.review_helpful, name='review_helpful'),
    path('reviews/moderate/', review_views.review_moderate, name='review_moderate'),
    path('reviews/delete/<int:pk>/', review_views.review_delete, name='review_delete'),
    
    # Order URLs
    path('orders/', order_views.order_history, name='order_history'),
    path('orders/<int:order_id>/', order_views.order_detail, name='order_detail'),
    path('orders/<int:order_id>/status/', order_views.order_status_api, name='order_status_api'),
]