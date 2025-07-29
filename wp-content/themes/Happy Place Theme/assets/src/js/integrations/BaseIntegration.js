/**
 * Base Integration Framework
 * 
 * Provides a standardized foundation for all external service integrations
 * with authentication, rate limiting, caching, and error handling.
 * 
 * @since 3.0.0
 */

class BaseIntegration {
    constructor(config = {}) {
        this.config = {
            apiUrl: '',
            apiKey: '',
            apiSecret: '',
            timeout: 30000,
            retryAttempts: 3,
            retryDelay: 1000,
            rateLimitRequests: 100,
            rateLimitWindow: 60000, // 1 minute
            cacheEnabled: true,
            cacheTTL: 300000, // 5 minutes
            debug: false,
            ...config
        };
        
        // Internal state
        this.authenticated = false;
        this.lastAuthTime = null;
        this.authToken = null;
        this.requestCounts = new Map();
        this.cache = new Map();
        this.webhookHandlers = new Map();
        
        // Event emitter for integration events
        this.eventTarget = new EventTarget();
        
        this.init();
    }
    
    /**
     * Initialize integration
     */
    async init() {
        try {
            await this.authenticate();
            this.setupRateLimiting();
            this.setupCache();
            this.setupWebhooks();
            
            this.log('Integration initialized successfully');
            this.emit('initialized', { integration: this.constructor.name });
        } catch (error) {
            this.log(`Integration initialization failed: ${error.message}`, 'error');
            this.emit('error', { type: 'initialization', error });
        }
    }
    
    /**
     * Authenticate with the service
     */
    async authenticate() {
        // Override in subclasses
        this.authenticated = true;
        this.lastAuthTime = Date.now();
    }
    
    /**
     * Check if authentication is valid
     */
    isAuthenticationValid() {
        if (!this.authenticated || !this.lastAuthTime) {
            return false;
        }
        
        // Check if auth is older than 1 hour
        const authAge = Date.now() - this.lastAuthTime;
        return authAge < 3600000; // 1 hour
    }
    
    /**
     * Make authenticated API request
     */
    async request(endpoint, options = {}) {
        const config = {
            method: 'GET',
            headers: {},
            data: null,
            cache: this.config.cacheEnabled,
            ...options
        };
        
        // Ensure authentication
        if (!this.isAuthenticationValid()) {
            await this.authenticate();
        }
        
        // Check rate limiting
        if (!this.checkRateLimit()) {
            throw new Error('Rate limit exceeded');
        }
        
        // Generate cache key
        const cacheKey = this.generateCacheKey(endpoint, config);
        
        // Check cache
        if (config.cache && config.method === 'GET') {
            const cached = this.getFromCache(cacheKey);
            if (cached) {
                this.log(`Cache hit for ${endpoint}`);
                return cached;
            }
        }
        
        // Make request with retries
        let lastError;
        for (let attempt = 0; attempt <= this.config.retryAttempts; attempt++) {
            try {
                const result = await this.makeHttpRequest(endpoint, config);
                
                // Cache successful GET requests
                if (config.cache && config.method === 'GET') {
                    this.setCache(cacheKey, result);
                }
                
                this.emit('request:success', { endpoint, method: config.method, attempt });
                return result;
                
            } catch (error) {
                lastError = error;
                
                // Don't retry on client errors (4xx) except 429 (rate limit)
                if (error.status >= 400 && error.status < 500 && error.status !== 429) {
                    break;
                }
                
                // Don't retry on authentication errors
                if (error.status === 401 || error.status === 403) {
                    this.authenticated = false;
                    break;
                }
                
                // Wait before retry
                if (attempt < this.config.retryAttempts) {
                    await this.delay(this.config.retryDelay * Math.pow(2, attempt));
                }
            }
        }
        
        this.emit('request:error', { endpoint, method: config.method, error: lastError });
        throw lastError;
    }
    
