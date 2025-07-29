/**
 * Service Worker for Dashboard Push Notifications
 * 
 * Handles push notifications and background sync for the dashboard
 * @since 3.0.0
 */

const CACHE_NAME = 'tpg-dashboard-v3.0.0';
const NOTIFICATION_CACHE = 'tpg-notifications-v1';

// Install event
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('Service Worker: Caching essential files...');
            return cache.addAll([
                '/assets/images/notification-icon.png',
                '/assets/sounds/notification.mp3',
                '/assets/sounds/alert.mp3',
                '/assets/sounds/success.mp3',
                '/assets/sounds/error.mp3'
            ]).catch(error => {
                // Don't fail if optional assets aren't available
                console.warn('Service Worker: Some assets could not be cached:', error);
            });
        })
    );
    
    // Force activation
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== NOTIFICATION_CACHE) {
                        console.log('Service Worker: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Take control of all clients
            return self.clients.claim();
        })
    );
});

// Push event handler
self.addEventListener('push', (event) => {
    console.log('Service Worker: Push received');
    
    let notificationData = {
        title: 'TPG Dashboard',
        body: 'You have a new notification',
        icon: '/assets/images/notification-icon.png',
        badge: '/assets/images/notification-badge.png',
        tag: 'dashboard-notification',
        data: {
            url: '/wp-admin/',
            timestamp: Date.now()
        },
        actions: [
            {
                action: 'view',
                title: 'View Dashboard',
                icon: '/assets/images/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/assets/images/dismiss-icon.png'
            }
        ],
        requireInteraction: false,
        silent: false
    };
    
    // Parse push data if available
    if (event.data) {
        try {
            const pushData = event.data.json();
            notificationData = {
                ...notificationData,
                ...pushData
            };
        } catch (error) {
            console.error('Service Worker: Error parsing push data:', error);
            // Use text data as body
            notificationData.body = event.data.text();
        }
    }
    
    // Customize based on notification type
    if (notificationData.type) {
        switch (notificationData.type) {
            case 'listing.created':
                notificationData.title = 'New Listing Added';
                notificationData.icon = '/assets/images/house-icon.png';
                notificationData.requireInteraction = true;
                break;
                
            case 'lead.received':
                notificationData.title = 'New Lead Received';
                notificationData.icon = '/assets/images/lead-icon.png';
                notificationData.requireInteraction = true;
                notificationData.tag = 'lead-notification';
                break;
                
            case 'listing.sold':
                notificationData.title = 'Listing Sold!';
                notificationData.icon = '/assets/images/sold-icon.png';
                notificationData.requireInteraction = true;
                break;
                
            case 'sync.error':
                notificationData.title = 'Sync Error';
                notificationData.icon = '/assets/images/error-icon.png';
                notificationData.requireInteraction = true;
                break;
        }
    }
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
            .then(() => {
                // Store notification for tracking
                return caches.open(NOTIFICATION_CACHE).then(cache => {
                    const notificationRecord = {
                        id: Date.now(),
                        data: notificationData,
                        timestamp: new Date().toISOString(),
                        shown: true
                    };
                    
                    return cache.put(
                        `notification-${notificationRecord.id}`,
                        new Response(JSON.stringify(notificationRecord))
                    );
                });
            })
            .catch(error => {
                console.error('Service Worker: Error showing notification:', error);
            })
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    console.log('Service Worker: Notification clicked');
    
    event.notification.close();
    
    const action = event.action;
    const notificationData = event.notification.data || {};
    
    if (action === 'dismiss') {
        // Just close the notification
        return;
    }
    
    // Default action or 'view' action
    let targetUrl = notificationData.url || '/wp-admin/';
    
    // Customize URL based on notification type
    if (notificationData.type) {
        switch (notificationData.type) {
            case 'listing.created':
            case 'listing.updated':
                if (notificationData.listingId) {
                    targetUrl = `/wp-admin/post.php?post=${notificationData.listingId}&action=edit`;
                } else {
                    targetUrl = '/wp-admin/edit.php?post_type=listing';
                }
                break;
                
            case 'lead.received':
                if (notificationData.leadId) {
                    targetUrl = `/wp-admin/admin.php?page=leads&lead=${notificationData.leadId}`;
                } else {
                    targetUrl = '/wp-admin/admin.php?page=leads';
                }
                break;
                
            case 'sync.error':
                targetUrl = '/wp-admin/admin.php?page=dashboard-settings&tab=integrations';
                break;
        }
    }
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if there's already a window open with the target URL
            for (const client of clientList) {
                if (client.url.includes('/wp-admin/') && 'focus' in client) {
                    // Navigate to the target URL
                    client.postMessage({
                        type: 'NAVIGATE',
                        url: targetUrl,
                        notificationData: notificationData
                    });
                    return client.focus();
                }
            }
            
            // No suitable window found, open a new one
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        }).catch(error => {
            console.error('Service Worker: Error handling notification click:', error);
        })
    );
});

