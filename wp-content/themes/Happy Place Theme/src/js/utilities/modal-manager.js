/**
 * Modal Manager Utility
 * Happy Place Theme
 */

class ModalManager {
    constructor() {
        this.activeModals = new Set();
        this.init();
    }

    init() {
        // Handle modal triggers
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-modal]');
            if (trigger) {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                this.openModal(modalId);
            }
        });

        // Handle modal close buttons
        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-modal-close]');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal');
                if (modal) {
                    this.closeModal(modal.id);
                }
            }
        });

        // Close modal on overlay click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeModal(e.target.querySelector('.modal').id);
            }
        });

        // Close modals on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModals.size > 0) {
                this.closeTopModal();
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn(`Modal with ID "${modalId}" not found`);
            return;
        }

        modal.classList.add('modal-active');
        document.body.classList.add('modal-open');
        this.activeModals.add(modalId);

        // Focus first focusable element
        const focusable = modal.querySelector('input, textarea, select, button, [tabindex]');
        if (focusable) {
            setTimeout(() => focusable.focus(), 100);
        }

        // Trigger event
        this.triggerEvent('modal:opened', { modalId, modal });
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.remove('modal-active');
        this.activeModals.delete(modalId);

        if (this.activeModals.size === 0) {
            document.body.classList.remove('modal-open');
        }

        // Trigger event
        this.triggerEvent('modal:closed', { modalId, modal });
    }

    closeTopModal() {
        if (this.activeModals.size > 0) {
            const modalIds = Array.from(this.activeModals);
            this.closeModal(modalIds[modalIds.length - 1]);
        }
    }

    closeAllModals() {
        Array.from(this.activeModals).forEach(modalId => {
            this.closeModal(modalId);
        });
    }

    createModal(options = {}) {
        const defaults = {
            id: 'modal-' + Date.now(),
            title: '',
            content: '',
            className: '',
            showCloseButton: true,
            backdrop: true
        };

        const config = { ...defaults, ...options };

        const modal = document.createElement('div');
        modal.id = config.id;
        modal.className = `modal-overlay ${config.className}`;
        
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    ${config.title ? `<h3 class="modal-title">${config.title}</h3>` : ''}
                    ${config.showCloseButton ? '<button type="button" class="modal-close" data-modal-close>&times;</button>' : ''}
                </div>
                <div class="modal-content">
                    ${config.content}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    triggerEvent(eventName, data) {
        const event = new CustomEvent(eventName, { detail: data });
        document.dispatchEvent(event);
    }
}

// Initialize globally
const modalManager = new ModalManager();

export default ModalManager;