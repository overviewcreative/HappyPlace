/**
 * Property Stats Module - Enhanced lot size display and property data interactions
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

export default class PropertyStats {
    constructor() {
        this.init();
    }
    
    init() {
        this.enhancePropertyStats();
        this.addStatTooltips();
        this.initStatAnimations();
        this.bindStatInteractions();
    }
    
    /**
     * Enhance property stats display with better formatting and interactions
     */
    enhancePropertyStats() {
        const stats = document.querySelectorAll('.hph-hero__stat, .hph-quick-fact');
        
        stats.forEach(stat => {
            this.enhanceStat(stat);
        });
    }
    
    /**
     * Enhance individual stat element
     */
    enhanceStat(stat) {
        const statType = stat.dataset.stat || this.getStatType(stat);
        const value = stat.querySelector('.hph-hero__stat-value, .hph-quick-fact__value');
        const icon = stat.querySelector('.hph-icon, .fas, .far, .fab');
        
        if (!value) return;
        
        // Add semantic classes for styling
        stat.classList.add(`hph-stat--${statType}`);
        
        // Enhance lot size specifically
        if (statType === 'lot_size' || stat.textContent.includes('acres')) {
            this.enhanceLotSize(stat, value);
        }
        
        // Enhance square footage
        if (statType === 'sqft' || value.textContent.includes('sq ft')) {
            this.enhanceSquareFootage(stat, value);
        }
        
        // Add icon enhancements
        if (icon) {
            icon.classList.add('hph-icon-enhanced');
            this.enhanceIcon(icon, statType);
        }
    }
    
    /**
     * Enhance lot size display with conversion tooltips and formatting
     */
    enhanceLotSize(stat, valueElement) {
        const text = valueElement.textContent.trim();
        const acres = this.extractNumber(text);
        
        if (acres && acres > 0) {
            // Calculate square feet equivalent
            const sqft = Math.round(acres * 43560);
            
            // Add conversion tooltip
            const tooltip = `${acres} acres = ${sqft.toLocaleString()} square feet`;
            stat.setAttribute('title', tooltip);
            stat.setAttribute('data-tooltip', tooltip);
            
            // Add smart formatting for very small or large lots
            if (acres < 0.1) {
                const sqftOnly = `${sqft.toLocaleString()} sq ft`;
                valueElement.innerHTML = `<span class="primary-value">${sqftOnly}</span><span class="alt-value">(${acres} acres)</span>`;
            } else if (acres >= 1) {
                valueElement.innerHTML = `<span class="primary-value">${acres}</span><span class="unit">acres</span>`;
            }
            
            // Add data attributes for sorting/filtering
            stat.setAttribute('data-acres', acres);
            stat.setAttribute('data-sqft', sqft);
        }
    }
    
    /**
     * Enhance square footage display
     */
    enhanceSquareFootage(stat, valueElement) {
        const text = valueElement.textContent.trim();
        const sqft = this.extractNumber(text);
        
        if (sqft && sqft > 0) {
            // Format with proper commas
            const formatted = sqft.toLocaleString();
            valueElement.innerHTML = `<span class="primary-value">${formatted}</span><span class="unit">sq ft</span>`;
            
            // Add data attribute for sorting
            stat.setAttribute('data-sqft', sqft);
        }
    }
    
    /**
     * Enhance icons with proper colors and animations
     */
    enhanceIcon(icon, statType) {
        const iconMap = {
            'bedrooms': 'hph-icon-bed',
            'bathrooms': 'hph-icon-bath', 
            'sqft': 'hph-icon-sqft',
            'lot_size': 'hph-icon-lot',
            'year_built': 'hph-icon-year',
            'garage': 'hph-icon-garage'
        };
        
        if (iconMap[statType]) {
            icon.classList.add(iconMap[statType]);
        }
        
        // Add interactive class for hover effects
        icon.classList.add('hph-icon-interactive');
    }
    
    /**
     * Add tooltips for enhanced information
     */
    addStatTooltips() {
        const stats = document.querySelectorAll('[data-tooltip]');
        
        stats.forEach(stat => {
            this.createTooltip(stat);
        });
    }
    
    /**
     * Create tooltip element
     */
    createTooltip(element) {
        const tooltip = document.createElement('div');
        tooltip.className = 'hph-tooltip';
        tooltip.textContent = element.getAttribute('data-tooltip');
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            pointer-events: none;
            top: -40px;
            left: 50%;
            transform: translateX(-50%) translateY(-10px);
        `;
        
        element.style.position = 'relative';
        element.appendChild(tooltip);
        
        element.addEventListener('mouseenter', () => {
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translateX(-50%) translateY(0)';
        });
        
        element.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateX(-50%) translateY(-10px)';
        });
    }
    
    /**
     * Initialize stat animations on scroll
     */
    initStatAnimations() {
        const stats = document.querySelectorAll('.hph-hero__stat, .hph-quick-fact');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('hph-stat--animated');
                    this.animateStatValue(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        stats.forEach(stat => observer.observe(stat));
    }
    
    /**
     * Animate stat value counting up
     */
    animateStatValue(stat) {
        const valueElement = stat.querySelector('.hph-hero__stat-value, .hph-quick-fact__value');
        if (!valueElement) return;
        
        const text = valueElement.textContent;
        const number = this.extractNumber(text);
        
        if (number && number > 0) {
            this.countUpAnimation(valueElement, number, text);
        }
    }
    
    /**
     * Count up animation for numeric values
     */
    countUpAnimation(element, targetNumber, originalText) {
        const duration = 1000; // 1 second
        const startTime = performance.now();
        const increment = targetNumber / (duration / 16); // 60fps
        
        let currentNumber = 0;
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            currentNumber = targetNumber * this.easeOutQuart(progress);
            
            // Update display
            if (originalText.includes('sq ft')) {
                element.innerHTML = `<span class="primary-value">${Math.round(currentNumber).toLocaleString()}</span><span class="unit">sq ft</span>`;
            } else if (originalText.includes('acres')) {
                element.innerHTML = `<span class="primary-value">${currentNumber.toFixed(2)}</span><span class="unit">acres</span>`;
            } else {
                element.textContent = Math.round(currentNumber).toString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    /**
     * Easing function for smooth animation
     */
    easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }
    
    /**
     * Bind interactive behaviors to stats
     */
    bindStatInteractions() {
        const stats = document.querySelectorAll('.hph-hero__stat, .hph-quick-fact');
        
        stats.forEach(stat => {
            // Add click to copy functionality
            stat.addEventListener('click', () => {
                this.copyStatToClipboard(stat);
            });
            
            // Add keyboard navigation
            stat.setAttribute('tabindex', '0');
            stat.setAttribute('role', 'button');
            stat.setAttribute('aria-label', this.getStatAccessibilityLabel(stat));
            
            stat.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.copyStatToClipboard(stat);
                }
            });
        });
    }
    
    /**
     * Copy stat value to clipboard
     */
    copyStatToClipboard(stat) {
        const value = stat.querySelector('.hph-hero__stat-value, .hph-quick-fact__value');
        if (!value) return;
        
        const text = value.textContent.trim();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showCopyFeedback(stat);
            });
        }
    }
    
    /**
     * Show visual feedback when copying
     */
    showCopyFeedback(stat) {
        const feedback = document.createElement('div');
        feedback.className = 'hph-copy-feedback';
        feedback.textContent = 'Copied!';
        feedback.style.cssText = `
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--hph-color-success);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1001;
            animation: fadeInOut 1.5s ease;
        `;
        
        stat.style.position = 'relative';
        stat.appendChild(feedback);
        
        setTimeout(() => {
            if (feedback.parentNode) {
                feedback.parentNode.removeChild(feedback);
            }
        }, 1500);
    }
    
    /**
     * Extract numeric value from text
     */
    extractNumber(text) {
        const match = text.match(/[\d,]+\.?\d*/);
        if (match) {
            return parseFloat(match[0].replace(/,/g, ''));
        }
        return null;
    }
    
    /**
     * Determine stat type from element
     */
    getStatType(stat) {
        const text = stat.textContent.toLowerCase();
        
        if (text.includes('bed')) return 'bedrooms';
        if (text.includes('bath')) return 'bathrooms';
        if (text.includes('sq ft') || text.includes('square')) return 'sqft';
        if (text.includes('acres') || text.includes('lot')) return 'lot_size';
        if (text.includes('year') || text.includes('built')) return 'year_built';
        if (text.includes('garage') || text.includes('car')) return 'garage';
        
        return 'unknown';
    }
    
    /**
     * Generate accessibility label for stat
     */
    getStatAccessibilityLabel(stat) {
        const type = this.getStatType(stat);
        const value = stat.querySelector('.hph-hero__stat-value, .hph-quick-fact__value');
        
        if (!value) return 'Property statistic';
        
        const text = value.textContent.trim();
        
        const labels = {
            'bedrooms': `${text} bedrooms`,
            'bathrooms': `${text} bathrooms`, 
            'sqft': `${text} square feet`,
            'lot_size': `Lot size: ${text}`,
            'year_built': `Built in ${text}`,
            'garage': `${text} garage spaces`
        };
        
        return labels[type] || `Property detail: ${text}`;
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    new PropertyStats();
});

// Export for manual initialization
export { PropertyStats };
