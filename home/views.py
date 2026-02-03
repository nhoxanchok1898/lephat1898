from django.shortcuts import render
from django.utils import timezone
from datetime import timedelta

# Import store models if available
try:
    from store.models import Brand, Product, Category
except Exception:
    Brand = None
    Product = None
    Category = None


def home_view(request):
    # Provide simple context using store models if present
    brands = Brand.objects.all()[:8] if Brand is not None else []
    new_products = Product.objects.filter(is_active=True).order_by('-created_at')[:12] if Product is not None else []
    trending_products = Product.objects.filter(is_active=True).order_by('-view_count')[:8] if Product is not None else []
    featured_products = trending_products

    return render(request, 'home/index.html', {
        'brands': brands,
        'new_products': new_products,
        'trending_products': trending_products,
        'featured_products': featured_products,
    })
