from django.shortcuts import render, redirect
from django.contrib.auth import authenticate, login, logout
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
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
    from .models import LoginAttempt, SuspiciousActivity
except Exception:
    LoginAttempt = None
    SuspiciousActivity = None


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
        form = UserCreationForm(request.POST)
        if form.is_valid():
            user = form.save()
            login(request, user)
            messages.success(request, 'Registration successful!')
            return redirect('store:home')
        else:
            messages.error(request, 'Registration failed. Please check the form.')
    else:
        form = UserCreationForm()
    
    return render(request, 'registration/register.html', {'form': form})


def logout_view(request):
    """User logout"""
    logout(request)
    messages.success(request, 'You have been logged out.')
    return redirect('store:home')
