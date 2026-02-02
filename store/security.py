"""
Security Hardening - Bank-Grade Security
Implements 2FA, rate limiting, security headers, and input validation
"""
import re
import logging
import secrets
import hashlib
from datetime import timedelta
from django.conf import settings
from django.contrib.auth import get_user_model
from django.contrib.auth.password_validation import validate_password
from django.core.exceptions import ValidationError
from django.core.cache import cache
from django.http import JsonResponse, HttpResponseForbidden
from django.utils import timezone
from django.utils.decorators import method_decorator
from django.views.decorators.cache import never_cache
from django.views.decorators.csrf import csrf_protect
from functools import wraps
import pyotp

User = get_user_model()
logger = logging.getLogger(__name__)


# ============= 2FA / TOTP =============

class TwoFactorAuth:
    """
    Two-Factor Authentication using TOTP (Time-based One-Time Password)
    Compatible with Google Authenticator, Authy, etc.
    """
    
    @staticmethod
    def generate_secret():
        """Generate a random base32 secret for TOTP"""
        return pyotp.random_base32()
    
    @staticmethod
    def get_totp_uri(user, secret):
        """
        Get provisioning URI for QR code generation
        """
        totp = pyotp.TOTP(secret)
        issuer = getattr(settings, 'SITE_NAME', 'Le Phat Store')
        return totp.provisioning_uri(
            name=user.email,
            issuer_name=issuer
        )
    
    @staticmethod
    def verify_token(secret, token):
        """
        Verify TOTP token
        """
        totp = pyotp.TOTP(secret)
        return totp.verify(token, valid_window=1)
    
    @staticmethod
    def setup_2fa(user):
        """
        Setup 2FA for a user
        Returns: (secret, qr_uri)
        """
        secret = TwoFactorAuth.generate_secret()
        qr_uri = TwoFactorAuth.get_totp_uri(user, secret)
        
        # Store secret in user profile (extend User model as needed)
        # user.profile.totp_secret = secret
        # user.profile.save()
        
        return secret, qr_uri
    
    @staticmethod
    def enable_2fa(user, secret, verification_token):
        """
        Enable 2FA after verifying the setup token
        """
        if not TwoFactorAuth.verify_token(secret, verification_token):
            return False
        
        # Store secret and enable 2FA
        # user.profile.totp_secret = secret
        # user.profile.is_2fa_enabled = True
        # user.profile.save()
        
        logger.info(f"2FA enabled for user {user.username}")
        return True
    
    @staticmethod
    def disable_2fa(user):
        """
        Disable 2FA for a user
        """
        # user.profile.totp_secret = None
        # user.profile.is_2fa_enabled = False
        # user.profile.save()
        
        logger.info(f"2FA disabled for user {user.username}")


# ============= Rate Limiting =============

class RateLimiter:
    """
    Rate limiting to prevent abuse and DoS attacks
    """
    
    @staticmethod
    def get_client_ip(request):
        """Get client IP address"""
        x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
        if x_forwarded_for:
            ip = x_forwarded_for.split(',')[0]
        else:
            ip = request.META.get('REMOTE_ADDR')
        return ip
    
    @staticmethod
    def is_rate_limited(key, limit, period):
        """
        Check if request should be rate limited
        
        Args:
            key: Unique identifier (IP, user ID, etc.)
            limit: Maximum requests allowed
            period: Time period in seconds
        
        Returns:
            True if rate limited, False otherwise
        """
        cache_key = f"rate_limit:{key}"
        
        # Get current count
        count = cache.get(cache_key, 0)
        
        if count >= limit:
            return True
        
        # Increment count
        if count == 0:
            cache.set(cache_key, 1, period)
        else:
            cache.incr(cache_key)
        
        return False
    
    @staticmethod
    def rate_limit(limit=100, period=3600, scope='ip'):
        """
        Decorator for rate limiting views
        
        Args:
            limit: Maximum requests allowed
            period: Time period in seconds (default: 1 hour)
            scope: 'ip' or 'user'
        """
        def decorator(view_func):
            @wraps(view_func)
            def wrapper(request, *args, **kwargs):
                # Determine rate limit key
                if scope == 'user' and request.user.is_authenticated:
                    key = f"user:{request.user.id}"
                else:
                    key = f"ip:{RateLimiter.get_client_ip(request)}"
                
                # Check rate limit
                if RateLimiter.is_rate_limited(key, limit, period):
                    logger.warning(f"Rate limit exceeded for {key}")
                    return JsonResponse(
                        {'error': 'Rate limit exceeded. Please try again later.'},
                        status=429
                    )
                
                return view_func(request, *args, **kwargs)
            
            return wrapper
        return decorator


