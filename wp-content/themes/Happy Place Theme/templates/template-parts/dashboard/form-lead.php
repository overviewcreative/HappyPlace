<?php

/**
 * Dashboard Lead Form Template Part
 *
 * @package HappyPlace
 */

// Get current lead data if editing
$lead_id = $_GET['lead_id'] ?? 0;
$lead_data = [];

if ($lead_id) {
    // Get existing lead data
    $lead_post = get_post($lead_id);
    if ($lead_post && $lead_post->post_author == get_current_user_id()) {
        $lead_data = [
            'ID' => $lead_post->ID,
            'title' => $lead_post->post_title,
            'content' => $lead_post->post_content,
            'status' => $lead_post->post_status,
        ];

        // Get custom fields
        if (function_exists('get_fields')) {
            $custom_fields = get_fields($lead_post->ID);
            if (is_array($custom_fields)) {
                $lead_data = array_merge($lead_data, $custom_fields);
            }
        }
    }
}

$is_editing = !empty($lead_data['ID']);
$form_title = $is_editing ? __('Edit Lead', 'happy-place') : __('Add New Lead', 'happy-place');

// Lead status options
$lead_statuses = [
    'new' => __('New', 'happy-place'),
    'contacted' => __('Contacted', 'happy-place'),
    'qualified' => __('Qualified', 'happy-place'),
    'showing' => __('Showing Scheduled', 'happy-place'),
    'negotiating' => __('Negotiating', 'happy-place'),
    'closed' => __('Closed', 'happy-place'),
    'lost' => __('Lost', 'happy-place'),
];

// Lead sources
$lead_sources = [
    'website' => __('Website', 'happy-place'),
    'referral' => __('Referral', 'happy-place'),
    'social_media' => __('Social Media', 'happy-place'),
    'advertising' => __('Advertising', 'happy-place'),
    'open_house' => __('Open House', 'happy-place'),
    'cold_call' => __('Cold Call', 'happy-place'),
    'walk_in' => __('Walk-in', 'happy-place'),
    'other' => __('Other', 'happy-place'),
];

// Property types they're interested in
$property_types = [
    'residential' => __('Residential', 'happy-place'),
    'commercial' => __('Commercial', 'happy-place'),
    'land' => __('Land', 'happy-place'),
    'investment' => __('Investment Property', 'happy-place'),
];
?>

