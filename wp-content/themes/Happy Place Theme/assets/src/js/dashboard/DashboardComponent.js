/**
 * Dashboard Component Base Class
 * 
 * Provides a standardized foundation for all dashboard components
 * with lifecycle management, state integration, and event handling.
 * 
 * @since 3.0.0
 */

class DashboardComponent {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            autoInit: true,
            bindEvents: true,
            stateSubscriptions: [],
            ...options
        };
        
        this.state = null;
        this.subscriptions = [];
        this.eventListeners = [];
        this.childComponents = new Map();
        this.initialized = false;
        this.destroyed = false;
        
        if (this.options.autoInit) {
            this.init();
        }
    }
    
    /**
     * Initialize component
     */
    init() {
        if (this.initialized || this.destroyed) return;
        
        this.onBeforeInit();
        
        // Get state instance
        this.state = window.dashboardState || new DashboardState();
        
        // Setup state subscriptions
        this.setupStateSubscriptions();
        
        // Bind events
        if (this.options.bindEvents) {
            this.bindEvents();
        }
        
        // Call component-specific initialization
        this.onInit();
        
        this.initialized = true;
        this.onAfterInit();
        
        this.log('Component initialized');
    }
    
    /**
     * Setup state subscriptions based on options
     */
    setupStateSubscriptions() {
        this.options.stateSubscriptions.forEach(subscription => {
            if (typeof subscription === 'string') {
                // Simple subscription
                this.subscribe(subscription, (value) => {
                    this.onStateChange(subscription, value);
                });
            } else if (typeof subscription === 'object') {
                // Advanced subscription with callback
                this.subscribe(subscription.path, subscription.callback.bind(this));
            }
        });
    }
    
    /**
     * Subscribe to state changes
     */
    subscribe(path, callback) {
        const unsubscribe = this.state.subscribe(path, callback);
        this.subscriptions.push(unsubscribe);
        return unsubscribe;
    }
    
    /**
     * Add event listener with automatic cleanup
     */
    addEventListener(element, event, callback, options = {}) {
        const boundCallback = callback.bind(this);
        element.addEventListener(event, boundCallback, options);
        
        this.eventListeners.push({
            element,
            event,
            callback: boundCallback,
            options
        });
        
        return boundCallback;
    }
    
    /**
     * Find elements within component
     */
    $(selector) {
        return this.element.querySelector(selector);
    }
    
    /**
     * Find all elements within component
     */
    $$(selector) {
        return this.element.querySelectorAll(selector);
    }
    
    /**
     * Add child component
     */
    addChild(name, component) {
        this.childComponents.set(name, component);
        component.parent = this;
    }
    
    /**
     * Get child component
     */
    getChild(name) {
        return this.childComponents.get(name);
    }
    
    /**
     * Remove child component
     */
    removeChild(name) {
        const child = this.childComponents.get(name);
        if (child) {
            child.destroy();
            this.childComponents.delete(name);
        }
    }
    
    /**
     * Show component
     */
    show() {
        this.element.style.display = '';
        this.element.classList.remove('hidden');
        this.onShow();
    }
    
    /**
     * Hide component
     */
    hide() {
        this.element.style.display = 'none';
        this.element.classList.add('hidden');
        this.onHide();
    }
    
    /**
     * Toggle component visibility
     */
    toggle() {
        if (this.isVisible()) {
            this.hide();
        } else {
            this.show();
        }
    }
    
    /**
     * Check if component is visible
     */
    isVisible() {
        return this.element.style.display !== 'none' && 
               !this.element.classList.contains('hidden');
    }
    
    /**
     * Update component data
     */
    update(data) {
        this.onUpdate(data);
    }
    
    /**
     * Refresh component
     */
    refresh() {
        this.onRefresh();
    }
    
    /**
     * Enable component
     */
    enable() {
        this.element.classList.remove('disabled');
        this.element.removeAttribute('disabled');
        this.onEnable();
    }
    
    /**
     * Disable component
     */
    disable() {
        this.element.classList.add('disabled');
        this.element.setAttribute('disabled', 'disabled');
        this.onDisable();
    }
    
    /**
     * Check if component is enabled
     */
    isEnabled() {
        return !this.element.classList.contains('disabled') && 
               !this.element.hasAttribute('disabled');
    }
    
    /**
     * Trigger custom event
     */
    trigger(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail: {
                component: this,
                ...detail
            },
            bubbles: true,
            cancelable: true
        });
        
        this.element.dispatchEvent(event);
        return event;
    }
    
    /**
     * Add CSS class with animation support
     */
    addClass(className, animated = false) {
        if (animated) {
            this.element.style.transition = 'all 0.3s ease';
        }
        
        this.element.classList.add(className);
        
        if (animated) {
            setTimeout(() => {
                this.element.style.transition = '';
            }, 300);
        }
    }
    
    /**
     * Remove CSS class with animation support
     */
    removeClass(className, animated = false) {
        if (animated) {
            this.element.style.transition = 'all 0.3s ease';
        }
        
        this.element.classList.remove(className);
        
        if (animated) {
            setTimeout(() => {
                this.element.style.transition = '';
            }, 300);
        }
    }
    
    /**
     * Set loading state
     */
    setLoading(loading = true) {
        if (loading) {
            this.addClass('loading');
            this.disable();
        } else {
            this.removeClass('loading');
            this.enable();
        }
    }
    
    /**
     * Show error state
     */
    showError(message) {
        this.addClass('error');
        
        // Create or update error message element
        let errorElement = this.$('.component-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'component-error';
            this.element.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        this.onError(message);
    }
    
    /**
     * Clear error state
     */
    clearError() {
        this.removeClass('error');
        
        const errorElement = this.$('.component-error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
        
        this.onErrorClear();
    }
    
    /**
     * Log message with component context
     */
    log(message, level = 'info') {
        if (window.hphAjax?.debug) {
            const componentName = this.constructor.name;
            console[level](`[${componentName}] ${message}`, this);
        }
    }
    
    /**
     * Destroy component and cleanup
     */
    destroy() {
        if (this.destroyed) return;
        
        this.onBeforeDestroy();
        
        // Destroy child components
        this.childComponents.forEach(child => child.destroy());
        this.childComponents.clear();
        
        // Unsubscribe from state
        this.subscriptions.forEach(unsubscribe => unsubscribe());
        this.subscriptions = [];
        
        // Remove event listeners
        this.eventListeners.forEach(({ element, event, callback, options }) => {
            element.removeEventListener(event, callback, options);
        });
        this.eventListeners = [];
        
        // Remove element from DOM if needed
        if (this.options.removeOnDestroy) {
            this.element.remove();
        }
        
        this.onDestroy();
        
        this.destroyed = true;
        this.log('Component destroyed');
    }
    
    // ===================
    // LIFECYCLE HOOKS
    // ===================
    
    /**
     * Called before initialization
     */
    onBeforeInit() {
        // Override in subclasses
    }
    
    /**
     * Called during initialization
     */
    onInit() {
        // Override in subclasses
    }
    
    /**
     * Called after initialization
     */
    onAfterInit() {
        // Override in subclasses
    }
    
    /**
     * Called when state changes
     */
    onStateChange(path, value) {
        // Override in subclasses
    }
    
    /**
     * Called when component is updated
     */
    onUpdate(data) {
        // Override in subclasses
    }
    
    /**
     * Called when component is refreshed
     */
    onRefresh() {
        // Override in subclasses
    }
    
    /**
     * Called when component is shown
     */
    onShow() {
        // Override in subclasses
    }
    
    /**
     * Called when component is hidden
     */
    onHide() {
        // Override in subclasses
    }
    
    /**
     * Called when component is enabled
     */
    onEnable() {
        // Override in subclasses
    }
    
    /**
     * Called when component is disabled
     */
    onDisable() {
        // Override in subclasses
    }
    
    /**
     * Called when error occurs
     */
    onError(message) {
        // Override in subclasses
    }
    
    /**
     * Called when error is cleared
     */
    onErrorClear() {
        // Override in subclasses
    }
    
    /**
     * Called before destruction
     */
    onBeforeDestroy() {
        // Override in subclasses
    }
    
    /**
     * Called during destruction
     */
    onDestroy() {
        // Override in subclasses
    }
    
    // ===================
    // EVENT BINDING
    // ===================
    
    /**
     * Bind component events - override in subclasses
     */
    bindEvents() {
        // Default implementation - override in subclasses
    }
    
    // ===================
    // STATIC METHODS
    // ===================
    
    /**
     * Create component from element
     */
    static create(element, options = {}) {
        return new this(element, options);
    }
    
    /**
     * Initialize all components with specific selector
     */
    static initAll(selector, options = {}) {
        const elements = document.querySelectorAll(selector);
        const components = [];
        
        elements.forEach(element => {
            const component = new this(element, options);
            components.push(component);
        });
        
        return components;
    }
}

/**
 * Component Registry for managing component types
 */
class ComponentRegistry {
    constructor() {
        this.components = new Map();
        this.instances = new WeakMap();
    }
    
    /**
     * Register component type
     */
    register(name, ComponentClass) {
        this.components.set(name, ComponentClass);
    }
    
    /**
     * Create component instance
     */
    create(name, element, options = {}) {
        const ComponentClass = this.components.get(name);
        if (!ComponentClass) {
            throw new Error(`Component type "${name}" not registered`);
        }
        
        const instance = new ComponentClass(element, options);
        this.instances.set(element, instance);
        
        return instance;
    }
    
    /**
     * Get component instance for element
     */
    getInstance(element) {
        return this.instances.get(element);
    }
    
    /**
     * Initialize components from DOM
     */
    initFromDOM(root = document) {
        const elements = root.querySelectorAll('[data-component]');
        
        elements.forEach(element => {
            const componentType = element.dataset.component;
            const options = element.dataset.componentOptions ? 
                JSON.parse(element.dataset.componentOptions) : {};
            
            if (this.components.has(componentType)) {
                this.create(componentType, element, options);
            }
        });
    }
    
    /**
     * Destroy all component instances
     */
    destroyAll() {
        // This is handled by the WeakMap automatically when elements are removed
    }
}

// Global registry
window.ComponentRegistry = new ComponentRegistry();
window.DashboardComponent = DashboardComponent;
