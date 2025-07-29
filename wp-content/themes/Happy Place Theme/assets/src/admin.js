// Happy Place Theme - Admin JavaScript Entry Point
// Admin-specific functionality for WordPress backend

// Import admin SCSS
import './scss/admin.scss';

// Admin dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin components
    initializeAdminComponents();
});

/**
 * Initialize admin-specific components
 */
function initializeAdminComponents() {
    // Metabox enhancements
    initializeMetaboxes();
    
    // Media uploader enhancements
    initializeMediaUploaders();
    
    // Custom post type enhancements
    initializeCustomPostTypes();
    
    // ACF field enhancements
    initializeACFFields();
}

/**
 * Initialize metabox enhancements
 */
function initializeMetaboxes() {
    // Collapsible metabox sections
    const metaboxHeaders = document.querySelectorAll('.hph-metabox-header');
    metaboxHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            if (content) {
                content.style.display = content.style.display === 'none' ? 'block' : 'none';
                this.classList.toggle('collapsed');
            }
        });
    });
}

/**
 * Initialize media uploader enhancements
 */
function initializeMediaUploaders() {
    // Custom media uploader buttons
    const uploadButtons = document.querySelectorAll('.hph-upload-button');
    uploadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const mediaUploader = wp.media({
                title: 'Select Images',
                button: {
                    text: 'Use Selected Images'
                },
                multiple: this.dataset.multiple === 'true'
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                const targetInput = document.getElementById(button.dataset.target);
                if (targetInput) {
                    targetInput.value = attachment.id;
                    
                    // Update preview if exists
                    const preview = document.getElementById(button.dataset.target + '_preview');
                    if (preview) {
                        preview.src = attachment.url;
                        preview.style.display = 'block';
                    }
                }
            });
            
            mediaUploader.open();
        });
    });
}

/**
 * Initialize custom post type enhancements
 */
function initializeCustomPostTypes() {
    // Listing-specific admin features
    if (document.body.classList.contains('post-type-listing')) {
        initializeListingAdmin();
    }
    
    // Agent-specific admin features
    if (document.body.classList.contains('post-type-agent')) {
        initializeAgentAdmin();
    }
}

/**
 * Initialize ACF field enhancements
 */
function initializeACFFields() {
    // Price formatting
    const priceFields = document.querySelectorAll('.acf-field[data-name="price"] input');
    priceFields.forEach(field => {
        field.addEventListener('blur', function() {
            const value = parseInt(this.value.replace(/[^0-9]/g, ''));
            if (!isNaN(value)) {
                this.value = value.toLocaleString();
            }
        });
    });
    
    // Phone number formatting
    const phoneFields = document.querySelectorAll('.acf-field[data-name*="phone"] input');
    phoneFields.forEach(field => {
        field.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length >= 6) {
                value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
            } else if (value.length >= 3) {
                value = value.substring(0, 3) + '-' + value.substring(3);
            }
            this.value = value;
        });
    });
}

/**
 * Initialize listing admin features
 */
function initializeListingAdmin() {
    // Auto-generate slug from address
    const addressField = document.querySelector('.acf-field[data-name="address"] input');
    const titleField = document.querySelector('#title');
    
    if (addressField && titleField) {
        addressField.addEventListener('blur', function() {
            if (!titleField.value && this.value) {
                titleField.value = this.value;
                titleField.dispatchEvent(new Event('input'));
            }
        });
    }
}

/**
 * Initialize agent admin features
 */
function initializeAgentAdmin() {
    // Auto-generate display name
    const firstNameField = document.querySelector('.acf-field[data-name="first_name"] input');
    const lastNameField = document.querySelector('.acf-field[data-name="last_name"] input');
    const titleField = document.querySelector('#title');
    
    if (firstNameField && lastNameField && titleField) {
        function updateDisplayName() {
            if (!titleField.value && (firstNameField.value || lastNameField.value)) {
                titleField.value = (firstNameField.value + ' ' + lastNameField.value).trim();
                titleField.dispatchEvent(new Event('input'));
            }
        }
        
        firstNameField.addEventListener('blur', updateDisplayName);
        lastNameField.addEventListener('blur', updateDisplayName);
    }
}
