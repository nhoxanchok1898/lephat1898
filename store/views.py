from django.shortcuts import render, get_object_or_404, redirect
from django.views.decorators.http import require_POST
from django.http import JsonResponse
from django.core.paginator import Paginator, EmptyPage, PageNotAnInteger
from django.core.mail import send_mail
from django.conf import settings
from django.db.models import Q, Avg, F
from django.core.cache import cache

from .models import (
    Brand, Category, Product, Order, OrderItem,
    ProductView, SearchQuery, Review
)
import os
import stripe
from django.views.decorators.csrf import csrf_exempt


def home_view(request):
    # Use select_related for performance
    brands = Brand.objects.all()[:8]
    new_products = Product.objects.filter(is_active=True).select_related('brand', 'category').order_by('-created_at')[:12]
    
    # Get trending products (most viewed in last 7 days)
    trending_products = Product.objects.filter(is_active=True).order_by('-view_count')[:8]
    
    return render(request, 'store/home.html', {
        'brands': brands,
        'new_products': new_products,
        'trending_products': trending_products,
    })


def product_list(request):
    # Optimized query with select_related
    qs = Product.objects.filter(is_active=True).select_related('brand', 'category')
    
    # Filters
    category = request.GET.get('category')
    brand = request.GET.get('brand')
    q = request.GET.get('q')
    on_sale = request.GET.get('on_sale')
    in_stock = request.GET.get('in_stock')
    price_min = request.GET.get('price_min')
    price_max = request.GET.get('price_max')
    
    if category:
        qs = qs.filter(category__id=category)
    if brand:
        qs = qs.filter(brand__id=brand)
    if q:
        qs = qs.filter(
            Q(name__icontains=q) | 
            Q(brand__name__icontains=q) |
            Q(description__icontains=q)
        )
    if on_sale:
        qs = qs.filter(is_on_sale=True)
    if in_stock:
        qs = qs.filter(stock_quantity__gt=0)
    if price_min:
        qs = qs.filter(price__gte=price_min)
    if price_max:
        qs = qs.filter(price__lte=price_max)

    # Track search query after all filters are applied
    if q:
        SearchQuery.objects.create(
            query=q,
            user=request.user if request.user.is_authenticated else None,
            session_key=request.session.session_key or '',
            results_count=qs.count()
        )

    # Sorting
    sort = request.GET.get('sort', '-created_at')
    if sort in ['price', '-price', 'rating', '-rating', 'created_at', '-created_at']:
        qs = qs.order_by(sort)

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
        'on_sale': on_sale,
        'in_stock': in_stock,
        'price_min': price_min,
        'price_max': price_max,
        'sort': sort,
    })


def product_detail(request, pk):
    p = get_object_or_404(Product.objects.select_related('brand', 'category'), pk=pk, is_active=True)
    
    # Ensure session exists for tracking
    if not request.session.session_key:
        request.session.create()
    
    # Track product view
    ProductView.objects.create(
        product=p,
        user=request.user if request.user.is_authenticated else None,
        session_key=request.session.session_key
    )
    
    # Increment view count atomically to avoid race conditions
    Product.objects.filter(pk=p.pk).update(view_count=F('view_count') + 1)
    p.refresh_from_db()  # Refresh to get updated view_count
    
    # Get reviews with average rating
    reviews = Review.objects.filter(product=p).select_related('user').order_by('-created_at')
    avg_rating = reviews.aggregate(Avg('rating'))['rating__avg'] or 0
    
    # Update product rating
    if avg_rating != p.rating:
        p.rating = round(avg_rating, 2)
        p.save(update_fields=['rating'])
    
    # Get recommended products (simple version - same category)
    recommended = Product.objects.filter(
        category=p.category,
        is_active=True
    ).exclude(pk=p.pk).order_by('-view_count')[:4]
    
    return render(request, 'store/product_detail.html', {
        'product': p,
        'reviews': reviews[:10],  # Limit to 10 most recent
        'avg_rating': avg_rating,
        'recommended_products': recommended,
    })


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
        payment_method = request.POST.get('payment_method', 'offline')
        order = Order.objects.create(full_name=name, phone=phone, address=address)
        for pid, qty in cart.items():
            try:
                p = Product.objects.get(pk=int(pid))
            except Product.DoesNotExist:
                continue
            OrderItem.objects.create(order=order, product=p, quantity=qty, price=p.price)
        request.session.pop('cart', None)
        request.session.modified = True
        # record payment details (stubbed). In a real integration you would
        # create a payment with Stripe/PayPal and update these fields
        order.payment_method = payment_method
        # for demo / local dev mark as paid when method is 'stripe' or 'paypal'
        if payment_method in ('stripe', 'paypal'):
            order.payment_status = 'paid'
            order.payment_reference = f"{payment_method.upper()}-SIM-{order.id}"
        else:
            order.payment_status = 'pending'
        order.save()
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


