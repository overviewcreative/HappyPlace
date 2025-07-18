<?php
/**
 * Contact form component
 */

$listing_id = get_the_ID();
$listing_title = get_the_title();
?>

<div class="sidebar-widget contact-form-widget">
    <h3 class="widget-title">Request Information</h3>
    
    <form class="contact-form" id="listing-contact-form" method="post">
        <?php wp_nonce_field('listing_contact_form', 'listing_contact_nonce'); ?>
        <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
        <input type="hidden" name="action" value="listing_contact_form">
        
        <div class="form-row">
            <div class="form-group">
                <label for="first-name">First Name *</label>
                <input type="text" id="first-name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last-name">Last Name *</label>
                <input type="text" id="last-name" name="last_name" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone">
        </div>
        
        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" 
                      name="message" 
                      rows="4" 
                      placeholder="I'm interested in <?php echo esc_attr($listing_title); ?>..."></textarea>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="action-btn btn-primary">
                Send Message
            </button>
            <button type="button" class="action-btn btn-outline schedule-tour" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                Schedule Tour
            </button>
        </div>
        
        <div class="form-messages" id="form-messages"></div>
    </form>
</div>
