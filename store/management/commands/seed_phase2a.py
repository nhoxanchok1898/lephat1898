"""
Management command to seed Phase 2A demo data
"""
from django.core.management.base import BaseCommand
from django.contrib.auth.models import User
from django.utils import timezone
from datetime import timedelta
from decimal import Decimal

from store.models import (
    Brand, Category, Product, Coupon,
    Review, Wishlist
)


class Command(BaseCommand):
    help = 'Seed Phase 2A demo data'

    def handle(self, *args, **options):
        self.stdout.write('Seeding Phase 2A demo data...')

        # Create demo user
        user, created = User.objects.get_or_create(
            username='demo',
            defaults={'email': 'demo@example.com'}
        )
        if created:
            user.set_password('demo123')
            user.save()
            self.stdout.write(self.style.SUCCESS(f'Created demo user: {user.username}'))

        # Update existing products with new fields
        products = Product.objects.all()
        for i, product in enumerate(products):
            product.stock_quantity = 10 + (i * 5)
            product.rating = Decimal('4.0') + (Decimal(i % 5) / 10)
            product.view_count = i * 10
            
            # Make some products on sale
            if i % 3 == 0:
                product.is_on_sale = True
                product.sale_price = product.price * Decimal('0.85')  # 15% off
            
            product.save(update_fields=['stock_quantity', 'rating', 'view_count', 'is_on_sale', 'sale_price'])
        
        self.stdout.write(self.style.SUCCESS(f'Updated {products.count()} products'))

        # Create demo coupons
        now = timezone.now()
        coupons_data = [
            {
                'code': 'WELCOME10',
                'discount_percentage': Decimal('10.00'),
                'valid_from': now,
                'valid_to': now + timedelta(days=90),
                'is_active': True,
            },
            {
                'code': 'SAVE20',
                'discount_percentage': Decimal('20.00'),
                'min_purchase_amount': Decimal('100.00'),
                'valid_from': now,
                'valid_to': now + timedelta(days=30),
                'is_active': True,
            },
            {
                'code': 'BIGSALE',
                'discount_amount': Decimal('50.00'),
                'min_purchase_amount': Decimal('200.00'),
                'valid_from': now,
                'valid_to': now + timedelta(days=7),
                'is_active': True,
            },
        ]

        for coupon_data in coupons_data:
            coupon, created = Coupon.objects.get_or_create(
                code=coupon_data['code'],
                defaults=coupon_data
            )
            if created:
                self.stdout.write(self.style.SUCCESS(f'Created coupon: {coupon.code}'))

        # Create demo reviews
        if products.exists() and user:
            for product in products[:5]:  # Add reviews to first 5 products
                review, created = Review.objects.get_or_create(
                    product=product,
                    user=user,
                    defaults={
                        'rating': 5,
                        'comment': f'Great product! Very satisfied with {product.name}.'
                    }
                )
                if created:
                    self.stdout.write(self.style.SUCCESS(f'Created review for: {product.name}'))

        # Create demo wishlist
        if products.exists() and user:
            wishlist, created = Wishlist.objects.get_or_create(user=user)
            wishlist.products.add(*products[:3])
            self.stdout.write(self.style.SUCCESS(f'Created wishlist with {wishlist.products.count()} products'))

        self.stdout.write(self.style.SUCCESS('Phase 2A demo data seeded successfully!'))
