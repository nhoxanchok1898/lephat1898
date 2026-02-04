from django.shortcuts import render, get_object_or_404, redirect
from django.views.decorators.http import require_POST
from rest_framework.decorators import api_view, authentication_classes, permission_classes
from rest_framework.permissions import AllowAny
from django.http import JsonResponse
from rest_framework.response import Response
from rest_framework import status as drf_status
from django.core.paginator import Paginator, EmptyPage, PageNotAnInteger
from django.core.mail import send_mail
from django.conf import settings
from django.db.models import Q, Count, Avg, Sum
from django.contrib.auth.decorators import login_required
from django.utils import timezone
from datetime import timedelta

from .models import (
    Brand, Category, Product, Order, OrderItem,
    SearchQuery, ProductView, StockLevel,
    ProductViewAnalytics
)
import os
import stripe
from django.views.decorators.csrf import csrf_exempt
import json


def get_client_ip(request):
    """Get client IP address from request"""
    x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
    if x_forwarded_for:
        ip = x_forwarded_for.split(',')[0]
    else:
        ip = request.META.get('REMOTE_ADDR')
    return ip


def home_view(request):
    # Check if redesign parameter is present or use redesigned version by default
    use_redesign = request.GET.get('redesign', 'true').lower() == 'true'
    
    # Use select_related for performance
    brands = Brand.objects.all()[:8]
    new_products = Product.objects.filter(is_active=True).select_related('brand', 'category').order_by('-created_at')[:12]
    
    # Get trending products (most viewed in last 7 days)
    trending_products = Product.objects.filter(is_active=True).order_by('-view_count')[:8]
    featured_products = trending_products  # Use same data for featured
    
    template = 'store/home_redesign.html' if use_redesign else 'store/home.html'
    
    return render(request, template, {
        'brands': brands,
        'new_products': new_products,
        'trending_products': trending_products,
        'featured_products': featured_products,
    })


def product_list(request):
    qs = Product.objects.filter(is_active=True).select_related('brand', 'category')
    
    # Get filter parameters
    category = request.GET.get('category')
    brand = request.GET.get('brand')
    q = request.GET.get('q')
    min_price = request.GET.get('min_price')
    max_price = request.GET.get('max_price')
    on_sale = request.GET.get('on_sale')
    new_arrivals = request.GET.get('new_arrivals')
    in_stock = request.GET.get('in_stock')
    sort_by = request.GET.get('sort', 'newest')
    
    # Apply filters with error handling
    if category:
        try:
            qs = qs.filter(category__id=int(category))
        except (ValueError, TypeError):
            pass  # Ignore invalid category values
    if brand:
        try:
            qs = qs.filter(brand__id=int(brand))
        except (ValueError, TypeError):
            pass  # Ignore invalid brand values
    if q:
        # Full-text search on name and description
        qs = qs.filter(
            Q(name__icontains=q) | 
            Q(description__icontains=q) |
            Q(brand__name__icontains=q)
        )
        # Track search query
        # Note: SearchQuery model not implemented yet
        # ip_address = get_client_ip(request)
        # user = request.user if request.user.is_authenticated else None
        # SearchQuery.objects.create(
        #     query=q,
        #     user=user,
        #     results_count=qs.count(),
        #     ip_address=ip_address
        # )
    
    if min_price:
        try:
            qs = qs.filter(price__gte=float(min_price))
        except ValueError:
            pass
    
    if max_price:
        try:
            qs = qs.filter(price__lte=float(max_price))
        except ValueError:
            pass
    
    if on_sale:
        qs = qs.filter(sale_price__isnull=False)
    
    if new_arrivals:
        # Products created in the last 30 days
        thirty_days_ago = timezone.now() - timedelta(days=30)
        qs = qs.filter(created_at__gte=thirty_days_ago)
    
    if in_stock:
        # Filter products that have quantity > 0
        qs = qs.filter(quantity__gt=0)
    
    # Sorting
    if sort_by == 'price_asc':
        qs = qs.order_by('price')
    elif sort_by == 'price_desc':
        qs = qs.order_by('-price')
    elif sort_by == 'name_asc':
        qs = qs.order_by('name')
    elif sort_by == 'name_desc':
        qs = qs.order_by('-name')
    elif sort_by == 'popular':
        # Most viewed products
        qs = qs.order_by('-view_count')
    else:  # newest
        qs = qs.order_by('-created_at')

    # Get categories and brands with product counts
    categories = Category.objects.annotate(product_count=Count('product')).all()
    brands = Brand.objects.annotate(product_count=Count('product')).all()

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
        'sort': sort_by,  # Changed from sort_by to sort for template consistency
        'min_price': min_price,
        'max_price': max_price,
        'on_sale': on_sale,
        'new_arrivals': new_arrivals,
        'in_stock': in_stock,
    })


