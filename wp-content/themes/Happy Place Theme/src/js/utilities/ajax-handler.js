/**
 * AJAX Handler Utility
 * Happy Place Theme
 */

class AjaxHandler {
    constructor() {
        this.ajaxUrl = window.hphTheme?.ajaxUrl || '/wp-admin/admin-ajax.php';
        this.nonce = window.hphTheme?.nonce || '';
    }

    /**
     * Make AJAX request
     */
    async request(action, data = {}, options = {}) {
        const defaults = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        };

        const config = { ...defaults, ...options };

        const formData = new URLSearchParams({
            action,
            nonce: this.nonce,
            ...data
        });

        try {
            const response = await fetch(this.ajaxUrl, {
                ...config,
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data?.message || 'Request failed');
            }

            return result.data;
        } catch (error) {
            console.error('AJAX Error:', error);
            throw error;
        }
    }

    /**
     * Load listings with filters
     */
    async loadListings(filters = {}) {
        return this.request('hph_load_listings', { filters });
    }

    /**
     * Load more listings (pagination)
     */
    async loadMoreListings(page, filters = {}) {
        return this.request('hph_load_more_listings', { page, filters });
    }

    /**
     * Save user preferences
     */
    async savePreferences(preferences) {
        return this.request('hph_save_preferences', { preferences });
    }

    /**
     * Submit contact form
     */
    async submitContactForm(formData) {
        return this.request('hph_submit_contact_form', formData);
    }
}

// Export for use in other modules
export default AjaxHandler;

// Make available globally for legacy code
window.HphAjaxHandler = AjaxHandler;