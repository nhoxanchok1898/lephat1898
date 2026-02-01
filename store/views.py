from django.shortcuts import render, get_object_or_404, redirect
from django.views.decorators.http import require_POST
from django.http import JsonResponse
from django.core.paginator import Paginator, EmptyPage, PageNotAnInteger
from django.core.mail import send_mail
from django.conf import settings

from .models import Brand, Category, Product, Order, OrderItem


def home_view(request):
    brands = Brand.objects.all()[:8]
    new_products = Product.objects.filter(is_active=True).order_by('-created_at')[:12]
    return render(request, 'store/home.html', {'brands': brands, 'new_products': new_products})


def product_list(request):
    qs = Product.objects.filter(is_active=True)
    category = request.GET.get('category')
    brand = request.GET.get('brand')
    q = request.GET.get('q')
    if category:
        qs = qs.filter(category__id=category)
    if brand:
        qs = qs.filter(brand__id=brand)
    if q:
        qs = qs.filter(name__icontains=q) | qs.filter(brand__name__icontains=q)

    categories = Category.objects.all()
    brands = Brand.objects.all()

    # resolve friendly names for meta
    current_category_name = None
    current_brand_name = None
    if category:
        try:
            current_category_name = categories.get(id=category).name
        except Exception:
            current_category_name = None
    if brand:
        try:
            current_brand_name = brands.get(id=brand).name
        except Exception:
            current_brand_name = None

    # pagination
    page = request.GET.get('page', 1)
    paginator = Paginator(qs, 12)
    try:
        products_page = paginator.page(page)
    except PageNotAnInteger:
        products_page = paginator.page(1)
    except EmptyPage:
        products_page = paginator.page(paginator.num_pages)

    return render(request, 'store/product_list.html', {
        'products': products_page,
        'categories': categories,
        'brands': brands,
        'page_obj': products_page,
        'paginator': paginator,
        'q': q,
        'current_category': category,
        'current_brand': brand,
        'current_category_name': current_category_name,
        'current_brand_name': current_brand_name,
    })


def product_detail(request, pk):
    p = get_object_or_404(Product, pk=pk, is_active=True)
    return render(request, 'store/product_detail.html', {'product': p})


def _get_cart(request):
    return request.session.setdefault('cart', {})


@require_POST
def cart_add(request, pk):
    product = get_object_or_404(Product, pk=pk, is_active=True)
    cart = _get_cart(request)
    qty = int(request.POST.get('quantity', 1))
    if str(pk) in cart:
        cart[str(pk)] += qty
    else:
        cart[str(pk)] = qty
    request.session.modified = True
    return redirect('store:cart_view')


def cart_view(request):
    cart = _get_cart(request)
    items = []
    total = 0
    for pid, qty in cart.items():
        try:
            p = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        subtotal = p.price * qty
        total += subtotal
        items.append({'product': p, 'qty': qty, 'subtotal': subtotal})
    return render(request, 'store/cart.html', {'items': items, 'total': total})


def cart_remove(request, pk):
    cart = _get_cart(request)
    cart.pop(str(pk), None)
    request.session.modified = True
    return redirect('store:cart_view')


@require_POST
def cart_update(request, pk):
    """Update cart quantity for given product pk. If quantity <= 0, remove item."""
    cart = _get_cart(request)
    try:
        qty = int(request.POST.get('quantity', 0))
    except Exception:
        qty = 0
    if qty <= 0:
        cart.pop(str(pk), None)
    else:
        cart[str(pk)] = qty
    request.session.modified = True
    return redirect('store:cart_view')


