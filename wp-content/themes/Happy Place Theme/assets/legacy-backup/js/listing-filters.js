(function($) {
    'use strict';

    class ListingFilters {
        constructor() {
            this.initializeFilterToggles();
            this.initializeButtonGroups();
            this.restoreFilterStates();
        }

        initializeFilterToggles() {
            // Handle filter section toggling
            $('.hph-filter-section .hph-filter-toggle').on('click', (e) => {
                const $toggle = $(e.currentTarget);
                const $section = $toggle.closest('.hph-filter-section');
                const $content = $section.find('.hph-filter-content');
                const isExpanded = $toggle.attr('aria-expanded') === 'true';

                // Toggle aria-expanded and visibility
                $toggle.attr('aria-expanded', !isExpanded);
                $content.attr('aria-hidden', isExpanded);
                
                // Add/remove expanded class for animations
                $section.toggleClass('is-expanded');

                // Save state to localStorage
                this.saveFilterState($section.data('section'), !isExpanded);
            });

            // Handle filter buttons in beds/baths sections
            $('.hph-filter-btn').on('click', function() {
                const $btn = $(this);
                const $row = $btn.closest('.hph-filter-row');
                
                // Remove active class from all buttons in this row
                $row.find('.hph-filter-btn').removeClass('active');
                
                // Add active class to clicked button
                $btn.addClass('active');
                
                // Update hidden input value
                $row.find('input[type="hidden"]').val($btn.data('value'));
            });

            // Ensure keyboard navigation works
            $('.hph-filter-toggle').on('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(e.currentTarget).click();
                }
            });
        }

        initializeButtonGroups() {
            // Initialize select elements to trigger form submission
            $('.hph-price-select').on('change', () => {
                this.submitForm();
            });

            // Initialize checkbox changes
            $('.hph-filter-checkbox input').on('change', () => {
                this.submitForm();
            });
        }

        submitForm() {
            // Find the closest form and submit it
            const $form = $('.hph-listing-filters-form');
            if ($form.length) {
                $form.submit();
            }
        }

        saveFilterState(sectionId, isExpanded) {
            if (!sectionId) return;
            
            try {
                const states = JSON.parse(localStorage.getItem('hphFilterStates') || '{}');
                states[sectionId] = isExpanded;
                localStorage.setItem('hphFilterStates', JSON.stringify(states));
            } catch (e) {
                console.warn('Failed to save filter state:', e);
            }
        }

        restoreFilterStates() {
            try {
                const states = JSON.parse(localStorage.getItem('hphFilterStates') || '{}');
                Object.entries(states).forEach(([sectionId, isExpanded]) => {
                    const $section = $(`.hph-filter-section[data-section="${sectionId}"]`);
                    if ($section.length) {
                        const $toggle = $section.find('.hph-filter-toggle');
                        const $content = $section.find('.hph-filter-content');
                        
                        $toggle.attr('aria-expanded', isExpanded);
                        $content.attr('aria-hidden', !isExpanded);
                        $section.toggleClass('is-expanded', isExpanded);
                    }
                });
            } catch (e) {
                console.warn('Failed to restore filter states:', e);
            }
        }

        saveFilterState(sectionId, isExpanded) {
            if (!sectionId) return;
            
            try {
                const states = JSON.parse(localStorage.getItem('hphFilterStates') || '{}');
                states[sectionId] = isExpanded;
                localStorage.setItem('hphFilterStates', JSON.stringify(states));
            } catch (e) {
                console.warn('Failed to save filter state:', e);
            }
        }

        restoreFilterStates() {
            try {
                const states = JSON.parse(localStorage.getItem('hphFilterStates') || '{}');
                Object.entries(states).forEach(([sectionId, isExpanded]) => {
                    const $section = $(`#${sectionId}`);
                    if ($section.length) {
                        const $header = $section.find('.filter-header');
                        const $content = $section.find('.filter-content');
                        
                        $header.attr('aria-expanded', isExpanded);
                        $content.attr('aria-hidden', !isExpanded);
                        $section.toggleClass('is-expanded', isExpanded);
                    }
                });
            } catch (e) {
                console.warn('Failed to restore filter states:', e);
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new ListingFilters();
    });

})(jQuery);
