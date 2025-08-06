/**
 * Dashboard Form JavaScript
 * 
 * Handles form interactions, validation, and AJAX submissions
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initFormValidation();
        initImageUpload();
        initDraftSaving();
        initFormEnhancements();
    });

    /**
     * Form validation
     */
    function initFormValidation() {
        $('.hph-form').on('submit', function(e) {
            var $form = $(this);
            var isValid = true;

            // Clear previous errors
            $form.find('.hph-form-field').removeClass('has-error');
            $form.find('.hph-form-error').remove();

            // Validate required fields
            $form.find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                var value = $field.val();

                if (!value || (Array.isArray(value) && value.length === 0)) {
                    isValid = false;
                    showFieldError($field, 'This field is required.');
                }
            });

            // Email validation
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val();

                if (email && !isValidEmail(email)) {
                    isValid = false;
                    showFieldError($field, 'Please enter a valid email address.');
                }
            });

            // Price validation
            $form.find('input[type="number"][min]').each(function() {
                var $field = $(this);
                var value = parseFloat($field.val());
                var min = parseFloat($field.attr('min'));

                if (!isNaN(value) && !isNaN(min) && value < min) {
                    isValid = false;
                    showFieldError($field, 'Value must be at least ' + min);
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                var $firstError = $form.find('.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
            }
        });
    }

    /**
     * Image upload handling
     */
    function initImageUpload() {
        // Handle file input changes
        $(document).on('change', '.hph-form-image-upload input[type="file"]', function() {
            var $input = $(this);
            var $container = $input.closest('.hph-form-image-upload');
            var $preview = $container.siblings('.hph-form-image-preview');

            if (!$preview.length) {
                $preview = $('<div class="hph-form-image-preview"></div>');
                $container.after($preview);
            }

            var files = this.files;
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                if (file.type.match('image.*')) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var $previewItem = $('<div class="hph-form-image-preview-item">' +
                            '<img src="' + e.target.result + '" alt="Preview">' +
                            '<button type="button" class="hph-form-image-preview-remove">' +
                            '<i class="fas fa-times"></i>' +
                            '</button>' +
                            '</div>');
                        $preview.append($previewItem);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Handle image removal
        $(document).on('click', '.hph-form-image-preview-remove', function() {
            $(this).closest('.hph-form-image-preview-item').remove();
        });
    }

    /**
     * Draft saving functionality
     */
    function initDraftSaving() {
        $('.hph-form [data-save-draft]').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('.hph-form');
            var formData = $form.serialize();
            var formType = $form.find('input[name="action"]').val();
            
            // Update action for draft
            formData = formData.replace('action=' + formType, 'action=' + formType.replace('save_', 'save_') + '_draft');
            
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            $.post(hph_ajax.ajaxurl, formData)
                .done(function(response) {
                    if (response.success) {
                        showToast('success', 'Draft saved successfully!');
                    } else {
                        showToast('error', 'Failed to save draft.');
                    }
                })
                .fail(function() {
                    showToast('error', 'Failed to save draft.');
                })
                .always(function() {
                    $button.prop('disabled', false).html('<i class="fas fa-save"></i> Save Draft');
                });
        });

        // Auto-save drafts every 2 minutes
        setInterval(function() {
            $('.hph-form').each(function() {
                var $form = $(this);
                var hasChanges = $form.data('hasChanges');
                
                if (hasChanges) {
                    $form.find('[data-save-draft]').trigger('click');
                    $form.data('hasChanges', false);
                }
            });
        }, 120000); // 2 minutes

        // Track changes
        $('.hph-form input, .hph-form select, .hph-form textarea').on('change input', function() {
            $(this).closest('.hph-form').data('hasChanges', true);
        });
    }

    /**
     * Form enhancements
     */
    function initFormEnhancements() {
        // Character counters for textareas
        $('.hph-form-textarea').each(function() {
            var $textarea = $(this);
            var maxLength = $textarea.attr('maxlength');
            
            if (maxLength) {
                var $counter = $('<div class="hph-form-char-counter">' +
                    '<span class="current">0</span> / <span class="max">' + maxLength + '</span>' +
                    '</div>');
                $textarea.after($counter);
                
                $textarea.on('input', function() {
                    var currentLength = $(this).val().length;
                    $counter.find('.current').text(currentLength);
                    
                    if (currentLength > maxLength * 0.9) {
                        $counter.addClass('warning');
                    } else {
                        $counter.removeClass('warning');
                    }
                });
            }
        });

        // Enhanced select styling
        $('.hph-form-select').each(function() {
            var $select = $(this);
            if (!$select.hasClass('enhanced')) {
                $select.addClass('enhanced');
                // Add custom styling or enhanced functionality here
            }
        });

        // Conditional field display
        $('[data-conditional-field]').each(function() {
            var $field = $(this);
            var targetField = $field.data('conditional-field');
            var targetValue = $field.data('conditional-value');
            var $targetField = $('[name="' + targetField + '"]');
            
            function toggleConditionalField() {
                var currentValue = $targetField.val();
                if (currentValue === targetValue) {
                    $field.show();
                } else {
                    $field.hide();
                }
            }
            
            $targetField.on('change', toggleConditionalField);
            toggleConditionalField(); // Initial check
        });

        // Numeric input formatting
        $('.hph-form-price input').on('input', function() {
            var value = $(this).val().replace(/[^\d.]/g, '');
            var parts = value.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            $(this).val(parts.join('.'));
        });
    }

    /**
     * Helper functions
     */
    function showFieldError($field, message) {
        $field.closest('.hph-form-field').addClass('has-error');
        $field.after('<div class="hph-form-error"><i class="fas fa-exclamation-circle"></i> ' + message + '</div>');
    }

    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showToast(type, message) {
        // Create toast container if it doesn't exist
        var $container = $('.hph-toast-container');
        if (!$container.length) {
            $container = $('<div class="hph-toast-container"></div>');
            $('body').append($container);
        }

        // Create toast element
        var iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        var $toast = $('<div class="hph-toast hph-toast--' + type + '">' +
            '<div class="hph-toast-icon"><i class="fas ' + iconClass + '"></i></div>' +
            '<div class="hph-toast-content">' +
            '<div class="hph-toast-message">' + message + '</div>' +
            '</div>' +
            '<button class="hph-toast-close"><i class="fas fa-times"></i></button>' +
            '</div>');

        // Add to container
        $container.append($toast);

        // Animate in
        setTimeout(function() {
            $toast.addClass('hph-toast--entered');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(function() {
            removeToast($toast);
        }, 5000);

        // Manual close
        $toast.find('.hph-toast-close').on('click', function() {
            removeToast($toast);
        });
    }

    function removeToast($toast) {
        $toast.addClass('hph-toast--exiting');
        setTimeout(function() {
            $toast.remove();
        }, 300);
    }

    // Make functions available globally
    window.HPH_Forms = {
        showToast: showToast,
        showFieldError: showFieldError,
        isValidEmail: isValidEmail
    };

})(jQuery);