def stripe_create_session(request):
    cart = _get_cart(request)
    if not cart:
        return redirect('store:checkout')

    # read secret key from env
    stripe_key = os.environ.get('STRIPE_SECRET_KEY')
    if not stripe_key:
        # Stripe not configured; fallback to checkout page
        return redirect('store:checkout')
    stripe.api_key = stripe_key

    line_items = []
    for pid, qty in cart.items():
        try:
            p = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        line_items.append({
            'price_data': {
                'currency': 'usd',
                'product_data': {'name': p.name},
                'unit_amount': int(p.price * 100),
            },
            'quantity': qty,
        })

    domain = os.environ.get('SITE_URL', 'http://127.0.0.1:8000')
    try:
        session = stripe.checkout.Session.create(
            payment_method_types=['card'],
            line_items=line_items,
            mode='payment',
            success_url=f"{domain}/store/payments/stripe/success/?session_id={{CHECKOUT_SESSION_ID}}",
            cancel_url=f"{domain}/store/checkout/",
        )
    except Exception:
        return redirect('store:checkout')

    return redirect(session.url)


def stripe_success(request):
    session_id = request.GET.get('session_id')
    stripe_key = os.environ.get('STRIPE_SECRET_KEY')
    if not stripe_key or not session_id:
        return redirect('store:checkout_success')
    stripe.api_key = stripe_key
    try:
        sess = stripe.checkout.Session.retrieve(session_id, expand=['line_items', 'payment_intent'])
    except Exception:
        return redirect('store:checkout_success')

    # create an order from session line items (best-effort)
    try:
        name = sess['customer_details'].get('name') or 'Stripe Customer'
    except Exception:
        name = 'Stripe Customer'
    order = Order.objects.create(full_name=name, phone='', address='')
    for item in sess.get('line_items', {}).get('data', []):
        prod_name = item['description']
        qty = item['quantity']
        # attempt to match product by name, else create a generic product entry
        try:
            p = Product.objects.filter(name__iexact=prod_name).first()
        except Exception:
            p = None
        price = (item['price']['unit_amount'] / 100) if item.get('price') else 0
        if p:
            OrderItem.objects.create(order=order, product=p, quantity=qty, price=price)
        else:
            # create a placeholder product-like row by linking to first product
            first = Product.objects.first()
            if first:
                OrderItem.objects.create(order=order, product=first, quantity=qty, price=price)
    order.payment_method = 'stripe'
    order.payment_status = 'paid'
    order.payment_reference = sess.get('payment_intent') or session_id
    order.save()

    # clear cart
    request.session.pop('cart', None)
    request.session.modified = True

    return redirect('store:checkout_success')


@csrf_exempt
def stripe_webhook(request):
    # Verify webhook signature and update order payment status when a
    # checkout session completes. Requires STRIPE_WEBHOOK_SECRET in env.
    from .models import PaymentLog
    
    payload = request.body
    sig_header = request.META.get('HTTP_STRIPE_SIGNATURE', '')
    webhook_secret = os.environ.get('STRIPE_WEBHOOK_SECRET')
    if not webhook_secret:
        return JsonResponse({'error': 'webhook not configured'}, status=400)

    try:
        event = stripe.Webhook.construct_event(payload, sig_header, webhook_secret)
    except ValueError:
        return JsonResponse({'error': 'invalid payload'}, status=400)
    except stripe.error.SignatureVerificationError:
        return JsonResponse({'error': 'invalid signature'}, status=400)

    # Handle the checkout.session.completed event
    if event['type'] == 'checkout.session.completed':
        session = event['data']['object']
        # session.payment_intent or session.id can be used to match orders
        pay_ref = session.get('payment_intent') or session.get('id')
        amount = session.get('amount_total', 0) / 100  # Convert from cents
        
        # find orders with this payment_reference
        orders = Order.objects.filter(payment_reference__icontains=pay_ref)
        for o in orders:
            o.payment_status = 'paid'
            o.payment_reference = pay_ref
            o.save()
            
            # Log the payment
            PaymentLog.objects.create(
                order=o,
                transaction_id=pay_ref,
                amount=amount,
                status='success',
                payment_method='stripe',
                raw_response=str(event)
            )

    return JsonResponse({'status': 'received'})


def contact_view(request):
    return render(request, 'store/contact.html')


def search_suggestions(request):
    q = request.GET.get('q', '')
    results = []
    if q:
        qs = Product.objects.filter(is_active=True).filter(name__icontains=q)[:8]
        for p in qs:
            results.append({'id': p.pk, 'name': p.name})
    return JsonResponse({'results': results})