def product_detail(request, pk):
    p = get_object_or_404(Product, pk=pk, is_active=True)
    
    # Track product view
    user = request.user if request.user.is_authenticated else None
    session_key = request.session.session_key or ''
    ip_address = get_client_ip(request)
    
    ProductView.objects.create(
        product=p,
        user=user,
        session_key=session_key,
        ip_address=ip_address
    )
    
    # Update product analytics
    analytics, created = ProductViewAnalytics.objects.get_or_create(
        product=p,
        defaults={'total_views': 0, 'unique_views': 0}
    )
    analytics.total_views += 1
    analytics.last_viewed = timezone.now()
    analytics.save()
    
    # Get recommendations (products viewed by users who viewed this product)
    # Get users who viewed this product
    viewers = ProductView.objects.filter(product=p).exclude(user__isnull=True).values_list('user', flat=True).distinct()
    # Get other products viewed by these users
    recommended_products = Product.objects.filter(
        views__user__in=viewers,
        is_active=True
    ).exclude(pk=p.pk).annotate(
        total_views=Count('views')
    ).order_by('-total_views')[:6]
    
    # Get products in the same category
    related_products = Product.objects.filter(
        category=p.category,
        is_active=True
    ).exclude(pk=p.pk)[:6]
    
    # Get stock information
    try:
        stock = p.stock
        stock_info = {
            'available': stock.available_quantity(),
            'is_low': stock.is_low_stock(),
            'is_out': stock.is_out_of_stock(),
        }
    except StockLevel.DoesNotExist:
        stock_info = None
    
    # Get average rating (use the stored `rating` field)
    avg_rating = p.rating
    
    return render(request, 'store/product_detail.html', {
        'product': p,
        'recommended_products': recommended_products,
        'related_products': related_products,
        'stock_info': stock_info,
        'avg_rating': avg_rating,
    })


def _get_cart(request):
    return request.session.setdefault('cart', {})


@require_POST
def cart_add(request, pk):
    product = get_object_or_404(Product, pk=pk, is_active=True)
    cart = _get_cart(request)
    qty = 1
    # support JSON body from fetch() as well as form-encoded POST
    if request.content_type == 'application/json' or request.META.get('HTTP_CONTENT_TYPE', '').startswith('application/json'):
        try:
            data = json.loads(request.body.decode('utf-8')) if request.body else {}
            qty = int(data.get('quantity', 1))
        except Exception:
            qty = 1
    else:
        try:
            qty = int(request.POST.get('quantity', 1))
        except Exception:
            qty = 1
    if str(pk) in cart:
        cart[str(pk)] += qty
    else:
        cart[str(pk)] = qty
    request.session.modified = True
    # If JSON request, return JSON summary
    if request.content_type == 'application/json' or request.META.get('HTTP_CONTENT_TYPE', '').startswith('application/json'):
        total = 0
        count = 0
        items = []
        for pid, q in cart.items():
            try:
                p = Product.objects.get(pk=int(pid))
            except Product.DoesNotExist:
                continue
            subtotal = float(p.price * q)
            total += subtotal
            count += q
            items.append({'pk': p.pk, 'name': p.name, 'quantity': q, 'price': float(p.price), 'subtotal': subtotal, 'image_url': request.build_absolute_uri(p.image.url) if p.image else None})
        return JsonResponse({'success': True, 'items': items, 'total': float(total), 'count': count})
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
    # support JSON body from fetch() as well as form posts
    qty = None
    if request.content_type == 'application/json' or request.META.get('HTTP_CONTENT_TYPE', '').startswith('application/json'):
        try:
            data = json.loads(request.body.decode('utf-8')) if request.body else {}
            qty = int(data.get('quantity', 0))
        except Exception:
            return JsonResponse({'error': 'invalid quantity'}, status=400)
    else:
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
    count = 0
    for pid, qty in cart.items():
        try:
            p = Product.objects.get(pk=int(pid))
        except Product.DoesNotExist:
            continue
        subtotal = p.price * qty
        total += subtotal
        count += qty
        items.append({
            'pk': p.pk,
            'name': p.name,
            'quantity': qty,
            'qty': qty,
            'price': float(p.price),
            'price_display': str(p.price),
            'subtotal': float(subtotal),
            'image_url': request.build_absolute_uri(p.image.url) if p.image else None,
        })
    return JsonResponse({'items': items, 'total': float(total), 'count': count})


