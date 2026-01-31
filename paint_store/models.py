from django.db import models

class Brand(models.Model):
    name = models.CharField(max_length=255, unique=True)

    def __str__(self):
        return self.name

class Product(models.Model):
    CATEGORY_CHOICES = [
        ('paint', 'Sơn'),
        ('waterproof', 'Chống thấm'),
        ('putty', 'Bột trét'),
        ('other', 'Khác'),
    ]

    UNIT_CHOICES = [
        ('L', 'Lít'),
        ('KG', 'Kg'),
    ]

    name = models.CharField(max_length=255)
    brand = models.ForeignKey(Brand, on_delete=models.CASCADE)
    price = models.DecimalField(max_digits=10, decimal_places=2)
    category = models.CharField(max_length=20, choices=CATEGORY_CHOICES)
    unit = models.CharField(max_length=2, choices=UNIT_CHOICES)
    quantity_value = models.FloatField()
    description = models.TextField(blank=True, null=True)
    image = models.ImageField(upload_to='product_images/')

    def clean(self):
        from django.core.exceptions import ValidationError
        if self.category == 'putty' and self.unit != 'KG':
            raise ValidationError("Bột trét phải sử dụng đơn vị KG.")
        if self.category in ['paint', 'waterproof'] and self.unit != 'L':
            raise ValidationError("Sơn và Chống thấm phải sử dụng đơn vị Lít.")

    def __str__(self):
        return self.name