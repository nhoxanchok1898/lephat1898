"""
Tests for Security Module
"""
from django.test import TestCase, RequestFactory
from django.contrib.auth.models import User
from django.core.exceptions import ValidationError
from django.core.cache import cache
from store.security import (
    TwoFactorAuth,
    RateLimiter,
    LoginProtection,
    InputValidator,
    SuspiciousActivityDetector,
)


class TwoFactorAuthTestCase(TestCase):
    """Test 2FA functionality"""
    
    def setUp(self):
        self.user = User.objects.create_user(
            username='testuser',
            email='test@example.com',
            password='testpass123'
        )
    
    def test_generate_secret(self):
        """Test TOTP secret generation"""
        secret = TwoFactorAuth.generate_secret()
        
        self.assertIsNotNone(secret)
        self.assertTrue(len(secret) >= 16)
    
    def test_get_totp_uri(self):
        """Test TOTP URI generation"""
        secret = TwoFactorAuth.generate_secret()
        uri = TwoFactorAuth.get_totp_uri(self.user, secret)
        
        self.assertIn('otpauth://totp/', uri)
        self.assertIn(self.user.email, uri)
    
    def test_verify_token(self):
        """Test TOTP token verification"""
        import pyotp
        
        secret = TwoFactorAuth.generate_secret()
        totp = pyotp.TOTP(secret)
        valid_token = totp.now()
        
        # Valid token should verify
        self.assertTrue(TwoFactorAuth.verify_token(secret, valid_token))
        
        # Invalid token should not verify
        self.assertFalse(TwoFactorAuth.verify_token(secret, '000000'))


class RateLimiterTestCase(TestCase):
    """Test rate limiting"""
    
    def setUp(self):
        cache.clear()
        self.factory = RequestFactory()
    
    def test_rate_limit_allows_within_limit(self):
        """Test rate limiter allows requests within limit"""
        key = 'test_key'
        limit = 5
        period = 60
        
        # Make requests within limit
        for i in range(limit):
            limited = RateLimiter.is_rate_limited(key, limit, period)
            self.assertFalse(limited, f"Request {i+1} should be allowed")
    
    def test_rate_limit_blocks_over_limit(self):
        """Test rate limiter blocks requests over limit"""
        key = 'test_key'
        limit = 3
        period = 60
        
        # Make requests up to limit
        for i in range(limit):
            RateLimiter.is_rate_limited(key, limit, period)
        
        # Next request should be blocked
        limited = RateLimiter.is_rate_limited(key, limit, period)
        self.assertTrue(limited, "Request over limit should be blocked")
    
    def test_get_client_ip(self):
        """Test client IP extraction"""
        request = self.factory.get('/')
        request.META['REMOTE_ADDR'] = '192.168.1.1'
        
        ip = RateLimiter.get_client_ip(request)
        self.assertEqual(ip, '192.168.1.1')
    
    def test_get_client_ip_with_proxy(self):
        """Test client IP extraction with X-Forwarded-For"""
        request = self.factory.get('/')
        request.META['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.1'
        request.META['REMOTE_ADDR'] = '192.168.1.1'
        
        ip = RateLimiter.get_client_ip(request)
        self.assertEqual(ip, '203.0.113.1')


class LoginProtectionTestCase(TestCase):
    """Test login protection"""
    
    def setUp(self):
        cache.clear()
    
    def test_failed_attempts_recorded(self):
        """Test failed login attempts are recorded"""
        username = 'testuser'
        
        LoginProtection.record_failed_attempt(username)
        remaining = LoginProtection.get_remaining_attempts(username)
        
        self.assertEqual(remaining, LoginProtection.LOCKOUT_THRESHOLD - 1)
    
    def test_account_lockout_after_threshold(self):
        """Test account locks out after threshold"""
        username = 'testuser'
        
        # Record failed attempts up to threshold
        for _ in range(LoginProtection.LOCKOUT_THRESHOLD):
            LoginProtection.record_failed_attempt(username)
        
        # Account should be locked
        self.assertTrue(LoginProtection.is_locked_out(username))
    
    def test_clear_attempts(self):
        """Test clearing failed attempts"""
        username = 'testuser'
        
        LoginProtection.record_failed_attempt(username)
        LoginProtection.clear_attempts(username)
        
        remaining = LoginProtection.get_remaining_attempts(username)
        self.assertEqual(remaining, LoginProtection.LOCKOUT_THRESHOLD)


class InputValidatorTestCase(TestCase):
    """Test input validation"""
    
    def test_validate_email_valid(self):
        """Test valid email validation"""
        valid_emails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.com',
        ]
        
        for email in valid_emails:
            try:
                result = InputValidator.validate_email(email)
                self.assertEqual(result, email.lower().strip())
            except ValidationError:
                self.fail(f"Valid email {email} failed validation")
    
    def test_validate_email_invalid(self):
        """Test invalid email validation"""
        invalid_emails = [
            'notanemail',
            '@example.com',
            'user@',
            'user @example.com',
        ]
        
        for email in invalid_emails:
            with self.assertRaises(ValidationError):
                InputValidator.validate_email(email)
    
    def test_validate_phone_valid(self):
        """Test valid phone validation"""
        valid_phones = [
            '1234567890',
            '+1 (555) 123-4567',
            '+44 20 1234 5678',
        ]
        
        for phone in valid_phones:
            try:
                result = InputValidator.validate_phone(phone)
                self.assertIsNotNone(result)
            except ValidationError:
                self.fail(f"Valid phone {phone} failed validation")
    
    def test_validate_phone_invalid(self):
        """Test invalid phone validation"""
        invalid_phones = [
            '123',  # Too short
            'abc1234567',  # Letters
            '',  # Empty
        ]
        
        for phone in invalid_phones:
            with self.assertRaises(ValidationError):
                InputValidator.validate_phone(phone)
    
    def test_sanitize_string(self):
        """Test string sanitization"""
        test_cases = [
            ('  test  ', 'test'),
            ('a' * 2000, 'a' * 1000),  # Max length
        ]
        
        for input_str, expected in test_cases:
            result = InputValidator.sanitize_string(input_str, max_length=1000)
            self.assertEqual(result, expected)
    
    def test_validate_password_strength_valid(self):
        """Test strong password validation"""
        strong_passwords = [
            'MyP@ssw0rd123!',
            'Str0ng!P@ssword',
            'C0mpl3x!Pass#word',
        ]
        
        for password in strong_passwords:
            try:
                result = InputValidator.validate_password_strength(password)
                self.assertTrue(result)
            except ValidationError as e:
                self.fail(f"Strong password {password} failed: {e}")
    
    def test_validate_password_strength_invalid(self):
        """Test weak password validation"""
        weak_passwords = [
            'short',  # Too short
            'nouppercase123!',  # No uppercase
            'NOLOWERCASE123!',  # No lowercase
            'NoNumbers!',  # No numbers
            'NoSpecialChars123',  # No special chars
        ]
        
        for password in weak_passwords:
            with self.assertRaises(ValidationError):
                InputValidator.validate_password_strength(password)


