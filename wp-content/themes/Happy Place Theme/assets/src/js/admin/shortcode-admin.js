/**
 * Shortcode Admin JavaScript
 * 
 * Handles the admin interface for the HPH shortcode system
 */

(function($) {
    'use strict';
    
    let currentEditor = 'content';
    
    /**
     * Initialize admin interface
     */
    function init() {
        bindEvents();
        initTabs();
    }
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Shortcode button in editor
        $(document).on('click', '.hph-shortcode-button', function(e) {
            e.preventDefault();
            currentEditor = $(this).data('editor') || 'content';
            openShortcodeModal();
        });
        
        // Shortcode selection
        $(document).on('click', '.hph-shortcode-item', function() {
            $('.hph-shortcode-item').removeClass('active');
            $(this).addClass('active');
            
            const shortcode = $(this).data('shortcode');
            loadShortcodeForm(shortcode);
        });
        
        // Form changes
        $(document).on('input change', '.hph-shortcode-form input, .hph-shortcode-form select, .hph-shortcode-form textarea', function() {
            generateShortcode();
        });
        
        // Copy shortcode
        $(document).on('click', '#hph-copy-shortcode', function() {
            const textarea = document.getElementById('hph-generated-shortcode');
            textarea.select();
            document.execCommand('copy');
            
            $(this).text('Copied!').addClass('copied');
            setTimeout(() => {
                $(this).text('Copy Shortcode').removeClass('copied');
            }, 2000);
        });
        
        // Copy examples
        $(document).on('click', '.hph-copy-example', function() {
            const textarea = $(this).siblings('textarea')[0];
            textarea.select();
            document.execCommand('copy');
            
            $(this).text('Copied!');
            setTimeout(() => {
                $(this).text('Copy');
            }, 2000);
        });
        
        // Preview shortcode
        $(document).on('click', '#hph-preview-shortcode', function() {
            previewShortcode();
        });
        
        // Insert shortcode into editor
        $(document).on('click', '#hph-insert-shortcode', function() {
            insertShortcode();
        });
    }
    
    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href').substring(1);
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.hph-tab-content').removeClass('active');
            $('#' + target).addClass('active');
        });
    }
    
    /**
     * Open shortcode modal (for editor integration)
     */
    function openShortcodeModal() {
        // Create modal if it doesn't exist
        if (!$('#hph-shortcode-modal').length) {
            createShortcodeModal();
        }
        
        $('#hph-shortcode-modal').show();
    }
    
    /**
     * Create shortcode modal
     */
    function createShortcodeModal() {
        const modal = $(`
            <div id="hph-shortcode-modal" class="hph-modal" style="display: none;">
                <div class="hph-modal-content">
                    <div class="hph-modal-header">
                        <h2>Insert HPH Shortcode</h2>
                        <button type="button" class="hph-modal-close">&times;</button>
                    </div>
                    <div class="hph-modal-body">
                        <div class="hph-shortcode-generator">
                            <div class="hph-generator-sidebar">
                                <h3>Available Shortcodes</h3>
                                <div class="hph-shortcode-list">
                                    ${generateShortcodeList()}
                                </div>
                            </div>
                            <div class="hph-generator-main">
                                <div class="hph-generator-form">
                                    <h3>Configure Shortcode</h3>
                                    <div id="hph-shortcode-form-container">
                                        <p>Select a shortcode from the left to configure it.</p>
                                    </div>
                                </div>
                                <div class="hph-generator-output">
                                    <h3>Generated Shortcode</h3>
                                    <textarea id="hph-generated-shortcode" readonly placeholder="Your shortcode will appear here..."></textarea>
                                    <div class="hph-generator-actions">
                                        <button type="button" class="button button-primary" id="hph-insert-shortcode">Insert Shortcode</button>
                                        <button type="button" class="button" id="hph-copy-shortcode">Copy Shortcode</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Close modal events
        $('.hph-modal-close, #hph-shortcode-modal').on('click', function(e) {
            if (e.target === this) {
                $('#hph-shortcode-modal').hide();
            }
        });
    }
    
    /**
     * Generate shortcode list HTML
     */
    function generateShortcodeList() {
        if (!window.hphShortcodeAdmin || !window.hphShortcodeAdmin.shortcodes) {
            return '<p>No shortcodes available.</p>';
        }
        
        let html = '';
        const shortcodes = window.hphShortcodeAdmin.shortcodes;
        
        for (const [tag, data] of Object.entries(shortcodes)) {
            html += `
                <div class="hph-shortcode-item" data-shortcode="${tag}">
                    <h4>${data.name}</h4>
                    <p>${data.description}</p>
                </div>
            `;
        }
        
        return html;
    }
    
    /**
     * Load shortcode form via AJAX
     */
    function loadShortcodeForm(shortcode) {
        console.log('Loading shortcode form for:', shortcode);
        
        const data = {
            action: 'hph_get_shortcode_form',
            shortcode: shortcode,
            nonce: window.hphShortcodeAdmin.nonce
        };
        
        console.log('AJAX data:', data);
        console.log('AJAX URL:', window.hphShortcodeAdmin.ajaxUrl);
        
        $('#hph-shortcode-form-container').html('<p>Loading...</p>');
        
        $.post(window.hphShortcodeAdmin.ajaxUrl, data)
            .done(function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    $('#hph-shortcode-form-container').html(response.data.form);
                    generateShortcode();
                } else {
                    console.error('AJAX error:', response);
                    $('#hph-shortcode-form-container').html('<p>Error loading form: ' + (response.data || 'Unknown error') + '</p>');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX request failed:', status, error);
                console.error('Response:', xhr.responseText);
                $('#hph-shortcode-form-container').html('<p>Error loading form: ' + error + '</p>');
            });
    }
    
    /**
     * Generate shortcode from form data
     */
    function generateShortcode() {
        const form = $('.hph-shortcode-form');
        if (!form.length) return;
        
        const shortcodeTag = form.data('shortcode');
        const formData = {};
        
        form.find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value) {
                formData[name] = value;
            }
        });
        
        let shortcode = `[${shortcodeTag}`;
        
        for (const [attr, value] of Object.entries(formData)) {
            if (value.trim()) {
                shortcode += ` ${attr}="${value}"`;
            }
        }
        
        shortcode += ']';
        
        $('#hph-generated-shortcode').val(shortcode);
    }
    
    /**
     * Preview shortcode
     */
    function previewShortcode() {
        const shortcode = $('#hph-generated-shortcode').val();
        if (!shortcode) return;
        
        // Simple preview - in a real implementation, this would render the shortcode
        const previewArea = $('#hph-shortcode-preview');
        previewArea.find('.hph-preview-content').html(
            `<div class="hph-preview-notice">
                <p><strong>Preview functionality coming soon!</strong></p>
                <p>Shortcode: <code>${shortcode}</code></p>
            </div>`
        );
        previewArea.show();
    }
    
    /**
     * Insert shortcode into editor
     */
    function insertShortcode() {
        const shortcode = $('#hph-generated-shortcode').val();
        if (!shortcode) return;
        
        // Insert into WordPress editor
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.insert(currentEditor, shortcode);
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get(currentEditor)) {
            tinyMCE.get(currentEditor).insertContent(shortcode);
        } else {
            // Fallback to textarea
            const textarea = document.getElementById(currentEditor);
            if (textarea) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const value = textarea.value;
                
                textarea.value = value.substring(0, start) + shortcode + value.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + shortcode.length;
                textarea.focus();
            }
        }
        
        $('#hph-shortcode-modal').hide();
        
        // Show success message
        if (typeof wp !== 'undefined' && wp.notices) {
            wp.notices.create({
                type: 'success',
                content: 'Shortcode inserted successfully!',
                autoDismiss: true
            });
        }
    }
    
    /**
     * Initialize when ready
     */
    $(document).ready(init);
    
})(jQuery);
