/**
 * Real-Time Notification System
 * 
 * Advanced notification system with WebSocket support, push notifications,
 * and comprehensive notification management.
 * 
 * @since 3.0.0
 */

class NotificationSystem {
    constructor(config = {}) {
        this.config = {
            websocketUrl: config.websocketUrl || '',
            pushNotifications: config.pushNotifications !== false,
            soundEnabled: config.soundEnabled !== false,
            desktopNotifications: config.desktopNotifications !== false,
            maxNotifications: config.maxNotifications || 100,
            autoHideDelay: config.autoHideDelay || 5000,
            ...config
        };
        
        // Notification storage
        this.notifications = [];
        this.unreadCount = 0;
        this.notificationId = 0;
        
        // WebSocket connection
        this.websocket = null;
        this.websocketReconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        
        // Push notification
        this.serviceWorkerRegistration = null;
        this.vapidPublicKey = config.vapidPublicKey || '';
        
        // DOM elements
        this.container = null;
        this.badge = null;
        this.panel = null;
        
        // Event handlers
        this.handlers = new Map();
        
        // Notification types and their configurations
        this.types = {
            'listing.created': {
                title: 'New Listing Added',
                icon: '🏠',
                color: '#10b981',
                sound: 'notification.mp3',
                priority: 'high'
            },
            'listing.updated': {
                title: 'Listing Updated',
                icon: '📝',
                color: '#3b82f6',
                sound: 'notification.mp3',
                priority: 'medium'
            },
            'listing.sold': {
                title: 'Listing Sold',
                icon: '✅',
                color: '#f59e0b',
                sound: 'success.mp3',
                priority: 'high'
            },
            'lead.received': {
                title: 'New Lead Received',
                icon: '👤',
                color: '#8b5cf6',
                sound: 'alert.mp3',
                priority: 'high'
            },
            'lead.updated': {
                title: 'Lead Updated',
                icon: '🔄',
                color: '#06b6d4',
                sound: 'notification.mp3',
                priority: 'medium'
            },
            'sync.completed': {
                title: 'Sync Completed',
                icon: '🔄',
                color: '#10b981',
                sound: 'notification.mp3',
                priority: 'low'
            },
            'sync.error': {
                title: 'Sync Error',
                icon: '⚠️',
                color: '#ef4444',
                sound: 'error.mp3',
                priority: 'high'
            },
            'system.alert': {
                title: 'System Alert',
                icon: '🔔',
                color: '#f59e0b',
                sound: 'alert.mp3',
                priority: 'high'
            },
            'reminder': {
                title: 'Reminder',
                icon: '⏰',
                color: '#6b7280',
                sound: 'notification.mp3',
                priority: 'medium'
            }
        };
        
        // Filters and preferences
        this.filters = {
            types: [],
            priority: 'all',
            unreadOnly: false
        };
        
        this.preferences = {
            desktopNotifications: true,
            soundNotifications: true,
            pushNotifications: true,
            notificationFrequency: 'immediate'
        };
    }
    
    /**
     * Initialize notification system
     */
    async init() {
        // Load preferences
        this.loadPreferences();
        
        // Create UI elements
        this.createUI();
        
        // Setup WebSocket connection
        if (this.config.websocketUrl) {
            this.setupWebSocket();
        }
        
        // Setup push notifications
        if (this.config.pushNotifications) {
            await this.setupPushNotifications();
        }
        
        // Request desktop notification permission
        if (this.config.desktopNotifications) {
            this.requestDesktopPermission();
        }
        
        // Load existing notifications
        this.loadStoredNotifications();
        
        // Setup periodic cleanup
        this.setupCleanup();
        
        // Listen for page visibility changes
        this.setupVisibilityHandler();
        
        console.log('Notification system initialized');
    }
    
    // ===================
    // UI CREATION
    // ===================
    
