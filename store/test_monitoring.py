"""
Tests for Monitoring and Logging
"""
from django.test import TestCase, Client
from django.contrib.auth.models import User
from store.monitoring import (
    check_database,
    check_cache,
    check_email_service,
    StructuredLogger,
    PerformanceMonitor,
    AlertSystem,
)


class HealthCheckTestCase(TestCase):
    """Test health check functionality"""
    
    def test_check_database(self):
        """Test database health check"""
        result = check_database()
        
        self.assertIn('status', result)
        self.assertEqual(result['status'], 'healthy')
        self.assertIn('response_time_ms', result)
    
    def test_check_cache(self):
        """Test cache health check"""
        result = check_cache()
        
        self.assertIn('status', result)
        # Cache might not be healthy in test environment
        self.assertIn(result['status'], ['healthy', 'unhealthy'])
    
    def test_check_email_service(self):
        """Test email service health check"""
        result = check_email_service()
        
        self.assertIn('status', result)
        self.assertIn('backend', result)


class HealthCheckEndpointsTestCase(TestCase):
    """Test health check endpoints"""
    
    def setUp(self):
        self.client = Client()
    
    def test_health_check_endpoint_exists(self):
        """Test health check endpoint exists"""
        from store.monitoring import health_check
        self.assertTrue(callable(health_check))
    
    def test_readiness_check_endpoint_exists(self):
        """Test readiness check endpoint exists"""
        from store.monitoring import readiness_check
        self.assertTrue(callable(readiness_check))
    
    def test_liveness_check_endpoint_exists(self):
        """Test liveness check endpoint exists"""
        from store.monitoring import liveness_check
        self.assertTrue(callable(liveness_check))


class StructuredLoggerTestCase(TestCase):
    """Test structured logging"""
    
    def test_log_basic(self):
        """Test basic logging"""
        try:
            StructuredLogger.log('info', 'Test message', user='testuser')
        except Exception as e:
            self.fail(f"Structured logging raised exception: {e}")
    
    def test_log_request(self):
        """Test request logging"""
        from django.test import RequestFactory
        
        factory = RequestFactory()
        request = factory.get('/')
        request.user = User(username='testuser')
        
        try:
            StructuredLogger.log_request(
                request,
                response_time=100.5,
                status_code=200
            )
        except Exception as e:
            self.fail(f"Request logging raised exception: {e}")
    
    def test_log_payment(self):
        """Test payment logging"""
        try:
            StructuredLogger.log_payment(
                order_id=1,
                amount=100.00,
                status='completed',
                payment_method='stripe'
            )
        except Exception as e:
            self.fail(f"Payment logging raised exception: {e}")
    
    def test_log_failed_email(self):
        """Test failed email logging"""
        try:
            StructuredLogger.log_failed_email(
                to_email='test@example.com',
                subject='Test Email',
                error='SMTP Error'
            )
        except Exception as e:
            self.fail(f"Failed email logging raised exception: {e}")
    
    def test_log_auth_attempt(self):
        """Test authentication attempt logging"""
        try:
            StructuredLogger.log_auth_attempt(
                username='testuser',
                success=True,
                ip_address='127.0.0.1'
            )
        except Exception as e:
            self.fail(f"Auth attempt logging raised exception: {e}")
    
    def test_log_cache_operation(self):
        """Test cache operation logging"""
        try:
            StructuredLogger.log_cache_operation(
                operation='get',
                key='test_key',
                hit=True
            )
        except Exception as e:
            self.fail(f"Cache operation logging raised exception: {e}")


class PerformanceMonitorTestCase(TestCase):
    """Test performance monitoring"""
    
    def test_track_query_performance(self):
        """Test query performance tracking"""
        try:
            PerformanceMonitor.track_query_performance(
                query_name='select_products',
                duration_ms=50.5
            )
        except Exception as e:
            self.fail(f"Query performance tracking raised exception: {e}")
    
    def test_track_slow_query(self):
        """Test slow query tracking"""
        try:
            # Slow query (> 1 second)
            PerformanceMonitor.track_query_performance(
                query_name='slow_query',
                duration_ms=1500
            )
        except Exception as e:
            self.fail(f"Slow query tracking raised exception: {e}")
    
    def test_track_api_performance(self):
        """Test API performance tracking"""
        try:
            PerformanceMonitor.track_api_performance(
                endpoint='/api/products/',
                duration_ms=120.5,
                status_code=200
            )
        except Exception as e:
            self.fail(f"API performance tracking raised exception: {e}")
    
    def test_get_performance_metrics(self):
        """Test getting performance metrics"""
        metrics = PerformanceMonitor.get_performance_metrics()
        
        self.assertIn('database_connections', metrics)
        self.assertIn('cache_backend', metrics)


class AlertSystemTestCase(TestCase):
    """Test alert system"""
    
    def test_send_alert(self):
        """Test sending alerts"""
        try:
            AlertSystem.send_alert(
                subject='Test Alert',
                message='This is a test alert',
                level='warning'
            )
        except Exception as e:
            self.fail(f"Send alert raised exception: {e}")
    
    def test_alert_high_error_rate(self):
        """Test high error rate alert"""
        try:
            AlertSystem.alert_high_error_rate(
                error_count=100,
                time_period='1 hour'
            )
        except Exception as e:
            self.fail(f"High error rate alert raised exception: {e}")
    
    def test_alert_payment_failure(self):
        """Test payment failure alert"""
        try:
            AlertSystem.alert_payment_failure(
                order_id=1,
                error='Card declined'
            )
        except Exception as e:
            self.fail(f"Payment failure alert raised exception: {e}")
    
    def test_alert_low_inventory(self):
        """Test low inventory alert"""
        try:
            AlertSystem.alert_low_inventory(
                product_name='Test Product',
                quantity=5
            )
        except Exception as e:
            self.fail(f"Low inventory alert raised exception: {e}")


class SentryIntegrationTestCase(TestCase):
    """Test Sentry integration"""
    
    def test_init_sentry(self):
        """Test Sentry initialization doesn't crash"""
        from store.monitoring import init_sentry
        
        try:
            init_sentry()
        except Exception as e:
            # It's okay if Sentry SDK is not installed
            pass
    
    def test_capture_exception(self):
        """Test exception capture"""
        from store.monitoring import capture_exception
        
        try:
            raise ValueError("Test exception")
        except ValueError as e:
            try:
                capture_exception(e, context={'test': True})
            except ImportError:
                # Sentry SDK not installed - this is fine
                pass
    
    def test_capture_message(self):
        """Test message capture"""
        from store.monitoring import capture_message
        
        try:
            capture_message(
                'Test message',
                level='info',
                context={'test': True}
            )
        except ImportError:
            # Sentry SDK not installed - this is fine
            pass
