# Payment Gateway Integration Guide

This document provides a comprehensive guide on integrating payment gateways using Stripe and PayPal. We'll cover the basic setup, code examples, and best practices to ensure a smooth payment experience for your users.

---

## Table of Contents
1. [Stripe Integration](#stripe-integration)
   - [Getting Started](#getting-started)
   - [Code Example](#code-example)
2. [PayPal Integration](#paypal-integration)
   - [Getting Started](#getting-started-1)
   - [Code Example](#code-example-1)

---

## Stripe Integration

### Getting Started
1. **Sign Up for a Stripe Account**: Go to [Stripe](https://stripe.com/) and create an account.
2. **Create API Keys**: Once logged in, navigate to the API section of your dashboard. Obtain your **Publishable Key** and **Secret Key**.
3. **Install Stripe SDK**: Use npm to install the Stripe library in your project:
   ```bash
   npm install stripe
   ```

### Code Example
Here’s a basic example of how to implement Stripe in a Node.js application:
```javascript
const express = require('express');
const stripe = require('stripe')('your_secret_key');
const app = express();

app.post('/create-charge', async (req, res) => {
    const { amount } = req.body; // Amount in cents
    try {
        const charge = await stripe.charges.create({
            amount,
            currency: 'usd',
            source: req.body.token,
            description: 'Payment Description',
        });
        res.status(200).send(charge);
    } catch (error) {
        res.status(500).send(error);
    }
});

app.listen(3000, () => console.log('Server running on port 3000'));
```

---

## PayPal Integration

### Getting Started
1. **Sign Up for a PayPal Developer Account**: Visit [PayPal Developer](https://developer.paypal.com/) to create an account.
2. **Create Sandbox Accounts**: Navigate to the dashboard to create sandbox accounts for testing.
3. **Obtain Client ID and Secret**: In your App settings, obtain the **Client ID** and **Secret**.
4. **Install PayPal SDK**: Use npm to install the PayPal SDK:
   ```bash
   npm install @paypal/checkout-server-sdk
   ```

### Code Example
Here’s a basic example of how to implement PayPal in a Node.js application:
```javascript
const paypal = require('@paypal/checkout-server-sdk');

const environment = new paypal.core.SandboxEnvironment('your_client_id', 'your_client_secret');
const client = new paypal.core.PayPalHttpClient(environment);

const request = new paypal.orders.OrdersCreateRequest();
request.requestBody({
    intent: 'CAPTURE',
    purchase_units: [{
        amount: {
            currency_code: 'USD',
            value: '100.00',
        },
    }],
});

client.execute(request).then((response) => {
    console.log('Order ID: ', response.result.id);
}).catch((error) => {
    console.error(error);
});
```

---

## Conclusion
Integrating payment gateways like Stripe and PayPal can enhance your application’s functionality. Always ensure to comply with PCI standards and handle sensitive information securely. Test thoroughly in sandbox environments before going live.