    /**
     * Create notification UI elements
     */
    createUI() {
        // Create notification container
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.innerHTML = `
            <div class="notifications-toast-area"></div>
            <div class="notifications-panel" style="display: none;">
                <div class="notifications-header">
                    <h3>Notifications</h3>
                    <div class="notifications-controls">
                        <button class="mark-all-read">Mark All Read</button>
                        <button class="clear-all">Clear All</button>
                        <button class="close-panel">×</button>
                    </div>
                </div>
                <div class="notifications-filters">
                    <select class="filter-type">
                        <option value="">All Types</option>
                        ${Object.keys(this.types).map(type => 
                            `<option value="${type}">${this.types[type].title}</option>`
                        ).join('')}
                    </select>
                    <select class="filter-priority">
                        <option value="all">All Priorities</option>
                        <option value="high">High Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="low">Low Priority</option>
                    </select>
                    <label>
                        <input type="checkbox" class="filter-unread"> Unread Only
                    </label>
                </div>
                <div class="notifications-list"></div>
                <div class="notifications-empty" style="display: none;">
                    <p>No notifications to display</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.container);
        
        // Create notification badge/trigger
        this.createNotificationBadge();
        
        // Setup event handlers
        this.setupUIEventHandlers();
        
        // Add CSS
        this.addStyles();
    }
    
    /**
     * Create notification badge
     */
    createNotificationBadge() {
        // Look for existing notification trigger in dashboard
        let trigger = document.querySelector('.notification-trigger');
        
        if (!trigger) {
            // Create a floating notification button
            trigger = document.createElement('button');
            trigger.className = 'notification-trigger floating-notification-button';
            trigger.innerHTML = `
                <i class="fas fa-bell"></i>
                <span class="notification-badge">0</span>
            `;
            document.body.appendChild(trigger);
        }
        
        this.badge = trigger.querySelector('.notification-badge');
        
        // Setup click handler
        trigger.addEventListener('click', () => {
            this.togglePanel();
        });
    }
    
    /**
     * Setup UI event handlers
     */
    setupUIEventHandlers() {
        const panel = this.container.querySelector('.notifications-panel');
        
        // Panel controls
        panel.querySelector('.mark-all-read').addEventListener('click', () => {
            this.markAllAsRead();
        });
        
        panel.querySelector('.clear-all').addEventListener('click', () => {
            this.clearAll();
        });
        
        panel.querySelector('.close-panel').addEventListener('click', () => {
            this.hidePanel();
        });
        
        // Filters
        panel.querySelector('.filter-type').addEventListener('change', (e) => {
            this.setFilter('types', e.target.value ? [e.target.value] : []);
        });
        
        panel.querySelector('.filter-priority').addEventListener('change', (e) => {
            this.setFilter('priority', e.target.value);
        });
        
        panel.querySelector('.filter-unread').addEventListener('change', (e) => {
            this.setFilter('unreadOnly', e.target.checked);
        });
        
        // Close panel when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target) && 
                !e.target.closest('.notification-trigger')) {
                this.hidePanel();
            }
        });
    }
    
    /**
     * Add notification styles
     */
    addStyles() {
        const styles = `
            <style id="notification-system-styles">
                .notification-container {
                    position: fixed;
                    top: 0;
                    right: 0;
                    z-index: 10000;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                
                .notifications-toast-area {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10001;
                    pointer-events: none;
                }
                
                .notification-toast {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    padding: 16px;
                    margin-bottom: 12px;
                    max-width: 400px;
                    min-width: 300px;
                    pointer-events: auto;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    border-left: 4px solid;
                }
                
                .notification-toast.show {
                    transform: translateX(0);
                }
                
                .notification-toast.removing {
                    transform: translateX(100%);
                    opacity: 0;
                }
                
                .notification-header {
                    display: flex;
                    align-items: center;
                    margin-bottom: 8px;
                }
                
                .notification-icon {
                    font-size: 20px;
                    margin-right: 12px;
                }
                
                .notification-title {
                    font-weight: 600;
                    margin: 0;
                    flex: 1;
                }
                
                .notification-time {
                    font-size: 12px;
                    color: #6b7280;
                }
                
                .notification-message {
                    color: #374151;
                    margin: 0;
                    line-height: 1.4;
                }
                
                .notification-actions {
                    margin-top: 12px;
                    display: flex;
                    gap: 8px;
                }
                
                .notification-action {
                    padding: 4px 12px;
                    border: 1px solid #d1d5db;
                    background: white;
                    border-radius: 4px;
                    font-size: 12px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                
                .notification-action:hover {
                    background: #f9fafb;
                }
                
                .notification-action.primary {
                    background: #3b82f6;
                    color: white;
                    border-color: #3b82f6;
                }
                
                .notification-action.primary:hover {
                    background: #2563eb;
                }
                
                .notification-close {
                    position: absolute;
                    top: 8px;
                    right: 8px;
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #6b7280;
                    padding: 4px;
                    line-height: 1;
                }
                
                .notification-close:hover {
                    color: #374151;
                }
                
                .notifications-panel {
                    position: fixed;
                    top: 60px;
                    right: 20px;
                    width: 400px;
                    max-height: 600px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }
                
                .notifications-header {
                    padding: 16px;
                    border-bottom: 1px solid #e5e7eb;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .notifications-header h3 {
                    margin: 0;
                    font-size: 16px;
                    font-weight: 600;
                }
                
                .notifications-controls {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                }
                
                .notifications-controls button {
                    padding: 4px 8px;
                    border: 1px solid #d1d5db;
                    background: white;
                    border-radius: 4px;
                    font-size: 12px;
                    cursor: pointer;
                }
                
                .notifications-controls .close-panel {
                    border: none;
                    font-size: 18px;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                }
                
                .notifications-filters {
                    padding: 12px 16px;
                    border-bottom: 1px solid #e5e7eb;
                    display: flex;
                    gap: 12px;
                    align-items: center;
                    font-size: 12px;
                }
                
                .notifications-filters select {
                    padding: 4px 8px;
                    border: 1px solid #d1d5db;
                    border-radius: 4px;
                    font-size: 12px;
                }
                
                .notifications-list {
                    flex: 1;
                    overflow-y: auto;
                    max-height: 400px;
                }
                
                .notification-item {
                    padding: 12px 16px;
                    border-bottom: 1px solid #f3f4f6;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                
                .notification-item:hover {
                    background: #f9fafb;
                }
                
                .notification-item.unread {
                    background: #eff6ff;
                    border-left: 3px solid #3b82f6;
                }
                
                .notification-item .notification-header {
                    padding: 0;
                    border: none;
                    margin-bottom: 4px;
                }
                
                .notification-item .notification-title {
                    font-size: 14px;
                }
                
                .notification-item .notification-message {
                    font-size: 12px;
                    margin-bottom: 4px;
                }
                
                .notification-item .notification-time {
                    font-size: 11px;
                }
                
                .notifications-empty {
                    padding: 40px 16px;
                    text-align: center;
                    color: #6b7280;
                }
                
                .floating-notification-button {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    background: #3b82f6;
                    color: white;
                    border: none;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 20px;
                    transition: transform 0.2s, box-shadow 0.2s;
                    z-index: 9999;
                }
                
                .floating-notification-button:hover {
                    transform: scale(1.05);
                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
                }
                
                .notification-badge {
                    position: absolute;
                    top: -8px;
                    right: -8px;
                    background: #ef4444;
                    color: white;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    font-size: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 600;
                }
                
                .notification-badge.hidden {
                    display: none;
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    // ===================
    // WEBSOCKET CONNECTION
    // ===================
    
    /**
     * Setup WebSocket connection
     */
    setupWebSocket() {
        if (!this.config.websocketUrl) return;
        
        try {
            this.websocket = new WebSocket(this.config.websocketUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connected');
                this.websocketReconnectAttempts = 0;
                this.reconnectDelay = 1000;
            };
            
            this.websocket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleWebSocketMessage(data);
                } catch (error) {
                    console.error('Failed to parse WebSocket message:', error);
                }
            };
            
            this.websocket.onclose = () => {
                console.log('WebSocket disconnected');
                this.reconnectWebSocket();
            };
            
            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
            
        } catch (error) {
            console.error('Failed to setup WebSocket:', error);
        }
    }
    
    /**
     * Handle WebSocket message
     */
    handleWebSocketMessage(data) {
        if (data.type === 'notification') {
            this.show(data.notification);
        } else if (data.type === 'broadcast') {
            this.show({
                type: 'system.alert',
                title: data.title || 'System Broadcast',
                message: data.message,
                priority: 'high'
            });
        }
    }
    
    /**
     * Reconnect WebSocket
     */
    reconnectWebSocket() {
        if (this.websocketReconnectAttempts >= this.maxReconnectAttempts) {
            console.log('Max WebSocket reconnection attempts reached');
            return;
        }
        
        this.websocketReconnectAttempts++;
        
        setTimeout(() => {
            console.log(`Attempting WebSocket reconnection (${this.websocketReconnectAttempts}/${this.maxReconnectAttempts})`);
            this.setupWebSocket();
        }, this.reconnectDelay);
        
        // Exponential backoff
        this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000);
    }
    
    // ===================
    // PUSH NOTIFICATIONS
    // ===================
    
    /**
     * Setup push notifications
     */
    async setupPushNotifications() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.log('Push notifications not supported');
            return;
        }
        
        try {
            // Register service worker
            this.serviceWorkerRegistration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service worker registered');
            
            // Check if user is subscribed
            const subscription = await this.serviceWorkerRegistration.pushManager.getSubscription();
            
            if (!subscription && this.preferences.pushNotifications) {
                await this.subscribeToPushNotifications();
            }
            
        } catch (error) {
            console.error('Failed to setup push notifications:', error);
        }
    }
    
