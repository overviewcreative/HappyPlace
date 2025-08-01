<?php
/**
 * Global Modals Template
 * 
 * @package HappyPlace
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Contact Modal -->
<div id="contact-modal" class="hph-modal" style="display: none;">
    <div class="hph-modal-content">
        <div class="hph-modal-header">
            <h2><?php _e('Contact Us', 'happy-place'); ?></h2>
            <button class="hph-modal-close" type="button" aria-label="<?php _e('Close modal', 'happy-place'); ?>">&times;</button>
        </div>
        <div class="hph-modal-body">
            <div id="contact-form-container">
                <!-- Contact form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div id="image-lightbox" class="hph-modal hph-lightbox" style="display: none;">
    <div class="hph-lightbox-content">
        <button class="hph-modal-close" type="button" aria-label="<?php _e('Close lightbox', 'happy-place'); ?>">&times;</button>
        <img src="" alt="" id="lightbox-image">
        <div class="hph-lightbox-caption">
            <span id="lightbox-caption-text"></span>
        </div>
    </div>
</div>

<script>
// Basic modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close modal functionality
    document.querySelectorAll('.hph-modal-close').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.hph-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modal on backdrop click
    document.querySelectorAll('.hph-modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
});
</script>
