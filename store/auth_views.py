from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth import authenticate, login, logout
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
from django.contrib.auth.decorators import login_required
from django.contrib import messages
try:
    from django_ratelimit.decorators import ratelimit
except Exception:
    # Provide a no-op fallback so the module can be imported in environments
    # where `django-ratelimit` isn't installed (development/test).
    def ratelimit(*args, **kwargs):
        def _decorator(fn):
            return fn
        return _decorator
from django.views.decorators.http import require_POST
from django.utils import timezone
from datetime import timedelta
try:
    from .models import LoginAttempt, SuspiciousActivity, UserProfile
except Exception:
    LoginAttempt = None
    SuspiciousActivity = None
    UserProfile = None


def get_client_ip(request):
    """Get client IP address from request"""
    x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
    if x_forwarded_for:
        ip = x_forwarded_for.split(',')[0]
    else:
        ip = request.META.get('REMOTE_ADDR')
    return ip


@ratelimit(key='ip', rate='5/15m', method='POST', block=True)
@require_POST
def login_view(request):
    """Login with rate limiting (5 attempts per 15 minutes)"""
    form = AuthenticationForm(request, data=request.POST)
    ip_address = get_client_ip(request)
    user_agent = request.META.get('HTTP_USER_AGENT', '')[:255]
    
    if form.is_valid():
        username = form.cleaned_data.get('username')
        password = form.cleaned_data.get('password')
        user = authenticate(username=username, password=password)
        
        if user is not None:
            login(request, user)
            
            # Log successful login (if model exists)
            try:
                if LoginAttempt is not None:
                    LoginAttempt.objects.create(
                        username=username,
                        ip_address=ip_address,
                        success=True,
                        user_agent=user_agent
                    )
            except Exception:
                pass
            
            messages.success(request, f'Welcome back, {username}!')
            return redirect('store:home')
        else:
            # Log failed login and check for suspicious activity (if models exist)
            try:
                if LoginAttempt is not None:
                    LoginAttempt.objects.create(
                        username=username,
                        ip_address=ip_address,
                        success=False,
                        user_agent=user_agent
                    )

                    recent_failures = LoginAttempt.objects.filter(
                        ip_address=ip_address,
                        success=False,
                        timestamp__gte=timezone.now() - timedelta(minutes=15)
                    ).count()

                    if recent_failures >= 3 and SuspiciousActivity is not None:
                        SuspiciousActivity.objects.create(
                            activity_type='multiple_failed_logins',
                            description=f'Multiple failed login attempts from {ip_address}',
                            ip_address=ip_address
                        )
            except Exception:
                pass
            
            messages.error(request, 'Invalid username or password.')
    else:
        messages.error(request, 'Invalid username or password.')
    
    return redirect('store:home')


def register_view(request):
    """User registration"""
    if request.method == 'POST':
        username = request.POST.get('username', '').strip()
        email = request.POST.get('email', '').strip()
        password = request.POST.get('password', '')
        password2 = request.POST.get('password2', '')
        
        # Validate form data
        errors = []
        
        if not username or not email or not password or not password2:
            errors.append('All fields are required')
        
        if password != password2:
            errors.append('Passwords do not match')
        
        # Check if username already exists
        from django.contrib.auth.models import User
        if username and User.objects.filter(username=username).exists():
            errors.append('Username already exists')
        
        if errors:
            for error in errors:
                messages.error(request, error)
            form = UserCreationForm()
            return render(request, 'auth/register.html', {'form': form})
        
        # Create user
        try:
            user = User.objects.create_user(username=username, email=email, password=password)
            # UserProfile is automatically created by post_save signal
            
            login(request, user)
            messages.success(request, 'Registration successful!')
            return redirect('store:home')
        except Exception as e:
            messages.error(request, f'Registration failed: {str(e)}')
    
    form = UserCreationForm()
    return render(request, 'auth/register.html', {'form': form})


def logout_view(request):
    """User logout"""
    logout(request)
    messages.success(request, 'You have been logged out.')
    return redirect('store:home')


@login_required
def profile_view(request):
    """User profile view"""
    user = request.user
    
    # Get or create user profile
    if UserProfile is not None:
        profile, created = UserProfile.objects.get_or_create(user=user)
    else:
        profile = None
    
    return render(request, 'auth/profile.html', {
        'user': user,
        'profile': profile
    })
