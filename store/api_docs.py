"""
API Documentation View
"""
from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.urls import reverse


@api_view(['GET'])
def api_documentation(request):
    """
    Phase 2A API Documentation
    
    This API provides comprehensive e-commerce functionality including:
    - Product catalog with advanced search and filtering
    - Shopping cart management
    - Order processing
    - Wishlist and reviews
    - Analytics and recommendations
    """
    
    base_url = request.build_absolute_uri('/api/v1/')
    
    documentation = {
        "version": "1.0",
        "description": "Advanced E-commerce API - Phase 2A",
        "authentication": {
            "method": "Token Authentication",
            "obtain_token": f"{base_url}auth/token/",
            "usage": "Include 'Authorization: Token <your-token>' in request headers"
        },
        "endpoints": {
            "products": {
                "list": {
                    "url": f"{base_url}products/",
                    "method": "GET",
                    "description": "List all products with filtering and search",
                    "filters": [
                        "brand - Filter by brand ID",
                        "category - Filter by category ID",
                        "is_on_sale - Filter sale products (true/false)",
                        "price_min - Minimum price",
                        "price_max - Maximum price",
                        "in_stock - Only in-stock products (true/false)",
                        "min_rating - Minimum rating (0-5)",
                        "search - Search in name, brand, description"
                    ],
                    "ordering": ["price", "created_at", "rating", "view_count"]
                },
                "detail": {
                    "url": f"{base_url}products/<id>/",
                    "method": "GET",
                    "description": "Get product details (increments view count)"
                },
                "recommendations": {
                    "url": f"{base_url}products/<id>/recommendations/",
                    "method": "GET",
                    "description": "Get product recommendations based on user behavior"
                },
                "trending": {
                    "url": f"{base_url}products/trending/",
                    "method": "GET",
                    "description": "Get trending products",
                    "params": ["days - Number of days to consider (default: 7)"]
                },
                "search_suggestions": {
                    "url": f"{base_url}products/search_suggestions/",
                    "method": "GET",
                    "description": "Get search autocomplete suggestions",
                    "params": ["q - Query string (min 2 characters)"]
                }
            },
            "cart": {
                "list": {
                    "url": f"{base_url}cart/",
                    "method": "GET",
                    "description": "Get user's cart (or session cart)"
                },
                "add_item": {
                    "url": f"{base_url}cart/add_item/",
                    "method": "POST",
                    "description": "Add item to cart",
                    "payload": {
                        "product_id": "integer",
                        "quantity": "integer (default: 1)"
                    }
                },
                "update_item": {
                    "url": f"{base_url}cart/update_item/",
                    "method": "POST",
                    "description": "Update cart item quantity",
                    "payload": {
                        "product_id": "integer",
                        "quantity": "integer (0 to remove)"
                    }
                },
                "remove_item": {
                    "url": f"{base_url}cart/remove_item/",
                    "method": "POST",
                    "description": "Remove item from cart",
                    "payload": {"product_id": "integer"}
                },
                "clear": {
                    "url": f"{base_url}cart/clear/",
                    "method": "POST",
                    "description": "Clear all items from cart"
                },
                "apply_coupon": {
                    "url": f"{base_url}cart/apply_coupon/",
                    "method": "POST",
                    "description": "Apply coupon code",
                    "payload": {"code": "string"}
                }
            },
            "orders": {
                "list": {
                    "url": f"{base_url}orders/",
                    "method": "GET",
                    "description": "List user orders",
                    "auth_required": True
                },
                "create": {
                    "url": f"{base_url}orders/",
                    "method": "POST",
                    "description": "Create new order",
                    "auth_required": True,
                    "payload": {
                        "full_name": "string",
                        "phone": "string",
                        "address": "string",
                        "payment_method": "string (offline/stripe/paypal)"
                    }
                },
                "tracking": {
                    "url": f"{base_url}orders/<id>/tracking/",
                    "method": "GET",
                    "description": "Get order tracking information"
                }
            },
            "reviews": {
                "list": {
                    "url": f"{base_url}reviews/",
                    "method": "GET",
                    "description": "List product reviews",
                    "filters": ["product", "rating"]
                },
                "create": {
                    "url": f"{base_url}reviews/",
                    "method": "POST",
                    "description": "Create product review",
                    "auth_required": True,
                    "payload": {
                        "product": "integer",
                        "rating": "integer (1-5)",
                        "comment": "string (optional)"
                    }
                }
            },
            "wishlist": {
                "list": {
                    "url": f"{base_url}wishlist/",
                    "method": "GET",
                    "description": "Get user wishlist",
                    "auth_required": True
                },
                "add_product": {
                    "url": f"{base_url}wishlist/add_product/",
                    "method": "POST",
                    "description": "Add product to wishlist",
                    "auth_required": True,
                    "payload": {"product_id": "integer"}
                },
                "remove_product": {
                    "url": f"{base_url}wishlist/remove_product/",
                    "method": "POST",
                    "description": "Remove product from wishlist",
                    "auth_required": True,
                    "payload": {"product_id": "integer"}
                }
            },
            "analytics": {
                "overview": {
                    "url": f"{base_url}analytics/overview/",
                    "method": "GET",
                    "description": "Get analytics overview (products, orders, revenue)"
                },
                "track_search": {
                    "url": f"{base_url}search/track/",
                    "method": "POST",
                    "description": "Track search query",
                    "auth_required": True,
                    "payload": {
                        "query": "string",
                        "results_count": "integer"
                    }
                }
            },
            "brands": {
                "list": {
                    "url": f"{base_url}brands/",
                    "method": "GET",
                    "description": "List all brands"
                }
            },
            "categories": {
                "list": {
                    "url": f"{base_url}categories/",
                    "method": "GET",
                    "description": "List all categories"
                }
            }
        },
        "response_format": {
            "success": {
                "paginated_list": {
                    "count": "integer - total items",
                    "next": "string - next page URL",
                    "previous": "string - previous page URL",
                    "results": "array - items"
                },
                "detail": "object - single item"
            },
            "error": {
                "error": "string - error message",
                "detail": "string - error details (optional)"
            }
        },
        "rate_limiting": {
            "anonymous": "100 requests per hour",
            "authenticated": "1000 requests per hour"
        },
        "features": [
            "Token authentication",
            "Advanced search and filtering",
            "Persistent cart (session and user-based)",
            "Coupon code support",
            "Product recommendations",
            "Trending products",
            "Product reviews and ratings",
            "Wishlist management",
            "Analytics tracking",
            "Rate limiting",
            "Pagination"
        ]
    }
    
    return Response(documentation)
