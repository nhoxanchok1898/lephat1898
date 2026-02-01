// Enhanced product filters with AJAX updates

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('#filter-form');
    
    if (filterForm) {
        // Handle filter changes
        const filterInputs = filterForm.querySelectorAll('input, select');
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                applyFilters();
            });
        });
        
        // Clear filters button
        const clearBtn = document.querySelector('#clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearFilters();
            });
        }
    }
});

function applyFilters() {
    const form = document.querySelector('#filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    // Update URL and reload (simple approach)
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

function clearFilters() {
    const form = document.querySelector('#filter-form');
    form.reset();
    window.location.href = window.location.pathname;
}

// Price range filter
const priceRange = document.querySelector('#price-range');
if (priceRange) {
    const priceDisplay = document.querySelector('#price-display');
    priceRange.addEventListener('input', function() {
        if (priceDisplay) {
            priceDisplay.textContent = `$0 - $${this.value}`;
        }
    });
}

// Sort functionality
const sortSelect = document.querySelector('#sort-select');
if (sortSelect) {
    sortSelect.addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('sort', this.value);
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    });
}

// Quick view functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('quick-view-btn')) {
        e.preventDefault();
        const productId = e.target.dataset.productId;
        showQuickView(productId);
    }
});

function showQuickView(productId) {
    // Fetch product details and show in modal
    fetch(`/products/${productId}/`)
        .then(response => response.text())
        .then(html => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Quick View</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${html}
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        })
        .catch(error => console.error('Error:', error));
}

// Grid/List view toggle
const viewToggle = document.querySelectorAll('.view-toggle');
viewToggle.forEach(btn => {
    btn.addEventListener('click', function() {
        const view = this.dataset.view;
        const productGrid = document.querySelector('.product-grid');
        
        if (productGrid) {
            productGrid.className = `product-grid view-${view}`;
            
            // Save preference
            localStorage.setItem('productView', view);
        }
    });
});

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('productView');
    if (savedView) {
        const productGrid = document.querySelector('.product-grid');
        if (productGrid) {
            productGrid.className = `product-grid view-${savedView}`;
        }
    }
});
