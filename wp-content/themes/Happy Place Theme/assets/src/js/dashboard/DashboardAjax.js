/**
 * Dashboard AJAX Manager
 * 
 * Provides a unified interface for all dashboard AJAX operations
 * with request management, caching, and error handling.
 * 
 * @since 3.0.0
 */

class DashboardAjax {
    constructor() {
        this.baseUrl = window.hphAjax?.ajax_url || '/wp-admin/admin-ajax.php';
        this.nonce = window.hphAjax?.nonce || '';
        this.timeout = 30000; // 30 seconds
        this.retryAttempts = 3;
        this.retryDelay = 1000; // 1 second
        
        // Request queue and cache
        this.activeRequests = new Map();
        this.requestQueue = [];
        this.cache = new Map();
        this.maxCacheSize = 100;
        this.defaultCacheTTL = 5 * 60 * 1000; // 5 minutes
        
        // Rate limiting
        this.requestCounts = new Map();
        this.rateLimitWindow = 60000; // 1 minute
        this.maxRequestsPerWindow = 100;
        
        this.init();
    }
    
    /**
     * Initialize AJAX manager
     */
    init() {
        // Clean up cache periodically
        setInterval(() => {
            this.cleanupCache();
        }, 60000); // Every minute
        
        // Clean up rate limiting data
        setInterval(() => {
            this.cleanupRateLimiting();
        }, this.rateLimitWindow);
        
        // Handle page visibility for request management
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseRequests();
            } else {
                this.resumeRequests();
            }
        });
    }
    
    /**
     * Make AJAX request
     */
    async request(action, data = {}, options = {}) {
        const config = {
            method: 'POST',
            cache: false,
            cacheTTL: this.defaultCacheTTL,
            retries: this.retryAttempts,
            timeout: this.timeout,
            priority: 'normal',
            ...options
        };
        
        // Generate request key for deduplication
        const requestKey = this.generateRequestKey(action, data);
        
        // Check rate limiting
        if (!this.checkRateLimit()) {
            throw new Error('Rate limit exceeded');
        }
        
        // Check cache first (for cacheable requests)
        if (config.cache) {
            const cached = this.getFromCache(requestKey);
            if (cached) {
                return cached;
            }
        }
        
        // Check for duplicate active requests
        if (this.activeRequests.has(requestKey)) {
            return this.activeRequests.get(requestKey);
        }
        
        // Create and execute request
        const requestPromise = this.executeRequest(action, data, config);
        
        // Track active request
        this.activeRequests.set(requestKey, requestPromise);
        
        try {
            const result = await requestPromise;
            
            // Cache successful cacheable requests
            if (config.cache && result.success) {
                this.setCache(requestKey, result, config.cacheTTL);
            }
            
            return result;
        } finally {
            // Clean up active request
            this.activeRequests.delete(requestKey);
        }
    }
    
    /**
     * Execute the actual AJAX request
     */
    async executeRequest(action, data, config) {
        let lastError;
        
        for (let attempt = 0; attempt <= config.retries; attempt++) {
            try {
                return await this.makeHttpRequest(action, data, config);
            } catch (error) {
                lastError = error;
                
                // Don't retry on client errors (4xx)
                if (error.status >= 400 && error.status < 500) {
                    break;
                }
                
                // Wait before retry
                if (attempt < config.retries) {
                    await this.delay(this.retryDelay * Math.pow(2, attempt));
                }
            }
        }
        
        throw lastError;
    }
    
    /**
     * Make HTTP request
     */
    async makeHttpRequest(action, data, config) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), config.timeout);
        
        try {
            const formData = new FormData();
            formData.append('action', `hph_${action}`);
            formData.append('nonce', this.nonce);
            
            // Add data to form
            Object.entries(data).forEach(([key, value]) => {
                if (value instanceof File || value instanceof Blob) {
                    formData.append(key, value);
                } else if (Array.isArray(value) || typeof value === 'object') {
                    formData.append(key, JSON.stringify(value));
                } else {
                    formData.append(key, value);
                }
            });
            
            const response = await fetch(this.baseUrl, {
                method: config.method,
                body: formData,
                signal: controller.signal,
                credentials: 'same-origin'
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success && result.data?.error) {
                throw new Error(result.data.error);
            }
            
            return result;
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }
            
            throw error;
        }
    }
    
    // ===================
    // SPECIALIZED METHODS
    // ===================
    
    /**
     * Get listings
     */
    async getListings(filters = {}) {
        return this.request('get_listings', filters, {
            cache: true,
            cacheTTL: 2 * 60 * 1000 // 2 minutes
        });
    }
    
    /**
     * Get single listing
     */
    async getListing(id) {
        return this.request('get_listing', { id }, {
            cache: true,
            cacheTTL: 5 * 60 * 1000 // 5 minutes
        });
    }
    
    /**
     * Save listing
     */
    async saveListing(listingData) {
        return this.request('save_listing', listingData, {
            cache: false
        });
    }
    
    /**
     * Delete listing
     */
    async deleteListing(id) {
        // Clear related cache entries
        this.clearCacheByPattern(`get_listing_${id}`);
        this.clearCacheByPattern('get_listings');
        
        return this.request('delete_listing', { id }, {
            cache: false
        });
    }
    
    /**
     * Get leads
     */
    async getLeads(filters = {}) {
        return this.request('get_leads', filters, {
            cache: true,
            cacheTTL: 1 * 60 * 1000 // 1 minute
        });
    }
    
    /**
     * Get analytics data
     */
    async getAnalytics(period = '30d') {
        return this.request('get_analytics', { period }, {
            cache: true,
            cacheTTL: 10 * 60 * 1000 // 10 minutes
        });
    }
    
    /**
     * Search listings
     */
    async searchListings(query, filters = {}) {
        return this.request('search_listings', { query, ...filters }, {
            cache: true,
            cacheTTL: 1 * 60 * 1000 // 1 minute
        });
    }
    
    /**
     * Generate flyer
     */
    async generateFlyer(listingId, template = 'default') {
        return this.request('generate_flyer', { 
            listing_id: listingId, 
            template 
        }, {
            cache: false,
            timeout: 60000 // 1 minute for flyer generation
        });
    }
    
    /**
     * Upload media
     */
    async uploadMedia(file, metadata = {}) {
        return this.request('upload_media', { 
            file, 
            ...metadata 
        }, {
            cache: false,
            timeout: 120000 // 2 minutes for file uploads
        });
    }
    
    /**
     * Get dashboard stats
     */
    async getDashboardStats() {
        return this.request('get_dashboard_stats', {}, {
            cache: true,
            cacheTTL: 5 * 60 * 1000 // 5 minutes
        });
    }
    
    /**
     * Update user preferences
     */
    async updatePreferences(preferences) {
        return this.request('update_preferences', preferences, {
            cache: false
        });
    }
    
    /**
     * Get user preferences
     */
    async getPreferences() {
        return this.request('get_preferences', {}, {
            cache: true,
            cacheTTL: 10 * 60 * 1000 // 10 minutes
        });
    }
    
    // ===================
    // CACHE MANAGEMENT
    // ===================
    
    /**
     * Generate request key for caching
     */
    generateRequestKey(action, data) {
        const sortedData = Object.keys(data)
            .sort()
            .reduce((sorted, key) => {
                sorted[key] = data[key];
                return sorted;
            }, {});
        
        return `${action}_${btoa(JSON.stringify(sortedData))}`;
    }
    
    /**
     * Get from cache
     */
    getFromCache(key) {
        const cached = this.cache.get(key);
        
        if (cached && cached.expires > Date.now()) {
            return cached.data;
        }
        
        // Remove expired entry
        if (cached) {
            this.cache.delete(key);
        }
        
        return null;
    }
    
    /**
     * Set cache entry
     */
    setCache(key, data, ttl = this.defaultCacheTTL) {
        // Remove oldest entries if cache is full
        if (this.cache.size >= this.maxCacheSize) {
            const oldestKey = this.cache.keys().next().value;
            this.cache.delete(oldestKey);
        }
        
        this.cache.set(key, {
            data,
            expires: Date.now() + ttl,
            created: Date.now()
        });
    }
    
    /**
     * Clear cache by pattern
     */
    clearCacheByPattern(pattern) {
        const regex = new RegExp(pattern);
        
        for (const key of this.cache.keys()) {
            if (regex.test(key)) {
                this.cache.delete(key);
            }
        }
    }
    
    /**
     * Clear all cache
     */
    clearCache() {
        this.cache.clear();
    }
    
    /**
     * Clean up expired cache entries
     */
    cleanupCache() {
        const now = Date.now();
        
        for (const [key, entry] of this.cache.entries()) {
            if (entry.expires <= now) {
                this.cache.delete(key);
            }
        }
    }
    
    // ===================
    // RATE LIMITING
    // ===================
    
    /**
     * Check rate limiting
     */
    checkRateLimit() {
        const now = Date.now();
        const windowStart = now - this.rateLimitWindow;
        
        // Count requests in current window
        let requestCount = 0;
        for (const [timestamp, count] of this.requestCounts.entries()) {
            if (timestamp > windowStart) {
                requestCount += count;
            }
        }
        
        if (requestCount >= this.maxRequestsPerWindow) {
            return false;
        }
        
        // Increment counter for current minute
        const currentMinute = Math.floor(now / 60000) * 60000;
        this.requestCounts.set(currentMinute, 
            (this.requestCounts.get(currentMinute) || 0) + 1);
        
        return true;
    }
    
    /**
     * Clean up old rate limiting data
     */
    cleanupRateLimiting() {
        const cutoff = Date.now() - this.rateLimitWindow;
        
        for (const timestamp of this.requestCounts.keys()) {
            if (timestamp < cutoff) {
                this.requestCounts.delete(timestamp);
            }
        }
    }
    
    // ===================
    // REQUEST MANAGEMENT
    // ===================
    
    /**
     * Cancel all active requests
     */
    cancelAllRequests() {
        this.activeRequests.forEach((requestPromise, key) => {
            // Requests are already tracked and will resolve/reject naturally
        });
        
        this.activeRequests.clear();
    }
    
    /**
     * Pause requests (on page hide)
     */
    pauseRequests() {
        // Implementation would depend on specific requirements
        // For now, just log the action
        console.log('Pausing dashboard requests');
    }
    
    /**
     * Resume requests (on page show)
     */
    resumeRequests() {
        console.log('Resuming dashboard requests');
    }
    
    // ===================
    // UTILITY METHODS
    // ===================
    
    /**
     * Delay utility
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Get cache statistics
     */
    getCacheStats() {
        const now = Date.now();
        let expired = 0;
        let active = 0;
        
        for (const entry of this.cache.values()) {
            if (entry.expires <= now) {
                expired++;
            } else {
                active++;
            }
        }
        
        return {
            total: this.cache.size,
            active,
            expired,
            maxSize: this.maxCacheSize
        };
    }
    
    /**
     * Get active request count
     */
    getActiveRequestCount() {
        return this.activeRequests.size;
    }
    
    /**
     * Get rate limit status
     */
    getRateLimitStatus() {
        const now = Date.now();
        const windowStart = now - this.rateLimitWindow;
        
        let requestCount = 0;
        for (const [timestamp, count] of this.requestCounts.entries()) {
            if (timestamp > windowStart) {
                requestCount += count;
            }
        }
        
        return {
            requestCount,
            maxRequests: this.maxRequestsPerWindow,
            remaining: this.maxRequestsPerWindow - requestCount,
            resetTime: Math.ceil((now + this.rateLimitWindow) / 60000) * 60000
        };
    }
}

// Export for use in other modules
window.DashboardAjax = DashboardAjax;
