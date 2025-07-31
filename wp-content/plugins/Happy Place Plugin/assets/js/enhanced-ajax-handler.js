/**
 * Enhanced AJAX Handler for Happy Place Dashboard
 * 
 * Provides robust AJAX handling with:
 * - Circuit breaker pattern
 * - Automatic retries with exponential backoff
 * - Request queuing and throttling
 * - Error recovery mechanisms
 * - Performance monitoring
 * 
 * @package HappyPlace
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Enhanced AJAX Handler Class
     */
    class HPHEnhancedAjax {
        constructor(options = {}) {
            this.config = {
                // Circuit breaker settings
                failureThreshold: 3,
                recoveryTimeout: 60000, // 1 minute
                
                // Retry settings
                maxRetries: 2,
                baseDelay: 1000, // 1 second
                maxDelay: 8000,  // 8 seconds
                backoffMultiplier: 2,
                
                // Request settings
                defaultTimeout: 30000, // 30 seconds
                maxConcurrentRequests: 5,
                
                // Debug settings
                debug: window.hphAjax?.debug || false,
                
                ...options
            };
            
            this.circuitBreakers = new Map();
            this.requestQueue = [];
            this.activeRequests = new Set();
            this.performanceMetrics = new Map();
            this.requestId = 0;
            
            this.init();
        }
        
        /**
         * Initialize the enhanced AJAX handler
         */
        init() {
            this.setupGlobalErrorHandling();
            this.setupPerformanceMonitoring();
            this.bindEvents();
            
            if (this.config.debug) {
                console.log('HPH Enhanced AJAX Handler initialized');
            }
        }
        
        /**
         * Setup global error handling
         */
        setupGlobalErrorHandling() {
            // Override jQuery's default AJAX error handling
            $(document).ajaxError((event, xhr, settings, error) => {
                if (settings.url && settings.url.includes('admin-ajax.php')) {
                    this.handleGlobalAjaxError(xhr, settings, error);
                }
            });
        }
        
        /**
         * Setup performance monitoring
         */
        setupPerformanceMonitoring() {
            // Monitor all AJAX requests
            $(document).ajaxStart(() => {
                this.showGlobalLoading();
            });
            
            $(document).ajaxStop(() => {
                this.hideGlobalLoading();
            });
        }
        
        /**
         * Bind dashboard events
         */
        bindEvents() {
            // Health check button
            $(document).on('click', '.hph-health-check', (e) => {
                e.preventDefault();
                this.performHealthCheck();
            });
            
            // Reset circuit breakers button
            $(document).on('click', '.hph-reset-circuits', (e) => {
                e.preventDefault();
                this.resetCircuitBreakers();
            });
            
            // Retry failed requests button
            $(document).on('click', '.hph-retry-request', (e) => {
                e.preventDefault();
                const requestData = $(e.currentTarget).data('request');
                if (requestData) {
                    this.retryRequest(requestData);
                }
            });
        }
        
        /**
         * Enhanced AJAX request with circuit breaker and retry logic
         */
        request(options) {
            return new Promise((resolve, reject) => {
                const requestId = ++this.requestId;
                const action = options.data?.action || 'unknown';
                
                const requestOptions = {
                    ...options,
                    requestId,
                    action,
                    startTime: Date.now(),
                    retryCount: 0,
                    resolve,
                    reject
                };
                
                this.executeRequest(requestOptions);
            });
        }
        
        /**
         * Execute AJAX request with circuit breaker protection
         */
        executeRequest(options) {
            const { action, requestId, retryCount } = options;
            
            // Check circuit breaker
            if (this.isCircuitOpen(action)) {
                const recovery = this.getCircuitRecoveryTime(action);
                this.handleCircuitBreakerError(options, recovery);
                return;
            }
            
            // Check concurrent request limit
            if (this.activeRequests.size >= this.config.maxConcurrentRequests) {
                this.queueRequest(options);
                return;
            }
            
            // Add to active requests
            this.activeRequests.add(requestId);
            
            // Setup AJAX options
            const ajaxOptions = {
                ...options,
                timeout: options.timeout || this.config.defaultTimeout,
                beforeSend: (xhr) => {
                    this.onRequestStart(options, xhr);
                },
                success: (data, status, xhr) => {
                    this.onRequestSuccess(options, data, status, xhr);
                },
                error: (xhr, status, error) => {
                    this.onRequestError(options, xhr, status, error);
                },
                complete: (xhr, status) => {
                    this.onRequestComplete(options, xhr, status);
                }
            };
            
            // Execute request
            $.ajax(ajaxOptions);
        }
        
        /**
         * Handle request start
         */
        onRequestStart(options, xhr) {
            if (this.config.debug) {
                console.log(`[AJAX] Request started: ${options.action} [${options.requestId}]`);
            }
            
            // Show loading indicator
            this.showRequestLoading(options);
            
            // Call original beforeSend if provided
            if (options.beforeSend && typeof options.beforeSend === 'function') {
                options.beforeSend(xhr);
            }
        }
        
        /**
         * Handle request success
         */
        onRequestSuccess(options, data, status, xhr) {
            const { action, requestId, resolve } = options;
            
            // Record success for circuit breaker
            this.recordCircuitSuccess(action);
            
            // Record performance metrics
            this.recordPerformanceMetrics(options, true);
            
            if (this.config.debug) {
                console.log(`[AJAX] Request successful: ${action} [${requestId}]`);
            }
            
            // Hide loading indicator
            this.hideRequestLoading(options);
            
            // Resolve promise
            resolve(data);
        }
        
        /**
         * Handle request error
         */
        onRequestError(options, xhr, status, error) {
            const { action, requestId, retryCount, maxRetries = this.config.maxRetries } = options;
            
            // Record failure for circuit breaker
            this.recordCircuitFailure(action, `${status}: ${error}`);
            
            // Check if we should retry
            if (retryCount < maxRetries && this.shouldRetry(xhr, status)) {
                this.scheduleRetry(options);
                return;
            }
            
            // Record performance metrics
            this.recordPerformanceMetrics(options, false);
            
            if (this.config.debug) {
                console.error(`[AJAX] Request failed: ${action} [${requestId}]`, {
                    status,
                    error,
                    retryCount
                });
            }
            
            // Hide loading indicator
            this.hideRequestLoading(options);
            
            // Handle final failure
            this.handleFinalFailure(options, xhr, status, error);
        }
        
        /**
         * Handle request completion
         */
        onRequestComplete(options, xhr, status) {
            // Remove from active requests
            this.activeRequests.delete(options.requestId);
            
            // Process queue
            this.processRequestQueue();
            
            // Call original complete if provided
            if (options.complete && typeof options.complete === 'function') {
                options.complete(xhr, status);
            }
        }
        
        /**
         * Check if circuit breaker is open
         */
        isCircuitOpen(action) {
            const circuit = this.circuitBreakers.get(action);
            if (!circuit) return false;
            
            const timeSinceFailure = Date.now() - circuit.lastFailure;
            
            if (circuit.failures >= this.config.failureThreshold) {
                if (timeSinceFailure >= this.config.recoveryTimeout) {
                    // Reset circuit breaker
                    this.circuitBreakers.delete(action);
                    return false;
                }
                return true;
            }
            
            return false;
        }
        
        /**
         * Record circuit breaker success
         */
        recordCircuitSuccess(action) {
            // Reset circuit breaker on success
            this.circuitBreakers.delete(action);
        }
        
        /**
         * Record circuit breaker failure
         */
        recordCircuitFailure(action, error) {
            const circuit = this.circuitBreakers.get(action) || {
                failures: 0,
                firstFailure: Date.now(),
                lastFailure: 0
            };
            
            circuit.failures++;
            circuit.lastFailure = Date.now();
            
            this.circuitBreakers.set(action, circuit);
            
            // Log circuit breaker opening
            if (circuit.failures >= this.config.failureThreshold) {
                console.warn(`[AJAX] Circuit breaker opened for ${action} after ${circuit.failures} failures`);
                this.showCircuitBreakerNotification(action);
            }
        }
        
        /**
         * Get circuit recovery time
         */
        getCircuitRecoveryTime(action) {
            const circuit = this.circuitBreakers.get(action);
            if (!circuit) return 0;
            
            const timeSinceFailure = Date.now() - circuit.lastFailure;
            return Math.max(0, this.config.recoveryTimeout - timeSinceFailure);
        }
        
        /**
         * Check if request should be retried
         */
        shouldRetry(xhr, status) {
            // Don't retry client errors (4xx) except for specific cases
            if (xhr.status >= 400 && xhr.status < 500) {
                return xhr.status === 408 || xhr.status === 429; // Timeout or Rate Limited
            }
            
            // Retry server errors (5xx) and network errors
            return xhr.status >= 500 || status === 'timeout' || status === 'error';
        }
        
        /**
         * Schedule retry with exponential backoff
         */
        scheduleRetry(options) {
            const retryCount = options.retryCount + 1;
            const delay = Math.min(
                this.config.baseDelay * Math.pow(this.config.backoffMultiplier, retryCount - 1),
                this.config.maxDelay
            );
            
            if (this.config.debug) {
                console.log(`[AJAX] Scheduling retry ${retryCount} for ${options.action} in ${delay}ms`);
            }
            
            setTimeout(() => {
                this.executeRequest({
                    ...options,
                    retryCount
                });
            }, delay);
        }
        
        /**
         * Queue request when concurrent limit is reached
         */
        queueRequest(options) {
            this.requestQueue.push(options);
            
            if (this.config.debug) {
                console.log(`[AJAX] Request queued: ${options.action} [${options.requestId}] (queue size: ${this.requestQueue.length})`);
            }
        }
        
        /**
         * Process request queue
         */
        processRequestQueue() {
            if (this.requestQueue.length === 0 || this.activeRequests.size >= this.config.maxConcurrentRequests) {
                return;
            }
            
            const options = this.requestQueue.shift();
            this.executeRequest(options);
        }
        
        /**
         * Handle circuit breaker error
         */
        handleCircuitBreakerError(options, recoveryTime) {
            const error = {
                message: 'Service temporarily unavailable. Please try again in a few moments.',
                code: 'circuit_open',
                recovery_time: Math.ceil(recoveryTime / 1000)
            };
            
            this.showCircuitBreakerError(options.action, recoveryTime);
            options.reject(error);
        }
        
        /**
         * Handle final failure after all retries
         */
        handleFinalFailure(options, xhr, status, error) {
            const errorData = {
                message: this.getErrorMessage(xhr, status, error),
                code: xhr.status || 'unknown',
                action: options.action,
                can_retry: true
            };
            
            this.showRetryableError(options, errorData);
            options.reject(errorData);
        }
        
        /**
         * Get user-friendly error message
         */
        getErrorMessage(xhr, status, error) {
            if (status === 'timeout') {
                return 'Request timed out. Please check your connection and try again.';
            }
            
            if (status === 'abort') {
                return 'Request was cancelled.';
            }
            
            if (xhr.status === 0) {
                return 'Network error. Please check your internet connection.';
            }
            
            if (xhr.status >= 500) {
                return 'Server error. Please try again in a few moments.';
            }
            
            return 'Request failed. Please try again.';
        }
        
        /**
         * Record performance metrics
         */
        recordPerformanceMetrics(options, success) {
            const { action, startTime } = options;
            const duration = Date.now() - startTime;
            
            const metrics = this.performanceMetrics.get(action) || {
                total: 0,
                success: 0,
                failures: 0,
                totalTime: 0,
                avgTime: 0,
                maxTime: 0
            };
            
            metrics.total++;
            if (success) {
                metrics.success++;
            } else {
                metrics.failures++;
            }
            
            metrics.totalTime += duration;
            metrics.avgTime = metrics.totalTime / metrics.total;
            metrics.maxTime = Math.max(metrics.maxTime, duration);
            
            this.performanceMetrics.set(action, metrics);
        }
        
        /**
         * Show loading indicators
         */
        showGlobalLoading() {
            if (!$('.hph-global-loading').length) {
                $('body').append('<div class="hph-global-loading"><div class="spinner"></div></div>');
            }
        }
        
        hideGlobalLoading() {
            $('.hph-global-loading').remove();
        }
        
        showRequestLoading(options) {
            // Show loading state for specific UI elements
            if (options.loadingTarget) {
                $(options.loadingTarget).addClass('loading');
            }
        }
        
        hideRequestLoading(options) {
            if (options.loadingTarget) {
                $(options.loadingTarget).removeClass('loading');
            }
        }
        
        /**
         * Show notifications
         */
        showCircuitBreakerNotification(action) {
            this.showNotification({
                type: 'warning',
                title: 'Service Temporarily Unavailable',
                message: `The ${action} service is experiencing issues and has been temporarily disabled. It will automatically recover in a few moments.`,
                timeout: 10000
            });
        }
        
        showCircuitBreakerError(action, recoveryTime) {
            this.showNotification({
                type: 'error',
                title: 'Service Unavailable',
                message: `The ${action} service is temporarily unavailable. Please try again in ${Math.ceil(recoveryTime / 1000)} seconds.`,
                timeout: 5000
            });
        }
        
        showRetryableError(options, errorData) {
            this.showNotification({
                type: 'error',
                title: 'Request Failed',
                message: errorData.message,
                actions: [
                    {
                        label: 'Retry',
                        action: () => this.retryRequest(options)
                    }
                ],
                timeout: 10000
            });
        }
        
        showNotification(notification) {
            // Use existing notification system or create simple fallback
            if (window.dashboardNotifications) {
                window.dashboardNotifications.show(notification);
            } else {
                console.log(`[NOTIFICATION] ${notification.title}: ${notification.message}`);
                // Fallback to browser alert for critical errors
                if (notification.type === 'error') {
                    alert(`${notification.title}\n${notification.message}`);
                }
            }
        }
        
        /**
         * Retry failed request
         */
        retryRequest(options) {
            this.executeRequest({
                ...options,
                retryCount: 0,
                requestId: ++this.requestId
            });
        }
        
        /**
         * Perform health check
         */
        async performHealthCheck() {
            try {
                const response = await this.request({
                    url: window.hphAjax?.ajaxUrl || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: {
                        action: 'hph_get_dashboard_health',
                        nonce: window.hphAjax?.nonce
                    }
                });
                
                this.displayHealthStatus(response);
            } catch (error) {
                console.error('Health check failed:', error);
                this.showNotification({
                    type: 'error',
                    title: 'Health Check Failed',
                    message: 'Unable to retrieve dashboard health status.'
                });
            }
        }
        
        /**
         * Reset circuit breakers
         */
        async resetCircuitBreakers() {
            try {
                await this.request({
                    url: window.hphAjax?.ajaxUrl || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: {
                        action: 'hph_reset_circuit_breakers',
                        nonce: window.hphAjax?.nonce
                    }
                });
                
                // Clear local circuit breakers
                this.circuitBreakers.clear();
                
                this.showNotification({
                    type: 'success',
                    title: 'Circuit Breakers Reset',
                    message: 'All circuit breakers have been reset successfully.'
                });
            } catch (error) {
                console.error('Failed to reset circuit breakers:', error);
            }
        }
        
        /**
         * Display health status
         */
        displayHealthStatus(healthData) {
            console.log('Dashboard Health Status:', healthData);
            
            const status = healthData.overall_status || 'unknown';
            const message = status === 'healthy' 
                ? 'Dashboard is operating normally.'
                : 'Dashboard has detected some issues.';
            
            this.showNotification({
                type: status === 'healthy' ? 'success' : 'warning',
                title: 'Dashboard Health Check',
                message: message,
                timeout: 5000
            });
        }
        
        /**
         * Handle global AJAX errors
         */
        handleGlobalAjaxError(xhr, settings, error) {
            if (this.config.debug) {
                console.error('[AJAX] Global error handler:', {
                    url: settings.url,
                    error,
                    status: xhr.status
                });
            }
            
            // Extract action from settings
            const action = this.extractActionFromSettings(settings);
            if (action) {
                this.recordCircuitFailure(action, `${xhr.status}: ${error}`);
            }
        }
        
        /**
         * Extract action from AJAX settings
         */
        extractActionFromSettings(settings) {
            if (settings.data) {
                if (typeof settings.data === 'string') {
                    const match = settings.data.match(/action=([^&]+)/);
                    return match ? match[1] : null;
                } else if (typeof settings.data === 'object') {
                    return settings.data.action || null;
                }
            }
            return null;
        }
        
        /**
         * Get performance metrics
         */
        getPerformanceMetrics() {
            return Object.fromEntries(this.performanceMetrics);
        }
        
        /**
         * Get circuit breaker status
         */
        getCircuitBreakerStatus() {
            const status = {};
            for (const [action, circuit] of this.circuitBreakers) {
                status[action] = {
                    failures: circuit.failures,
                    isOpen: this.isCircuitOpen(action),
                    recoveryTime: this.getCircuitRecoveryTime(action)
                };
            }
            return status;
        }
    }
    
    /**
     * Initialize Enhanced AJAX Handler
     */
    $(document).ready(function() {
        // Create global instance
        window.hphEnhancedAjax = new HPHEnhancedAjax();
        
        // Provide jQuery integration
        $.hphAjax = function(options) {
            return window.hphEnhancedAjax.request(options);
        };
        
        console.log('HPH Enhanced AJAX Handler ready');
    });
    
})(jQuery);
