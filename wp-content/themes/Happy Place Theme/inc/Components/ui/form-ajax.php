<?php
/**
 * AJAX Handlers for Forms
 * 
 * @package HappyPlace
 */

/**
 * Get listing preview for open house form
 */
function hph_ajax_get_listing_preview() {
    check_ajax_referer('hph_form_nonce', 'nonce');

    $listing_id = absint($_POST['listing_id']);
    if (!$listing_id) {
        wp_send_json_error();
    }

    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'listing') {
        wp_send_json_error();
    }

    // Get listing details
    $price = get_post_meta($listing_id, 'listing_price', true);
    $address = get_post_meta($listing_id, 'listing_address', true);
    $beds = get_post_meta($listing_id, 'listing_beds', true);
    $baths = get_post_meta($listing_id, 'listing_baths', true);
    $sqft = get_post_meta($listing_id, 'listing_sqft', true);

    ob_start();
    ?>
    <div class="listing-preview">
        <div class="hph-property-summary">
            <?php if (has_post_thumbnail($listing_id)) : ?>
                <div class="hph-property-image">
                    <?php echo get_the_post_thumbnail($listing_id, 'thumbnail'); ?>
                </div>
            <?php endif; ?>

            <div class="hph-property-details">
                <h4><?php echo esc_html($address); ?></h4>
                <?php if ($price) : ?>
                    <div class="hph-property-price">
                        <?php echo hph_format_price($price); ?>
                    </div>
                <?php endif; ?>

                <div class="hph-property-specs">
                    <?php if ($beds) : ?>
                        <span class="hph-property-spec">
                            <i class="fas fa-bed"></i>
                            <?php echo esc_html($beds); ?> <?php _e('beds', 'happy-place'); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($baths) : ?>
                        <span class="hph-property-spec">
                            <i class="fas fa-bath"></i>
                            <?php echo esc_html($baths); ?> <?php _e('baths', 'happy-place'); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($sqft) : ?>
                        <span class="hph-property-spec">
                            <i class="fas fa-ruler-combined"></i>
                            <?php echo number_format($sqft); ?> <?php _e('sq ft', 'happy-place'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'suggested_title' => sprintf(
            __('Open House: %s', 'happy-place'),
            $address
        )
    ]);
}
add_action('wp_ajax_hph_get_listing_preview', 'hph_ajax_get_listing_preview');
add_action('wp_ajax_nopriv_hph_get_listing_preview', 'hph_ajax_get_listing_preview');

/**
 * Get agent preview for contact form
 */
function hph_ajax_get_agent_preview() {
    check_ajax_referer('hph_contact_nonce', 'nonce');

    $agent_id = absint($_POST['agent_id']);
    if (!$agent_id) {
        wp_send_json_error();
    }

    $agent = get_userdata($agent_id);
    if (!$agent) {
        wp_send_json_error();
    }

    $phone = get_user_meta($agent_id, 'agent_phone', true);
    ob_start();
    ?>
    <div class="hph-agent-summary">
        <div class="hph-agent-avatar">
            <?php echo get_avatar($agent_id, 96); ?>
        </div>
        <div class="hph-agent-details">
            <h4><?php echo esc_html($agent->display_name); ?></h4>
            <?php if ($phone || $agent->user_email) : ?>
                <div class="hph-agent-contact">
                    <?php if ($phone) : ?>
                        <p>
                            <i class="fas fa-phone"></i>
                            <?php echo esc_html($phone); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($agent->user_email) : ?>
                        <p>
                            <i class="fas fa-envelope"></i>
                            <?php echo esc_html($agent->user_email); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_hph_get_agent_preview', 'hph_ajax_get_agent_preview');
add_action('wp_ajax_nopriv_hph_get_agent_preview', 'hph_ajax_get_agent_preview');