    /**
     * Subscribe to push notifications
     */
    async subscribeToPushNotifications() {
        try {
            const permission = await Notification.requestPermission();
            
            if (permission !== 'granted') {
                console.log('Push notification permission denied');
                return;
            }
            
            const subscription = await this.serviceWorkerRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });
            
            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
            console.log('Subscribed to push notifications');
            
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
        }
    }
    
    /**
     * Send subscription to server
     */
    async sendSubscriptionToServer(subscription) {
        try {
            const ajax = new DashboardAjax();
            await ajax.request('save_push_subscription', {
                subscription: JSON.stringify(subscription)
            });
        } catch (error) {
            console.error('Failed to send subscription to server:', error);
        }
    }
    
    /**
     * Convert VAPID key
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }
    
    // ===================
    // NOTIFICATION MANAGEMENT
    // ===================
    
    /**
     * Show notification
     */
    show(notification) {
        // Generate unique ID
        const id = ++this.notificationId;
        
        // Prepare notification object
        const notificationObj = {
            id,
            timestamp: new Date(),
            read: false,
            ...notification
        };
        
        // Get type configuration
        const typeConfig = this.types[notification.type] || this.types['system.alert'];
        notificationObj.typeConfig = typeConfig;
        
        // Add to storage
        this.notifications.unshift(notificationObj);
        this.unreadCount++;
        
        // Trim to max notifications
        if (this.notifications.length > this.config.maxNotifications) {
            this.notifications = this.notifications.slice(0, this.config.maxNotifications);
        }
        
        // Update UI
        this.updateBadge();
        this.updatePanel();
        
        // Show toast
        this.showToast(notificationObj);
        
        // Play sound
        if (this.config.soundEnabled && this.preferences.soundNotifications) {
            this.playSound(typeConfig.sound);
        }
        
        // Show desktop notification
        if (this.config.desktopNotifications && this.preferences.desktopNotifications) {
            this.showDesktopNotification(notificationObj);
        }
        
        // Store in localStorage
        this.saveNotifications();
        
        // Emit event
        this.emit('notification:shown', notificationObj);
        
        return notificationObj;
    }
    
    /**
     * Show toast notification
     */
    showToast(notification) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.style.borderLeftColor = notification.typeConfig.color;
        
        toast.innerHTML = `
            <button class="notification-close">&times;</button>
            <div class="notification-header">
                <div class="notification-icon">${notification.typeConfig.icon}</div>
                <h4 class="notification-title">${notification.title || notification.typeConfig.title}</h4>
                <span class="notification-time">${this.formatTime(notification.timestamp)}</span>
            </div>
            <p class="notification-message">${notification.message}</p>
            ${notification.actions ? this.renderActions(notification.actions) : ''}
        `;
        
        // Add to toast area
        const toastArea = this.container.querySelector('.notifications-toast-area');
        toastArea.appendChild(toast);
        
        // Show animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Setup close handler
        toast.querySelector('.notification-close').addEventListener('click', () => {
            this.removeToast(toast);
        });
        
        // Setup action handlers
        notification.actions?.forEach((action, index) => {
            const button = toast.querySelector(`.notification-action[data-index="${index}"]`);
            if (button) {
                button.addEventListener('click', () => {
                    if (typeof action.handler === 'function') {
                        action.handler(notification);
                    }
                    this.removeToast(toast);
                });
            }
        });
        
        // Auto-hide
        if (this.config.autoHideDelay > 0) {
            setTimeout(() => {
                this.removeToast(toast);
            }, this.config.autoHideDelay);
        }
    }
    
    /**
     * Remove toast notification
     */
    removeToast(toast) {
        toast.classList.add('removing');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
    
    /**
     * Show desktop notification
     */
    showDesktopNotification(notification) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        
        const options = {
            body: notification.message,
            icon: '/assets/images/notification-icon.png',
            tag: `notification-${notification.id}`,
            requireInteraction: notification.typeConfig.priority === 'high'
        };
        
        const desktopNotification = new Notification(
            notification.title || notification.typeConfig.title,
            options
        );
        
        desktopNotification.onclick = () => {
            window.focus();
            this.markAsRead(notification.id);
            this.showPanel();
            desktopNotification.close();
        };
    }
    
    /**
     * Play notification sound
     */
    playSound(soundFile) {
        if (!soundFile) return;
        
        try {
            const audio = new Audio(`/assets/sounds/${soundFile}`);
            audio.volume = 0.5;
            audio.play().catch(error => {
                console.log('Could not play notification sound:', error);
            });
        } catch (error) {
            console.log('Could not play notification sound:', error);
        }
    }
    
    /**
     * Mark notification as read
     */
    markAsRead(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification && !notification.read) {
            notification.read = true;
            this.unreadCount--;
            this.updateBadge();
            this.updatePanel();
            this.saveNotifications();
            this.emit('notification:read', notification);
        }
    }
    
    /**
     * Mark all notifications as read
     */
    markAllAsRead() {
        this.notifications.forEach(notification => {
            if (!notification.read) {
                notification.read = true;
            }
        });
        
        this.unreadCount = 0;
        this.updateBadge();
        this.updatePanel();
        this.saveNotifications();
        this.emit('notifications:all_read');
    }
    
    /**
     * Clear all notifications
     */
    clearAll() {
        this.notifications = [];
        this.unreadCount = 0;
        this.updateBadge();
        this.updatePanel();
        this.saveNotifications();
        this.emit('notifications:cleared');
    }
    
    /**
     * Remove notification
     */
    remove(id) {
        const index = this.notifications.findIndex(n => n.id === id);
        if (index !== -1) {
            const notification = this.notifications[index];
            if (!notification.read) {
                this.unreadCount--;
            }
            this.notifications.splice(index, 1);
            this.updateBadge();
            this.updatePanel();
            this.saveNotifications();
            this.emit('notification:removed', notification);
        }
    }
    
    // ===================
    // UI UPDATES
    // ===================
    
    /**
     * Update notification badge
     */
    updateBadge() {
        if (this.badge) {
            this.badge.textContent = this.unreadCount;
            this.badge.classList.toggle('hidden', this.unreadCount === 0);
        }
    }
    
    /**
     * Update notification panel
     */
    updatePanel() {
        const list = this.container.querySelector('.notifications-list');
        const empty = this.container.querySelector('.notifications-empty');
        
        // Apply filters
        const filteredNotifications = this.getFilteredNotifications();
        
        if (filteredNotifications.length === 0) {
            list.style.display = 'none';
            empty.style.display = 'block';
        } else {
            list.style.display = 'block';
            empty.style.display = 'none';
            
            list.innerHTML = filteredNotifications.map(notification => 
                this.renderNotificationItem(notification)
            ).join('');
            
            // Setup event handlers
            list.querySelectorAll('.notification-item').forEach(item => {
                const id = parseInt(item.dataset.id);
                
                item.addEventListener('click', () => {
                    this.markAsRead(id);
                    
                    // Execute click handler if available
                    const notification = this.notifications.find(n => n.id === id);
                    if (notification?.onClick) {
                        notification.onClick(notification);
                    }
                });
            });
        }
    }
    
    /**
     * Render notification item
     */
    renderNotificationItem(notification) {
        return `
            <div class="notification-item ${notification.read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-header">
                    <div class="notification-icon">${notification.typeConfig.icon}</div>
                    <h4 class="notification-title">${notification.title || notification.typeConfig.title}</h4>
                    <span class="notification-time">${this.formatTime(notification.timestamp)}</span>
                </div>
                <p class="notification-message">${notification.message}</p>
                <div class="notification-time">${this.formatRelativeTime(notification.timestamp)}</div>
            </div>
        `;
    }
    
    /**
     * Render notification actions
     */
    renderActions(actions) {
        return `
            <div class="notification-actions">
                ${actions.map((action, index) => `
                    <button class="notification-action ${action.primary ? 'primary' : ''}" data-index="${index}">
                        ${action.label}
                    </button>
                `).join('')}
            </div>
        `;
    }
    
    /**
     * Show notification panel
     */
    showPanel() {
        this.container.querySelector('.notifications-panel').style.display = 'flex';
        this.updatePanel();
    }
    
    /**
     * Hide notification panel
     */
    hidePanel() {
        this.container.querySelector('.notifications-panel').style.display = 'none';
    }
    
    /**
     * Toggle notification panel
     */
    togglePanel() {
        const panel = this.container.querySelector('.notifications-panel');
        const isVisible = panel.style.display === 'flex';
        
        if (isVisible) {
            this.hidePanel();
        } else {
            this.showPanel();
        }
    }
    
    // ===================
    // FILTERING
    // ===================
    
    /**
     * Set filter
     */
    setFilter(key, value) {
        this.filters[key] = value;
        this.updatePanel();
    }
    
    /**
     * Get filtered notifications
     */
    getFilteredNotifications() {
        return this.notifications.filter(notification => {
            // Type filter
            if (this.filters.types.length > 0 && !this.filters.types.includes(notification.type)) {
                return false;
            }
            
            // Priority filter
            if (this.filters.priority !== 'all' && notification.typeConfig.priority !== this.filters.priority) {
                return false;
            }
            
            // Unread filter
            if (this.filters.unreadOnly && notification.read) {
                return false;
            }
            
            return true;
        });
    }
    
    // ===================
    // UTILITIES
    // ===================
    
    /**
     * Format time
     */
    formatTime(date) {
        return new Date(date).toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    /**
     * Format relative time
     */
    formatRelativeTime(date) {
        const now = new Date();
        const diff = now - new Date(date);
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        return `${days}d ago`;
    }
    
    /**
     * Event emitter
     */
    emit(event, data) {
        const handlers = this.handlers.get(event) || [];
        handlers.forEach(handler => {
            try {
                handler(data);
            } catch (error) {
                console.error(`Error in notification event handler (${event}):`, error);
            }
        });
    }
    
    /**
     * Event listener
     */
    on(event, handler) {
        if (!this.handlers.has(event)) {
            this.handlers.set(event, []);
        }
        this.handlers.get(event).push(handler);
    }
    
    /**
     * Remove event listener
     */
    off(event, handler) {
        const handlers = this.handlers.get(event) || [];
        const index = handlers.indexOf(handler);
        if (index !== -1) {
            handlers.splice(index, 1);
        }
    }
    
    // ===================
    // PERSISTENCE
    // ===================
    
    /**
     * Load stored notifications
     */
    loadStoredNotifications() {
        try {
            const stored = localStorage.getItem('dashboard_notifications');
            if (stored) {
                const data = JSON.parse(stored);
                this.notifications = data.notifications || [];
                this.unreadCount = data.unreadCount || 0;
                this.notificationId = data.lastId || 0;
                
                // Update type configs
                this.notifications.forEach(notification => {
                    notification.typeConfig = this.types[notification.type] || this.types['system.alert'];
                });
                
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to load stored notifications:', error);
        }
    }
    
    /**
     * Save notifications to storage
     */
    saveNotifications() {
        try {
            const data = {
                notifications: this.notifications,
                unreadCount: this.unreadCount,
                lastId: this.notificationId
            };
            
            localStorage.setItem('dashboard_notifications', JSON.stringify(data));
        } catch (error) {
            console.error('Failed to save notifications:', error);
        }
    }
    
    /**
     * Load user preferences
     */
    loadPreferences() {
        try {
            const stored = localStorage.getItem('notification_preferences');
            if (stored) {
                this.preferences = { ...this.preferences, ...JSON.parse(stored) };
            }
        } catch (error) {
            console.error('Failed to load notification preferences:', error);
        }
    }
    
    /**
     * Save user preferences
     */
    savePreferences() {
        try {
            localStorage.setItem('notification_preferences', JSON.stringify(this.preferences));
        } catch (error) {
            console.error('Failed to save notification preferences:', error);
        }
    }
    
    /**
     * Update preferences
     */
    updatePreferences(newPreferences) {
        this.preferences = { ...this.preferences, ...newPreferences };
        this.savePreferences();
        this.emit('preferences:updated', this.preferences);
    }
    
    // ===================
    // CLEANUP & UTILITIES
    // ===================
    
    /**
     * Setup periodic cleanup
     */
    setupCleanup() {
        // Clean up old notifications every hour
        setInterval(() => {
            const oneWeekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
            
            const initialLength = this.notifications.length;
            this.notifications = this.notifications.filter(notification => 
                new Date(notification.timestamp) > oneWeekAgo
            );
            
            if (this.notifications.length !== initialLength) {
                this.saveNotifications();
                console.log(`Cleaned up ${initialLength - this.notifications.length} old notifications`);
            }
        }, 3600000); // 1 hour
    }
    
    /**
     * Setup page visibility handler
     */
    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                // Page became visible - update relative times
                this.updatePanel();
            }
        });
    }
    
    /**
     * Request desktop notification permission
     */
    requestDesktopPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                console.log('Desktop notification permission:', permission);
            });
        }
    }
    
    /**
     * Get notification statistics
     */
    getStats() {
        const typeStats = {};
        
        this.notifications.forEach(notification => {
            if (!typeStats[notification.type]) {
                typeStats[notification.type] = { total: 0, unread: 0 };
            }
            typeStats[notification.type].total++;
            if (!notification.read) {
                typeStats[notification.type].unread++;
            }
        });
        
        return {
            total: this.notifications.length,
            unread: this.unreadCount,
            byType: typeStats,
            oldestTimestamp: this.notifications.length > 0 ? 
                Math.min(...this.notifications.map(n => new Date(n.timestamp).getTime())) : null
        };
    }
    
    /**
     * Destroy notification system
     */
    destroy() {
        // Close WebSocket
        if (this.websocket) {
            this.websocket.close();
        }
        
        // Remove UI elements
        if (this.container) {
            this.container.remove();
        }
        
        // Clear handlers
        this.handlers.clear();
        
        // Save state
        this.saveNotifications();
        this.savePreferences();
    }
}

// Export for use in other modules
window.NotificationSystem = NotificationSystem;