@require_POST
def cart_update_ajax(request, pk):
    """AJAX endpoint: set quantity and return JSON with updated subtotal and total."""
    cart = _get_cart(request)
    try:
        qty = int(request.POST.get('quantity', 0))
    except Exception:
        return JsonResponse({'error': 'invalid quantity'}, status=400)

    if qty <= 0:
        cart.pop(str(pk), None)
    else:
        cart[str(pk)] = qty
    request.session.modified = True

    # compute new subtotal for this product and total for cart
    subtotal = 0
    total = 0
    try:
        p = Product.objects.get(pk=pk)
        subtotal = p.price * cart.get(str(pk), 0)
    except Product.DoesNotExist:
        subtotal = 0

    for pid, q in cart.items():
        try:
            prod = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        total += prod.price * q

    return JsonResponse({'pk': pk, 'quantity': cart.get(str(pk), 0), 'subtotal': subtotal, 'total': total})


def cart_summary_ajax(request):
    """Return a small JSON summary of cart contents for mini-cart flyout."""
    cart = _get_cart(request)
    items = []
    total = 0
    for pid, qty in cart.items():
        try:
            p = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        subtotal = p.price * qty
        total += subtotal
        items.append({
            'pk': p.pk,
            'name': p.name,
            'qty': qty,
            'price': p.price,
            'subtotal': subtotal,
            'image_url': request.build_absolute_uri(p.image.url) if p.image else None,
        })
    return JsonResponse({'items': items, 'total': total})


@require_POST
def cart_remove_ajax(request, pk):
    """AJAX remove endpoint: remove product from cart and return updated total."""
    cart = _get_cart(request)
    cart.pop(str(pk), None)
    request.session.modified = True

    total = 0
    items = []
    for pid, qty in cart.items():
        try:
            p = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        subtotal = p.price * qty
        total += subtotal
        items.append({'pk': p.pk, 'name': p.name, 'qty': qty, 'price': p.price, 'subtotal': subtotal})

    return JsonResponse({'removed': pk, 'items': items, 'total': total})


def checkout_view(request):
    cart = _get_cart(request)
    if request.method == 'POST':
        name = request.POST.get('name')
        phone = request.POST.get('phone')
        address = request.POST.get('address')
        order = Order.objects.create(full_name=name, phone=phone, address=address)
        for pid, qty in cart.items():
            try:
                p = Product.objects.get(pk=int(pid))
            except Product.DoesNotExist:
                continue
            OrderItem.objects.create(order=order, product=p, quantity=qty, price=p.price)
        request.session.pop('cart', None)
        request.session.modified = True
        # send notification email (development: console backend)
        try:
            items_text = []
            total = 0
            for it in order.items.all():
                items_text.append(f"{it.product.name} x{it.quantity} @{it.price}")
                total += it.quantity * it.price
            subject = f'New Order #{order.id}'
            message = f'Order #{order.id}\nName: {order.full_name}\nPhone: {order.phone}\nAddress: {order.address}\nItems:\n' + "\n".join(items_text) + f'\nTotal: {total}'
            send_mail(subject, message, settings.DEFAULT_FROM_EMAIL, [settings.DEFAULT_FROM_EMAIL])
        except Exception:
            pass
        return redirect('store:checkout_success')
    items = []
    total = 0
    for pid, qty in cart.items():
        try:
            p = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        items.append({'product': p, 'qty': qty, 'subtotal': p.price * qty})
        total += p.price * qty
    return render(request, 'store/checkout.html', {'items': items, 'total': total})


def checkout_success(request):
    return render(request, 'store/checkout_success.html')


def contact_view(request):
    return render(request, 'store/contact.html')


def wishlist_view(request):
    """Simple wishlist page stub to satisfy template reverses.
    Stores wishlist in session as a list of product pks under 'wishlist'.
    """
    wishlist = request.session.setdefault('wishlist', [])
    products = []
    for pk in wishlist:
        try:
            products.append(Product.objects.get(pk=int(pk)))
        except Exception:
            continue
    return render(request, 'store/wishlist.html', {'products': products})


def search_suggestions(request):
    q = request.GET.get('q', '')
    results = []
    if q:
        qs = Product.objects.filter(is_active=True).filter(name__icontains=q)[:8]
        for p in qs:
            results.append({'id': p.pk, 'name': p.name})
    return JsonResponse({'results': results})
