/**
 * Analytics Tracker
 * 
 * Handles tracking of user interactions and performance metrics
 */

export class AnalyticsTracker {
    constructor() {
        this.enabled = window.hphData?.analytics?.enabled || false;
        this.debug = window.hphData?.debug || false;
        this.queue = [];
        this.sessionId = this.generateSessionId();
        
        this.init();
    }
    
    init() {
        // Process any queued events
        this.processQueue();
        
        // Set up beforeunload to send remaining events
        window.addEventListener('beforeunload', () => {
            this.flush();
        });
    }
    
    track(event, data = {}) {
        const eventData = {
            event: event,
            timestamp: Date.now(),
            session_id: this.sessionId,
            url: window.location.href,
            user_agent: navigator.userAgent,
            ...data
        };
        
        if (this.debug) {
            console.log('Analytics Event:', eventData);
        }
        
        if (this.enabled) {
            this.queue.push(eventData);
            
            // Send immediately for critical events
            if (this.isCriticalEvent(event)) {
                this.flush();
            }
        }
    }
    
    isCriticalEvent(event) {
        const criticalEvents = [
            'card_clicked',
            'favorite_added',
            'contact_submitted',
            'tour_scheduled'
        ];
        
        return criticalEvents.includes(event);
    }
    
    flush() {
        if (this.queue.length === 0) return;
        
        const events = [...this.queue];
        this.queue = [];
        
        // Send to analytics endpoint
        this.sendEvents(events);
    }
    
    async sendEvents(events) {
        try {
            const response = await fetch('/wp-json/hph/v1/analytics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.hphData?.nonce || ''
                },
                body: JSON.stringify({ events })
            });
            
            if (!response.ok) {
                throw new Error('Failed to send analytics data');
            }
        } catch (error) {
            if (this.debug) {
                console.error('Analytics error:', error);
            }
            
            // Re-queue events on failure (with limit)
            if (this.queue.length < 100) {
                this.queue.unshift(...events);
            }
        }
    }
    
    processQueue() {
        // Send queued events periodically
        setInterval(() => {
            if (this.queue.length > 0) {
                this.flush();
            }
        }, 10000); // Every 10 seconds
    }
    
    generateSessionId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    // Performance tracking
    trackPerformance(metric, value, data = {}) {
        this.track('performance_metric', {
            metric: metric,
            value: value,
            ...data
        });
    }
    
    // Error tracking
    trackError(error, context = {}) {
        this.track('javascript_error', {
            message: error.message,
            stack: error.stack,
            filename: error.filename,
            lineno: error.lineno,
            colno: error.colno,
            ...context
        });
    }
}
