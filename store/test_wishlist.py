from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import Brand, Category, Product, Wishlist


class WishlistTests(TestCase):
    def setUp(self):
        self.client = Client()
        self.user = User.objects.create_user(username='testuser', password='testpass123')
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCat')
        self.product = Product.objects.create(
            name='Test Paint',
            brand=self.brand,
            category=self.category,
            price=100.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )
        self.wishlist_url = reverse('store:wishlist')
    
    def test_wishlist_view_requires_login(self):
        response = self.client.get(self.wishlist_url)
        self.assertIn(response.status_code, [302, 403])
    
    def test_wishlist_view_authenticated(self):
        self.client.login(username='testuser', password='testpass123')
        response = self.client.get(self.wishlist_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Wishlist')
    
    def test_add_to_wishlist(self):
        self.client.login(username='testuser', password='testpass123')
        add_url = reverse('store:wishlist_add', args=[self.product.pk])
        
        response = self.client.post(add_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check item was added to wishlist
        self.assertTrue(Wishlist.objects.filter(user=self.user, product=self.product).exists())
    
    def test_add_to_wishlist_duplicate(self):
        self.client.login(username='testuser', password='testpass123')
        Wishlist.objects.create(user=self.user, product=self.product)
        
        add_url = reverse('store:wishlist_add', args=[self.product.pk])
        response = self.client.post(add_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check only one item exists
        self.assertEqual(Wishlist.objects.filter(user=self.user, product=self.product).count(), 1)
    
    def test_remove_from_wishlist(self):
        self.client.login(username='testuser', password='testpass123')
        Wishlist.objects.create(user=self.user, product=self.product)
        
        remove_url = reverse('store:wishlist_remove', args=[self.product.pk])
        response = self.client.post(remove_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check item was removed
        self.assertFalse(Wishlist.objects.filter(user=self.user, product=self.product).exists())
    
    def test_wishlist_view_with_items(self):
        self.client.login(username='testuser', password='testpass123')
        Wishlist.objects.create(user=self.user, product=self.product)
        
        response = self.client.get(self.wishlist_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, self.product.name)
        self.assertContains(response, str(self.product.price))
    
    def test_wishlist_check_ajax(self):
        self.client.login(username='testuser', password='testpass123')
        Wishlist.objects.create(user=self.user, product=self.product)
        
        check_url = reverse('store:wishlist_check', args=[self.product.pk])
        response = self.client.get(check_url)
        self.assertEqual(response.status_code, 200)
        
        data = response.json()
        self.assertTrue(data['in_wishlist'])
    
    def test_wishlist_share_view(self):
        self.client.login(username='testuser', password='testpass123')
        share_url = reverse('store:wishlist_share')
        
        response = self.client.get(share_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Share')
    
    def test_wishlist_shared_view(self):
        Wishlist.objects.create(user=self.user, product=self.product)
        
        shared_url = reverse('store:wishlist_shared', args=[self.user.username])
        response = self.client.get(shared_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, self.user.username)
        self.assertContains(response, self.product.name)
    
    def test_add_to_wishlist_ajax(self):
        self.client.login(username='testuser', password='testpass123')
        add_url = reverse('store:wishlist_add', args=[self.product.pk])
        
        response = self.client.post(add_url, HTTP_X_REQUESTED_WITH='XMLHttpRequest')
        self.assertEqual(response.status_code, 200)
        
        data = response.json()
        self.assertTrue(data['success'])
        self.assertTrue(data['created'])
