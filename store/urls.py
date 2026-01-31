from django.urls import path
from . import views

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
    path('contact/', views.contact_view, name='contact'),
    path('ajax/search_suggestions/', views.search_suggestions, name='search_suggestions'),
]