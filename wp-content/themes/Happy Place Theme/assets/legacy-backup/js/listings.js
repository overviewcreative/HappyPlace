/**
 * Listings functionality
 */

(function($) {
    'use strict';

    class ListingsManager {
        constructor() {
            this.grid = $('.hph-listings-grid');
            this.viewToggles = $('.hph-view-toggle__button');
            this.filterForm = $('.hph-filters__form');
            this.bindEvents();
        }

        bindEvents() {
            // View toggle
            this.viewToggles.on('click', this.handleViewToggle.bind(this));

            // Filter form
            this.filterForm.on('submit', this.handleFilterSubmit.bind(this));
            this.filterForm.find('.hph-filters__button--reset').on('click', this.handleFilterReset.bind(this));

            // Load more (if using AJAX pagination)
            $('.hph-load-more').on('click', this.handleLoadMore.bind(this));
        }

        handleViewToggle(e) {
            const $button = $(e.currentTarget);
            const viewType = $button.data('view');

            // Update buttons
            this.viewToggles.removeClass('is-active');
            $button.addClass('is-active');

            // Update grid
            this.grid.attr('data-view-type', viewType);

            // Save preference in localStorage
            localStorage.setItem('hphListingView', viewType);
        }

        handleFilterSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            this.grid.addClass('is-loading');

            $.ajax({
                url: happyPlaceListings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'filter_listings',
                    nonce: happyPlaceListings.nonce,
                    filters: Object.fromEntries(formData)
                },
                success: (response) => {
                    if (response.success) {
                        this.updateListings(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('An error occurred. Please try again.');
                },
                complete: () => {
                    this.grid.removeClass('is-loading');
                }
            });
        }

        handleFilterReset(e) {
            e.preventDefault();
            this.filterForm[0].reset();
            this.filterForm.submit();
        }

        handleLoadMore(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const page = $button.data('page');

            $button.text(happyPlaceListings.messages.loading);

            $.ajax({
                url: happyPlaceListings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'load_more_listings',
                    nonce: happyPlaceListings.nonce,
                    page: page
                },
                success: (response) => {
                    if (response.success) {
                        this.appendListings(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('An error occurred. Please try again.');
                },
                complete: () => {
                    $button.text(happyPlaceListings.messages.loadMore);
                }
            });
        }

        updateListings(data) {
            if (data.listings) {
                this.grid.html(data.listings);
            } else {
                this.grid.html(`
                    <div class="hph-no-listings">
                        <p>${happyPlaceListings.messages.noResults}</p>
                    </div>
                `);
            }

            if (data.pagination) {
                $('.hph-pagination').html(data.pagination);
            }
        }

        appendListings(data) {
            if (data.listings) {
                this.grid.append(data.listings);
            }

            if (data.pagination) {
                $('.hph-pagination').html(data.pagination);
            }

            // Update load more button
            const $loadMore = $('.hph-load-more');
            if (data.hasMore) {
                $loadMore.data('page', data.nextPage).show();
            } else {
                $loadMore.hide();
            }
        }

        showError(message) {
            // You can implement a proper error notification system here
            alert(message);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new ListingsManager();
    });

})(jQuery);
