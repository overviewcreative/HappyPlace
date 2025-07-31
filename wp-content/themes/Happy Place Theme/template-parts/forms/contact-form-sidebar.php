<?php
/**
 * Template part for displaying contact form in sidebar
 * 
 * This is a fallback template part used when the Contact Form component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$listing_title = get_the_title($listing_id);
$listing_url = get_permalink($listing_id);
?>

<div class="contact-form-sidebar widget">
    <h3 class="widget-title">Request Information</h3>
    
    <form class="contact-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <?php wp_nonce_field('contact_form_nonce', 'contact_nonce'); ?>
        <input type="hidden" name="action" value="submit_contact_form">
        <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
        <input type="hidden" name="listing_title" value="<?php echo esc_attr($listing_title); ?>">
        <input type="hidden" name="listing_url" value="<?php echo esc_url($listing_url); ?>">
        
        <div class="form-fields">
            <div class="field-group">
                <label for="contact-name" class="field-label">Full Name *</label>
                <input type="text" 
                       id="contact-name" 
                       name="contact_name" 
                       class="field-input"
                       required
                       placeholder="Enter your name">
            </div>
            
            <div class="field-group">
                <label for="contact-email" class="field-label">Email Address *</label>
                <input type="email" 
                       id="contact-email" 
                       name="contact_email" 
                       class="field-input"
                       required
                       placeholder="Enter your email">
            </div>
            
            <div class="field-group">
                <label for="contact-phone" class="field-label">Phone Number</label>
                <input type="tel" 
                       id="contact-phone" 
                       name="contact_phone" 
                       class="field-input"
                       placeholder="Enter your phone">
            </div>
            
            <div class="field-group">
                <label for="inquiry-type" class="field-label">I'm interested in:</label>
                <select id="inquiry-type" name="inquiry_type" class="field-select">
                    <option value="general">General Information</option>
                    <option value="tour">Scheduling a Tour</option>
                    <option value="financing">Financing Options</option>
                    <option value="agent">Speaking with Agent</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="field-group">
                <label for="contact-message" class="field-label">Message</label>
                <textarea id="contact-message" 
                          name="contact_message" 
                          class="field-textarea"
                          rows="4"
                          placeholder="Tell us how we can help...">I'm interested in learning more about <?php echo esc_html($listing_title); ?>.</textarea>
            </div>
            
            <div class="field-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="newsletter_opt_in" value="1">
                    <span class="checkbox-text">Subscribe to our newsletter for new listings</span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block submit-btn">
                Send Message
            </button>
        </div>
        
        <div class="form-status" style="display: none;">
            <div class="status-message"></div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.contact-form');
    const statusDiv = form.querySelector('.form-status');
    const statusMessage = statusDiv.querySelector('.status-message');
    const submitBtn = form.querySelector('.submit-btn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;
        statusDiv.style.display = 'none';
        
        // Collect form data
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusMessage.textContent = 'Thank you! Your message has been sent successfully.';
                statusMessage.className = 'status-message success';
                form.reset();
            } else {
                statusMessage.textContent = data.data || 'There was an error sending your message. Please try again.';
                statusMessage.className = 'status-message error';
            }
            statusDiv.style.display = 'block';
        })
        .catch(error => {
            statusMessage.textContent = 'There was an error sending your message. Please try again.';
            statusMessage.className = 'status-message error';
            statusDiv.style.display = 'block';
        })
        .finally(() => {
            submitBtn.textContent = 'Send Message';
            submitBtn.disabled = false;
        });
    });
});
</script>
