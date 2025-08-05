<?php
/**
 * Email Campaign Handler
 * 
 * Handles the creation and sending of email campaigns.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Handlers
 */

namespace HappyPlace\Dashboard\Handlers;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Campaign_Handler {
    
    /**
     * Initialize handler
     */
    public function init(): void {
        // Hook into email campaign generation
        add_action('hph_generate_email_campaign', [$this, 'generate_campaign'], 10, 2);
    }
    
    /**
     * Generate email campaign
     *
     * @param array $form_data Form submission data
     * @return array|WP_Error Generation result
     */
    public function generate(array $form_data) {
        $campaign_type = sanitize_text_field($form_data['campaign_type'] ?? 'new_listing');
        $recipient_list = sanitize_text_field($form_data['recipient_list'] ?? 'all_contacts');
        $subject = sanitize_text_field($form_data['subject'] ?? '');
        $template = sanitize_text_field($form_data['template'] ?? 'default');
        $preheader = sanitize_text_field($form_data['preheader'] ?? '');
        $listing_id = intval($form_data['listing_id'] ?? 0);
        
        if (empty($subject)) {
            return new \WP_Error('missing_subject', __('Email subject is required', 'happy-place'));
        }
        
        // Get recipients
        $recipients = $this->get_recipients($recipient_list);
        
        if (empty($recipients)) {
            return new \WP_Error('no_recipients', __('No recipients found for the selected list', 'happy-place'));
        }
        
        // Generate email content
        $email_content = $this->generate_email_content([
            'campaign_type' => $campaign_type,
            'template' => $template,
            'subject' => $subject,
            'preheader' => $preheader,
            'listing_id' => $listing_id
        ]);
        
        if (is_wp_error($email_content)) {
            return $email_content;
        }
        
        // Create campaign
        $campaign_id = $this->create_campaign([
            'type' => $campaign_type,
            'subject' => $subject,
            'content' => $email_content,
            'recipients' => $recipients,
            'listing_id' => $listing_id
        ]);
        
        if (!$campaign_id) {
            return new \WP_Error('campaign_creation_failed', __('Failed to create email campaign', 'happy-place'));
        }
        
        // Send or schedule campaign
        $send_result = $this->send_campaign($campaign_id);
        
        if (is_wp_error($send_result)) {
            return $send_result;
        }
        
        // Log activity
        $this->log_campaign_creation($campaign_id, count($recipients));
        
        return [
            'success' => true,
            'message' => sprintf(
                __('Email campaign created and sent to %d recipient(s)', 'happy-place'),
                count($recipients)
            ),
            'campaign_id' => $campaign_id,
            'recipients_count' => count($recipients),
            'campaign_url' => $this->get_campaign_url($campaign_id)
        ];
    }
    
    /**
     * Get recipients for campaign
     *
     * @param string $recipient_list List type
     * @return array Recipients
     */
    private function get_recipients(string $recipient_list): array {
        $recipients = [];
        
        switch ($recipient_list) {
            case 'all_contacts':
                $recipients = $this->get_all_contacts();
                break;
                
            case 'active_leads':
                $recipients = $this->get_active_leads();
                break;
                
            case 'past_clients':
                $recipients = $this->get_past_clients();
                break;
                
            case 'sphere':
                $recipients = $this->get_sphere_contacts();
                break;
                
            case 'custom':
                // Would be handled by additional form fields
                $recipients = [];
                break;
                
            default:
                $recipients = [];
        }
        
        // Filter out invalid emails and apply unsubscribe rules
        return array_filter($recipients, [$this, 'is_valid_recipient']);
    }
    