    /**
     * Make HTTP request
     */
    async makeHttpRequest(endpoint, config) {
        const url = this.buildUrl(endpoint);
        const headers = this.buildHeaders(config.headers);
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
        
        try {
            const requestOptions = {
                method: config.method,
                headers,
                signal: controller.signal
            };
            
            // Add body for non-GET requests
            if (config.data && config.method !== 'GET') {
                if (config.data instanceof FormData) {
                    requestOptions.body = config.data;
                } else {
                    requestOptions.body = JSON.stringify(config.data);
                    headers['Content-Type'] = 'application/json';
                }
            }
            
            this.log(`Making ${config.method} request to ${url}`);
            
            const response = await fetch(url, requestOptions);
            clearTimeout(timeoutId);
            
            // Update rate limiting
            this.updateRateLimit();
            
            if (!response.ok) {
                const error = new Error(`HTTP ${response.status}: ${response.statusText}`);
                error.status = response.status;
                error.response = response;
                throw error;
            }
            
            const result = await response.json();
            return result;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }
            
            throw error;
        }
    }
    
    /**
     * Build full URL
     */
    buildUrl(endpoint) {
        return `${this.config.apiUrl.replace(/\/$/, '')}/${endpoint.replace(/^\//, '')}`;
    }
    
    /**
     * Build request headers
     */
    buildHeaders(additionalHeaders = {}) {
        const headers = {
            'User-Agent': 'HappyPlace-WordPress-Theme/3.0.0',
            'Accept': 'application/json',
            ...additionalHeaders
        };
        
        // Add authentication headers
        if (this.authToken) {
            headers['Authorization'] = `Bearer ${this.authToken}`;
        } else if (this.config.apiKey) {
            headers['X-API-Key'] = this.config.apiKey;
        }
        
        return headers;
    }
    
    // ===================
    // RATE LIMITING
    // ===================
    
    /**
     * Setup rate limiting
     */
    setupRateLimiting() {
        // Clean up old rate limit data periodically
        setInterval(() => {
            this.cleanupRateLimiting();
        }, this.config.rateLimitWindow);
    }
    
    /**
     * Check rate limiting
     */
    checkRateLimit() {
        const now = Date.now();
        const windowStart = now - this.config.rateLimitWindow;
        
        // Count requests in current window
        let requestCount = 0;
        for (const [timestamp, count] of this.requestCounts.entries()) {
            if (timestamp > windowStart) {
                requestCount += count;
            }
        }
        
        return requestCount < this.config.rateLimitRequests;
    }
    
    /**
     * Update rate limiting counter
     */
    updateRateLimit() {
        const now = Date.now();
        const currentMinute = Math.floor(now / 60000) * 60000;
        
        this.requestCounts.set(currentMinute, 
            (this.requestCounts.get(currentMinute) || 0) + 1);
    }
    
    /**
     * Clean up old rate limiting data
     */
    cleanupRateLimiting() {
        const cutoff = Date.now() - this.config.rateLimitWindow;
        
        for (const timestamp of this.requestCounts.keys()) {
            if (timestamp < cutoff) {
                this.requestCounts.delete(timestamp);
            }
        }
    }
    
    // ===================
    // CACHING
    // ===================
    
    /**
     * Setup caching
     */
    setupCache() {
        // Clean up expired cache entries periodically
        setInterval(() => {
            this.cleanupCache();
        }, 60000); // Every minute
    }
    
    /**
     * Generate cache key
     */
    generateCacheKey(endpoint, config) {
        const key = `${endpoint}_${JSON.stringify(config.data || {})}_${config.method}`;
        return btoa(key).replace(/[=+/]/g, '');
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
    setCache(key, data, ttl = this.config.cacheTTL) {
        // Remove oldest entries if cache is getting large
        if (this.cache.size >= 100) {
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
     * Clear cache
     */
    clearCache(pattern = null) {
        if (pattern) {
            const regex = new RegExp(pattern);
            for (const key of this.cache.keys()) {
                if (regex.test(key)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
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
    // WEBHOOKS
    // ===================
    
    /**
     * Setup webhook handling
     */
    setupWebhooks() {
        // Override in subclasses if webhooks are supported
    }
    
    /**
     * Register webhook handler
     */
    registerWebhook(event, handler) {
        if (!this.webhookHandlers.has(event)) {
            this.webhookHandlers.set(event, new Set());
        }
        
        this.webhookHandlers.get(event).add(handler);
    }
    
    /**
     * Handle incoming webhook
     */
    handleWebhook(event, data) {
        const handlers = this.webhookHandlers.get(event);
        
        if (handlers) {
            handlers.forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    this.log(`Webhook handler error for ${event}: ${error.message}`, 'error');
                }
            });
        }
        
        this.emit('webhook', { event, data });
    }
    
    // ===================
    // EVENT HANDLING
    // ===================
    
    /**
     * Emit event
     */
    emit(eventType, data = {}) {
        const event = new CustomEvent(eventType, {
            detail: {
                integration: this.constructor.name,
                timestamp: new Date().toISOString(),
                ...data
            }
        });
        
        this.eventTarget.dispatchEvent(event);
    }
    
    /**
     * Listen for events
     */
    on(eventType, handler) {
        this.eventTarget.addEventListener(eventType, handler);
        
        // Return unsubscribe function
        return () => {
            this.eventTarget.removeEventListener(eventType, handler);
        };
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
     * Log message
     */
    log(message, level = 'info') {
        if (this.config.debug || window.hphAjax?.debug) {
            const prefix = `[${this.constructor.name}]`;
            console[level](`${prefix} ${message}`);
        }
    }
    
    /**
     * Get integration health status
     */
    getHealthStatus() {
        return {
            name: this.constructor.name,
            authenticated: this.authenticated,
            lastAuthTime: this.lastAuthTime,
            cacheSize: this.cache.size,
            rateLimitStatus: this.getRateLimitStatus(),
            config: {
                apiUrl: this.config.apiUrl,
                timeout: this.config.timeout,
                retryAttempts: this.config.retryAttempts,
                cacheEnabled: this.config.cacheEnabled
            }
        };
    }
    
    /**
     * Get rate limit status
     */
    getRateLimitStatus() {
        const now = Date.now();
        const windowStart = now - this.config.rateLimitWindow;
        
        let requestCount = 0;
        for (const [timestamp, count] of this.requestCounts.entries()) {
            if (timestamp > windowStart) {
                requestCount += count;
            }
        }
        
        return {
            requestCount,
            maxRequests: this.config.rateLimitRequests,
            remaining: this.config.rateLimitRequests - requestCount,
            resetTime: Math.ceil((now + this.config.rateLimitWindow) / 60000) * 60000
        };
    }
    
    /**
     * Test connection
     */
    async testConnection() {
        try {
            await this.authenticate();
            const status = await this.getStatus();
            return {
                success: true,
                status,
                timestamp: new Date().toISOString()
            };
        } catch (error) {
            return {
                success: false,
                error: error.message,
                timestamp: new Date().toISOString()
            };
        }
    }
    
    /**
     * Get service status - override in subclasses
     */
    async getStatus() {
        return { status: 'connected' };
    }
    
    /**
     * Destroy integration and cleanup
     */
    destroy() {
        this.clearCache();
        this.requestCounts.clear();
        this.webhookHandlers.clear();
        this.authenticated = false;
        this.authToken = null;
        
        this.emit('destroyed');
        this.log('Integration destroyed');
    }
}

// Export for use in other modules
window.BaseIntegration = BaseIntegration;
