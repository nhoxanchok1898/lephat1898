// Review functionality - helpful voting and AJAX interactions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize review helpful buttons
    const helpfulButtons = document.querySelectorAll('.btn-review-helpful');
    
    helpfulButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const reviewId = this.dataset.reviewId;
            markReviewHelpful(reviewId);
        });
    });
});

function markReviewHelpful(reviewId) {
    const csrfToken = document.querySelector('[name=csrfmiddlewaretoken]').value;
    
    fetch(`/reviews/helpful/${reviewId}/`, {
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
            // Update helpful count
            const countElement = document.querySelector(`#helpful-count-${reviewId}`);
            if (countElement) {
                countElement.textContent = data.helpful_count;
            }
            
            // Show feedback
            const message = data.action === 'added' ? 'Marked as helpful!' : 'Removed helpful vote';
            showNotification(message, 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error processing request', 'error');
    });
}

function showNotification(message, type) {
    // Create notification element
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
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Review form validation
const reviewForm = document.querySelector('#review-form');
if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
        const rating = document.querySelector('input[name="rating"]:checked');
        const comment = document.querySelector('#comment').value.trim();
        
        if (!rating) {
            e.preventDefault();
            showNotification('Please select a rating', 'error');
            return false;
        }
        
        if (comment.length < 10) {
            e.preventDefault();
            showNotification('Review must be at least 10 characters long', 'error');
            return false;
        }
    });
}
