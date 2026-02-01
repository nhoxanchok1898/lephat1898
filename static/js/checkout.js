// Enhanced checkout form validation and multi-step functionality

document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.querySelector('#checkout-form');
    
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (!validateCheckoutForm()) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time validation
        const inputs = checkoutForm.querySelectorAll('input[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    }
});

function validateCheckoutForm() {
    let isValid = true;
    const form = document.querySelector('#checkout-form');
    
    // Validate name
    const name = form.querySelector('[name="name"]');
    if (name && name.value.trim().length < 2) {
        showFieldError(name, 'Name must be at least 2 characters');
        isValid = false;
    }
    
    // Validate phone
    const phone = form.querySelector('[name="phone"]');
    if (phone && !validatePhone(phone.value)) {
        showFieldError(phone, 'Please enter a valid phone number');
        isValid = false;
    }
    
    // Validate address
    const address = form.querySelector('[name="address"]');
    if (address && address.value.trim().length < 10) {
        showFieldError(address, 'Address must be at least 10 characters');
        isValid = false;
    }
    
    // Validate payment method
    const paymentMethod = form.querySelector('[name="payment_method"]:checked');
    if (!paymentMethod) {
        showNotification('Please select a payment method', 'error');
        isValid = false;
    }
    
    return isValid;
}

function validateField(field) {
    clearFieldError(field);
    
    if (field.hasAttribute('required') && !field.value.trim()) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    if (field.name === 'phone' && !validatePhone(field.value)) {
        showFieldError(field, 'Please enter a valid phone number');
        return false;
    }
    
    if (field.name === 'email' && field.value && !validateEmail(field.value)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    }
    
    return true;
}

function validatePhone(phone) {
    // Allow various phone formats
    const phoneRegex = /^[\d\s\-\+\(\)]{8,20}$/;
    return phoneRegex.test(phone.trim());
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email.trim());
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Payment method selection handler
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            updatePaymentInfo(this.value);
        });
    });
});

function updatePaymentInfo(method) {
    const infoDiv = document.querySelector('#payment-info');
    if (!infoDiv) return;
    
    let message = '';
    switch(method) {
        case 'stripe':
            message = 'You will be redirected to Stripe for secure payment.';
            break;
        case 'paypal':
            message = 'You will be redirected to PayPal for secure payment.';
            break;
        case 'offline':
            message = 'Please pay upon delivery or at our store.';
            break;
    }
    
    infoDiv.innerHTML = `<div class="alert alert-info">${message}</div>`;
}