class SuspiciousActivityTestCase(TestCase):
    """Test suspicious activity detection"""
    
    def test_detect_sql_injection(self):
        """Test SQL injection detection"""
        sql_injections = [
            "' OR 1=1--",
            "admin'; DROP TABLE users--",
            "UNION SELECT * FROM passwords",
        ]
        
        for injection in sql_injections:
            result = SuspiciousActivityDetector.detect_sql_injection(injection)
            self.assertTrue(result, f"Failed to detect SQL injection: {injection}")
    
    def test_detect_sql_injection_safe_input(self):
        """Test safe input doesn't trigger SQL injection detection"""
        safe_inputs = [
            "John Doe",
            "user@example.com",
            "1234567890",
        ]
        
        for safe_input in safe_inputs:
            result = SuspiciousActivityDetector.detect_sql_injection(safe_input)
            self.assertFalse(result, f"Safe input triggered SQL injection: {safe_input}")
    
    def test_detect_xss(self):
        """Test XSS detection"""
        xss_attacks = [
            '<script>alert("XSS")</script>',
            'javascript:alert(1)',
            '<img src=x onerror=alert(1)>',
        ]
        
        for attack in xss_attacks:
            result = SuspiciousActivityDetector.detect_xss(attack)
            self.assertTrue(result, f"Failed to detect XSS: {attack}")
    
    def test_detect_xss_safe_input(self):
        """Test safe input doesn't trigger XSS detection"""
        safe_inputs = [
            "Normal text",
            "Email: test@example.com",
            "Price: $99.99",
        ]
        
        for safe_input in safe_inputs:
            result = SuspiciousActivityDetector.detect_xss(safe_input)
            self.assertFalse(result, f"Safe input triggered XSS: {safe_input}")