@csrf_exempt
@api_view(['POST'])
@authentication_classes([])
@permission_classes([AllowAny])
def api_cart_add_public(request):
    """Simple JSON API endpoint for adding items to the session cart (public)."""
    """Public session-backed cart add endpoint (no tracing)."""
    data = request.data or {}
    product_id = data.get('product_id')
    try:
        quantity = int(data.get('quantity', 1))
    except Exception:
        quantity = 1

    if not product_id:
        return Response({'error': 'product_id is required'}, status=drf_status.HTTP_400_BAD_REQUEST)

    try:
        product = Product.objects.get(pk=product_id, is_active=True)
    except Product.DoesNotExist:
        return Response({'error': 'Product not found'}, status=drf_status.HTTP_404_NOT_FOUND)

    # Ensure session exists
    if not request.session.session_key:
        request.session.create()

    cart = request.session.get('cart', {})
    product_key = str(product_id)
    cart[product_key] = cart.get(product_key, 0) + quantity
    request.session['cart'] = cart
    request.session.modified = True

    return Response({
        'success': True,
        'product': {'id': product.pk, 'name': product.name},
        'quantity': cart[product_key]
    }, status=drf_status.HTTP_200_OK)


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
        payment_method = request.POST.get('payment_method', Order.PAYMENT_METHOD_COD)
        # Map legacy value to new COD identifier so existing clients continue to work
        if payment_method == Order.PAYMENT_METHOD_OFFLINE:
            payment_method = Order.PAYMENT_METHOD_COD
        order = Order.objects.create(
            user=request.user if request.user.is_authenticated else None,
            full_name=name,
            phone=phone,
            address=address,
        )
        for pid, qty in cart.items():
            try:
                p = Product.objects.get(pk=int(pid))
            except Product.DoesNotExist:
                continue
            OrderItem.objects.create(
                order=order,
                product=p,
                quantity=qty,
                price=p.get_price(),
            )
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
        qs = Product.objects.filter(is_active=True).filter(
            Q(name__icontains=q) | Q(description__icontains=q)
        )[:8]
        for p in qs:
            results.append({
                'id': p.pk,
                'name': p.name,
                'price': str(p.get_price()),
                'image': p.image.url if p.image else None,
            })
    return JsonResponse({'results': results})


@login_required
def recent_searches(request):
    """Show user's recent searches"""
    # Note: SearchQuery model not implemented yet
    searches = []
    # searches = SearchQuery.objects.filter(user=request.user).order_by('-created_at')[:20]
    return render(request, 'store/recent_searches.html', {'searches': searches})


def popular_searches(request):
    """Show popular search queries"""
    # Note: SearchQuery model not implemented yet
    popular = []
    # # Get top 20 most common search queries
    # popular = SearchQuery.objects.values('query').annotate(
    #     count=Count('id'),
    #     total_results=Sum('results_count')
    # ).order_by('-count')[:20]
    return render(request, 'store/popular_searches.html', {'popular': popular})


def trending_products(request):
    """Show trending products based on views and sales"""
    # Get most viewed products in last 7 days
    seven_days_ago = timezone.now() - timedelta(days=7)
    most_viewed = Product.objects.filter(
        is_active=True,
        views__viewed_at__gte=seven_days_ago
    ).annotate(
        view_count=Count('views')
    ).order_by('-view_count')[:12]
    
    # Get best sellers
    best_sellers = Product.objects.filter(
        is_active=True
    ).annotate(
        sales_count=Count('orderitem')
    ).order_by('-sales_count')[:12]
    
    return render(request, 'store/trending_products.html', {
        'most_viewed': most_viewed,
        'best_sellers': best_sellers,
    })


# --- Wishlist views (session-backed, minimal implementation) ---
@login_required
def wishlist_view(request):
    w = request.session.get('wishlist', [])
    products = Product.objects.filter(pk__in=w)
    total = sum([p.price for p in products])
    return render(request, 'wishlist/wishlist.html', {
        'wishlist_items': products,
        'total_value': total,
    })