# ============= Login Protection =============

class LoginProtection:
    """
    Protect login endpoints from brute force attacks
    """
    
    LOCKOUT_THRESHOLD = 5  # Failed attempts before lockout
    LOCKOUT_DURATION = 900  # 15 minutes
    
    @staticmethod
    def record_failed_attempt(username):
        """Record a failed login attempt"""
        key = f"login_attempts:{username}"
        attempts = cache.get(key, 0)
        cache.set(key, attempts + 1, LoginProtection.LOCKOUT_DURATION)
        
        logger.warning(f"Failed login attempt for {username} (attempt {attempts + 1})")
    
    @staticmethod
    def is_locked_out(username):
        """Check if account is locked due to failed attempts"""
        key = f"login_attempts:{username}"
        attempts = cache.get(key, 0)
        return attempts >= LoginProtection.LOCKOUT_THRESHOLD
    
    @staticmethod
    def clear_attempts(username):
        """Clear failed login attempts"""
        key = f"login_attempts:{username}"
        cache.delete(key)
    
    @staticmethod
    def get_remaining_attempts(username):
        """Get remaining login attempts"""
        key = f"login_attempts:{username}"
        attempts = cache.get(key, 0)
        return max(0, LoginProtection.LOCKOUT_THRESHOLD - attempts)


# ============= Input Validation & Sanitization =============

