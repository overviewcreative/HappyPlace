<?php
/**
 * Form Helper Functions
 * 
 * @package HappyPlace
 */

/**
 * Format price for display
 */
function hph_format_price($price) {
    if (!$price) return '';

    // Remove any existing formatting
    $price = str_replace(['$', ','], '', $price);
    
    // Convert to float
    $price = floatval($price);

    // Format with $ and commas
    return '$' . number_format($price, 0);
}

/**
 * Get form error messages
 */
function hph_get_form_errors($form_action) {
    if (!session_id()) {
        session_start();
    }

    $errors = $_SESSION[$form_action . '_errors'] ?? [];
    unset($_SESSION[$form_action . '_errors']);
    return $errors;
}

/**
 * Get form previous data
 */
function hph_get_form_data($form_action) {
    if (!session_id()) {
        session_start();
    }

    $data = $_SESSION[$form_action . '_data'] ?? [];
    unset($_SESSION[$form_action . '_data']);
    return $data;
}

/**
 * Display form messages
 */
function hph_display_form_messages() {
    if (!session_id()) {
        session_start();
    }

    $messages = HPH_Form_Handler::get_messages();
    foreach ($messages as $message) {
        printf(
            '<div class="hph-message hph-message--%s">%s</div>',
            esc_attr($message['type']),
            esc_html($message['message'])
        );
    }
}

/**
 * Get available time slots
 */
function hph_get_time_slots($start_hour = 8, $end_hour = 20, $interval = 30) {
    $slots = [];
    
    for ($hour = $start_hour; $hour <= $end_hour; $hour++) {
        foreach ([0, 30] as $minute) {
            if ($hour === $end_hour && $minute > 0) continue;
            
            $time = sprintf(
                '%02d:%02d',
                $hour,
                $minute
            );
            
            $slots[$time] = date('g:i A', strtotime($time));
        }
    }
    
    return $slots;
}

/**
 * Check if a timeslot is available
 */
function hph_is_timeslot_available($listing_id, $date, $time) {
    global $wpdb;

    // Check existing open houses
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm1
         INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
         INNER JOIN {$wpdb->postmeta} pm3 ON pm1.post_id = pm3.post_id
         WHERE pm1.meta_key = 'listing_id' AND pm1.meta_value = %d
         AND pm2.meta_key = 'open_house_date' AND pm2.meta_value = %s
         AND pm3.meta_key = 'open_house_time' AND pm3.meta_value = %s",
        $listing_id,
        $date,
        $time
    ));

    return !$existing;
}

/**
 * Get agent's available hours
 */
function hph_get_agent_hours($agent_id) {
    $hours = get_user_meta($agent_id, 'agent_hours', true);
    if (!$hours) return false;

    $schedule = [];
    foreach ($hours as $day => $times) {
        if (empty($times['start']) || empty($times['end'])) continue;
        
        $schedule[$day] = [
            'start' => date('H:i', strtotime($times['start'])),
            'end' => date('H:i', strtotime($times['end']))
        ];
    }

    return $schedule;
}

/**
 * Check if an agent is available at a specific time
 */
function hph_is_agent_available($agent_id, $date, $time) {
    // Get day of week
    $day = strtolower(date('l', strtotime($date)));
    
    // Get agent's hours
    $hours = hph_get_agent_hours($agent_id);
    if (!$hours || !isset($hours[$day])) return false;

    // Check if time is within hours
    $time = date('H:i', strtotime($time));
    return $time >= $hours[$day]['start'] && $time <= $hours[$day]['end'];
}

/**
 * Get property preview HTML
 */
function hph_get_property_preview_html($property_id) {
    if (!$property_id) return '';

    $property = get_post($property_id);
    if (!$property) return '';

    $price = get_post_meta($property_id, 'property_price', true);
    $address = get_post_meta($property_id, 'property_address', true);
    $beds = get_post_meta($property_id, 'property_beds', true);
    $baths = get_post_meta($property_id, 'property_baths', true);
    $sqft = get_post_meta($property_id, 'property_sqft', true);

    ob_start();
    ?>
    <div class="hph-property-summary">
        <?php if (has_post_thumbnail($property_id)) : ?>
            <div class="hph-property-image">
                <?php echo get_the_post_thumbnail($property_id, 'thumbnail'); ?>
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
    <?php
    return ob_get_clean();
}

/**
 * Get agent preview HTML
 */
function hph_get_agent_preview_html($agent_id) {
    if (!$agent_id) return '';

    $agent = get_userdata($agent_id);
    if (!$agent) return '';

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
    return ob_get_clean();
}
