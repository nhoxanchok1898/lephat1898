"""
Authentication Views
Handles user registration, login, logout, and profile management
"""
from django.shortcuts import render, redirect
from django.contrib.auth import login, logout, authenticate
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.contrib import messages
from django.db import IntegrityError
from .models import UserProfile, Order


def register_view(request):
    """
    User registration view
    Handles GET (display form) and POST (process registration)
    """
    if request.user.is_authenticated:
        return redirect('store:home')
    
    if request.method == 'POST':
        username = request.POST.get('username', '').strip()
        email = request.POST.get('email', '').strip()
        password = request.POST.get('password', '')
        password_confirm = request.POST.get('password_confirm', '')
        
        # Validation
        if not username or not email or not password:
            messages.error(request, 'All fields are required.')
            return render(request, 'auth/register.html')
        
        if password != password_confirm:
            messages.error(request, 'Passwords do not match.')
            return render(request, 'auth/register.html')
        
        if len(password) < 6:
            messages.error(request, 'Password must be at least 6 characters long.')
            return render(request, 'auth/register.html')
        
        # Check if username exists
        if User.objects.filter(username=username).exists():
            messages.error(request, 'Username already exists.')
            return render(request, 'auth/register.html')
        
        # Check if email exists
        if User.objects.filter(email=email).exists():
            messages.error(request, 'Email already registered.')
            return render(request, 'auth/register.html')
        
        # Create user
        try:
            user = User.objects.create_user(
                username=username,
                email=email,
                password=password
            )
            
            # Log the user in
            login(request, user)
            messages.success(request, f'Welcome {username}! Your account has been created.')
            return redirect('store:home')
        
        except IntegrityError:
            messages.error(request, 'An error occurred. Please try again.')
            return render(request, 'auth/register.html')
    
    return render(request, 'auth/register.html')


def login_view(request):
    """
    User login view
    Handles GET (display form) and POST (process login)
    """
    if request.user.is_authenticated:
        return redirect('store:home')
    
    if request.method == 'POST':
        username = request.POST.get('username', '').strip()
        password = request.POST.get('password', '')
        
        if not username or not password:
            messages.error(request, 'Username and password are required.')
            return render(request, 'auth/login.html')
        
        # Authenticate user
        user = authenticate(request, username=username, password=password)
        
        if user is not None:
            login(request, user)
            messages.success(request, f'Welcome back, {user.username}!')
            
            # Redirect to next page if specified
            next_url = request.GET.get('next', request.POST.get('next', 'store:home'))
            return redirect(next_url)
        else:
            messages.error(request, 'Invalid username or password.')
            return render(request, 'auth/login.html')
    
    return render(request, 'auth/login.html')


def logout_view(request):
    """
    User logout view
    Logs out the user and redirects to home page
    """
    if request.user.is_authenticated:
        username = request.user.username
        logout(request)
        messages.success(request, f'Goodbye, {username}! You have been logged out.')
    
    return redirect('store:home')


@login_required
def profile_view(request):
    """
    User profile view
    Display and update user profile information
    """
    user = request.user
    
    # Get or create user profile
    try:
        profile = user.profile
    except UserProfile.DoesNotExist:
        profile = UserProfile.objects.create(user=user)
    
    if request.method == 'POST':
        # Update profile
        email = request.POST.get('email', '').strip()
        phone = request.POST.get('phone', '').strip()
        address = request.POST.get('address', '').strip()
        
        # Update user email
        if email and email != user.email:
            if User.objects.filter(email=email).exclude(pk=user.pk).exists():
                messages.error(request, 'Email already in use by another account.')
            else:
                user.email = email
                user.save()
        
        # Update profile
        profile.phone = phone
        profile.address = address
        profile.save()
        
        messages.success(request, 'Profile updated successfully.')
        return redirect('store:profile')
    
    # Get user's recent orders
    recent_orders = Order.objects.filter(
        full_name__icontains=user.username
    ).order_by('-created_at')[:5]
    
    context = {
        'user': user,
        'profile': profile,
        'recent_orders': recent_orders,
    }
    
    return render(request, 'auth/profile.html', context)
