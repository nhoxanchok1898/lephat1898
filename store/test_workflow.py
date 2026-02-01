from django.test import TestCase, Client
from django.contrib.auth.models import User
from store.models import Brand, Category, Product, Wishlist, Review, Order


class CompleteWorkflowTest(TestCase):
    """End-to-end test of the complete e-commerce workflow"""
    
    def setUp(self):
        self.client = Client()
        
        # Create test data
        self.brand = Brand.objects.create(name='TestBrand')
        self.category = Category.objects.create(name='TestCategory')
        self.product = Product.objects.create(
            name='Test Paint',
            brand=self.brand,
            category=self.category,
            price=50.00,
            unit_type=Product.UNIT_LIT,
            volume=5,
            is_active=True
        )
    
    def test_complete_user_journey(self):
        """Test the complete user journey from registration to order"""
        
        # 1. User registers
        response = self.client.post('/auth/register/', {
            'username': 'newuser',
            'email': 'newuser@example.com',
            'password': 'testpass123',
            'password2': 'testpass123'
        })
        self.assertIn(response.status_code, [200, 302])
        self.assertTrue(User.objects.filter(username='newuser').exists())
        
        # 2. User logs in
        response = self.client.post('/auth/login/', {
            'username': 'newuser',
            'password': 'testpass123'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # 3. User browses products
        response = self.client.get('/products/')
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, self.product.name)
        
        # 4. User adds product to wishlist
        response = self.client.post(f'/wishlist/add/{self.product.pk}/')
        self.assertIn(response.status_code, [200, 302])
        user = User.objects.get(username='newuser')
        self.assertTrue(Wishlist.objects.filter(user=user, product=self.product).exists())
        
        # 5. User adds product to cart
        response = self.client.post(f'/cart/add/{self.product.pk}/', {'quantity': 2})
        self.assertIn(response.status_code, [200, 302])
        
        # 6. User views cart
        response = self.client.get('/cart/')
        self.assertEqual(response.status_code, 200)
        
        # 7. User proceeds to checkout
        response = self.client.post('/checkout/', {
            'name': 'Test User',
            'phone': '555-1234',
            'address': '123 Test St',
            'payment_method': 'offline'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Verify order was created
        self.assertTrue(Order.objects.filter(full_name='Test User').exists())
        
        # 8. User writes a review
        response = self.client.post(f'/products/{self.product.pk}/reviews/create/', {
            'rating': 5,
            'comment': 'Great product!'
        })
        self.assertIn(response.status_code, [200, 302])
        self.assertTrue(Review.objects.filter(product=self.product, user=user).exists())
        
        # 9. User views order history
        response = self.client.get('/orders/history/')
        self.assertEqual(response.status_code, 200)
        
        # 10. User views profile
        response = self.client.get('/auth/profile/')
        self.assertEqual(response.status_code, 200)
        
        print("\n✅ Complete workflow test passed!")
        print("   - User registration")
        print("   - User login")
        print("   - Product browsing")
        print("   - Wishlist management")
        print("   - Cart operations")
        print("   - Checkout process")
        print("   - Review submission")
        print("   - Order history")
        print("   - Profile access")
