<?php

/**
 * Dashboard Open House Form Template Part
 *
 * @package HappyPlace
 */

// Get current open house data if editing
$open_house_id = $_GET['open_house_id'] ?? 0;
$open_house_data = [];
$available_listings = [];

// Get agent's listings for the dropdown
$current_user_id = get_current_user_id();
$listings_query = new WP_Query([
    'post_type' => 'listing',
    'author' => $current_user_id,
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

if ($listings_query->have_posts()) {
    while ($listings_query->have_posts()) {
        $listings_query->the_post();
        $available_listings[get_the_ID()] = get_the_title();
    }
    wp_reset_postdata();
}

if ($open_house_id) {
    // Get existing open house data
    $open_house_post = get_post($open_house_id);
    if ($open_house_post && $open_house_post->post_author == get_current_user_id()) {
        $open_house_data = [
            'ID' => $open_house_post->ID,
            'title' => $open_house_post->post_title,
            'content' => $open_house_post->post_content,
            'status' => $open_house_post->post_status,
        ];

        // Get custom fields
        if (function_exists('get_fields')) {
            $custom_fields = get_fields($open_house_post->ID);
            if (is_array($custom_fields)) {
                $open_house_data = array_merge($open_house_data, $custom_fields);
            }
        }
    }
}

$is_editing = !empty($open_house_data['ID']);
$form_title = $is_editing ? __('Edit Open House', 'happy-place') : __('Schedule Open House', 'happy-place');
?>

<div class="hph-dashboard-form-container">
    <div class="hph-section-header">
        <h2 class="hph-section-title">
            <i class="fas fa-<?php echo $is_editing ? 'edit' : 'calendar-plus'; ?>"></i>
            <?php echo esc_html($form_title); ?>
        </h2>
        <p class="hph-section-description">
            <?php echo $is_editing
                ? __('Update your open house event details and schedule.', 'happy-place')
                : __('Schedule a new open house event for one of your listings.', 'happy-place'); ?>
        </p>
    </div>

    <form id="hph-open-house-form" class="hph-dashboard-form">
        <?php wp_nonce_field('hph_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="save_open_house">
        <input type="hidden" name="open_house_id" value="<?php echo esc_attr($open_house_data['ID'] ?? ''); ?>">

        <div class="hph-form-grid">
            <!-- Property Selection -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-home"></i>
                    <?php _e('Property Information', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="listing_id" class="hph-form-label">
                        <?php _e('Select Property', 'happy-place'); ?> *
                    </label>
                    <select id="listing_id" name="listing_id" class="hph-form-select" required>
                        <option value=""><?php _e('Choose a listing...', 'happy-place'); ?></option>
                        <?php foreach ($available_listings as $listing_id => $listing_title) : ?>
                            <option value="<?php echo esc_attr($listing_id); ?>"
                                <?php selected($open_house_data['listing_id'] ?? '', $listing_id); ?>>
                                <?php echo esc_html($listing_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($available_listings)) : ?>
                        <p class="hph-form-note">
                            <i class="fas fa-info-circle"></i>
                            <?php _e('You need to have active listings before scheduling an open house.', 'happy-place'); ?>
                            <a href="<?php echo esc_url(add_query_arg(['action' => 'new-listing'], get_permalink())); ?>">
                                <?php _e('Add a listing first', 'happy-place'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>

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
            </div>

            <!-- Schedule Information -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <?php _e('Schedule Details', 'happy-place'); ?>
                </h3>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="start_date" class="hph-form-label">
                            <?php _e('Start Date', 'happy-place'); ?> *
                        </label>
                        <input type="date"
                            id="start_date"
                            name="start_date"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['start_date'] ?? ''); ?>"
                            min="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>

                    <div class="hph-form-group">
                        <label for="end_date" class="hph-form-label">
                            <?php _e('End Date', 'happy-place'); ?>
                        </label>
                        <input type="date"
                            id="end_date"
                            name="end_date"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['end_date'] ?? ''); ?>"
                            min="<?php echo date('Y-m-d'); ?>">
                        <p class="hph-form-note">
                            <?php _e('Leave blank if single day event', 'happy-place'); ?>
                        </p>
                    </div>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="start_time" class="hph-form-label">
                            <?php _e('Start Time', 'happy-place'); ?> *
                        </label>
                        <input type="time"
                            id="start_time"
                            name="start_time"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['start_time'] ?? '10:00'); ?>"
                            required>
                    </div>

                    <div class="hph-form-group">
                        <label for="end_time" class="hph-form-label">
                            <?php _e('End Time', 'happy-place'); ?> *
                        </label>
                        <input type="time"
                            id="end_time"
                            name="end_time"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['end_time'] ?? '14:00'); ?>"
                            required>
                    </div>
                </div>

                <div class="hph-form-group">
                    <label for="recurring" class="hph-form-label">
                        <?php _e('Recurring Event', 'happy-place'); ?>
                    </label>
                    <select id="recurring" name="recurring" class="hph-form-select">
                        <option value="none" <?php selected($open_house_data['recurring'] ?? 'none', 'none'); ?>>
                            <?php _e('No Repeat', 'happy-place'); ?>
                        </option>
                        <option value="weekly" <?php selected($open_house_data['recurring'] ?? '', 'weekly'); ?>>
                            <?php _e('Weekly', 'happy-place'); ?>
                        </option>
                        <option value="biweekly" <?php selected($open_house_data['recurring'] ?? '', 'biweekly'); ?>>
                            <?php _e('Every 2 Weeks', 'happy-place'); ?>
                        </option>
                        <option value="monthly" <?php selected($open_house_data['recurring'] ?? '', 'monthly'); ?>>
                            <?php _e('Monthly', 'happy-place'); ?>
                        </option>
                    </select>
                </div>

                <div id="recurring-options" class="hph-form-group" style="display: none;">
                    <label for="recurring_until" class="hph-form-label">
                        <?php _e('Repeat Until', 'happy-place'); ?>
                    </label>
                    <input type="date"
                        id="recurring_until"
                        name="recurring_until"
                        class="hph-form-input"
                        value="<?php echo esc_attr($open_house_data['recurring_until'] ?? ''); ?>"
                        min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <!-- Event Details -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-info-circle"></i>
                    <?php _e('Event Details', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="description" class="hph-form-label">
                        <?php _e('Event Description', 'happy-place'); ?>
                    </label>
                    <textarea id="description"
                        name="description"
                        class="hph-form-textarea"
                        rows="4"
                        placeholder="<?php esc_attr_e('Describe what visitors can expect, special features to highlight, parking instructions, etc.', 'happy-place'); ?>"><?php echo esc_textarea($open_house_data['content'] ?? ''); ?></textarea>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="max_visitors" class="hph-form-label">
                            <?php _e('Maximum Visitors', 'happy-place'); ?>
                        </label>
                        <input type="number"
                            id="max_visitors"
                            name="max_visitors"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['max_visitors'] ?? ''); ?>"
                            placeholder="20"
                            min="1"
                            max="100">
                        <p class="hph-form-note">
                            <?php _e('Leave blank for unlimited', 'happy-place'); ?>
                        </p>
                    </div>

                    <div class="hph-form-group">
                        <label for="registration_required" class="hph-form-label">
                            <?php _e('Registration Required', 'happy-place'); ?>
                        </label>
                        <select id="registration_required" name="registration_required" class="hph-form-select">
                            <option value="0" <?php selected($open_house_data['registration_required'] ?? '0', '0'); ?>>
                                <?php _e('No Registration', 'happy-place'); ?>
                            </option>
                            <option value="1" <?php selected($open_house_data['registration_required'] ?? '', '1'); ?>>
                                <?php _e('Registration Required', 'happy-place'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="hph-form-group">
                    <label for="special_instructions" class="hph-form-label">
                        <?php _e('Special Instructions', 'happy-place'); ?>
                    </label>
                    <textarea id="special_instructions"
                        name="special_instructions"
                        class="hph-form-textarea"
                        rows="3"
                        placeholder="<?php esc_attr_e('Parking information, entry instructions, COVID protocols, etc.', 'happy-place'); ?>"><?php echo esc_textarea($open_house_data['special_instructions'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Contact & Marketing -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-bullhorn"></i>
                    <?php _e('Marketing & Contact', 'happy-place'); ?>
                </h3>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="contact_phone" class="hph-form-label">
                            <?php _e('Contact Phone', 'happy-place'); ?>
                        </label>
                        <input type="tel"
                            id="contact_phone"
                            name="contact_phone"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['contact_phone'] ?? ''); ?>"
                            placeholder="(555) 123-4567">
                    </div>

                    <div class="hph-form-group">
                        <label for="contact_email" class="hph-form-label">
                            <?php _e('Contact Email', 'happy-place'); ?>
                        </label>
                        <input type="email"
                            id="contact_email"
                            name="contact_email"
                            class="hph-form-input"
                            value="<?php echo esc_attr($open_house_data['contact_email'] ?? get_userdata(get_current_user_id())->user_email); ?>"
                            placeholder="agent@email.com">
                    </div>
                </div>

                <div class="hph-form-group">
                    <label class="hph-form-label">
                        <?php _e('Marketing Options', 'happy-place'); ?>
                    </label>
                    <div class="hph-checkbox-group">
                        <label class="hph-checkbox-label">
                            <input type="checkbox"
                                name="promote_on_website"
                                value="1"
                                <?php checked($open_house_data['promote_on_website'] ?? '1', '1'); ?>>
                            <span><?php _e('Promote on website', 'happy-place'); ?></span>
                        </label>
                        <label class="hph-checkbox-label">
                            <input type="checkbox"
                                name="send_email_notifications"
                                value="1"
                                <?php checked($open_house_data['send_email_notifications'] ?? '1', '1'); ?>>
                            <span><?php _e('Send email notifications to leads', 'happy-place'); ?></span>
                        </label>
                        <label class="hph-checkbox-label">
                            <input type="checkbox"
                                name="allow_virtual_tour"
                                value="1"
                                <?php checked($open_house_data['allow_virtual_tour'] ?? '0', '1'); ?>>
                            <span><?php _e('Include virtual tour option', 'happy-place'); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-toggle-on"></i>
                    <?php _e('Event Status', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="event_status" class="hph-form-label">
                        <?php _e('Status', 'happy-place'); ?>
                    </label>
                    <select id="event_status" name="event_status" class="hph-form-select">
                        <option value="scheduled" <?php selected($open_house_data['event_status'] ?? 'scheduled', 'scheduled'); ?>>
                            <?php _e('Scheduled', 'happy-place'); ?>
                        </option>
                        <option value="cancelled" <?php selected($open_house_data['event_status'] ?? '', 'cancelled'); ?>>
                            <?php _e('Cancelled', 'happy-place'); ?>
                        </option>
                        <option value="completed" <?php selected($open_house_data['event_status'] ?? '', 'completed'); ?>>
                            <?php _e('Completed', 'happy-place'); ?>
                        </option>
                        <option value="draft" <?php selected($open_house_data['event_status'] ?? '', 'draft'); ?>>
                            <?php _e('Draft', 'happy-place'); ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="hph-form-actions">
            <button type="button" class="hph-btn hph-btn-secondary" onclick="history.back();">
                <i class="fas fa-arrow-left"></i>
                <?php _e('Cancel', 'happy-place'); ?>
            </button>

            <button type="submit" class="hph-btn hph-btn-primary" <?php echo empty($available_listings) ? 'disabled' : ''; ?>>
                <i class="fas fa-calendar-check"></i>
                <?php echo $is_editing ? __('Update Open House', 'happy-place') : __('Schedule Open House', 'happy-place'); ?>
            </button>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Handle recurring options visibility
        $('#recurring').on('change', function() {
            if ($(this).val() !== 'none') {
                $('#recurring-options').show();
            } else {
                $('#recurring-options').hide();
            }
        });

        // Trigger initial state
        $('#recurring').trigger('change');

        // Auto-update end date when start date changes
        $('#start_date').on('change', function() {
            const startDate = $(this).val();
            if (startDate && !$('#end_date').val()) {
                $('#end_date').val(startDate);
            }
            $('#end_date').attr('min', startDate);
        });

        // Validate time range
        $('#start_time, #end_time').on('change', function() {
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();

            if (startTime && endTime && startTime >= endTime) {
                alert('<?php _e("End time must be after start time", "happy-place"); ?>');
                $('#end_time').focus();
            }
        });

        // Handle form submission
        $('#hph-open-house-form').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> <?php _e("Scheduling...", "happy-place"); ?>').prop('disabled', true);

            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Redirect back to listings or open houses section
                        window.location.href = '<?php echo esc_url(remove_query_arg(["action", "open_house_id"])); ?>';
                    } else {
                        alert(response.data || '<?php _e("An error occurred while saving.", "happy-place"); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e("An error occurred while saving.", "happy-place"); ?>');
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>