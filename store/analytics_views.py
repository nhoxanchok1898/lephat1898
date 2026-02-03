from django.shortcuts import render
from django.http import JsonResponse
from django.views.decorators.http import require_GET
from django.contrib.admin.views.decorators import staff_member_required
from django.db.models import Count, Sum, Avg, F
from django.utils import timezone
from datetime import timedelta
from django.contrib.auth.models import User
from .models import Order, OrderItem, Product, ProductView, OrderAnalytics, UserAnalytics


@staff_member_required
@require_GET
def admin_dashboard(request):
    """Main admin dashboard view"""
    # Get basic stats
    today = timezone.now().date()
    week_ago = today - timedelta(days=7)
    month_ago = today - timedelta(days=30)
    
    # Today's stats
    today_orders = Order.objects.filter(created_at__date=today)
    today_revenue = today_orders.aggregate(
        total=Sum(F('items__price') * F('items__quantity'))
    )['total'] or 0
    
    # This week's stats
    week_orders = Order.objects.filter(created_at__date__gte=week_ago)
    week_revenue = week_orders.aggregate(
        total=Sum(F('items__price') * F('items__quantity'))
    )['total'] or 0
    
    # This month's stats
    month_orders = Order.objects.filter(created_at__date__gte=month_ago)
    month_revenue = month_orders.aggregate(
        total=Sum(F('items__price') * F('items__quantity'))
    )['total'] or 0
    
    # Recent orders
    recent_orders = Order.objects.order_by('-created_at')[:10]
    
    # Top products
    top_products = Product.objects.filter(
        orderitem__order__created_at__date__gte=month_ago
    ).annotate(
        sales_count=Count('orderitem')
    ).order_by('-sales_count')[:5]
    
    # User stats
    total_users = User.objects.count()
    new_users_week = User.objects.filter(date_joined__gte=week_ago).count()
    
    context = {
        'today_orders': today_orders.count(),
        'today_revenue': today_revenue,
        'week_orders': week_orders.count(),
        'week_revenue': week_revenue,
        'month_orders': month_orders.count(),
        'month_revenue': month_revenue,
        'recent_orders': recent_orders,
        'top_products': top_products,
        'total_users': total_users,
        'new_users_week': new_users_week,
    }
    
    return render(request, 'admin/dashboard.html', context)


@staff_member_required
@require_GET
def analytics_data(request):
    """Get analytics data for dashboard"""
    days = int(request.GET.get('days', 30))
    end_date = timezone.now().date()
    start_date = end_date - timedelta(days=days)
    
    # Sales data
    sales = Order.objects.filter(
        created_at__date__gte=start_date
    ).extra(
        select={'date': 'DATE(created_at)'}
    ).values('date').annotate(
        count=Count('id'),
        revenue=Sum(F('items__price') * F('items__quantity'))
    ).order_by('date')
    
    return JsonResponse({
        'sales': list(sales),
        'period': f'{days} days'
    })


@staff_member_required
@require_GET
def sales_chart_data(request):
    """Get sales data for charts"""
    days = int(request.GET.get('days', 30))
    end_date = timezone.now().date()
    start_date = end_date - timedelta(days=days)
    
    # Daily sales trend
    daily_sales = []
    current_date = start_date
    while current_date <= end_date:
        day_orders = Order.objects.filter(created_at__date=current_date)
        revenue = day_orders.aggregate(
            total=Sum(F('items__price') * F('items__quantity'))
        )['total'] or 0
        
        daily_sales.append({
            'date': current_date.isoformat(),
            'orders': day_orders.count(),
            'revenue': float(revenue),
        })
        current_date += timedelta(days=1)
    
    # Top products by revenue
    top_products = Product.objects.filter(
        orderitem__order__created_at__date__gte=start_date
    ).annotate(
        revenue=Sum(F('orderitem__price') * F('orderitem__quantity')),
        sales_count=Count('orderitem')
    ).order_by('-revenue')[:10]
    
    product_data = [{
        'name': p.name,
        'revenue': float(p.revenue),
        'sales': p.sales_count,
    } for p in top_products]
    
    return JsonResponse({
        'daily_sales': daily_sales,
        'top_products': product_data,
    })


@staff_member_required
@require_GET
def product_performance(request):
    """Get detailed product performance metrics"""
    product_id = request.GET.get('product_id')
    days = int(request.GET.get('days', 30))
    
    if product_id:
        products = Product.objects.filter(pk=product_id)
    else:
        products = Product.objects.filter(is_active=True)
    
    end_date = timezone.now().date()
    start_date = end_date - timedelta(days=days)
    
    performance_data = []
    for product in products[:20]:  # Limit to 20 products
        # Views
        views = ProductView.objects.filter(
            product=product,
            viewed_at__date__gte=start_date
        ).count()
        
        # Sales
        sales = OrderItem.objects.filter(
            product=product,
            order__created_at__date__gte=start_date
        ).aggregate(
            count=Count('id'),
            revenue=Sum(F('price') * F('quantity'))
        )
        
        # Conversion rate
        conversion_rate = (sales['count'] / views * 100) if views > 0 else 0
        
        performance_data.append({
            'product_id': product.id,
            'product_name': product.name,
            'views': views,
            'sales': sales['count'] or 0,
            'revenue': float(sales['revenue'] or 0),
            'conversion_rate': round(conversion_rate, 2),
        })
    
    return JsonResponse({
        'performance': performance_data,
        'period': f'{days} days'
    })
