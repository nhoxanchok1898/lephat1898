"""
Monitoring + Logging - Production Ready
Sentry integration, health checks, and comprehensive logging
"""
import logging
import json
import time
from datetime import datetime
from django.conf import settings
from django.http import JsonResponse
from django.db import connection
from django.core.cache import cache
from django.core.mail import send_mail
from django.views.decorators.http import require_GET
from django.views.decorators.cache import never_cache

logger = logging.getLogger(__name__)


# ============= Sentry Integration =============

def init_sentry():
    """
    Initialize Sentry for error tracking
    """
    try:
        import sentry_sdk
        from sentry_sdk.integrations.django import DjangoIntegration
        
        sentry_dsn = getattr(settings, 'SENTRY_DSN', None)
        
        if sentry_dsn:
            sentry_sdk.init(
                dsn=sentry_dsn,
                integrations=[DjangoIntegration()],
                traces_sample_rate=0.1,  # 10% performance monitoring
                send_default_pii=False,  # Don't send PII
                environment=settings.ENVIRONMENT if hasattr(settings, 'ENVIRONMENT') else 'production',
                release=getattr(settings, 'RELEASE_VERSION', 'unknown'),
            )
            logger.info("Sentry initialized successfully")
        else:
            logger.warning("SENTRY_DSN not configured, error tracking disabled")
    
    except ImportError:
        logger.warning("sentry-sdk not installed, error tracking disabled")
    except Exception as e:
        logger.exception(f"Error initializing Sentry: {e}")


def capture_exception(exception, context=None):
    """
    Capture exception with context
    """
    try:
        import sentry_sdk
        
        if context:
            with sentry_sdk.push_scope() as scope:
                for key, value in context.items():
                    scope.set_context(key, value)
                sentry_sdk.capture_exception(exception)
        else:
            sentry_sdk.capture_exception(exception)
    
    except ImportError:
        logger.exception(f"Exception (Sentry not available): {exception}")
    except Exception as e:
        logger.exception(f"Error capturing exception: {e}")


def capture_message(message, level='info', context=None):
    """
    Capture message with context
    """
    try:
        import sentry_sdk
        
        if context:
            with sentry_sdk.push_scope() as scope:
                for key, value in context.items():
                    scope.set_context(key, value)
                sentry_sdk.capture_message(message, level=level)
        else:
            sentry_sdk.capture_message(message, level=level)
    
    except ImportError:
        logger.log(getattr(logging, level.upper(), logging.INFO), message)
    except Exception as e:
        logger.exception(f"Error capturing message: {e}")


# ============= Health Checks =============

@require_GET
@never_cache
def health_check(request):
    """
    Comprehensive health check endpoint
    Checks database, cache, and external services
    """
    health = {
        'status': 'healthy',
        'timestamp': datetime.utcnow().isoformat(),
        'checks': {},
    }
    
    # Check database
    db_health = check_database()
    health['checks']['database'] = db_health
    
    # Check cache
    cache_health = check_cache()
    health['checks']['cache'] = cache_health
    
    # Check email
    email_health = check_email_service()
    health['checks']['email'] = email_health
    
    # Determine overall status
    all_healthy = all(
        check['status'] == 'healthy' 
        for check in health['checks'].values()
    )
    
    health['status'] = 'healthy' if all_healthy else 'degraded'
    
    status_code = 200 if all_healthy else 503
    
    return JsonResponse(health, status=status_code)


def check_database():
    """Check database connectivity"""
    try:
        start_time = time.time()
        
        # Execute a simple query
        with connection.cursor() as cursor:
            cursor.execute("SELECT 1")
            cursor.fetchone()
        
        response_time = (time.time() - start_time) * 1000  # ms
        
        return {
            'status': 'healthy',
            'response_time_ms': round(response_time, 2),
        }
    
    except Exception as e:
        logger.exception(f"Database health check failed: {e}")
        return {
            'status': 'unhealthy',
            'error': str(e),
        }


def check_cache():
    """Check cache connectivity"""
    try:
        start_time = time.time()
        
        # Test cache set/get
        test_key = 'health_check_test'
        test_value = str(time.time())
        
        cache.set(test_key, test_value, 10)
        retrieved = cache.get(test_key)
        
        if retrieved != test_value:
            raise Exception("Cache value mismatch")
        
        cache.delete(test_key)
        
        response_time = (time.time() - start_time) * 1000  # ms
        
        return {
            'status': 'healthy',
            'response_time_ms': round(response_time, 2),
        }
    
    except Exception as e:
        logger.exception(f"Cache health check failed: {e}")
        return {
            'status': 'unhealthy',
            'error': str(e),
        }


def check_email_service():
    """Check email service"""
    try:
        # Just check if email backend is configured
        from django.core.mail import get_connection
        
        connection = get_connection()
        
        return {
            'status': 'healthy',
            'backend': type(connection).__name__,
        }
    
    except Exception as e:
        logger.exception(f"Email service health check failed: {e}")
        return {
            'status': 'unhealthy',
            'error': str(e),
        }


@require_GET
@never_cache
def readiness_check(request):
    """
    Readiness check for Kubernetes/load balancer
    Indicates if the service is ready to handle traffic
    """
    # Check critical dependencies
    db_ok = check_database()['status'] == 'healthy'
    
    if db_ok:
        return JsonResponse({'status': 'ready'}, status=200)
    else:
        return JsonResponse({'status': 'not ready'}, status=503)


