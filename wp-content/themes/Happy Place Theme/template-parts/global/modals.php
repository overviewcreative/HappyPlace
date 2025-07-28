<?php
/**
 * Global Modals Template
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Global Modals Container -->
<div id="hph-modals-container">
    <!-- Contact Modal -->
    <div id="contact-modal" class="hph-modal" style="display: none;">
        <div class="hph-modal__overlay"></div>
        <div class="hph-modal__content">
            <div class="hph-modal__header">
                <h3 class="hph-modal__title">
                    <i class="fas fa-envelope"></i>
                    Contact Agent
                </h3>
                <button class="hph-modal__close" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="hph-modal__body">
                <!-- Contact form will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <!-- Photo Gallery Modal -->
    <div id="gallery-modal" class="hph-gallery-modal" style="display: none;">
        <div class="hph-gallery-modal-header">
            <h3 class="hph-gallery-modal-title">Property Photos</h3>
            <button class="hph-gallery-modal-close" aria-label="Close gallery">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hph-gallery-modal-content">
            <div class="hph-gallery-modal-main">
                <img class="hph-gallery-modal-image" src="" alt="">
            </div>
            <div class="hph-gallery-modal-thumbs">
                <!-- Thumbnails will be populated via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Schedule Tour Modal -->
    <div id="tour-modal" class="hph-modal" style="display: none;">
        <div class="hph-modal__overlay"></div>
        <div class="hph-modal__content">
            <div class="hph-modal__header">
                <h3 class="hph-modal__title">
                    <i class="fas fa-calendar-alt"></i>
                    Schedule a Tour
                </h3>
                <button class="hph-modal__close" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="hph-modal__body">
                <!-- Tour scheduling form will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>