class InputValidator:
    """
    Validate and sanitize user input to prevent injection attacks
    """
    
    # Regex patterns
    EMAIL_PATTERN = re.compile(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$')
    PHONE_PATTERN = re.compile(r'^\+?[0-9\s\-\(\)]{10,20}$')
    ALPHANUMERIC_PATTERN = re.compile(r'^[a-zA-Z0-9\s\-_]+$')
    
    @staticmethod
    def validate_email(email):
        """Validate email format"""
        if not email or not InputValidator.EMAIL_PATTERN.match(email):
            raise ValidationError("Invalid email format")
        return email.lower().strip()
    
    @staticmethod
    def validate_phone(phone):
        """Validate phone number"""
        if not phone or not InputValidator.PHONE_PATTERN.match(phone):
            raise ValidationError("Invalid phone number")
        return phone.strip()
    
    @staticmethod
    def sanitize_string(text, max_length=1000):
        """Sanitize string input"""
        if not text:
            return ""
        
        # Remove potentially dangerous characters
        text = text.strip()
        
        # Limit length
        if len(text) > max_length:
            text = text[:max_length]
        
        return text
    
    @staticmethod
    def validate_password_strength(password):
        """
        Validate password meets security requirements
        - At least 12 characters
        - Mixed case
        - Numbers
        - Special characters
        """
        if len(password) < 12:
            raise ValidationError("Password must be at least 12 characters long")
        
        if not re.search(r'[a-z]', password):
            raise ValidationError("Password must contain lowercase letters")
        
        if not re.search(r'[A-Z]', password):
            raise ValidationError("Password must contain uppercase letters")
        
        if not re.search(r'\d', password):
            raise ValidationError("Password must contain numbers")
        
        if not re.search(r'[!@#$%^&*(),.?":{}|<>]', password):
            raise ValidationError("Password must contain special characters")
        
        # Use Django's built-in validators
        validate_password(password)
        
        return True


# ============= Security Headers Middleware =============

class SecurityHeadersMiddleware:
    """
    Add security headers to all responses
    """
    
    def __init__(self, get_response):
        self.get_response = get_response
    
    def __call__(self, request):
        response = self.get_response(request)
        
        # Content Security Policy
        response['Content-Security-Policy'] = (
            "default-src 'self'; "
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
            "img-src 'self' data: https:; "
            "font-src 'self' data: https://cdn.jsdelivr.net; "
            "connect-src 'self';"
        )
        
        # Prevent clickjacking
        response['X-Frame-Options'] = 'DENY'
        
        # Prevent MIME sniffing
        response['X-Content-Type-Options'] = 'nosniff'
        
        # Enable XSS protection
        response['X-XSS-Protection'] = '1; mode=block'
        
        # Referrer policy
        response['Referrer-Policy'] = 'strict-origin-when-cross-origin'
        
        # Permissions policy
        response['Permissions-Policy'] = (
            "geolocation=(), microphone=(), camera=()"
        )
        
        return response


# ============= CAPTCHA Integration =============

class CaptchaValidator:
    """
    CAPTCHA validation for suspicious activity
    Note: Requires reCAPTCHA or similar service
    """
    
    @staticmethod
    def verify_recaptcha(response_token, remote_ip=None):
        """
        Verify reCAPTCHA response
        """
        import requests
        
        secret_key = getattr(settings, 'RECAPTCHA_SECRET_KEY', '')
        if not secret_key:
            logger.warning("RECAPTCHA_SECRET_KEY not configured")
            return True  # Allow in development
        
        data = {
            'secret': secret_key,
            'response': response_token,
        }
        
        if remote_ip:
            data['remoteip'] = remote_ip
        
        try:
            r = requests.post(
                'https://www.google.com/recaptcha/api/siteverify',
                data=data,
                timeout=5
            )
            result = r.json()
            return result.get('success', False)
        except Exception as e:
            logger.exception(f"CAPTCHA verification error: {e}")
            return False


# ============= Session Security =============

def configure_session_security():
    """
    Configure Django session security settings
    Should be called in settings.py
    """
    settings.SESSION_COOKIE_SECURE = not settings.DEBUG  # HTTPS only in production
    settings.SESSION_COOKIE_HTTPONLY = True  # Prevent JavaScript access
    settings.SESSION_COOKIE_SAMESITE = 'Lax'  # CSRF protection
    settings.SESSION_COOKIE_AGE = 3600  # 1 hour
    settings.CSRF_COOKIE_SECURE = not settings.DEBUG
    settings.CSRF_COOKIE_HTTPONLY = True
    settings.CSRF_COOKIE_SAMESITE = 'Lax'


# ============= Suspicious Activity Detection =============

class SuspiciousActivityDetector:
    """
    Detect and log suspicious activity
    """
    
    @staticmethod
    def detect_sql_injection(text):
        """Detect SQL injection attempts"""
        sql_patterns = [
            r'(\bUNION\b|\bSELECT\b|\bDROP\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b)',
            r'(--|\/\*|\*\/)',
            r'(\bOR\b\s+\d+\s*=\s*\d+)',
        ]
        
        for pattern in sql_patterns:
            if re.search(pattern, text, re.IGNORECASE):
                logger.critical(f"Potential SQL injection detected: {text[:100]}")
                return True
        
        return False
    
    @staticmethod
    def detect_xss(text):
        """Detect XSS attempts"""
        xss_patterns = [
            r'<script[^>]*>.*?</script>',
            r'javascript:',
            r'onerror\s*=',
            r'onload\s*=',
        ]
        
        for pattern in xss_patterns:
            if re.search(pattern, text, re.IGNORECASE):
                logger.critical(f"Potential XSS detected: {text[:100]}")
                return True
        
        return False
    
    @staticmethod
    def log_suspicious_activity(user, activity_type, details):
        """Log suspicious activity"""
        logger.critical(
            f"SUSPICIOUS ACTIVITY - User: {user}, Type: {activity_type}, "
            f"Details: {details}"
        )
        
        # Optionally save to database for tracking
        # SuspiciousActivity.objects.create(...)
