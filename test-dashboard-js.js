// Test JavaScript syntax validation
document.addEventListener('DOMContentLoaded', function() {
    // Dashboard object for global functionality
    window.HphDashboard = {
        currentSection: 'overview',
        userId: 1,
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: 'test_nonce',
        
        // Show loading overlay
        showLoading: function() {
            document.getElementById('dashboard-loading').classList.remove('hph-dashboard-loading--hidden');
        },
        
        // Hide loading overlay
        hideLoading: function() {
            document.getElementById('dashboard-loading').classList.add('hph-dashboard-loading--hidden');
        },
        
        // Show toast notification
        showToast: function(message, type = 'info') {
            const container = document.getElementById('dashboard-notifications');
            const toast = document.createElement('div');
            toast.className = `hph-dashboard-toast hph-dashboard-toast--${type}`;
            toast.innerHTML = `
                <div class="hph-dashboard-toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="hph-dashboard-toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }
    };
    
    // Section navigation handling
    document.querySelectorAll('.hph-dashboard-nav-item').forEach(link => {
        link.addEventListener('click', function(e) {
            const section = this.dataset.section;
            if (section && section !== HphDashboard.currentSection) {
                HphDashboard.showLoading();
                // Let the default navigation happen
            }
        });
    });
    
    // Dashboard initialization complete
}); // End of DOMContentLoaded

// Initialize Listing Form JavaScript (separate from DOMContentLoaded)
window.HphListingForm = {
    currentListing: null,
    currentTab: 'basic',
    
    // Initialize the form
    init: function() {
        this.bindEvents();
        this.initTabs();
        this.initMediaUpload();
    },
    
    // Focus virtual tour field
    focusVirtualTour: function() {
        this.switchTab('features');
        setTimeout(() => {
            document.getElementById('virtual-tour-url').focus();
        }, 100);
    }
}; // End of HphListingForm
