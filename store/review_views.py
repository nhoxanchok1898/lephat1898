from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib.admin.views.decorators import staff_member_required
from django.contrib import messages
from django.http import JsonResponse
from django.views.decorators.http import require_POST
from django.db.models import Avg

from .models import Review, ReviewImage, ReviewHelpful, Product, Order, OrderItem


@login_required
def review_create(request, pk):
    """Create a review for a product"""
    product = get_object_or_404(Product, pk=pk, is_active=True)
    
    # Check if user already reviewed this product
    if Review.objects.filter(product=product, user=request.user).exists():
        messages.error(request, 'You have already reviewed this product.')
        return redirect('store:product_detail', pk=pk)
    
    if request.method == 'POST':
        rating = request.POST.get('rating')
        comment = request.POST.get('comment')
        
        # Validate rating
        try:
            rating = int(rating)
            if not 1 <= rating <= 5:
                raise ValueError
        except (TypeError, ValueError):
            messages.error(request, 'Please select a valid rating (1-5 stars).')
            return redirect('store:review_create', pk=pk)
        
        # Check if this is a verified purchase
        verified_purchase = OrderItem.objects.filter(
            product=product,
            order__full_name__icontains=request.user.username
        ).exists()
        
        # Create review
        review = Review.objects.create(
            product=product,
            user=request.user,
            rating=rating,
            comment=comment,
            verified_purchase=verified_purchase,
            is_approved=False  # Requires admin approval
        )
        
        # Handle image uploads
        images = request.FILES.getlist('images')
        for image in images[:5]:  # Limit to 5 images
            ReviewImage.objects.create(review=review, image=image)
        
        messages.success(request, 'Your review has been submitted and is awaiting approval.')
        return redirect('store:product_detail', pk=pk)
    
    return render(request, 'reviews/review_form.html', {'product': product})


def review_list(request, pk):
    """List reviews for a product"""
    product = get_object_or_404(Product, pk=pk, is_active=True)
    reviews = Review.objects.filter(
        product=product,
        is_approved=True
    ).select_related('user').prefetch_related('images')
    
    # Calculate average rating
    avg_rating = reviews.aggregate(Avg('rating'))['rating__avg'] or 0
    
    return render(request, 'reviews/review_list.html', {
        'product': product,
        'reviews': reviews,
        'avg_rating': round(avg_rating, 1)
    })


@staff_member_required
def review_approve(request, pk):
    """Approve a review (admin only)"""
    review = get_object_or_404(Review, pk=pk)
    
    if request.method == 'POST':
        review.is_approved = True
        review.save()
        messages.success(request, 'Review approved successfully.')
        
        # Send notification email to user
        try:
            from django.core.mail import send_mail
            from django.conf import settings
            from .models import EmailLog
            
            subject = 'Your review has been approved'
            message = f'Hello {review.user.username},\n\nYour review for {review.product.name} has been approved and is now visible to other customers.\n\nThank you for your feedback!'
            send_mail(subject, message, settings.DEFAULT_FROM_EMAIL, [review.user.email])
            
            EmailLog.objects.create(
                recipient=review.user.email,
                subject=subject,
                template_name='review_approved',
                status='sent'
            )
        except Exception:
            pass
    
    return redirect('admin:store_review_changelist')


@login_required
@require_POST
def review_helpful(request, pk):
    """Mark a review as helpful"""
    review = get_object_or_404(Review, pk=pk, is_approved=True)
    
    # Toggle helpful vote
    vote, created = ReviewHelpful.objects.get_or_create(
        review=review,
        user=request.user
    )
    
    if not created:
        # Remove vote if already exists
        vote.delete()
        review.helpful_count = max(0, review.helpful_count - 1)
        action = 'removed'
    else:
        # Add vote
        review.helpful_count += 1
        action = 'added'
    
    review.save()
    
    # Return JSON for AJAX requests
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'action': action,
            'helpful_count': review.helpful_count
        })
    
    return redirect('store:product_detail', pk=review.product.pk)


@staff_member_required
def review_moderate(request):
    """List all reviews for moderation (admin only)"""
    pending_reviews = Review.objects.filter(is_approved=False).select_related('product', 'user')
    approved_reviews = Review.objects.filter(is_approved=True).select_related('product', 'user')[:20]
    
    return render(request, 'reviews/moderate.html', {
        'pending_reviews': pending_reviews,
        'approved_reviews': approved_reviews
    })


@staff_member_required
@require_POST
def review_delete(request, pk):
    """Delete a review (admin only)"""
    review = get_object_or_404(Review, pk=pk)
    product_pk = review.product.pk
    review.delete()
    
    messages.success(request, 'Review deleted successfully.')
    return redirect('store:product_detail', pk=product_pk)
