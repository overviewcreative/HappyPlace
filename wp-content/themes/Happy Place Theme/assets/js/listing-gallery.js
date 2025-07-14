/**
 * Listing Gallery JavaScript
 * 
 * Handles image gallery functionality including modal viewer,
 * navigation, and thumbnail interactions.
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    class ListingGallery {
        constructor() {
            this.modal = null;
            this.currentIndex = 0;
            this.images = [];
            this.init();
        }

        init() {
            this.setupGallery();
            this.setupModal();
            this.bindEvents();
        }

        setupGallery() {
            const $gallery = $('.hph-gallery-grid');
            if (!$gallery.length) return;

            // Initialize gallery items
            $gallery.find('.hph-gallery-item').each((index, item) => {
                const $item = $(item);
                const $img = $item.find('img');
                
                if ($img.length) {
                    this.images.push({
                        src: $img.data('full') || $img.attr('src'),
                        alt: $img.attr('alt') || '',
                        thumb: $img.attr('src')
                    });
                }
            });
        }

        setupModal() {
            if ($('#gallery-modal').length) return;

            const modalHTML = `
                <div class="hph-gallery-modal" id="gallery-modal">
                    <div class="hph-gallery-modal-header">
                        <h3 class="hph-gallery-modal-title">Property Gallery</h3>
                        <button class="hph-gallery-modal-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="hph-gallery-modal-content">
                        <div class="hph-gallery-modal-main">
                            <img src="" alt="" class="hph-gallery-modal-image">
                            
                            <button class="hph-gallery-nav hph-gallery-prev">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <button class="hph-gallery-nav hph-gallery-next">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="hph-gallery-modal-thumbs"></div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            this.modal = $('#gallery-modal');
            this.generateThumbnails();
        }

        generateThumbnails() {
            const $thumbsContainer = this.modal.find('.hph-gallery-modal-thumbs');
            
            this.images.forEach((image, index) => {
                const thumbHTML = `
                    <div class="hph-gallery-thumb" data-index="${index}">
                        <img src="${image.thumb}" alt="${image.alt}">
                    </div>
                `;
                $thumbsContainer.append(thumbHTML);
            });
        }

        bindEvents() {
            // Gallery item clicks
            $(document).on('click', '.hph-gallery-item', (e) => {
                e.preventDefault();
                const index = $(e.currentTarget).index();
                this.openModal(index);
            });

            // Gallery more button
            $(document).on('click', '.hph-gallery-more', (e) => {
                e.preventDefault();
                this.openModal(0);
            });

            // Modal close
            $(document).on('click', '.hph-gallery-modal-close', () => {
                this.closeModal();
            });

            // Modal background click
            $(document).on('click', '.hph-gallery-modal', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });

            // Navigation
            $(document).on('click', '.hph-gallery-prev', () => {
                this.prevImage();
            });

            $(document).on('click', '.hph-gallery-next', () => {
                this.nextImage();
            });

            // Thumbnail clicks
            $(document).on('click', '.hph-gallery-thumb', (e) => {
                const index = parseInt($(e.currentTarget).data('index'));
                this.showImage(index);
            });

            // Keyboard navigation
            $(document).on('keydown', (e) => {
                if (!this.modal || !this.modal.hasClass('active')) return;

                switch(e.keyCode) {
                    case 27: // Escape
                        this.closeModal();
                        break;
                    case 37: // Left arrow
                        this.prevImage();
                        break;
                    case 39: // Right arrow
                        this.nextImage();
                        break;
                }
            });
        }

        openModal(index = 0) {
            if (!this.modal || !this.images.length) return;

            this.currentIndex = index;
            this.modal.addClass('active');
            this.showImage(index);
            $('body').addClass('modal-open');
        }

        closeModal() {
            if (!this.modal) return;

            this.modal.removeClass('active');
            $('body').removeClass('modal-open');
        }

        showImage(index) {
            if (index < 0 || index >= this.images.length) return;

            this.currentIndex = index;
            const image = this.images[index];

            // Update main image
            const $mainImage = this.modal.find('.hph-gallery-modal-image');
            $mainImage.attr('src', image.src).attr('alt', image.alt);

            // Update active thumbnail
            this.modal.find('.hph-gallery-thumb').removeClass('active');
            this.modal.find(`.hph-gallery-thumb[data-index="${index}"]`).addClass('active');

            // Update navigation visibility
            this.modal.find('.hph-gallery-prev').toggle(index > 0);
            this.modal.find('.hph-gallery-next').toggle(index < this.images.length - 1);
        }

        prevImage() {
            if (this.currentIndex > 0) {
                this.showImage(this.currentIndex - 1);
            }
        }

        nextImage() {
            if (this.currentIndex < this.images.length - 1) {
                this.showImage(this.currentIndex + 1);
            }
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        new ListingGallery();
    });

})(jQuery);
