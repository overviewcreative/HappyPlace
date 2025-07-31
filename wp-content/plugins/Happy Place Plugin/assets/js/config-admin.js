/**
 * Configuration Manager Admin JavaScript
 */

(function($) {
    'use strict';

    const ConfigAdmin = {
        
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            // Form submission
            $('#config-form').on('submit', this.saveConfig.bind(this));
            
            // Reset configuration
            $('#reset-config').on('click', this.resetConfig.bind(this));
            
            // Auto-save on change (debounced)
            let saveTimeout;
            $('#config-form input, #config-form select, #config-form textarea').on('change', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    ConfigAdmin.saveConfig(null, true);
                }, 2000);
            });
        },
        
        saveConfig(event, silent = false) {
            if (event) {
                event.preventDefault();
            }
            
            const $form = $('#config-form');
            const group = $form.data('group');
            const formData = new FormData($form[0]);
            
            // Convert FormData to object
            const config = {};
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('config[') && key.endsWith(']')) {
                    const setting = key.slice(7, -1);
                    
                    // Handle checkboxes
                    if ($form.find(`input[name="${key}"][type="checkbox"]`).length) {
                        config[setting] = value === '1';
                    } else {
                        config[setting] = value;
                    }
                }
            }
            
            // Add unchecked checkboxes as false
            $form.find('input[type="checkbox"]').each(function() {
                const name = $(this).attr('name');
                if (name && name.startsWith('config[') && !$(this).is(':checked')) {
                    const setting = name.slice(7, -1);
                    config[setting] = false;
                }
            });
            
            if (!silent) {
                $form.find('.button-primary').prop('disabled', true).text('Saving...');
            }
            
            $.ajax({
                url: hphConfigAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_update_config',
                    nonce: hphConfigAdmin.nonce,
                    group: group,
                    config: config
                },
                success: (response) => {
                    if (response.success) {
                        if (!silent) {
                            this.showNotice(hphConfigAdmin.strings.saved, 'success');
                        }
                    } else {
                        this.showNotice(response.data || hphConfigAdmin.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(hphConfigAdmin.strings.error, 'error');
                },
                complete: () => {
                    if (!silent) {
                        $form.find('.button-primary').prop('disabled', false).text('Save Configuration');
                    }
                }
            });
        },
        
        resetConfig(event) {
            event.preventDefault();
            
            if (!confirm(hphConfigAdmin.strings.confirmReset)) {
                return;
            }
            
            const group = $(event.target).data('group');
            const $button = $(event.target);
            
            $button.prop('disabled', true).text('Resetting...');
            
            $.ajax({
                url: hphConfigAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_reset_config',
                    nonce: hphConfigAdmin.nonce,
                    group: group
                },
                success: (response) => {
                    if (response.success) {
                        location.reload(); // Reload to show reset values
                    } else {
                        this.showNotice(response.data || hphConfigAdmin.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(hphConfigAdmin.strings.error, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text('Reset to Defaults');
                }
            });
        },
        
        showNotice(message, type = 'info') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.hph-config-manager h1').after($notice);
            
            // Auto-dismiss success notices
            if (type === 'success') {
                setTimeout(() => {
                    $notice.fadeOut();
                }, 3000);
            }
            
            // Handle dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut();
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(() => {
        ConfigAdmin.init();
    });
    
})(jQuery);
