"""
Quick setup script to create test data for the e-commerce site
"""
from django.contrib.auth.models import User
from store.models import (
    Brand, Category, Product, UserProfile, 
    Wishlist, Review, Order, OrderItem
)

# Create test user
user, created = User.objects.get_or_create(
    username='testuser',
    defaults={
        'email': 'test@example.com',
        'first_name': 'Test',
        'last_name': 'User'
    }
)
if created:
    user.set_password('testpass123')
    user.save()
    print(f"✓ Created user: {user.username}")

# Create profile
profile, created = UserProfile.objects.get_or_create(
    user=user,
    defaults={
        'phone': '555-1234',
        'address': '123 Test Street',
        'email_verified': True
    }
)
if created:
    print(f"✓ Created profile for {user.username}")

# Create brands
brand1, _ = Brand.objects.get_or_create(name='Premium Paint Co')
brand2, _ = Brand.objects.get_or_create(name='EcoCoat')
print(f"✓ Created {Brand.objects.count()} brands")

# Create categories
cat1, _ = Category.objects.get_or_create(name='Interior Paint')
cat2, _ = Category.objects.get_or_create(name='Exterior Paint')
cat3, _ = Category.objects.get_or_create(name='Primer')
print(f"✓ Created {Category.objects.count()} categories")

# Create products
products_data = [
    ('Premium White Interior', brand1, cat1, 49.99, 5),
    ('EcoCoat Green Exterior', brand2, cat2, 59.99, 10),
    ('Ultra Primer', brand1, cat3, 39.99, 5),
    ('Blue Sky Interior', brand1, cat1, 54.99, 5),
]

for name, brand, cat, price, volume in products_data:
    product, created = Product.objects.get_or_create(
        name=name,
        defaults={
            'brand': brand,
            'category': cat,
            'price': price,
            'unit_type': Product.UNIT_LIT,
            'volume': volume,
            'is_active': True
        }
    )
    if created:
        print(f"  ✓ Created product: {name}")

print(f"✓ Total products: {Product.objects.count()}")

# Add product to wishlist
if Product.objects.exists():
    product = Product.objects.first()
    wishlist, created = Wishlist.objects.get_or_create(
        user=user,
        product=product
    )
    if created:
        print(f"✓ Added {product.name} to {user.username}'s wishlist")

# Create a review
if Product.objects.count() > 1:
    product = Product.objects.all()[1]
    review, created = Review.objects.get_or_create(
        product=product,
        user=user,
        defaults={
            'rating': 5,
            'comment': 'Excellent paint! Great coverage and quality.',
            'is_approved': True,
            'verified_purchase': True
        }
    )
    if created:
        print(f"✓ Created review for {product.name}")

print("\n✅ Setup complete!")
print(f"   - Users: {User.objects.count()}")
print(f"   - Products: {Product.objects.count()}")
print(f"   - Wishlist items: {Wishlist.objects.count()}")
print(f"   - Reviews: {Review.objects.count()}")
print(f"\nTest user credentials:")
print(f"   Username: testuser")
print(f"   Password: testpass123")