@require_GET
@never_cache
def liveness_check(request):
    """
    Liveness check for Kubernetes
    Indicates if the service is alive (not hung)
    """
    return JsonResponse({
        'status': 'alive',
        'timestamp': datetime.utcnow().isoformat(),
    }, status=200)


# ============= Structured Logging =============

class StructuredLogger:
    """
    Structured JSON logging for better log analysis
    """
    
    @staticmethod
    def log(level, message, **context):
        """
        Log with structured context
        """
        log_data = {
            'timestamp': datetime.utcnow().isoformat(),
            'level': level,
            'message': message,
            **context
        }
        
        log_func = getattr(logger, level.lower(), logger.info)
        log_func(json.dumps(log_data))
    
    @staticmethod
    def log_request(request, response_time=None, status_code=None):
        """
        Log HTTP request
        """
        StructuredLogger.log(
            'info',
            'HTTP Request',
            method=request.method,
            path=request.path,
            user=str(request.user),
            ip=request.META.get('REMOTE_ADDR'),
            response_time_ms=response_time,
            status_code=status_code,
        )
    
    @staticmethod
    def log_payment(order_id, amount, status, payment_method):
        """
        Log payment transaction
        """
        StructuredLogger.log(
            'info',
            'Payment Transaction',
            order_id=order_id,
            amount=float(amount),
            status=status,
            payment_method=payment_method,
        )
    
    @staticmethod
    def log_failed_email(to_email, subject, error):
        """
        Log failed email
        """
        StructuredLogger.log(
            'error',
            'Failed Email',
            to_email=to_email,
            subject=subject,
            error=str(error),
        )
    
    @staticmethod
    def log_auth_attempt(username, success, ip_address):
        """
        Log authentication attempt
        """
        StructuredLogger.log(
            'warning' if not success else 'info',
            'Authentication Attempt',
            username=username,
            success=success,
            ip_address=ip_address,
        )
    
    @staticmethod
    def log_cache_operation(operation, key, hit=None):
        """
        Log cache operation
        """
        StructuredLogger.log(
            'debug',
            'Cache Operation',
            operation=operation,
            key=key,
            hit=hit,
        )


# ============= Performance Monitoring =============

class PerformanceMonitor:
    """
    Monitor application performance
    """
    
    @staticmethod
    def track_query_performance(query_name, duration_ms):
        """
        Track database query performance
        """
        # Log slow queries
        if duration_ms > 1000:  # > 1 second
            logger.warning(f"Slow query detected: {query_name} took {duration_ms}ms")
            
            # Send to Sentry
            capture_message(
                f"Slow query: {query_name}",
                level='warning',
                context={'duration_ms': duration_ms}
            )
    
    @staticmethod
    def track_api_performance(endpoint, duration_ms, status_code):
        """
        Track API endpoint performance
        """
        # Log slow endpoints
        if duration_ms > 2000:  # > 2 seconds
            logger.warning(f"Slow endpoint: {endpoint} took {duration_ms}ms")
    
    @staticmethod
    def get_performance_metrics():
        """
        Get performance metrics
        """
        return {
            'database_connections': len(connection.queries) if settings.DEBUG else 'N/A',
            'cache_backend': type(cache).__name__,
        }


# ============= Alert System =============

class AlertSystem:
    """
    Send alerts for critical issues
    """
    
    @staticmethod
    def send_alert(subject, message, level='warning'):
        """
        Send alert via email and Sentry
        """
        # Send to Sentry
        capture_message(message, level=level)
        
        # Send email to admins
        if settings.DEBUG:
            logger.warning(f"Alert (not sent in DEBUG): {subject} - {message}")
        else:
            try:
                admin_emails = [email for name, email in settings.ADMINS]
                if admin_emails:
                    send_mail(
                        subject=f"[Alert] {subject}",
                        message=message,
                        from_email=settings.DEFAULT_FROM_EMAIL,
                        recipient_list=admin_emails,
                        fail_silently=True,
                    )
            except Exception as e:
                logger.exception(f"Error sending alert email: {e}")
    
    @staticmethod
    def alert_high_error_rate(error_count, time_period):
        """
        Alert for high error rate
        """
        AlertSystem.send_alert(
            'High Error Rate',
            f'{error_count} errors in the last {time_period}',
            level='error'
        )
    
    @staticmethod
    def alert_payment_failure(order_id, error):
        """
        Alert for payment failure
        """
        AlertSystem.send_alert(
            'Payment Failure',
            f'Payment failed for order {order_id}: {error}',
            level='error'
        )
    
    @staticmethod
    def alert_low_inventory(product_name, quantity):
        """
        Alert for low inventory
        """
        AlertSystem.send_alert(
            'Low Inventory',
            f'{product_name} is low on stock ({quantity} remaining)',
            level='warning'
        )


# ============= Request Logging Middleware =============

class RequestLoggingMiddleware:
    """
    Middleware to log all requests with timing
    """
    
    def __init__(self, get_response):
        self.get_response = get_response
    
    def __call__(self, request):
        start_time = time.time()
        
        # Process request
        response = self.get_response(request)
        
        # Calculate response time
        response_time = (time.time() - start_time) * 1000  # ms
        
        # Log request
        StructuredLogger.log_request(
            request,
            response_time=round(response_time, 2),
            status_code=response.status_code
        )
        
        # Add response time header
        response['X-Response-Time'] = f"{response_time:.2f}ms"
        
        # Track performance
        PerformanceMonitor.track_api_performance(
            request.path,
            response_time,
            response.status_code
        )
        
        return response


# Initialize on import
if not settings.DEBUG:
    init_sentry()
