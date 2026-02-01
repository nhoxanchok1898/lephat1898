// Wishlist functionality - Add/remove items via AJAX

document.addEventListener('DOMContentLoaded', function() {
    // Initialize wishlist buttons
    const wishlistButtons = document.querySelectorAll('.btn-add-wishlist');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            addToWishlist(productId);
        });
    });
    
    // Initialize remove buttons
    const removeButtons = document.querySelectorAll('.btn-remove-wishlist');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            removeFromWishlist(productId);
        });
    });
});

function addToWishlist(productId) {
    const csrfToken = document.querySelector('[name=csrfmiddlewaretoken]').value;
    
    fetch(`/wishlist/add/${productId}/`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRFToken': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateWishlistUI(productId, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to wishlist', 'error');
    });
}

function removeFromWishlist(productId) {
    const csrfToken = document.querySelector('[name=csrfmiddlewaretoken]').value;
    
    fetch(`/wishlist/remove/${productId}/`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRFToken': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.removed) {
            showNotification('Removed from wishlist', 'success');
            updateWishlistUI(productId, false);
            
            // Remove row if on wishlist page
            const row = document.querySelector(`#wishlist-item-${productId}`);
            if (row) {
                row.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing from wishlist', 'error');
    });
}

function updateWishlistUI(productId, inWishlist) {
    const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
    buttons.forEach(button => {
        if (inWishlist) {
            button.classList.add('in-wishlist');
            button.innerHTML = '❤️ In Wishlist';
        } else {
            button.classList.remove('in-wishlist');
            button.innerHTML = '🤍 Add to Wishlist';
        }
    });
}

function checkWishlistStatus(productId) {
    fetch(`/wishlist/check/${productId}/`)
        .then(response => response.json())
        .then(data => {
            updateWishlistUI(productId, data.in_wishlist);
        })
        .catch(error => console.error('Error:', error));
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
