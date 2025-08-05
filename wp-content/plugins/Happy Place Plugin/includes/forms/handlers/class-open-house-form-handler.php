<?php
/**
 * Open House Form Handler - Handles open house scheduling and management
 * 
 * Manages frontend and admin forms for scheduling open houses with
 * flexible agent assignment and comprehensive tracking.
 * 
 * @package HappyPlace
 * @subpackage Forms\Handlers
 */

namespace HappyPlace\Forms\Handlers;

use HappyPlace\Forms\Base_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Open_House_Form_Handler extends Base_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('open_house', 'open-house');
    }
    
    /**
     * Setup validation rules for open house forms
     */
    protected function setup_validation_rules() {
        $this->required_fields = [
            'post_title',
            'listing',
            'hosting_agent',
            'open_house_date',
            'start_time',
            'end_time'
        ];
        
        $this->validation_rules = [
            'listing' => [
                'post_exists' => true,
                'sanitize' => 'absint'
            ],
            'hosting_agent' => [
                'post_exists' => true,
                'sanitize' => 'absint'
            ],
            'open_house_date' => [
                'sanitize' => [$this, 'sanitize_date']
            ],
            'start_time' => [
                'sanitize' => [$this, 'sanitize_time']
            ],
            'end_time' => [
                'sanitize' => [$this, 'sanitize_time']
            ],
            'max_attendees' => [
                'numeric' => true,
                'sanitize' => 'absint'
            ],
            'contact_info' => [
                'sanitize' => [$this, 'sanitize_contact_info']
            ],
            'total_attendees' => [
                'numeric' => true,
                'sanitize' => 'absint'
            ],
            'leads_generated' => [
                'numeric' => true,
                'sanitize' => 'absint'
            ],
            'follow_ups_needed' => [
                'numeric' => true,
                'sanitize' => 'absint'
            ]
        ];
    }
    
    /**
     * Process open house form submission
     *
     * @param array $form_data Validated form data
     * @return int|WP_Error Post ID on success, WP_Error on failure
     */
    protected function process_submission($form_data) {
        $post_id = isset($form_data['post_id']) ? absint($form_data['post_id']) : 0;
        $is_new = !$post_id;
        
        // Generate title if not provided
        if (empty($form_data['post_title'])) {
            $form_data['post_title'] = $this->generate_open_house_title($form_data);
        }
        
        // Prepare post data
        $post_data = [
            'post_title' => $form_data['post_title'],
            'post_content' => $form_data['description'] ?? '',
            'post_type' => 'open-house',
            'post_status' => $this->determine_post_status($form_data),
            'meta_input' => []
        ];
        
        if ($post_id) {
            // Update existing post
            $post_data['ID'] = $post_id;
            $result = wp_update_post($post_data);
        } else {
            // Create new post
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $post_id = $result;
        
        // Save ACF fields
        $this->save_acf_fields($post_id, $form_data);
        
        // Auto-populate contact info from hosting agent if not provided
        $this->auto_populate_contact_info($post_id, $form_data);
        
        // Check for scheduling conflicts
        $conflict_check = $this->check_scheduling_conflicts($post_id, $form_data);
        if (is_wp_error($conflict_check)) {
            // Log warning but don't fail the submission
            error_log('HPH Open House: Scheduling conflict detected for post ' . $post_id . ': ' . $conflict_check->get_error_message());
        }
        
        // Send notifications
        $this->send_notifications($post_id, $form_data, $is_new);
        
        // Create calendar events if applicable
        $this->create_calendar_event($post_id, $form_data);
        
        return $post_id;
    }
    
    /**
     * Custom validation for open house data
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    protected function custom_validation($form_data) {
        $errors = [];
        
        // Date validation - must be in the future
        if (isset($form_data['open_house_date'])) {
            $open_date = strtotime($form_data['open_house_date']);
            $today = strtotime('today');
            
            if ($open_date < $today) {
                $errors['open_house_date'] = __('Open house date must be in the future', 'happy-place');
            }
            
            // Don't allow scheduling more than 6 months in advance
            $six_months = strtotime('+6 months');
            if ($open_date > $six_months) {
                $errors['open_house_date'] = __('Open house cannot be scheduled more than 6 months in advance', 'happy-place');
            }
        }
        
        // Time validation
        if (isset($form_data['start_time'], $form_data['end_time'])) {
            $start_time = strtotime($form_data['start_time']);
            $end_time = strtotime($form_data['end_time']);
            
            if ($end_time <= $start_time) {
                $errors['end_time'] = __('End time must be after start time', 'happy-place');
            }
            
            // Check for reasonable duration (minimum 30 minutes, maximum 8 hours)
            $duration = $end_time - $start_time;
            if ($duration < 1800) { // 30 minutes
                $errors['end_time'] = __('Open house must be at least 30 minutes long', 'happy-place');
            }
            if ($duration > 28800) { // 8 hours
                $errors['end_time'] = __('Open house cannot exceed 8 hours', 'happy-place');
            }
        }
        
        // Listing validation
        if (isset($form_data['listing'])) {
            $listing = get_post($form_data['listing']);
            if (!$listing || $listing->post_type !== 'listing') {
                $errors['listing'] = __('Please select a valid listing', 'happy-place');
            } else {
                // Check if listing is active
                $listing_status = get_field('status', $form_data['listing']);
                if ($listing_status && !in_array($listing_status, ['active', 'pending', 'contingent'])) {
                    $errors['listing'] = __('Cannot schedule open house for inactive listings', 'happy-place');
                }
            }
        }
        
        // Hosting agent validation
        if (isset($form_data['hosting_agent'])) {
            $agent = get_post($form_data['hosting_agent']);
            if (!$agent || $agent->post_type !== 'agent') {
                $errors['hosting_agent'] = __('Please select a valid agent', 'happy-place');
            } else {
                // Check if agent is active
                $agent_status = get_field('status', $form_data['hosting_agent']);
                if ($agent_status === 'inactive') {
                    $errors['hosting_agent'] = __('Selected agent is not currently active', 'happy-place');
                }
            }
        }
        
        // Max attendees validation
        if (isset($form_data['max_attendees']) && !empty($form_data['max_attendees'])) {
            $max_attendees = intval($form_data['max_attendees']);
            if ($max_attendees < 1 || $max_attendees > 500) {
                $errors['max_attendees'] = __('Maximum attendees must be between 1 and 500', 'happy-place');
            }
        }
        
        // Business hours validation (optional warning)
        if (isset($form_data['start_time'])) {
            $start_hour = date('H', strtotime($form_data['start_time']));
            if ($start_hour < 8 || $start_hour > 20) {
                // This is a warning, not an error
                $errors['start_time_warning'] = __('Consider scheduling during typical business hours (8 AM - 8 PM)', 'happy-place');
            }
        }
        
        return $errors;
    }
    
    /**
     * Render open house preview
     *
     * @param array $form_data Form data
     */
    protected function render_preview($form_data) {
        $listing_title = '';
        $agent_name = '';
        
        if (isset($form_data['listing'])) {
            $listing_title = get_the_title($form_data['listing']);
        }
        
        if (isset($form_data['hosting_agent'])) {
            $agent_name = get_the_title($form_data['hosting_agent']);
        }
        
        $formatted_date = isset($form_data['open_house_date']) ? 
            date('F j, Y', strtotime($form_data['open_house_date'])) : 'Date TBD';
        
        $formatted_time = '';
        if (isset($form_data['start_time'], $form_data['end_time'])) {
            $formatted_time = date('g:i A', strtotime($form_data['start_time'])) . 
                            ' - ' . 
                            date('g:i A', strtotime($form_data['end_time']));
        }
        
        ?>
        <div class="open-house-preview">
            <div class="preview-header">
                <h4><?php echo esc_html($form_data['post_title'] ?? __('New Open House', 'happy-place')); ?></h4>
                <div class="preview-status status-<?php echo esc_attr($form_data['status'] ?? 'scheduled'); ?>">
                    <?php echo esc_html(ucfirst($form_data['status'] ?? 'scheduled')); ?>
                </div>
            </div>
            
            <div class="preview-details">
                <?php if ($listing_title): ?>
                    <div class="preview-listing">
                        <strong><?php _e('Property:', 'happy-place'); ?></strong>
                        <?php echo esc_html($listing_title); ?>
                    </div>
                <?php endif; ?>
                
                <div class="preview-datetime">
                    <div class="preview-date">
                        <strong><?php _e('Date:', 'happy-place'); ?></strong>
                        <?php echo esc_html($formatted_date); ?>
                    </div>
                    <?php if ($formatted_time): ?>
                        <div class="preview-time">
                            <strong><?php _e('Time:', 'happy-place'); ?></strong>
                            <?php echo esc_html($formatted_time); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($agent_name): ?>
                    <div class="preview-agent">
                        <strong><?php _e('Hosting Agent:', 'happy-place'); ?></strong>
                        <?php echo esc_html($agent_name); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['max_attendees'])): ?>
                    <div class="preview-attendees">
                        <strong><?php _e('Max Attendees:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['max_attendees']); ?> people
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['registration_required'])): ?>
                    <div class="preview-registration">
                        <strong><?php _e('Registration:', 'happy-place'); ?></strong>
                        <?php _e('Required', 'happy-place'); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['description'])): ?>
                    <div class="preview-description">
                        <strong><?php _e('Description:', 'happy-place'); ?></strong>
                        <?php echo wp_kses_post(wp_trim_words($form_data['description'], 20)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Generate automatic title for open house
     *
     * @param array $form_data Form data
     * @return string Generated title
     */
    private function generate_open_house_title($form_data) {
        $title_parts = [];
        
        // Add "Open House" prefix
        $title_parts[] = __('Open House', 'happy-place');
        
        // Add listing title/address if available
        if (isset($form_data['listing'])) {
            $listing_title = get_the_title($form_data['listing']);
            if ($listing_title) {
                $title_parts[] = '-';
                $title_parts[] = $listing_title;
            }
        }
        
        // Add date if available
        if (isset($form_data['open_house_date'])) {
            $title_parts[] = '-';
            $title_parts[] = date('M j', strtotime($form_data['open_house_date']));
        }
        
        return implode(' ', $title_parts);
    }
    
    /**
     * Determine post status
     *
     * @param array $form_data Form data
     * @return string Post status
     */
    private function determine_post_status($form_data) {
        // Draft if required fields are missing
        foreach ($this->required_fields as $field) {
            if (empty($form_data[$field])) {
                return 'draft';
            }
        }
        
        // Check user permissions
        if (!current_user_can('publish_posts')) {
            return 'pending';
        }
        
        return $form_data['post_status'] ?? 'publish';
    }
    
    /**
     * Save ACF fields
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function save_acf_fields($post_id, $form_data) {
        $acf_fields = [
            'listing',
            'hosting_agent',
            'open_house_date',
            'start_time',
            'end_time',
            'status',
            'registration_required',
            'max_attendees',
            'description',
            'special_instructions',
            'contact_info',
            'attendee_tracking'
        ];
        
        foreach ($acf_fields as $field) {
            if (isset($form_data[$field])) {
                update_field($field, $form_data[$field], $post_id);
            }
        }
        
        // Handle nested contact info fields
        if (isset($form_data['contact_phone']) || isset($form_data['contact_email'])) {
            $contact_info = [
                'phone' => $form_data['contact_phone'] ?? '',
                'email' => $form_data['contact_email'] ?? ''
            ];
            update_field('contact_info', $contact_info, $post_id);
        }
        
        // Handle attendee tracking fields (admin only)
        if (current_user_can('edit_posts')) {
            $tracking_fields = ['total_attendees', 'leads_generated', 'follow_ups_needed'];
            $attendee_tracking = [];
            
            foreach ($tracking_fields as $field) {
                if (isset($form_data[$field])) {
                    $attendee_tracking[$field] = $form_data[$field];
                }
            }
            
            if (!empty($attendee_tracking)) {
                update_field('attendee_tracking', $attendee_tracking, $post_id);
            }
        }
    }
    
    /**
     * Auto-populate contact info from hosting agent
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function auto_populate_contact_info($post_id, $form_data) {
        $contact_info = get_field('contact_info', $post_id);
        
        // Only auto-populate if contact info is empty
        if (empty($contact_info['phone']) && empty($contact_info['email']) && isset($form_data['hosting_agent'])) {
            $agent_id = $form_data['hosting_agent'];
            
            if (function_exists('hph_bridge_get_agent_data')) {
                $agent_data = hph_bridge_get_agent_data($agent_id);
                
                $auto_contact = [
                    'phone' => $agent_data['phone'] ?? '',
                    'email' => $agent_data['email'] ?? ''
                ];
                
                update_field('contact_info', $auto_contact, $post_id);
            }
        }
    }
    
    /**
     * Check for scheduling conflicts
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @return bool|WP_Error True if no conflicts, WP_Error if conflicts found
     */
    private function check_scheduling_conflicts($post_id, $form_data) {
        if (!isset($form_data['hosting_agent'], $form_data['open_house_date'], $form_data['start_time'], $form_data['end_time'])) {
            return true;
        }
        
        $agent_id = $form_data['hosting_agent'];
        $date = $form_data['open_house_date'];
        $start_time = $form_data['start_time'];
        $end_time = $form_data['end_time'];
        
        // Query for existing open houses on the same date for the same agent
        $existing_opens = get_posts([
            'post_type' => 'open-house',
            'post_status' => ['publish', 'pending', 'draft'],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'hosting_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'open_house_date',
                    'value' => $date,
                    'compare' => '='
                ]
            ],
            'post__not_in' => [$post_id],
            'posts_per_page' => 10
        ]);
        
        foreach ($existing_opens as $existing) {
            $existing_start = get_field('start_time', $existing->ID);
            $existing_end = get_field('end_time', $existing->ID);
            $existing_status = get_field('status', $existing->ID);
            
            // Skip cancelled open houses
            if ($existing_status === 'cancelled') {
                continue;
            }
            
            // Check for time overlap
            if ($this->times_overlap($start_time, $end_time, $existing_start, $existing_end)) {
                return new \WP_Error(
                    'scheduling_conflict',
                    sprintf(
                        __('Agent has another open house scheduled from %s to %s on %s', 'happy-place'),
                        date('g:i A', strtotime($existing_start)),
                        date('g:i A', strtotime($existing_end)),
                        date('F j, Y', strtotime($date))
                    )
                );
            }
        }
        
        return true;
    }
    
    /**
     * Check if two time ranges overlap
     *
     * @param string $start1 First range start time
     * @param string $end1 First range end time
     * @param string $start2 Second range start time
     * @param string $end2 Second range end time
     * @return bool True if times overlap
     */
    private function times_overlap($start1, $end1, $start2, $end2) {
        $start1_ts = strtotime($start1);
        $end1_ts = strtotime($end1);
        $start2_ts = strtotime($start2);
        $end2_ts = strtotime($end2);
        
        return ($start1_ts < $end2_ts) && ($end1_ts > $start2_ts);
    }
    
    /**
     * Send notifications for new/updated open houses
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @param bool $is_new Whether this is a new open house
     */
    private function send_notifications($post_id, $form_data, $is_new) {
        $open_house_title = get_the_title($post_id);
        
        // Notify hosting agent
        if (isset($form_data['hosting_agent'])) {
            $agent_email = '';
            if (function_exists('hph_bridge_get_agent_data')) {
                $agent_data = hph_bridge_get_agent_data($form_data['hosting_agent']);
                $agent_email = $agent_data['email'] ?? '';
            }
            
            if ($agent_email) {
                $subject = sprintf(
                    __('[%s] %s Open House: %s', 'happy-place'),
                    get_bloginfo('name'),
                    $is_new ? 'New' : 'Updated',
                    $open_house_title
                );
                
                $message = sprintf(
                    __("Hello,\n\nAn open house has been %s:\n\n%s\nDate: %s\nTime: %s - %s\n\nView details: %s\n\nBest regards,\nThe %s Team", 'happy-place'),
                    $is_new ? 'scheduled' : 'updated',
                    $open_house_title,
                    date('F j, Y', strtotime($form_data['open_house_date'])),
                    date('g:i A', strtotime($form_data['start_time'])),
                    date('g:i A', strtotime($form_data['end_time'])),
                    get_permalink($post_id),
                    get_bloginfo('name')
                );
                
                wp_mail($agent_email, $subject, $message);
            }
        }
        
        // Notify listing agent if different from hosting agent
        if (isset($form_data['listing'])) {
            $listing_agent_id = get_field('listing_agent', $form_data['listing']);
            if ($listing_agent_id && $listing_agent_id != $form_data['hosting_agent']) {
                if (function_exists('hph_bridge_get_agent_data')) {
                    $listing_agent_data = hph_bridge_get_agent_data($listing_agent_id);
                    $listing_agent_email = $listing_agent_data['email'] ?? '';
                    
                    if ($listing_agent_email) {
                        $subject = sprintf(
                            __('[%s] Open House Scheduled for Your Listing: %s', 'happy-place'),
                            get_bloginfo('name'),
                            get_the_title($form_data['listing'])
                        );
                        
                        $message = sprintf(
                            __("Hello,\n\nAn open house has been scheduled for your listing:\n\n%s\nDate: %s\nTime: %s - %s\nHosting Agent: %s\n\nView open house: %s\n\nBest regards,\nThe %s Team", 'happy-place'),
                            $open_house_title,
                            date('F j, Y', strtotime($form_data['open_house_date'])),
                            date('g:i A', strtotime($form_data['start_time'])),
                            date('g:i A', strtotime($form_data['end_time'])),
                            get_the_title($form_data['hosting_agent']),
                            get_permalink($post_id),
                            get_bloginfo('name')
                        );
                        
                        wp_mail($listing_agent_email, $subject, $message);
                    }
                }
            }
        }
        
        // Fire action hooks for integrations
        do_action('hph_open_house_saved', $post_id, $form_data, $is_new);
        
        if ($is_new) {
            do_action('hph_new_open_house_scheduled', $post_id, $form_data);
        } else {
            do_action('hph_open_house_updated', $post_id, $form_data);
        }
    }
    
    /**
     * Create calendar event (placeholder for future integration)
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function create_calendar_event($post_id, $form_data) {
        // Placeholder for calendar integration (Google Calendar, Outlook, etc.)
        // This would create calendar events for the hosting agent
        
        do_action('hph_create_open_house_calendar_event', $post_id, $form_data);
    }
    
    /**
     * Sanitize date field
     *
     * @param string $date Date string
     * @return string Sanitized date
     */
    public function sanitize_date($date) {
        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }
    
    /**
     * Sanitize time field
     *
     * @param string $time Time string
     * @return string Sanitized time
     */
    public function sanitize_time($time) {
        $timestamp = strtotime($time);
        return $timestamp ? date('H:i:s', $timestamp) : '';
    }
    
    /**
     * Sanitize contact info group
     *
     * @param array $contact_info Contact info array
     * @return array Sanitized contact info
     */
    public function sanitize_contact_info($contact_info) {
        if (!is_array($contact_info)) {
            return [];
        }
        
        return [
            'phone' => sanitize_text_field($contact_info['phone'] ?? ''),
            'email' => sanitize_email($contact_info['email'] ?? '')
        ];
    }
}