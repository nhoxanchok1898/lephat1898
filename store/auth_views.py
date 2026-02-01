from django.shortcuts import render, redirect
from django.contrib.auth import login, authenticate, logout
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.contrib import messages
from django.core.mail import send_mail
from django.conf import settings
from django.contrib.auth.tokens import default_token_generator
from django.utils.http import urlsafe_base64_encode, urlsafe_base64_decode
from django.utils.encoding import force_bytes, force_str
from django.template.loader import render_to_string

from .models import UserProfile, EmailLog


def register_view(request):
    """User registration with email verification"""
    if request.method == 'POST':
        username = request.POST.get('username')
        email = request.POST.get('email')
        password = request.POST.get('password')
        password2 = request.POST.get('password2')
        
        # Validation
        if password != password2:
            messages.error(request, 'Passwords do not match.')
            return render(request, 'auth/register.html')
        
        if User.objects.filter(username=username).exists():
            messages.error(request, 'Username already exists.')
            return render(request, 'auth/register.html')
        
        if User.objects.filter(email=email).exists():
            messages.error(request, 'Email already registered.')
            return render(request, 'auth/register.html')
        
        # Create user
        user = User.objects.create_user(username=username, email=email, password=password)
        UserProfile.objects.create(user=user)
        
        # Send welcome email
        try:
            subject = 'Welcome to Our Paint Store!'
            message = f'Hello {username},\n\nThank you for registering at our paint store. We are excited to have you as a customer!\n\nBest regards,\nPaint Store Team'
            send_mail(subject, message, settings.DEFAULT_FROM_EMAIL, [email])
            EmailLog.objects.create(
                recipient=email,
                subject=subject,
                template_name='welcome_email',
                status='sent'
            )
        except Exception as e:
            EmailLog.objects.create(
                recipient=email,
                subject='Welcome to Our Paint Store!',
                template_name='welcome_email',
                status='failed',
                error_message=str(e)
            )
        
        messages.success(request, 'Registration successful! You can now log in.')
        return redirect('store:login')
    
    return render(request, 'auth/register.html')


def login_view(request):
    """User login"""
    if request.method == 'POST':
        username = request.POST.get('username')
        password = request.POST.get('password')
        
        user = authenticate(request, username=username, password=password)
        if user is not None:
            login(request, user)
            messages.success(request, f'Welcome back, {username}!')
            next_url = request.GET.get('next', 'store:home')
            return redirect(next_url)
        else:
            messages.error(request, 'Invalid username or password.')
    
    return render(request, 'auth/login.html')


def logout_view(request):
    """User logout"""
    logout(request)
    messages.success(request, 'You have been logged out.')
    return redirect('store:home')


@login_required
def profile_view(request):
    """User profile page"""
    profile, created = UserProfile.objects.get_or_create(user=request.user)
    return render(request, 'auth/profile.html', {'profile': profile})


@login_required
def profile_update(request):
    """Update user profile"""
    profile, created = UserProfile.objects.get_or_create(user=request.user)
    
    if request.method == 'POST':
        # Update user info
        request.user.first_name = request.POST.get('first_name', '')
        request.user.last_name = request.POST.get('last_name', '')
        request.user.email = request.POST.get('email', '')
        request.user.save()
        
        # Update profile info
        profile.phone = request.POST.get('phone', '')
        profile.address = request.POST.get('address', '')
        profile.save()
        
        messages.success(request, 'Profile updated successfully!')
        return redirect('store:profile')
    
    return render(request, 'auth/profile_update.html', {'profile': profile})


def password_reset_request(request):
    """Request password reset"""
    if request.method == 'POST':
        email = request.POST.get('email')
        
        try:
            user = User.objects.get(email=email)
            # Generate token
            token = default_token_generator.make_token(user)
            uid = urlsafe_base64_encode(force_bytes(user.pk))
            
            # Send reset email
            reset_url = request.build_absolute_uri(
                f'/auth/password-reset/{uid}/{token}/'
            )
            subject = 'Password Reset Request'
            message = f'Click the link to reset your password: {reset_url}'
            send_mail(subject, message, settings.DEFAULT_FROM_EMAIL, [email])
            
            EmailLog.objects.create(
                recipient=email,
                subject=subject,
                template_name='password_reset',
                status='sent'
            )
            messages.success(request, 'Password reset link sent to your email.')
        except User.DoesNotExist:
            messages.error(request, 'No user found with that email address.')
        except Exception as e:
            messages.error(request, 'Error sending reset email.')
    
    return render(request, 'auth/password_reset_request.html')


def password_reset_confirm(request, uidb64, token):
    """Confirm password reset with token"""
    try:
        uid = force_str(urlsafe_base64_decode(uidb64))
        user = User.objects.get(pk=uid)
    except (TypeError, ValueError, OverflowError, User.DoesNotExist):
        user = None
    
    if user is not None and default_token_generator.check_token(user, token):
        if request.method == 'POST':
            password = request.POST.get('password')
            password2 = request.POST.get('password2')
            
            if password == password2:
                user.set_password(password)
                user.save()
                messages.success(request, 'Password reset successful! You can now log in.')
                return redirect('store:login')
            else:
                messages.error(request, 'Passwords do not match.')
        
        return render(request, 'auth/password_reset_confirm.html', {'validlink': True})
    else:
        messages.error(request, 'Invalid or expired reset link.')
        return render(request, 'auth/password_reset_confirm.html', {'validlink': False})
