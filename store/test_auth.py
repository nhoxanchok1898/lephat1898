from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import UserProfile, EmailLog


class AuthenticationTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.register_url = reverse('store:register')
        self.login_url = reverse('store:login')
        self.logout_url = reverse('store:logout')
        self.profile_url = reverse('store:profile')
    
    def test_register_view_get(self):
        response = self.client.get(self.register_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Register')
    
    def test_register_user_success(self):
        response = self.client.post(self.register_url, {
            'username': 'newuser',
            'email': 'newuser@example.com',
            'password': 'testpass123',
            'password2': 'testpass123'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Check user was created
        user_exists = User.objects.filter(username='newuser').exists()
        self.assertTrue(user_exists)
        
        # Check UserProfile was created
        if user_exists:
            user = User.objects.get(username='newuser')
            profile_exists = UserProfile.objects.filter(user=user).exists()
            self.assertTrue(profile_exists)
    
    def test_register_password_mismatch(self):
        response = self.client.post(self.register_url, {
            'username': 'newuser',
            'email': 'newuser@example.com',
            'password': 'testpass123',
            'password2': 'wrongpass'
        })
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Passwords do not match')
        self.assertFalse(User.objects.filter(username='newuser').exists())
    
    def test_register_duplicate_username(self):
        User.objects.create_user(username='existing', email='existing@example.com', password='testpass')
        response = self.client.post(self.register_url, {
            'username': 'existing',
            'email': 'new@example.com',
            'password': 'testpass123',
            'password2': 'testpass123'
        })
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Username already exists')
    
    def test_login_view_get(self):
        response = self.client.get(self.login_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Login')
    
    def test_login_success(self):
        user = User.objects.create_user(username='testuser', password='testpass123')
        response = self.client.post(self.login_url, {
            'username': 'testuser',
            'password': 'testpass123'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Check user is authenticated
        self.assertTrue(self.client.session.get('_auth_user_id'))
    
    def test_login_invalid_credentials(self):
        response = self.client.post(self.login_url, {
            'username': 'nonexistent',
            'password': 'wrongpass'
        })
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Invalid username or password')
        self.assertFalse(self.client.session.get('_auth_user_id'))
    
    def test_logout(self):
        user = User.objects.create_user(username='testuser', password='testpass123')
        self.client.login(username='testuser', password='testpass123')
        
        response = self.client.get(self.logout_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check user is logged out
        self.assertFalse(self.client.session.get('_auth_user_id'))
    
    def test_profile_view_requires_login(self):
        response = self.client.get(self.profile_url)
        self.assertIn(response.status_code, [302, 403])
    
    def test_profile_view_authenticated(self):
        user = User.objects.create_user(username='testuser', password='testpass123')
        # Use get_or_create since UserProfile is auto-created by signal
        profile, created = UserProfile.objects.get_or_create(user=user, defaults={'phone': '123-456-7890'})
        if not created:
            profile.phone = '123-456-7890'
            profile.save()
        self.client.login(username='testuser', password='testpass123')
        
        response = self.client.get(self.profile_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'testuser')
    
    def test_profile_update(self):
        user = User.objects.create_user(username='testuser', password='testpass123')
        # Use get_or_create since UserProfile is auto-created by signal
        UserProfile.objects.get_or_create(user=user)
        self.client.login(username='testuser', password='testpass123')
        
        update_url = reverse('store:profile_update')
        response = self.client.post(update_url, {
            'first_name': 'Test',
            'last_name': 'User',
            'email': 'updated@example.com',
            'phone': '555-1234',
            'address': '456 New St'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Check user was updated
        user.refresh_from_db()
        self.assertEqual(user.first_name, 'Test')
        self.assertEqual(user.last_name, 'User')
    
    def test_password_reset_request(self):
        user = User.objects.create_user(username='testuser', email='test@example.com', password='testpass123')
        reset_url = reverse('store:password_reset_request')
        
        response = self.client.post(reset_url, {
            'email': 'test@example.com'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Check email log was created
        self.assertTrue(EmailLog.objects.filter(recipient='test@example.com', template_name='password_reset').exists())
