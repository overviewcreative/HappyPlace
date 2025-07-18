/**
 * Lazy Loader
 * 
 * Handles lazy loading of images and other content
 */

export class LazyLoader {
    constructor() {
        this.observer = null;
        this.elements = new Set();
        
        this.init();
    }
    
    init() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(
                this.handleIntersection.bind(this),
                {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                }
            );
        }
    }
    
    observe() {
        // Find all lazy loadable elements
        const lazyImages = document.querySelectorAll('img[data-src]');
        const lazyBackgrounds = document.querySelectorAll('[data-bg]');
        const lazyComponents = document.querySelectorAll('[data-lazy-component]');
        
        // Observe images
        lazyImages.forEach(img => {
            this.elements.add(img);
            if (this.observer) {
                this.observer.observe(img);
            } else {
                this.loadImage(img);
            }
        });
        
        // Observe background images
        lazyBackgrounds.forEach(el => {
            this.elements.add(el);
            if (this.observer) {
                this.observer.observe(el);
            } else {
                this.loadBackground(el);
            }
        });
        
        // Observe lazy components
        lazyComponents.forEach(el => {
            this.elements.add(el);
            if (this.observer) {
                this.observer.observe(el);
            } else {
                this.loadComponent(el);
            }
        });
    }
    
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                
                if (element.hasAttribute('data-src')) {
                    this.loadImage(element);
                } else if (element.hasAttribute('data-bg')) {
                    this.loadBackground(element);
                } else if (element.hasAttribute('data-lazy-component')) {
                    this.loadComponent(element);
                }
                
                this.observer.unobserve(element);
                this.elements.delete(element);
            }
        });
    }
    
    loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;
        
        if (src) {
            const imageLoader = new Image();
            
            imageLoader.onload = () => {
                img.src = src;
                if (srcset) {
                    img.srcset = srcset;
                }
                img.classList.remove('lazy');
                img.classList.add('loaded');
                
                // Dispatch loaded event
                img.dispatchEvent(new CustomEvent('lazyLoaded'));
            };
            
            imageLoader.onerror = () => {
                img.classList.add('error');
            };
            
            imageLoader.src = src;
        }
    }
    
    loadBackground(element) {
        const bg = element.dataset.bg;
        
        if (bg) {
            const imageLoader = new Image();
            
            imageLoader.onload = () => {
                element.style.backgroundImage = `url(${bg})`;
                element.classList.remove('lazy');
                element.classList.add('loaded');
                
                // Dispatch loaded event
                element.dispatchEvent(new CustomEvent('lazyLoaded'));
            };
            
            imageLoader.src = bg;
        }
    }
    
    loadComponent(element) {
        const componentName = element.dataset.lazyComponent;
        
        if (componentName && window.HPH && window.HPH.theme) {
            // Initialize the component
            window.HPH.theme.componentManager.initialize(componentName, element);
            
            element.classList.remove('lazy');
            element.classList.add('loaded');
        }
    }
    
    // Manually load all remaining elements
    loadAll() {
        this.elements.forEach(element => {
            if (element.hasAttribute('data-src')) {
                this.loadImage(element);
            } else if (element.hasAttribute('data-bg')) {
                this.loadBackground(element);
            } else if (element.hasAttribute('data-lazy-component')) {
                this.loadComponent(element);
            }
        });
        
        this.elements.clear();
    }
    
    // Add new elements to observe
    add(elements) {
        if (!Array.isArray(elements)) {
            elements = [elements];
        }
        
        elements.forEach(element => {
            this.elements.add(element);
            if (this.observer) {
                this.observer.observe(element);
            }
        });
    }
    
    // Remove elements from observation
    remove(elements) {
        if (!Array.isArray(elements)) {
            elements = [elements];
        }
        
        elements.forEach(element => {
            this.elements.delete(element);
            if (this.observer) {
                this.observer.unobserve(element);
            }
        });
    }
    
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        this.elements.clear();
    }
}