// Background sync
self.addEventListener('sync', (event) => {
    console.log('Service Worker: Background sync triggered:', event.tag);
    
    if (event.tag === 'dashboard-sync') {
        event.waitUntil(performBackgroundSync());
    }
});

// Message handler for communication with main thread
self.addEventListener('message', (event) => {
    console.log('Service Worker: Message received:', event.data);
    
    const { type, data } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'CLAIM_CLIENTS':
            self.clients.claim();
            break;
            
        case 'CACHE_NOTIFICATION':
            cacheNotification(data);
            break;
            
        case 'GET_NOTIFICATIONS':
            getStoredNotifications().then(notifications => {
                event.ports[0].postMessage({
                    type: 'NOTIFICATIONS_RESPONSE',
                    notifications
                });
            });
            break;
    }
});

// Fetch event for caching strategy
self.addEventListener('fetch', (event) => {
    // Only handle requests for our domain
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }
    
    // Skip POST requests and API calls
    if (event.request.method !== 'GET' || event.request.url.includes('/wp-admin/admin-ajax.php')) {
        return;
    }
    
    // Cache strategy for assets
    if (event.request.url.includes('/assets/')) {
        event.respondWith(
            caches.match(event.request).then(response => {
                return response || fetch(event.request).then(fetchResponse => {
                    const responseClone = fetchResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                    return fetchResponse;
                });
            }).catch(() => {
                // Return a fallback for critical assets
                if (event.request.url.includes('notification-icon')) {
                    return new Response('', { status: 404 });
                }
            })
        );
    }
});

/**
 * Perform background sync
 */
async function performBackgroundSync() {
    try {
        console.log('Service Worker: Performing background sync...');
        
        // This would typically sync with the server
        // For now, we'll just clean up old notifications
        await cleanupOldNotifications();
        
        console.log('Service Worker: Background sync completed');
        
    } catch (error) {
        console.error('Service Worker: Background sync failed:', error);
        throw error; // Re-throw to trigger retry
    }
}

/**
 * Cache notification for offline access
 */
async function cacheNotification(notificationData) {
    try {
        const cache = await caches.open(NOTIFICATION_CACHE);
        const id = Date.now();
        
        const record = {
            id,
            data: notificationData,
            timestamp: new Date().toISOString(),
            cached: true
        };
        
        await cache.put(
            `notification-${id}`,
            new Response(JSON.stringify(record))
        );
        
        console.log('Service Worker: Notification cached:', id);
        
    } catch (error) {
        console.error('Service Worker: Error caching notification:', error);
    }
}

/**
 * Get stored notifications
 */
async function getStoredNotifications() {
    try {
        const cache = await caches.open(NOTIFICATION_CACHE);
        const keys = await cache.keys();
        
        const notifications = [];
        
        for (const key of keys) {
            if (key.url.includes('notification-')) {
                const response = await cache.match(key);
                const data = await response.json();
                notifications.push(data);
            }
        }
        
        // Sort by timestamp
        notifications.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
        
        return notifications;
        
    } catch (error) {
        console.error('Service Worker: Error getting stored notifications:', error);
        return [];
    }
}

/**
 * Clean up old notifications
 */
async function cleanupOldNotifications() {
    try {
        const cache = await caches.open(NOTIFICATION_CACHE);
        const keys = await cache.keys();
        
        const oneWeekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
        let cleanedCount = 0;
        
        for (const key of keys) {
            if (key.url.includes('notification-')) {
                const response = await cache.match(key);
                const data = await response.json();
                
                if (new Date(data.timestamp) < oneWeekAgo) {
                    await cache.delete(key);
                    cleanedCount++;
                }
            }
        }
        
        if (cleanedCount > 0) {
            console.log(`Service Worker: Cleaned up ${cleanedCount} old notifications`);
        }
        
    } catch (error) {
        console.error('Service Worker: Error during cleanup:', error);
    }
}

// Error handler
self.addEventListener('error', (event) => {
    console.error('Service Worker: Global error:', event.error);
});

// Unhandled rejection handler
self.addEventListener('unhandledrejection', (event) => {
    console.error('Service Worker: Unhandled rejection:', event.reason);
    event.preventDefault();
});

console.log('Service Worker: Script loaded successfully');
