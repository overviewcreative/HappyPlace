<?php
/**
 * Open House Form Template Part
 * 
 * Can be used in both dashboard and public contexts.
 * For dashboard, pass $context = 'dashboard'
 * For public, pass $context = 'public'
 * 
 * @package HappyPlace
 */

// Set defaults
$context = $context ?? 'public';
$open_house_id = isset($_GET['open_house_id']) ? intval($_GET['open_house_id']) : 0;
$open_house_data = [];

// Get current data if editing
if ($open_house_id) {
    $open_house = get_post($open_house_id);
    if ($open_house && $open_house->post_author == get_current_user_id()) {
        $open_house_data = [
            'ID' => $open_house->ID,
            'title' => $open_house->post_title,
            'content' => $open_house->post_content,
            'status' => $open_house->post_status,
        ];

        // Get custom fields
        if (function_exists('get_fields')) {
            $custom_fields = get_fields($open_house->ID);
            if (is_array($custom_fields)) {
                $open_house_data = array_merge($open_house_data, $custom_fields);
            }
        }
    }
}

$is_editing = !empty($open_house_data['ID']);
$form_title = $is_editing ? __('Edit Open House', 'happy-place') : __('Schedule Open House', 'happy-place');
?>

<form id="hph-open-house-form" class="hph-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hph_open_house_form', 'hph_open_house_nonce'); ?>
    <input type="hidden" name="action" value="hph_save_open_house">
    <?php if ($is_editing) : ?>
        <input type="hidden" name="open_house_id" value="<?php echo esc_attr($open_house_id); ?>">
    <?php endif; ?>

    <div class="hph-form-grid">
        <?php if ($context === 'dashboard') : ?>
            <!-- Header for dashboard context -->
            <div class="hph-section-header">
                <h2 class="hph-section-title">
                    <i class="fas fa-<?php echo $is_editing ? 'edit' : 'plus'; ?>"></i>
                    <?php echo esc_html($form_title); ?>
                </h2>
                <p class="hph-section-description">
                    <?php echo $is_editing
                        ? __('Update your open house event details.', 'happy-place')
                        : __('Schedule a new open house event.', 'happy-place'); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Property Information -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <i class="fas fa-home"></i>
                <?php _e('Property Information', 'happy-place'); ?>
            </h3>

            <div class="hph-form-group">
                <label for="listing_id" class="hph-form-label">
                    <?php _e('Select Property', 'happy-place'); ?> *
                </label>
                <?php 
                // Get agent's listings
                $listings_query = new WP_Query([
                    'post_type' => 'listing',
                    'author' => get_current_user_id(),
                    'post_status' => ['publish', 'private'],
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'listing_status',
                            'value' => ['active', 'pending'],
                            'compare' => 'IN'
                        ]
                    ]
                ]);
                ?>
                
                <select id="listing_id" name="listing_id" class="hph-form-select" required>
                    <option value=""><?php _e('Choose a listing...', 'happy-place'); ?></option>
                    <?php 
                    if ($listings_query->have_posts()) :
                        while ($listings_query->have_posts()) : 
                            $listings_query->the_post();
                            $listing_id = get_the_ID();
                            $address = get_post_meta($listing_id, 'listing_address', true);
                            $title = $address ? $address : get_the_title();
                    ?>
                            <option value="<?php echo esc_attr($listing_id); ?>"
                                <?php selected($open_house_data['listing_id'] ?? '', $listing_id); ?>>
                                <?php echo esc_html($title); ?>
                            </option>
                    <?php 
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </select>
                
                <?php if (!$listings_query->have_posts()) : ?>
                    <p class="hph-form-note hph-text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php _e('You need to have active listings before scheduling an open house.', 'happy-place'); ?>
                        <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'], remove_query_arg('open_house_id'))); ?>" class="hph-link">
                            <?php _e('Add a listing first', 'happy-place'); ?>
                        </a>
                    </p>
                <?php endif; ?>

            </div>

            <!-- Event Details -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <?php _e('Event Details', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="open_house_title" class="hph-form-label">
                        <?php _e('Event Title', 'happy-place'); ?>
                    </label>
                    <input type="text"
                           id="open_house_title"
                           name="open_house_title"
                           class="hph-form-input"
                           value="<?php echo esc_attr($open_house_data['title'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('e.g., Open House - Beautiful Family Home', 'happy-place'); ?>">
                    <p class="hph-form-note">
                        <?php _e('Leave blank to auto-generate from property title', 'happy-place'); ?>
                    </p>
                </div>

                <div class="hph-form-row hph-form-row--3-col">
                    <div class="hph-form-group">
                        <label for="open_house_date" class="hph-form-label">
                            <?php _e('Date', 'happy-place'); ?> *
                        </label>
                        <input type="date"
                               id="open_house_date"
                               name="open_house_date"
                               class="hph-form-input"
                               value="<?php echo esc_attr($open_house_data['date'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>

                    <div class="hph-form-group">
                        <label for="open_house_start_time" class="hph-form-label">
                            <?php _e('Start Time', 'happy-place'); ?> *
                        </label>
                        <input type="time"
                               id="open_house_start_time"
                               name="open_house_start_time"
                               class="hph-form-input"
                               value="<?php echo esc_attr($open_house_data['start_time'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="hph-form-group">
                        <label for="open_house_end_time" class="hph-form-label">
                            <?php _e('End Time', 'happy-place'); ?> *
                        </label>
                        <input type="time"
                               id="open_house_end_time"
                               name="open_house_end_time"
                               class="hph-form-input"
                               value="<?php echo esc_attr($open_house_data['end_time'] ?? ''); ?>"
                               required>
                    </div>
                </div>

                <div class="hph-form-group">
                    <label for="open_house_host" class="hph-form-label">
                        <?php _e('Host', 'happy-place'); ?>
                    </label>
                    <input type="text"
                           id="open_house_host"
                           name="open_house_host"
                           class="hph-form-input"
                           value="<?php echo esc_attr($open_house_data['host'] ?? wp_get_current_user()->display_name); ?>"
                           placeholder="<?php esc_attr_e('Who will be hosting this open house?', 'happy-place'); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="open_house_max_visitors" class="hph-form-label">
                        <?php _e('Maximum Visitors', 'happy-place'); ?>
                    </label>
                    <input type="number"
                           id="open_house_max_visitors"
                           name="open_house_max_visitors"
                           class="hph-form-input"
                           value="<?php echo esc_attr($open_house_data['max_visitors'] ?? ''); ?>"
                           min="1"
                           placeholder="<?php esc_attr_e('Leave blank for no limit', 'happy-place'); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="open_house_notes" class="hph-form-label">
                        <?php _e('Notes', 'happy-place'); ?>
                    </label>
                    <textarea id="open_house_notes"
                            name="open_house_notes"
                            class="hph-form-textarea"
                            rows="4"
                            placeholder="<?php esc_attr_e('Any special instructions for visitors, parking info, etc.', 'happy-place'); ?>"><?php 
                        echo esc_textarea($open_house_data['notes'] ?? '');
                    ?></textarea>
                </div>
            </div>
                        value="<?php echo esc_attr($open_house_data['end_time'] ?? ''); ?>" required>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="hph-form-section">
            <h3>Additional Information</h3>
            
            <div class="hph-form-row">
                <label for="open_house_notes">Notes</label>
                <textarea name="open_house_notes" id="open_house_notes" rows="4"><?php 
                    echo esc_textarea($open_house_data['notes'] ?? ''); 
                ?></textarea>
            </div>

            <div class="hph-form-row">
                <label>
                    <input type="checkbox" name="open_house_refreshments" id="open_house_refreshments" 
                        <?php checked($open_house_data['refreshments'] ?? false); ?>>
                    Refreshments will be served
                </label>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="hph-form-actions">
        <button type="submit" class="hph-button hph-button--primary">
            <?php echo $open_house_id ? 'Update Open House' : 'Schedule Open House'; ?>
        </button>
    </div>
</form>
