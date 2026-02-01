# DATABASE_SCHEMA.md

## Models Documentation

### Brand
- **id**: AutoField, primary key
- **name**: CharField, max_length=255
- **created_at**: DateTimeField, auto_now_add=True
- **updated_at**: DateTimeField, auto_now=True

### Category
- **id**: AutoField, primary key
- **name**: CharField, max_length=255
- **created_at**: DateTimeField, auto_now_add=True
- **updated_at**: DateTimeField, auto_now=True

### Product
- **id**: AutoField, primary key
- **name**: CharField, max_length=255
- **description**: TextField
- **price**: DecimalField, max_digits=10, decimal_places=2
- **brand**: ForeignKey to Brand
- **category**: ForeignKey to Category
- **created_at**: DateTimeField, auto_now_add=True
- **updated_at**: DateTimeField, auto_now=True

### Order
- **id**: AutoField, primary key
- **user**: ForeignKey to User (Assuming a User model exists)
- **created_at**: DateTimeField, auto_now_add=True
- **updated_at**: DateTimeField, auto_now=True

### OrderItem
- **id**: AutoField, primary key
- **order**: ForeignKey to Order
- **product**: ForeignKey to Product
- **quantity**: IntegerField
- **price**: DecimalField, max_digits=10, decimal_places=2

## Relationships
- A **Brand** can have multiple **Products**.
- A **Category** can have multiple **Products**.
- An **Order** can contain multiple **OrderItems**.
- An **OrderItem** is associated with a single **Product**.


**Last updated:** 2026-02-01 05:37:44 UTC