<div class="hph-dashboard-form-container">
    <div class="hph-form-header">
        <h2 class="hph-section-title">
            <i class="fas fa-<?php echo $is_editing ? 'edit' : 'user-plus'; ?>"></i>
            <?php echo esc_html($form_title); ?>
        </h2>
        <p class="hph-section-description">
            <?php echo $is_editing
                ? __('Update lead information and track their progress.', 'happy-place')
                : __('Add a new lead and track their journey through your sales process.', 'happy-place'); ?>
        </p>
    </div>

    <form id="lead-form" class="hph-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('hph_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="save_lead">
        <?php if ($is_editing) : ?>
            <input type="hidden" name="lead_id" value="<?php echo esc_attr($lead_data['ID']); ?>">
        <?php endif; ?>

        <div class="hph-form-body">
            <!-- Lead Information Section -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-user"></i>
                    <?php _e('Contact Information', 'happy-place'); ?>
                </h3>

                <div class="hph-form-grid hph-form-grid--2-col">
                    <div class="hph-form-field">
                        <label for="first_name" class="hph-form-label">
                            <?php _e('First Name', 'happy-place'); ?>
                            <span class="hph-form-required">*</span>
                        </label>
                        <input type="text"
                            id="first_name"
                            name="first_name"
                            class="hph-form-input"
                            value="<?php echo esc_attr($lead_data['first_name'] ?? ''); ?>"
                            required>
                    </div>

                    <div class="hph-form-field">
                        <label for="last_name" class="hph-form-label">
                            <?php _e('Last Name', 'happy-place'); ?>
                            <span class="hph-form-required">*</span>
                        </label>
                        <input type="text"
                            id="last_name"
                            name="last_name"
                            class="hph-form-input"
                            value="<?php echo esc_attr($lead_data['last_name'] ?? ''); ?>"
                            required>
                    </div>

                    <div class="hph-form-field">
                        <label for="email" class="hph-form-label">
                            <?php _e('Email Address', 'happy-place'); ?>
                            <span class="hph-form-required">*</span>
                        </label>
                        <input type="email"
                            id="email"
                            name="email"
                            class="hph-form-input"
                            value="<?php echo esc_attr($lead_data['email'] ?? ''); ?>"
                            required>
                    </div>

                    <div class="hph-form-field">
                        <label for="phone" class="hph-form-label">
                            <?php _e('Phone Number', 'happy-place'); ?>
                        </label>
                        <input type="tel"
                            id="phone"
                            name="phone"
                            class="hph-form-input"
                            value="<?php echo esc_attr($lead_data['phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Lead Details Section -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-info-circle"></i>
                    <?php _e('Lead Details', 'happy-place'); ?>
                </h3>

                <div class="hph-form-grid hph-form-grid--3-col">
                    <div class="hph-form-field">
                        <label for="lead_status" class="hph-form-label">
                            <?php _e('Status', 'happy-place'); ?>
                        </label>
                        <select id="lead_status" name="lead_status" class="hph-form-select">
                            <?php foreach ($lead_statuses as $status_key => $status_label) : ?>
                                <option value="<?php echo esc_attr($status_key); ?>"
                                    <?php selected($lead_data['lead_status'] ?? 'new', $status_key); ?>>
                                    <?php echo esc_html($status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="hph-form-field">
                        <label for="lead_source" class="hph-form-label">
                            <?php _e('Lead Source', 'happy-place'); ?>
                        </label>
                        <select id="lead_source" name="lead_source" class="hph-form-select">
                            <option value=""><?php _e('Select Source', 'happy-place'); ?></option>
                            <?php foreach ($lead_sources as $source_key => $source_label) : ?>
                                <option value="<?php echo esc_attr($source_key); ?>"
                                    <?php selected($lead_data['lead_source'] ?? '', $source_key); ?>>
                                    <?php echo esc_html($source_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="hph-form-field">
                        <label for="property_type_interest" class="hph-form-label">
                            <?php _e('Property Interest', 'happy-place'); ?>
                        </label>
                        <select id="property_type_interest" name="property_type_interest" class="hph-form-select">
                            <option value=""><?php _e('Select Type', 'happy-place'); ?></option>
                            <?php foreach ($property_types as $type_key => $type_label) : ?>
                                <option value="<?php echo esc_attr($type_key); ?>"
                                    <?php selected($lead_data['property_type_interest'] ?? '', $type_key); ?>>
                                    <?php echo esc_html($type_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="hph-form-grid hph-form-grid--2-col">
                    <div class="hph-form-field">
                        <label for="budget_min" class="hph-form-label">
                            <?php _e('Budget Range (Min)', 'happy-place'); ?>
                        </label>
                        <div class="hph-form-price">
                            <input type="number"
                                id="budget_min"
                                name="budget_min"
                                class="hph-form-input"
                                value="<?php echo esc_attr($lead_data['budget_min'] ?? ''); ?>"
                                min="0"
                                step="1000">
                        </div>
                    </div>

                    <div class="hph-form-field">
                        <label for="budget_max" class="hph-form-label">
                            <?php _e('Budget Range (Max)', 'happy-place'); ?>
                        </label>
                        <div class="hph-form-price">
                            <input type="number"
                                id="budget_max"
                                name="budget_max"
                                class="hph-form-input"
                                value="<?php echo esc_attr($lead_data['budget_max'] ?? ''); ?>"
                                min="0"
                                step="1000">
                        </div>
                    </div>
                </div>

                <div class="hph-form-field">
                    <label for="preferred_location" class="hph-form-label">
                        <?php _e('Preferred Location/Areas', 'happy-place'); ?>
                    </label>
                    <input type="text"
                        id="preferred_location"
                        name="preferred_location"
                        class="hph-form-input"
                        value="<?php echo esc_attr($lead_data['preferred_location'] ?? ''); ?>"
                        placeholder="<?php _e('Enter preferred neighborhoods or areas', 'happy-place'); ?>">
                </div>
            </div>

            <!-- Timeline & Requirements -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <?php _e('Timeline & Requirements', 'happy-place'); ?>
                </h3>

                <div class="hph-form-grid hph-form-grid--2-col">
                    <div class="hph-form-field">
                        <label for="timeline" class="hph-form-label">
                            <?php _e('Buying Timeline', 'happy-place'); ?>
                        </label>
                        <select id="timeline" name="timeline" class="hph-form-select">
                            <option value=""><?php _e('Select Timeline', 'happy-place'); ?></option>
                            <option value="immediately" <?php selected($lead_data['timeline'] ?? '', 'immediately'); ?>>
                                <?php _e('Immediately', 'happy-place'); ?>
                            </option>
                            <option value="1-3_months" <?php selected($lead_data['timeline'] ?? '', '1-3_months'); ?>>
                                <?php _e('1-3 Months', 'happy-place'); ?>
                            </option>
                            <option value="3-6_months" <?php selected($lead_data['timeline'] ?? '', '3-6_months'); ?>>
                                <?php _e('3-6 Months', 'happy-place'); ?>
                            </option>
                            <option value="6-12_months" <?php selected($lead_data['timeline'] ?? '', '6-12_months'); ?>>
                                <?php _e('6-12 Months', 'happy-place'); ?>
                            </option>
                            <option value="over_1_year" <?php selected($lead_data['timeline'] ?? '', 'over_1_year'); ?>>
                                <?php _e('Over 1 Year', 'happy-place'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="hph-form-field">
                        <label for="financing" class="hph-form-label">
                            <?php _e('Financing Status', 'happy-place'); ?>
                        </label>
                        <select id="financing" name="financing" class="hph-form-select">
                            <option value=""><?php _e('Select Status', 'happy-place'); ?></option>
                            <option value="cash" <?php selected($lead_data['financing'] ?? '', 'cash'); ?>>
                                <?php _e('Cash Buyer', 'happy-place'); ?>
                            </option>
                            <option value="pre_approved" <?php selected($lead_data['financing'] ?? '', 'pre_approved'); ?>>
                                <?php _e('Pre-approved', 'happy-place'); ?>
                            </option>
                            <option value="need_financing" <?php selected($lead_data['financing'] ?? '', 'need_financing'); ?>>
                                <?php _e('Need Financing', 'happy-place'); ?>
                            </option>
                            <option value="unsure" <?php selected($lead_data['financing'] ?? '', 'unsure'); ?>>
                                <?php _e('Unsure', 'happy-place'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="hph-form-field">
                    <label for="first_time_buyer" class="hph-form-label">
                        <?php _e('First Time Buyer', 'happy-place'); ?>
                    </label>
                    <div class="hph-form-toggle">
                        <label class="hph-toggle-switch">
                            <input type="checkbox"
                                name="first_time_buyer"
                                value="1"
                                <?php checked($lead_data['first_time_buyer'] ?? 0, 1); ?>>
                            <span class="hph-toggle-slider"></span>
                        </label>
                        <span><?php _e('This is a first-time buyer', 'happy-place'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-sticky-note"></i>
                    <?php _e('Notes & Comments', 'happy-place'); ?>
                </h3>

                <div class="hph-form-field">
                    <label for="lead_notes" class="hph-form-label">
                        <?php _e('Lead Notes', 'happy-place'); ?>
                    </label>
                    <textarea id="lead_notes"
                        name="lead_notes"
                        class="hph-form-textarea"
                        rows="6"
                        placeholder="<?php _e('Add any additional notes about this lead...', 'happy-place'); ?>"><?php echo esc_textarea($lead_data['lead_notes'] ?? ''); ?></textarea>
                    <div class="hph-form-help">
                        <?php _e('Include any important information, preferences, or conversation notes.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="hph-form-actions">
            <a href="<?php echo esc_url(add_query_arg(['section' => 'leads'], get_permalink())); ?>"
                class="hph-btn hph-btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <?php _e('Back to Leads', 'happy-place'); ?>
            </a>

            <button type="button" class="hph-btn hph-btn-outline" id="save-draft">
                <i class="fas fa-save"></i>
                <?php _e('Save Draft', 'happy-place'); ?>
            </button>

            <button type="submit" class="hph-btn hph-btn-primary">
                <i class="fas fa-check"></i>
                <?php echo $is_editing ? __('Update Lead', 'happy-place') : __('Save Lead', 'happy-place'); ?>
            </button>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Form validation
        $('#lead-form').on('submit', function(e) {
            var isValid = true;
            var requiredFields = ['first_name', 'last_name', 'email'];

            // Clear previous errors
            $('.hph-form-field').removeClass('has-error');
            $('.hph-form-error').remove();

            requiredFields.forEach(function(field) {
                var $field = $('#' + field);
                if (!$field.val().trim()) {
                    isValid = false;
                    $field.closest('.hph-form-field').addClass('has-error');
                    $field.after('<div class="hph-form-error"><i class="fas fa-exclamation-circle"></i> This field is required.</div>');
                }
            });

            // Email validation
            var email = $('#email').val();
            if (email && !isValidEmail(email)) {
                isValid = false;
                $('#email').closest('.hph-form-field').addClass('has-error');
                $('#email').after('<div class="hph-form-error"><i class="fas fa-exclamation-circle"></i> Please enter a valid email address.</div>');
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                $('html, body').animate({
                    scrollTop: $('.has-error').first().offset().top - 100
                }, 500);
            }
        });

        // Save draft functionality
        $('#save-draft').on('click', function() {
            var formData = $('#lead-form').serialize();
            formData += '&action=save_lead_draft';

            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    showToast('success', 'Draft saved successfully!');
                } else {
                    showToast('error', 'Failed to save draft.');
                }
            });
        });

        function isValidEmail(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showToast(type, message) {
            // Add toast notification (you can implement this based on your toast system)
            console.log(type + ': ' + message);
        }
    });
</script>