    /**
     * Get all contacts
     *
     * @return array All contacts
     */
    private function get_all_contacts(): array {
        // In a real implementation, this would query the CRM/contacts system
        $contacts = [];
        
        // Query leads
        $leads = get_posts([
            'post_type' => 'lead',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'lead_email',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);
        
        foreach ($leads as $lead) {
            $email = get_post_meta($lead->ID, 'lead_email', true);
            $name = get_post_meta($lead->ID, 'lead_first_name', true) . ' ' . get_post_meta($lead->ID, 'lead_last_name', true);
            
            if (is_email($email)) {
                $contacts[] = [
                    'email' => $email,
                    'name' => trim($name),
                    'type' => 'lead',
                    'id' => $lead->ID
                ];
            }
        }
        
        return $contacts;
    }
    
    /**
     * Get active leads
     *
     * @return array Active leads
     */
    private function get_active_leads(): array {
        // Query for leads with recent activity or specific status
        return array_filter($this->get_all_contacts(), function($contact) {
            return $contact['type'] === 'lead';
        });
    }
    
    /**
     * Get past clients
     *
     * @return array Past clients
     */
    private function get_past_clients(): array {
        // Query for contacts marked as past clients
        return []; // Placeholder
    }
    
    /**
     * Get sphere contacts
     *
     * @return array Sphere contacts
     */
    private function get_sphere_contacts(): array {
        // Query for sphere of influence contacts
        return []; // Placeholder
    }
    
    /**
     * Check if recipient is valid
     *
     * @param array $recipient Recipient data
     * @return bool Is valid
     */
    private function is_valid_recipient(array $recipient): bool {
        // Check email validity
        if (!is_email($recipient['email'])) {
            return false;
        }
        
        // Check unsubscribe status
        if ($this->is_unsubscribed($recipient['email'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if email is unsubscribed
     *
     * @param string $email Email address
     * @return bool Is unsubscribed
     */
    private function is_unsubscribed(string $email): bool {
        // Check unsubscribe list
        $unsubscribed = get_option('hph_unsubscribed_emails', []);
        return in_array($email, $unsubscribed);
    }
    
    /**
     * Generate email content
     *
     * @param array $content_data Content data
     * @return string|WP_Error Generated content
     */
    private function generate_email_content(array $content_data) {
        $template = $content_data['template'];
        $campaign_type = $content_data['campaign_type'];
        $listing_id = $content_data['listing_id'];
        
        // Load template
        $template_content = $this->load_email_template($template, $campaign_type);
        
        if (!$template_content) {
            return new \WP_Error('template_not_found', __('Email template not found', 'happy-place'));
        }
        
        // Get template variables
        $variables = $this->get_template_variables($listing_id);
        
        // Replace placeholders
        $content = $this->replace_template_variables($template_content, $variables);
        
        return $content;
    }
    
    /**
     * Load email template
     *
     * @param string $template Template name
     * @param string $campaign_type Campaign type
     * @return string|null Template content
     */
    private function load_email_template(string $template, string $campaign_type): ?string {
        // Look for template file
        $template_file = plugin_dir_path(__FILE__) . "templates/email/{$template}-{$campaign_type}.html";
        
        if (!file_exists($template_file)) {
            $template_file = plugin_dir_path(__FILE__) . "templates/email/{$template}.html";
        }
        
        if (!file_exists($template_file)) {
            // Use default template
            return $this->get_default_email_template($campaign_type);
        }
        
        return file_get_contents($template_file);
    }
    
    /**
     * Get default email template
     *
     * @param string $campaign_type Campaign type
     * @return string Default template
     */
    private function get_default_email_template(string $campaign_type): string {
        switch ($campaign_type) {
            case 'new_listing':
                return $this->get_new_listing_template();
                
            case 'open_house':
                return $this->get_open_house_template();
                
            case 'newsletter':
                return $this->get_newsletter_template();
                
            default:
                return $this->get_generic_template();
        }
    }
    
    /**
     * Get new listing email template
     *
     * @return string Template HTML
     */
    private function get_new_listing_template(): string {
        return '
        <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
            <h1 style="color: #2563eb;">{{listing_headline}}</h1>
            <img src="{{listing_featured_image}}" alt="{{listing_address}}" style="width: 100%; height: auto;">
            <div style="padding: 20px;">
                <h2>{{listing_address}}</h2>
                <p style="font-size: 24px; color: #16a34a; font-weight: bold;">${{listing_price}}</p>
                <p>{{listing_description}}</p>
                <div style="display: flex; gap: 20px;">
                    <div>🛏️ {{listing_bedrooms}} bed</div>
                    <div>🛁 {{listing_bathrooms}} bath</div>
                    <div>📐 {{listing_square_feet}} sq ft</div>
                </div>
                <a href="{{listing_url}}" style="background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px;">View Details</a>
            </div>
            <div style="text-align: center; padding: 20px; border-top: 1px solid #e5e7eb;">
                <p>{{agent_name}} | {{agent_phone}} | {{agent_email}}</p>
            </div>
        </div>';
    }
    
    /**
     * Get open house email template
     *
     * @return string Template HTML
     */
    private function get_open_house_template(): string {
        return '
        <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
            <h1 style="color: #2563eb;">Open House This Weekend!</h1>
            <img src="{{listing_featured_image}}" alt="{{listing_address}}" style="width: 100%; height: auto;">
            <div style="padding: 20px;">
                <h2>{{listing_address}}</h2>
                <p style="font-size: 24px; color: #16a34a; font-weight: bold;">${{listing_price}}</p>
                <div style="background: #f3f4f6; padding: 15px; border-radius: 6px; margin: 20px 0;">
                    <h3>Open House Details</h3>
                    <p>📅 {{open_house_date}}</p>
                    <p>🕐 {{open_house_time}}</p>
                    <p>📍 {{listing_address}}</p>
                </div>
                <p>Come see this beautiful home in person!</p>
                <a href="{{listing_url}}" style="background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px;">View Details</a>
            </div>
        </div>';
    }
    
    /**
     * Get newsletter email template
     *
     * @return string Template HTML
     */
    private function get_newsletter_template(): string {
        return '
        <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
            <h1 style="color: #2563eb;">Market Update</h1>
            <div style="padding: 20px;">
                <p>{{newsletter_intro}}</p>
                <h2>Featured Listings</h2>
                {{featured_listings}}
                <h2>Market Stats</h2>
                {{market_stats}}
            </div>
        </div>';
    }
    
    /**
     * Get generic email template
     *
     * @return string Template HTML
     */
    private function get_generic_template(): string {
        return '
        <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
            <div style="padding: 20px;">
                {{email_content}}
            </div>
        </div>';
    }
    
    /**
     * Get template variables
     *
     * @param int $listing_id Listing ID
     * @return array Variables
     */
    private function get_template_variables(int $listing_id = 0): array {
        $variables = [
            'agent_name' => wp_get_current_user()->display_name,
            'agent_email' => wp_get_current_user()->user_email,
            'agent_phone' => get_user_meta(get_current_user_id(), 'phone', true),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        ];
        
        if ($listing_id && function_exists('hph_bridge_get_listing_data')) {
            $listing_data = hph_bridge_get_listing_data($listing_id);
            if ($listing_data) {
                $variables = array_merge($variables, [
                    'listing_address' => $listing_data['address'],
                    'listing_price' => number_format($listing_data['price']),
                    'listing_bedrooms' => $listing_data['bedrooms'],
                    'listing_bathrooms' => $listing_data['bathrooms'],
                    'listing_square_feet' => number_format($listing_data['square_feet']),
                    'listing_description' => $listing_data['description'],
                    'listing_url' => get_permalink($listing_id),
                    'listing_featured_image' => get_the_post_thumbnail_url($listing_id, 'large')
                ]);
            }
        }
        
        return $variables;
    }
    
    /**
     * Replace template variables
     *
     * @param string $content Template content
     * @param array $variables Variables
     * @return string Processed content
     */
    private function replace_template_variables(string $content, array $variables): string {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Create campaign
     *
     * @param array $campaign_data Campaign data
     * @return int|false Campaign ID or false
     */
    private function create_campaign(array $campaign_data) {
        $post_data = [
            'post_title' => $campaign_data['subject'] . ' - ' . date('Y-m-d H:i'),
            'post_content' => $campaign_data['content'],
            'post_status' => 'draft',
            'post_type' => 'email_campaign',
            'post_author' => get_current_user_id(),
            'meta_input' => [
                'campaign_type' => $campaign_data['type'],
                'campaign_subject' => $campaign_data['subject'],
                'campaign_recipients' => $campaign_data['recipients'],
                'campaign_listing_id' => $campaign_data['listing_id'],
                'campaign_status' => 'draft'
            ]
        ];
        
        return wp_insert_post($post_data);
    }
    
    /**
     * Send campaign
     *
     * @param int $campaign_id Campaign ID
     * @return bool|WP_Error Send result
     */
    private function send_campaign(int $campaign_id) {
        // In a real implementation, this would:
        // 1. Queue emails for sending
        // 2. Integrate with email service (SendGrid, Mailchimp, etc.)
        // 3. Track opens, clicks, etc.
        
        // For now, just update status
        update_post_meta($campaign_id, 'campaign_status', 'sent');
        update_post_meta($campaign_id, 'campaign_sent_at', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Get campaign URL
     *
     * @param int $campaign_id Campaign ID
     * @return string Campaign URL
     */
    private function get_campaign_url(int $campaign_id): string {
        return admin_url('post.php?post=' . $campaign_id . '&action=edit');
    }
    
    /**
     * Log campaign creation activity
     *
     * @param int $campaign_id Campaign ID
     * @param int $recipients_count Recipients count
     */
    private function log_campaign_creation(int $campaign_id, int $recipients_count): void {
        if (function_exists('hph_log_activity')) {
            hph_log_activity([
                'type' => 'email_campaign_created',
                'object_type' => 'campaign',
                'object_id' => $campaign_id,
                'description' => sprintf(
                    __('Email campaign sent to %d recipient(s)', 'happy-place'),
                    $recipients_count
                ),
                'metadata' => [
                    'recipients_count' => $recipients_count
                ]
            ]);
        }
    }
}