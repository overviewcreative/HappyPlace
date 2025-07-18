/**
 * Touch Handler Utility
 * 
 * Handles touch gestures for swipe interactions
 */

export class TouchHandler {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            threshold: 50,
            velocity: 0.3,
            onSwipeLeft: null,
            onSwipeRight: null,
            onSwipeUp: null,
            onSwipeDown: null,
            ...options
        };

        this.startX = 0;
        this.startY = 0;
        this.currentX = 0;
        this.currentY = 0;
        this.startTime = 0;

        this.init();
    }

    init() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: true });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        this.element.addEventListener('touchcancel', this.handleTouchEnd.bind(this), { passive: true });
    }

    handleTouchStart(event) {
        const touch = event.touches[0];
        this.startX = touch.clientX;
        this.startY = touch.clientY;
        this.startTime = Date.now();
    }

    handleTouchMove(event) {
        if (!event.touches.length) return;
        
        const touch = event.touches[0];
        this.currentX = touch.clientX;
        this.currentY = touch.clientY;
    }

    handleTouchEnd(event) {
        if (!this.startTime) return;

        const deltaX = this.currentX - this.startX;
        const deltaY = this.currentY - this.startY;
        const deltaTime = Date.now() - this.startTime;
        const velocity = Math.abs(deltaX) / deltaTime;

        // Reset
        this.startTime = 0;

        // Check if swipe meets threshold and velocity requirements
        if (Math.abs(deltaX) < this.options.threshold && Math.abs(deltaY) < this.options.threshold) {
            return;
        }

        if (velocity < this.options.velocity) {
            return;
        }

        // Determine swipe direction
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            // Horizontal swipe
            if (deltaX > 0 && this.options.onSwipeRight) {
                this.options.onSwipeRight(event);
            } else if (deltaX < 0 && this.options.onSwipeLeft) {
                this.options.onSwipeLeft(event);
            }
        } else {
            // Vertical swipe
            if (deltaY > 0 && this.options.onSwipeDown) {
                this.options.onSwipeDown(event);
            } else if (deltaY < 0 && this.options.onSwipeUp) {
                this.options.onSwipeUp(event);
            }
        }
    }

    destroy() {
        this.element.removeEventListener('touchstart', this.handleTouchStart);
        this.element.removeEventListener('touchmove', this.handleTouchMove);
        this.element.removeEventListener('touchend', this.handleTouchEnd);
        this.element.removeEventListener('touchcancel', this.handleTouchEnd);
    }
}
