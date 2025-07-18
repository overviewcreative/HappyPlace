<?php
/**
 * Showing Request Form Template
 * 
 * @package HappyPlace
 */

// Get listing ID if passed
$listing_id = isset($_GET['listing_id']) ? absint($_GET['listing_id']) : 0;
$listing = $listing_id ? get_post($listing_id) : null;

if (!$listing || $listing->post_type !== 'listing') {
    wp_die(__('Invalid listing', 'happy-place'));
}

// Get listing details
$address = get_post_meta($listing_id, 'listing_address', true);
$price = get_post_meta($listing_id, 'listing_price', true);
$agent_id = $listing->post_author;
$agent = get_userdata($agent_id);
?>

<form id="hph-showing-form" class="hph-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hph_request_showing', 'showing_request_nonce'); ?>
    <input type="hidden" name="action" value="hph_request_showing">
    <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">

    <div class="hph-form-grid">
        <!-- Property Info -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <i class="fas fa-home"></i>
                <?php _e('Property Information', 'happy-place'); ?>
            </h3>

            <div class="hph-property-summary">
                <div class="hph-property-image">
                    <?php echo get_the_post_thumbnail($listing_id, 'thumbnail'); ?>
                </div>
                <div class="hph-property-details">
                    <h4><?php echo esc_html($address); ?></h4>
                    <p class="hph-property-price">
                        <?php echo esc_html(hph_format_price($price)); ?>
                    </p>
                    <p class="hph-property-agent">
                        <?php printf(
                            __('Listed by: %s', 'happy-place'),
                            esc_html($agent->display_name)
                        ); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Preferred Showing Time -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <i class="fas fa-calendar"></i>
                <?php _e('Preferred Showing Time', 'happy-place'); ?>
            </h3>

            <div class="hph-form-row hph-form-row--2-col">
                <div class="hph-form-group">
                    <label for="preferred_date" class="hph-form-label">
                        <?php _e('Preferred Date', 'happy-place'); ?> *
                    </label>
                    <input type="date"
                           id="preferred_date"
                           name="preferred_date"
                           class="hph-form-input"
                           min="<?php echo date('Y-m-d'); ?>"
                           required>
                </div>

                <div class="hph-form-group">
                    <label for="preferred_time" class="hph-form-label">
                        <?php _e('Preferred Time', 'happy-place'); ?> *
                    </label>
                    <select id="preferred_time" name="preferred_time" class="hph-form-select" required>
                        <option value=""><?php _e('Select a time...', 'happy-place'); ?></option>
                        <?php
                        $start_hour = 9;
                        $end_hour = 19;
                        for ($hour = $start_hour; $hour <= $end_hour; $hour++) {
                            printf(
                                '<option value="%1$s:00">%2$s</option>',
                                str_pad($hour, 2, '0', STR_PAD_LEFT),
                                date('g:i A', strtotime($hour . ':00'))
                            );
                            if ($hour != $end_hour) {
                                printf(
                                    '<option value="%1$s:30">%2$s</option>',
                                    str_pad($hour, 2, '0', STR_PAD_LEFT),
                                    date('g:i A', strtotime($hour . ':30'))
                                );
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="hph-form-row hph-form-row--2-col">
                <div class="hph-form-group">
                    <label for="alternate_date" class="hph-form-label">
                        <?php _e('Alternate Date', 'happy-place'); ?>
                    </label>
                    <input type="date"
                           id="alternate_date"
                           name="alternate_date"
                           class="hph-form-input"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="alternate_time" class="hph-form-label">
                        <?php _e('Alternate Time', 'happy-place'); ?>
                    </label>
                    <select id="alternate_time" name="alternate_time" class="hph-form-select">
                        <option value=""><?php _e('Select a time...', 'happy-place'); ?></option>
                        <?php
                        for ($hour = $start_hour; $hour <= $end_hour; $hour++) {
                            printf(
                                '<option value="%1$s:00">%2$s</option>',
                                str_pad($hour, 2, '0', STR_PAD_LEFT),
                                date('g:i A', strtotime($hour . ':00'))
                            );
                            if ($hour != $end_hour) {
                                printf(
                                    '<option value="%1$s:30">%2$s</option>',
                                    str_pad($hour, 2, '0', STR_PAD_LEFT),
                                    date('g:i A', strtotime($hour . ':30'))
                                );
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <i class="fas fa-user"></i>
                <?php _e('Your Information', 'happy-place'); ?>
            </h3>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="name" class="hph-form-label">
                        <?php _e('Full Name', 'happy-place'); ?> *
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="hph-form-input"
                           required>
                </div>
            </div>

            <div class="hph-form-row hph-form-row--2-col">
                <div class="hph-form-group">
                    <label for="email" class="hph-form-label">
                        <?php _e('Email', 'happy-place'); ?> *
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="hph-form-input"
                           required>
                </div>

                <div class="hph-form-group">
                    <label for="phone" class="hph-form-label">
                        <?php _e('Phone', 'happy-place'); ?> *
                    </label>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           class="hph-form-input"
                           required>
                </div>
            </div>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="message" class="hph-form-label">
                        <?php _e('Additional Notes', 'happy-place'); ?>
                    </label>
                    <textarea id="message"
                              name="message"
                              class="hph-form-textarea"
                              rows="4"
                              placeholder="<?php esc_attr_e('Any specific questions or requests?', 'happy-place'); ?>"></textarea>
                </div>
            </div>
        </div>

        <!-- Financing Information -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <i class="fas fa-dollar-sign"></i>
                <?php _e('Financing Information', 'happy-place'); ?>
            </h3>

            <div class="hph-form-group">
                <label for="financing" class="hph-form-label">
                    <?php _e('How do you plan to finance this purchase?', 'happy-place'); ?>
                </label>
                <select id="financing" name="financing" class="hph-form-select">
                    <option value="mortgage"><?php _e('Mortgage', 'happy-place'); ?></option>
                    <option value="cash"><?php _e('Cash', 'happy-place'); ?></option>
                    <option value="other"><?php _e('Other', 'happy-place'); ?></option>
                </select>
            </div>

            <div class="hph-form-group">
                <label class="hph-checkbox">
                    <input type="checkbox"
                           name="preapproved"
                           value="1">
                    <span><?php _e('I am pre-approved for a mortgage', 'happy-place'); ?></span>
                </label>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="hph-form-actions">
            <button type="submit" class="action-btn action-btn--primary">
                <i class="fas fa-calendar-check"></i>
                <?php _e('Request Showing', 'happy-place'); ?>
            </button>
        </div>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    const form = $('#hph-showing-form');
    
    // Date validation
    $('#preferred_date, #alternate_date').on('change', function() {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            alert('<?php _e("Please select a future date", "happy-place"); ?>');
            $(this).val('').focus();
        }
    });

    // Form submission
    form.on('submit', function(e) {
        const preferredDate = $('#preferred_date').val();
        const preferredTime = $('#preferred_time').val();
        
        if (!preferredDate || !preferredTime) {
            e.preventDefault();
            alert('<?php _e("Please select your preferred showing date and time", "happy-place"); ?>');
            return false;
        }
    });
});
</script>

<style>
/* Property Summary */
.hph-property-summary {
    display: flex;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-4);
    background: var(--hph-color-gray-50);
    border-radius: var(--hph-radius-lg);
    margin-bottom: var(--hph-spacing-6);
}

.hph-property-image {
    width: 120px;
    height: 120px;
    border-radius: var(--hph-radius-md);
    overflow: hidden;
}

.hph-property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hph-property-details {
    flex: 1;
}

.hph-property-details h4 {
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
    margin: 0 0 var(--hph-spacing-2);
}

.hph-property-price {
    font-size: var(--hph-font-size-xl);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-primary-600);
    margin: 0 0 var(--hph-spacing-2);
}

.hph-property-agent {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
    margin: 0;
}

/* Form Layout */
.hph-form-row--2-col {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--hph-spacing-4);
}

@media (max-width: 768px) {
    .hph-property-summary {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .hph-property-image {
        width: 100%;
        max-width: 240px;
        height: auto;
        aspect-ratio: 1;
    }

    .hph-form-row--2-col {
        grid-template-columns: 1fr;
    }
}
</style>
