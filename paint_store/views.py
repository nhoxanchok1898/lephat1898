from django.shortcuts import render, get_object_or_404
from .models import Product, Brand

def home(request):
    brands = Brand.objects.all()
    products = Product.objects.order_by('-id')[:10]
    return render(request, 'home.html', {'brands': brands, 'products': products})

def product_list(request):
    category = request.GET.get('category')
    brand = request.GET.get('brand')
    unit = request.GET.get('unit')

    products = Product.objects.all()

    if category:
        products = products.filter(category=category)
    if brand:
        products = products.filter(brand__name=brand)
    if unit:
        products = products.filter(unit=unit)

    return render(request, 'product_list.html', {'products': products})

def product_detail(request, pk):
    product = get_object_or_404(Product, pk=pk)
    return render(request, 'product_detail.html', {'product': product})