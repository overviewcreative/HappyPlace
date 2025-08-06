/**
 * Notification System
 * Happy Place Theme
 */

class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = new Map();
        this.init();
    }

    init() {
        this.createContainer();
    }

    createContainer() {
        if (document.getElementById('notification-container')) {
            this.container = document.getElementById('notification-container');
            return;
        }

        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'notification-container';
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', options = {}) {
        const defaults = {
            duration: 5000,
            closable: true,
            id: 'notification-' + Date.now(),
            persistent: false
        };

        const config = { ...defaults, ...options };

        const notification = this.createNotification(message, type, config);
        this.container.appendChild(notification);
        this.notifications.set(config.id, notification);

        // Trigger entrance animation
        setTimeout(() => {
            notification.classList.add('notification-enter');
        }, 10);

        // Auto remove (unless persistent)
        if (!config.persistent && config.duration > 0) {
            setTimeout(() => {
                this.hide(config.id);
            }, config.duration);
        }

        return config.id;
    }

    createNotification(message, type, config) {
        const notification = document.createElement('div');
        notification.id = config.id;
        notification.className = `notification notification-${type}`;

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        notification.innerHTML = `
            <div class="notification-icon">
                ${icons[type] || icons.info}
            </div>
            <div class="notification-content">
                <div class="notification-message">${message}</div>
            </div>
            ${config.closable ? '<button class="notification-close" aria-label="Close">&times;</button>' : ''}
        `;

        // Add close functionality
        if (config.closable) {
            const closeBtn = notification.querySelector('.notification-close');
            closeBtn.addEventListener('click', () => {
                this.hide(config.id);
            });
        }

        return notification;
    }

    hide(id) {
        const notification = this.notifications.get(id);
        if (!notification) return;

        notification.classList.add('notification-exit');
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            this.notifications.delete(id);
        }, 300);
    }

    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }

    clear() {
        Array.from(this.notifications.keys()).forEach(id => {
            this.hide(id);
        });
    }
}

// Initialize globally
const notifications = new NotificationSystem();

// Make available globally
window.notifications = notifications;

export default NotificationSystem;