@require_POST
@login_required
def wishlist_add(request, product_id):
    # Add product id to session wishlist
    try:
        p = Product.objects.get(pk=product_id, is_active=True)
    except Product.DoesNotExist:
        return JsonResponse({'error': 'not found'}, status=404)
    w = request.session.setdefault('wishlist', [])
    if product_id not in w:
        w.append(product_id)
        request.session.modified = True
    # If request is AJAX/Fetch, return JSON; otherwise redirect back
    if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.content_type == 'application/json':
        return JsonResponse({'status': 'ok', 'added': product_id})
    return redirect(request.META.get('HTTP_REFERER', 'store:product_list'))


@require_POST
@login_required
def wishlist_remove(request, product_id):
    w = request.session.get('wishlist', [])
    if product_id in w:
        try:
            w.remove(product_id)
            request.session.modified = True
        except ValueError:
            pass
    if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.content_type == 'application/json':
        return JsonResponse({'status': 'ok', 'removed': product_id})
    return redirect('store:wishlist')


@login_required
def wishlist_share(request):
    # Minimal sharing page (static form in template)
    w = request.session.get('wishlist', [])
    products = Product.objects.filter(pk__in=w)
    owner = request.user
    return render(request, 'wishlist/share.html', {'wishlist_items': products, 'owner': owner})


@login_required
def admin_dashboard(request):
    """Enhanced admin dashboard with KPIs and analytics"""
    from django.contrib.admin.views.decorators import staff_member_required
    from .models import OrderAnalytics, UserAnalytics, StockAlert
    
    # Require staff permission
    if not request.user.is_staff:
        from django.core.exceptions import PermissionDenied
        raise PermissionDenied
    
    today = timezone.now().date()
    yesterday = today - timedelta(days=1)
    this_week_start = today - timedelta(days=today.weekday())
    this_month_start = today.replace(day=1)
    
    # Get analytics
    try:
        today_analytics = OrderAnalytics.objects.get(date=today)
    except OrderAnalytics.DoesNotExist:
        today_analytics = None
    
    # This week analytics
    week_analytics = OrderAnalytics.objects.filter(
        date__gte=this_week_start
    ).aggregate(
        total_revenue=Sum('revenue'),
        total_orders=Sum('order_count'),
        total_items=Sum('items_sold')
    )
    
    # This month analytics
    month_analytics = OrderAnalytics.objects.filter(
        date__gte=this_month_start
    ).aggregate(
        total_revenue=Sum('revenue'),
        total_orders=Sum('order_count'),
        total_items=Sum('items_sold')
    )
    
    # Recent orders
    recent_orders = Order.objects.all().order_by('-created_at')[:10]
    
    # Top selling products
    top_products = Product.objects.filter(
        is_active=True
    ).annotate(
        total_sold=Sum('orderitem__quantity')
    ).order_by('-total_sold')[:10]
    
    # Low stock alerts
    low_stock_alerts = StockAlert.objects.filter(
        is_resolved=False
    ).order_by('-created_at')[:10]
    
    # User statistics
    try:
        user_analytics = UserAnalytics.objects.get(date=today)
    except UserAnalytics.DoesNotExist:
        user_analytics = None
    
    # Calculate conversion rate (orders / unique visitors)
    # For simplicity, we'll use view count as proxy for visitors
    total_views_today = ProductView.objects.filter(
        viewed_at__date=today
    ).count()
    unique_visitors_today = ProductView.objects.filter(
        viewed_at__date=today
    ).values('ip_address').distinct().count()
    
    orders_today = Order.objects.filter(created_at__date=today).count()
    conversion_rate = (orders_today / unique_visitors_today * 100) if unique_visitors_today > 0 else 0
    
    context = {
        'today_analytics': today_analytics,
        'week_analytics': week_analytics,
        'month_analytics': month_analytics,
        'recent_orders': recent_orders,
        'top_products': top_products,
        'low_stock_alerts': low_stock_alerts,
        'user_analytics': user_analytics,
        'conversion_rate': round(conversion_rate, 2),
        'total_views_today': total_views_today,
        'unique_visitors_today': unique_visitors_today,
    }
    
    return render(request, 'store/admin_dashboard.html', context)
