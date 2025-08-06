/**
 * Happy Place Theme - Main JavaScript
 * 
 * Core theme functionality and initialization
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    /**
     * Document ready handler
     */
    $(document).ready(function() {
        HPH.init();
    });

    /**
     * Main theme object
     */
    window.HPH = {
        
        /**
         * Initialize theme
         */
        init: function() {
            this.setupGlobalHandlers();
            this.initializeComponents();
            this.setupAjax();
        },

        /**
         * Setup global event handlers
         */
        setupGlobalHandlers: function() {
            // Mobile menu toggle
            $('.hph-mobile-menu-toggle').on('click', this.toggleMobileMenu);
            
            // Cookie notice
            $('#accept-cookies').on('click', this.acceptCookies);
            
            // Search form enhancements
            $('.hph-search-form').on('submit', this.handleSearchSubmit);
        },

        /**
         * Initialize components
         */
        initializeComponents: function() {
            // Initialize any global components here
            this.initScrollToTop();
            this.initLazyLoading();
        },

        /**
         * Setup AJAX configuration
         */
        setupAjax: function() {
            // Global AJAX error handler
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                console.error('AJAX Error:', thrownError);
            });

            // Global AJAX success handler for common actions
            $(document).ajaxSuccess(function(event, jqXHR, ajaxSettings, data) {
                // Handle common success actions
                if (data && data.reload) {
                    location.reload();
                }
            });
        },

        /**
         * Toggle mobile menu
         */
        toggleMobileMenu: function(e) {
            e.preventDefault();
            $('body').toggleClass('mobile-menu-open');
            $('.hph-mobile-menu').slideToggle();
        },

        /**
         * Accept cookies
         */
        acceptCookies: function(e) {
            e.preventDefault();
            $('#cookie-notice').fadeOut();
            
            // Set cookie to remember preference
            document.cookie = 'hph_cookies_accepted=1; path=/; max-age=31536000'; // 1 year
        },

        /**
         * Handle search form submission
         */
        handleSearchSubmit: function(e) {
            var $form = $(this);
            var searchTerm = $form.find('input[name="search"]').val().trim();
            
            if (!searchTerm) {
                e.preventDefault();
                $form.find('input[name="search"]').focus();
                return false;
            }
        },

        /**
         * Initialize scroll to top button
         */
        initScrollToTop: function() {
            var $scrollBtn = $('.hph-scroll-to-top');
            
            if ($scrollBtn.length) {
                $(window).on('scroll', function() {
                    if ($(this).scrollTop() > 500) {
                        $scrollBtn.fadeIn();
                    } else {
                        $scrollBtn.fadeOut();
                    }
                });

                $scrollBtn.on('click', function(e) {
                    e.preventDefault();
                    $('html, body').animate({ scrollTop: 0 }, 'smooth');
                });
            }
        },

        /**
         * Initialize lazy loading for images
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },

        /**
         * Utility: Format price
         */
        formatPrice: function(price) {
            if (!price) return '';
            return '$' + parseFloat(price).toLocaleString();
        },

        /**
         * Utility: Show loading state
         */
        showLoading: function($element) {
            $element.addClass('loading').attr('disabled', true);
        },

        /**
         * Utility: Hide loading state
         */
        hideLoading: function($element) {
            $element.removeClass('loading').attr('disabled', false);
        },

        /**
         * Utility: Show notification
         */
        showNotification: function(message, type = 'success') {
            var $notification = $('<div class="hph-notification hph-notification-' + type + '">' + message + '</div>');
            $('body').append($notification);
            
            $notification.fadeIn().delay(3000).fadeOut(function() {
                $(this).remove();
            });
        }
    };

})(jQuery);
