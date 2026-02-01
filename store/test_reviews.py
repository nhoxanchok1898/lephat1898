from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from store.models import Brand, Category, Product, Review, ReviewHelpful, ReviewImage


class ReviewTests(TestCase):
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
    
    def test_review_create_view_requires_login(self):
        create_url = reverse('store:review_create', args=[self.product.pk])
        response = self.client.get(create_url)
        self.assertIn(response.status_code, [302, 403])
    
    def test_review_create_view_get(self):
        self.client.login(username='testuser', password='testpass123')
        create_url = reverse('store:review_create', args=[self.product.pk])
        
        response = self.client.get(create_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Write a Review')
        self.assertContains(response, self.product.name)
    
    def test_review_create_success(self):
        self.client.login(username='testuser', password='testpass123')
        create_url = reverse('store:review_create', args=[self.product.pk])
        
        response = self.client.post(create_url, {
            'rating': 5,
            'comment': 'Excellent product!'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Check review was created
        self.assertTrue(Review.objects.filter(
            product=self.product,
            user=self.user,
            rating=5
        ).exists())
    
    def test_review_create_duplicate(self):
        self.client.login(username='testuser', password='testpass123')
        Review.objects.create(
            product=self.product,
            user=self.user,
            rating=4,
            comment='First review'
        )
        
        create_url = reverse('store:review_create', args=[self.product.pk])
        response = self.client.post(create_url, {
            'rating': 5,
            'comment': 'Second review'
        })
        self.assertIn(response.status_code, [200, 302])
        
        # Check only one review exists
        self.assertEqual(Review.objects.filter(product=self.product, user=self.user).count(), 1)
    
    def test_review_list_view(self):
        # Create approved review
        Review.objects.create(
            product=self.product,
            user=self.user,
            rating=5,
            comment='Great product!',
            is_approved=True
        )
        
        list_url = reverse('store:review_list', args=[self.product.pk])
        response = self.client.get(list_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Great product!')
        self.assertContains(response, self.user.username)
    
    def test_review_list_excludes_unapproved(self):
        # Create unapproved review
        Review.objects.create(
            product=self.product,
            user=self.user,
            rating=5,
            comment='Pending review',
            is_approved=False
        )
        
        list_url = reverse('store:review_list', args=[self.product.pk])
        response = self.client.get(list_url)
        self.assertEqual(response.status_code, 200)
        self.assertNotContains(response, 'Pending review')
    
    def test_review_helpful_vote(self):
        self.client.login(username='testuser', password='testpass123')
        
        # Create a review by another user
        other_user = User.objects.create_user(username='other', password='testpass123')
        review = Review.objects.create(
            product=self.product,
            user=other_user,
            rating=5,
            comment='Helpful review',
            is_approved=True
        )
        
        helpful_url = reverse('store:review_helpful', args=[review.pk])
        response = self.client.post(helpful_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check helpful vote was created
        self.assertTrue(ReviewHelpful.objects.filter(review=review, user=self.user).exists())
    
    def test_review_helpful_toggle(self):
        self.client.login(username='testuser', password='testpass123')
        
        other_user = User.objects.create_user(username='other', password='testpass123')
        review = Review.objects.create(
            product=self.product,
            user=other_user,
            rating=5,
            comment='Helpful review',
            is_approved=True,
            helpful_count=1
        )
        
        ReviewHelpful.objects.create(review=review, user=self.user)
        
        helpful_url = reverse('store:review_helpful', args=[review.pk])
        response = self.client.post(helpful_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check helpful vote was removed
        self.assertFalse(ReviewHelpful.objects.filter(review=review, user=self.user).exists())
    
    def test_review_moderate_requires_staff(self):
        moderate_url = reverse('store:review_moderate')
        response = self.client.get(moderate_url)
        self.assertIn(response.status_code, [302, 403])
    
    def test_review_moderate_staff(self):
        staff_user = User.objects.create_user(username='staff', password='testpass123', is_staff=True)
        self.client.login(username='staff', password='testpass123')
        
        moderate_url = reverse('store:review_moderate')
        response = self.client.get(moderate_url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Moderation')
    
    def test_review_approve_staff(self):
        staff_user = User.objects.create_user(username='staff', password='testpass123', is_staff=True)
        self.client.login(username='staff', password='testpass123')
        
        review = Review.objects.create(
            product=self.product,
            user=self.user,
            rating=5,
            comment='Pending review',
            is_approved=False
        )
        
        approve_url = reverse('store:review_approve', args=[review.pk])
        response = self.client.post(approve_url)
        self.assertIn(response.status_code, [200, 302])
        
        # Check review was approved
        review.refresh_from_db()
        self.assertTrue(review.is_approved)
    
    def test_review_average_rating(self):
        Review.objects.create(product=self.product, user=self.user, rating=5, comment='Great', is_approved=True)
        user2 = User.objects.create_user(username='user2', password='testpass123')
        Review.objects.create(product=self.product, user=user2, rating=3, comment='OK', is_approved=True)
        
        list_url = reverse('store:review_list', args=[self.product.pk])
        response = self.client.get(list_url)
        self.assertEqual(response.status_code, 200)
        
        # Average should be 4.0
        self.assertContains(response, '4.0')
