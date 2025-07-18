/**
 * Component Manager
 * 
 * Manages initialization and lifecycle of components
 */

export class ComponentManager {
    constructor() {
        this.components = new Map();
        this.instances = new Map();
    }
    
    register(name, componentClass) {
        this.components.set(name, componentClass);
    }
    
    initializeAll() {
        // Find all components on the page
        const componentElements = document.querySelectorAll('[data-component]');
        
        componentElements.forEach(element => {
            const componentName = element.dataset.component;
            this.initialize(componentName, element);
        });
    }
    
    initialize(name, element) {
        const ComponentClass = this.components.get(name);
        
        if (!ComponentClass) {
            console.warn(`Component ${name} not registered`);
            return null;
        }
        
        const instance = new ComponentClass(element);
        const instanceId = this.generateInstanceId(element);
        
        this.instances.set(instanceId, instance);
        element.dataset.componentInstance = instanceId;
        
        return instance;
    }
    
    generateInstanceId(element) {
        return `${element.dataset.component}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    
    getInstance(element) {
        const instanceId = element.dataset.componentInstance;
        return this.instances.get(instanceId);
    }
    
    destroy(element) {
        const instanceId = element.dataset.componentInstance;
        const instance = this.instances.get(instanceId);
        
        if (instance && typeof instance.destroy === 'function') {
            instance.destroy();
        }
        
        this.instances.delete(instanceId);
        delete element.dataset.componentInstance;
    }
}
