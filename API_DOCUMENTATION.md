# API Documentation

## Home Endpoint
- **URL:** `/`
- **Method:** GET
- **Description:** Returns the home page of the application. This endpoint is used to fetch the main content and layout of the site.

## Products Endpoint
- **URL:** `/products/`
- **Method:** GET
- **Description:** Retrieves a list of products available in the store.
- **Response:** A JSON array containing product objects with details such as id, name, price, and description.

## Cart Endpoint
- **URL:** `/cart/`
- **Method:** GET, POST, DELETE
- **Description:****
  - GET: Fetches the items currently in the cart.
  - POST: Adds a new item to the cart. Requires product ID and quantity.
  - DELETE: Removes an item from the cart. Requires item ID.

## Checkout Endpoint
- **URL:** `/checkout/`
- **Method:** POST
- **Description:** Initiates the checkout process. Requires cart details and user information to create an order.
- **Response:** Confirmation of the order and payment details.

## Stripe Payments Endpoint
- **URL:** `/checkout/payment/`
- **Method:** POST
- **Description:** Handles the payment process through Stripe. Requires payment information and order details.
- **Response:** Payment confirmation or error message.

## Contact Endpoint
- **URL:** `/contact/`
- **Method:** POST
- **Description:** Allows users to send messages or inquiries to customer service. Requires user details and message content.
- **Response:** A confirmation message indicating that the inquiry has been received.

---

This document provides an overview of the available API endpoints used in the application. For more detailed usage and examples, please refer to the specific endpoint documentation in your project.