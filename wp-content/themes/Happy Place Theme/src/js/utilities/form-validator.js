/**
 * Form Validator Utility
 * Happy Place Theme
 */

class FormValidator {
    constructor(form, options = {}) {
        this.form = form;
        this.options = {
            showErrors: true,
            validateOnBlur: true,
            errorClass: 'error',
            ...options
        };
        
        this.init();
    }

    init() {
        if (this.options.validateOnBlur) {
            this.form.querySelectorAll('input, textarea, select').forEach(field => {
                field.addEventListener('blur', () => this.validateField(field));
            });
        }

        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });
    }

    validateForm() {
        let isValid = true;
        const fields = this.form.querySelectorAll('[required], [data-validate]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const rules = field.dataset.validate?.split('|') || [];
        let isValid = true;
        let errorMessage = '';

        // Clear previous errors
        this.clearFieldError(field);

        // Required validation
        if (field.required && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }

        // Type-specific validation
        if (value && isValid) {
            switch (type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address.';
                    }
                    break;
                case 'tel':
                    if (!this.isValidPhone(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid phone number.';
                    }
                    break;
                case 'url':
                    if (!this.isValidUrl(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid URL.';
                    }
                    break;
            }
        }

        // Custom validation rules
        if (value && isValid && rules.length) {
            rules.forEach(rule => {
                if (!isValid) return;

                const [ruleName, ruleValue] = rule.split(':');
                
                switch (ruleName) {
                    case 'min':
                        if (value.length < parseInt(ruleValue)) {
                            isValid = false;
                            errorMessage = `Minimum ${ruleValue} characters required.`;
                        }
                        break;
                    case 'max':
                        if (value.length > parseInt(ruleValue)) {
                            isValid = false;
                            errorMessage = `Maximum ${ruleValue} characters allowed.`;
                        }
                        break;
                    case 'numeric':
                        if (!/^\d+$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Please enter numbers only.';
                        }
                        break;
                    case 'alpha':
                        if (!/^[a-zA-Z\s]+$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Please enter letters only.';
                        }
                        break;
                }
            });
        }

        // Show error if validation failed
        if (!isValid && this.options.showErrors) {
            this.showFieldError(field, errorMessage);
        }

        // Update field appearance
        field.classList.toggle(this.options.errorClass, !isValid);

        return isValid;
    }

    showFieldError(field, message) {
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        
        field.parentNode.appendChild(errorElement);
    }

    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidPhone(phone) {
        return /^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/\D/g, ''));
    }

    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
}

export default FormValidator;