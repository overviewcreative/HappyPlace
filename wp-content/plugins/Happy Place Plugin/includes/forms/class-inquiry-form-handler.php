<?php
/**
 * Property Inquiry Form Handler
 * 
 * File: includes/forms/class-inquiry-form-handler.php
 */

namespace HappyPlace\Forms;

use HappyPlace\Integrations\Integrations_Manager;

class Inquiry_Form_Handler {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_ajax_hph_submit_inquiry', [$this, 'handle_inquiry_submission']);
        add_action('wp_ajax_nopriv_hph_submit_inquiry', [$this, 'handle_inquiry_submission']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('hph_inquiry_form', [$this, 'render_inquiry_form']);
    }

    /**
     * Handle inquiry form submission
     */
    public function handle_inquiry_submission(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_inquiry_nonce')) {
            wp_send_json_error('Invalid security token');
        }

        // Sanitize form data
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'message' => sanitize_textarea_field($_POST['message']),
            'listing_id' => intval($_POST['listing_id']),
            'inquiry_type' => sanitize_text_field($_POST['inquiry_type'] ?? 'general')
        ];

        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            wp_send_json_error('Please fill in all required fields');
        }

        if (!is_email($data['email'])) {
            wp_send_json_error('Please provide a valid email address');
        }

        // Save inquiry to database
        $inquiry_id = $this->save_inquiry($data);
        if (!$inquiry_id) {
            wp_send_json_error('Failed to save inquiry');
        }

        // Process integrations
        $this->process_integrations($data, $inquiry_id);

        // Send notifications
        $this->send_notifications($data, $inquiry_id);

        wp_send_json_success([
            'message' => 'Thank you for your inquiry! We will contact you soon.',
            'inquiry_id' => $inquiry_id
        ]);
    }

    /**
     * Save inquiry to database
     */
    private function save_inquiry(array $data): int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hph_inquiries';
        
        $agent_id = get_field('agent', $data['listing_id']);
        $agent_id = is_object($agent_id) ? $agent_id->ID : $agent_id;

        $result = $wpdb->insert(
            $table_name,
            [
                'listing_id' => $data['listing_id'],
                'agent_id' => $agent_id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'message' => $data['message'],
                'inquiry_type' => $data['inquiry_type'],
                'status' => 'new',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : 0;
    }

    /**
     * Process integrations (Follow Up Boss, Airtable, Mailchimp)
     */
    private function process_integrations(array $data, int $inquiry_id): void {
        $integrations = Integrations_Manager::get_instance();
        $listing = get_post($data['listing_id']);

        // Create Follow Up Boss lead
        $lead_data = [
            'person' => [
                'firstName' => $data['name'],
                'emails' => [['value' => $data['email']]],
                'phones' => !empty($data['phone']) ? [['value' => $data['phone']]] : []
            ],
            'source' => 'Website Property Inquiry',
            'type' => 'buyer',
            'property' => [
                'address' => get_field('street_address', $listing->ID),
                'price' => get_field('price', $listing->ID),
                'url' => get_permalink($listing->ID)
            ],
            'message' => $data['message']
        ];

        $integrations->create_followupboss_lead($lead_data);

        // Add to Mailchimp (if they opted in)
        if (!empty($_POST['email_opt_in'])) {
            $name_parts = explode(' ', $data['name'], 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';

            $integrations->add_mailchimp_subscriber(
                $data['email'],
                $first_name,
                $last_name,
                [
                    'INTEREST' => 'Property Inquiry',
                    'PROPERTY' => $listing->post_title
                ]
            );
        }

        // Log the inquiry for Airtable sync
        wp_schedule_single_event(time() + 300, 'hph_sync_inquiry_airtable', [$inquiry_id]);
    }

    /**
     * Send email notifications
     */
    private function send_notifications(array $data, int $inquiry_id): void {
        $listing = get_post($data['listing_id']);
        $agent = get_field('agent', $listing->ID);

        // Send to agent
        if ($agent) {
            $agent_email = get_field('email', $agent->ID);
            if ($agent_email) {
                $subject = "New Listing Inquiry - {$listing->post_title}";
                $message = $this->get_agent_email_template($data, $listing, $inquiry_id);
                wp_mail($agent_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
            }
        }

        // Send confirmation to inquirer
        $subject = "Thank you for your interest in {$listing->post_title}";
        $message = $this->get_confirmation_email_template($data, $listing);
        wp_mail($data['email'], $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
    }

    /**
     * Render inquiry form shortcode
     */
    public function render_inquiry_form($atts): string {
        $atts = shortcode_atts([
            'listing_id' => get_the_ID(),
            'show_phone' => 'true',
            'show_opt_in' => 'true',
            'title' => 'Request Information'
        ], $atts);

        ob_start();
        ?>
        <div class="hph-inquiry-form-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            
            <form id="hph-inquiry-form" class="hph-inquiry-form">
                <?php wp_nonce_field('hph_inquiry_nonce', 'inquiry_nonce'); ?>
                <input type="hidden" name="listing_id" value="<?php echo esc_attr($atts['listing_id']); ?>">
                
                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="inquiry_name">Name *</label>
                        <input type="text" id="inquiry_name" name="name" required>
                    </div>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="inquiry_email">Email *</label>
                        <input type="email" id="inquiry_email" name="email" required>
                    </div>
                    
                    <?php if ($atts['show_phone'] === 'true'): ?>
                    <div class="hph-form-group">
                        <label for="inquiry_phone">Phone</label>
                        <input type="tel" id="inquiry_phone" name="phone">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="inquiry_type">I'm interested in:</label>
                        <select id="inquiry_type" name="inquiry_type">
                            <option value="general">General Information</option>
                            <option value="showing">Scheduling a Showing</option>
                            <option value="pricing">Pricing Information</option>
                            <option value="financing">Financing Options</option>
                            <option value="neighborhood">Neighborhood Information</option>
                        </select>
                    </div>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="inquiry_message">Message *</label>
                        <textarea id="inquiry_message" name="message" rows="4" required 
                                  placeholder="Please tell us about your interest in this property..."></textarea>
                    </div>
                </div>

                <?php if ($atts['show_opt_in'] === 'true'): ?>
                <div class="hph-form-row">
                    <div class="hph-form-group checkbox">
                        <label>
                            <input type="checkbox" name="email_opt_in" value="1">
                            Send me updates about new listings and market trends
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="hph-form-row">
                    <button type="submit" class="hph-btn hph-btn--primary">Send Inquiry</button>
                </div>

                <div class="hph-form-message" style="display: none;"></div>
            </form>
        </div>

        <style>
        .hph-inquiry-form-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
        }
        .hph-form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .hph-form-group {
            flex: 1;
        }
        .hph-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .hph-form-group input,
        .hph-form-group select,
        .hph-form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .hph-form-group.checkbox {
            display: flex;
            align-items: center;
        }
        .hph-form-group.checkbox input {
            width: auto;
            margin-right: 8px;
        }
        .hph-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .hph-btn-primary {
            background: #0073aa;
            color: white;
        }
        .hph-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .hph-form-message.success {
            color: #46b450;
            background: #ecf7ed;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .hph-form-message.error {
            color: #dc3232;
            background: #fbeaea;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue scripts for inquiry form
     */
    public function enqueue_scripts(): void {
        if (is_singular('listing') || has_shortcode(get_post()->post_content ?? '', 'hph_inquiry_form')) {
            wp_enqueue_script(
                'hph-inquiry-form',
                HPH_PLUGIN_URL . 'assets/js/inquiry-form.js',
                ['jquery'],
                HPH_VERSION,
                true
            );

            wp_localize_script('hph-inquiry-form', 'hphInquiry', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_inquiry_nonce')
            ]);
        }
    }

    /**
     * Get agent email template
     */
    private function get_agent_email_template(array $data, $property, int $inquiry_id): string {
        $property_url = get_permalink($property->ID);
        $price = get_field('price', $property->ID);
        
        return "
        <h2>New Property Inquiry</h2>
        <p><strong>Property:</strong> {$property->post_title}</p>
        <p><strong>Price:</strong> $" . number_format($price) . "</p>
        <p><strong>Property Link:</strong> <a href='{$property_url}'>{$property_url}</a></p>
        
        <hr>
        
        <p><strong>Name:</strong> {$data['name']}</p>
        <p><strong>Email:</strong> {$data['email']}</p>
        <p><strong>Phone:</strong> {$data['phone']}</p>
        <p><strong>Interest:</strong> {$data['inquiry_type']}</p>
        
        <p><strong>Message:</strong><br>{$data['message']}</p>
        
        <p><small>Inquiry ID: {$inquiry_id}</small></p>
        ";
    }

    /**
     * Get confirmation email template
     */
    private function get_confirmation_email_template(array $data, $property): string {
        return "
        <h2>Thank you for your interest!</h2>
        <p>Hi {$data['name']},</p>
        <p>Thank you for your inquiry about <strong>{$property->post_title}</strong>.</p>
        <p>We have received your message and will contact you within 24 hours to discuss your interest.</p>
        
        <p>Best regards,<br>The Happy Place Team</p>
        ";
    }
}