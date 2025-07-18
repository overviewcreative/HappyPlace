/**
 * Happy Place Filter Sidebar JavaScript
 * 
 * @package HappyPlace
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize collapsible filter sections
    initFilterToggles();
    
    // Initialize price and number inputs
    initNumericInputs();
    
    // Initialize sort select functionality
    initSortSelect();
});

/**
 * Initialize filter section toggles
 */
function initFilterToggles() {
    const toggles = document.querySelectorAll('.hph-filter-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isCollapsed = content.classList.contains('hph-filter-content--collapsed');
            
            // Toggle the active class
            this.classList.toggle('open', isCollapsed);
            
            // Toggle the content visibility
            content.classList.toggle('hph-filter-content--collapsed', !isCollapsed);
            
            // Save state in localStorage (optional)
            const sectionId = this.textContent.trim().toLowerCase().replace(/\s+/g, '-');
            localStorage.setItem(`hph_filter_${sectionId}`, isCollapsed ? 'open' : 'closed');
        });
        
        // Check if we should open this section based on localStorage or URL parameters
        const sectionId = toggle.textContent.trim().toLowerCase().replace(/\s+/g, '-');
        const sectionState = localStorage.getItem(`hph_filter_${sectionId}`);
        const content = toggle.nextElementSibling;
        
        // Check if any filter in this section is active
        const hasActiveFilter = checkSectionForActiveFilters(content);
        
        if (sectionState === 'open' || hasActiveFilter) {
            toggle.classList.add('open');
            content.classList.remove('hph-filter-content--collapsed');
        }
    });
}

/**
 * Check if a filter section has any active filters
 * 
 * @param {HTMLElement} section The filter section content element
 * @returns {boolean} True if any filter is active
 */
function checkSectionForActiveFilters(section) {
    // Check inputs
    const inputs = section.querySelectorAll('input[type="text"], input[type="number"]');
    for (const input of inputs) {
        if (input.value) {
            return true;
        }
    }
    
    // Check selects
    const selects = section.querySelectorAll('select');
    for (const select of selects) {
        if (select.value) {
            return true;
        }
    }
    
    // Check checkboxes
    const checkboxes = section.querySelectorAll('input[type="checkbox"]');
    for (const checkbox of checkboxes) {
        if (checkbox.checked) {
            return true;
        }
    }
    
    return false;
}

/**
 * Initialize numeric input formatting and validation
 */
function initNumericInputs() {
    // Price inputs
    const priceInputs = document.querySelectorAll('input[name="price_min"], input[name="price_max"]');
    
    priceInputs.forEach(input => {
        // Format initial value if present
        if (input.value) {
            input.value = formatPrice(input.value);
        }
        
        // Format on blur
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = formatPrice(this.value);
            }
        });
        
        // Clean up on focus
        input.addEventListener('focus', function() {
            this.value = this.value.replace(/[$,]/g, '');
        });
        
        // Allow only numbers and formatting characters
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9$,]/g, '');
        });
    });
    
    // Other numeric inputs
    const numericInputs = document.querySelectorAll('input[name="sq_ft_min"], input[name="sq_ft_max"], input[name="lot_size_min"], input[name="lot_size_max"], input[name="year_built_min"], input[name="year_built_max"]');
    
    numericInputs.forEach(input => {
        // Format initial value if present
        if (input.value && !input.name.includes('year_built') && !input.name.includes('lot_size')) {
            input.value = formatNumber(input.value);
        }
        
        // Format on blur
        input.addEventListener('blur', function() {
            if (this.value && !this.name.includes('year_built') && !this.name.includes('lot_size')) {
                this.value = formatNumber(this.value);
            }
        });
        
        // Clean up on focus
        input.addEventListener('focus', function() {
            if (!this.name.includes('year_built') && !this.name.includes('lot_size')) {
                this.value = this.value.replace(/[,]/g, '');
            }
        });
        
        // Allow only numbers and decimal point for lot size
        input.addEventListener('input', function() {
            if (this.name.includes('lot_size')) {
                this.value = this.value.replace(/[^0-9.]/g, '');
            } else {
                this.value = this.value.replace(/[^0-9]/g, '');
            }
        });
    });
}

/**
 * Format price with dollar sign and commas
 * 
 * @param {string|number} price Price to format
 * @returns {string} Formatted price
 */
function formatPrice(price) {
    // Remove any existing formatting
    price = price.toString().replace(/[$,]/g, '');
    
    // Parse as integer
    const numericPrice = parseInt(price, 10);
    
    // Return empty string if invalid
    if (isNaN(numericPrice)) {
        return '';
    }
    
    // Format with commas
    return numericPrice.toLocaleString('en-US');
}

/**
 * Format number with commas
 * 
 * @param {string|number} number Number to format
 * @returns {string} Formatted number
 */
function formatNumber(number) {
    // Remove any existing formatting
    number = number.toString().replace(/[,]/g, '');
    
    // Parse as integer
    const numericValue = parseInt(number, 10);
    
    // Return empty string if invalid
    if (isNaN(numericValue)) {
        return '';
    }
    
    // Format with commas
    return numericValue.toLocaleString('en-US');
}

/**
 * Initialize sort select functionality
 */
function initSortSelect() {
    const sortSelect = document.getElementById('sort-select');
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            window.location.href = this.value;
        });
    }
}