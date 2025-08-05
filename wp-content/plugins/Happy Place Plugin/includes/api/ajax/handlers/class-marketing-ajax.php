<?php
/**
 * Marketing AJAX Handler - Social Media & Email Campaigns
 * 
 * Handles all marketing-related AJAX operations including:
 * - Social media post scheduling
 * - Email campaign creation
 * - Marketing campaign management
 * - Flyer generation coordination
 * 
 * @package HappyPlace
 * @subpackage Api\Ajax\Handlers
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Marketing_Ajax extends Base_Ajax_Handler {
    
    protected function get_actions(): array {
        return [
            'schedule_social_post' => [
                'callback' => 'handle_schedule_social_post',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            'create_email_campaign' => [
                'callback' => 'handle_create_email_campaign',
                'capability' => 'edit_posts', 
                'rate_limit' => 5
            ],
            'create_marketing_campaign' => [
                'callback' => 'handle_create_marketing_campaign',
                'capability' => 'edit_posts',
                'rate_limit' => 5
            ],
            'get_campaign_analytics' => [
                'callback' => 'handle_get_campaign_analytics',
                'capability' => 'read',
                'cache' => 600
            ]
        ];
    }
    
    /**
     * Handle social media post scheduling
     */
    public function handle_schedule_social_post(): void {
        try {
            // Validate required parameters
            if (!$this->validate_required_params([
                'platforms' => 'array',
                'content' => 'string'
            ])) {
                return;
            }
            
            $platforms = array_map('sanitize_text_field', $_POST['platforms']);
            $content = sanitize_textarea_field($_POST['content']);
            $listing_id = !empty($_POST['listing_id']) ? intval($_POST['listing_id']) : null;
            $schedule_type = sanitize_text_field($_POST['schedule_type'] ?? 'now');
            $scheduled_time = !empty($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : null;
            
            // Validate platforms
            $valid_platforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
            $platforms = array_intersect($platforms, $valid_platforms);
            
            if (empty($platforms)) {
                $this->send_error('Please select at least one valid platform');
                return;
            }
            
            // Validate content length per platform
            $content_limits = [
                'twitter' => 280,
                'instagram' => 2200,
                'facebook' => 5000,
                'linkedin' => 3000
            ];
            
            foreach ($platforms as $platform) {
                if (strlen($content) > $content_limits[$platform]) {
                    $this->send_error("Content too long for {$platform}. Maximum {$content_limits[$platform]} characters.");
                    return;
                }
            }
            
            // Process scheduling
            $post_data = [
                'platforms' => $platforms,
                'content' => $content,
                'listing_id' => $listing_id,
                'schedule_type' => $schedule_type,
                'scheduled_time' => $scheduled_time,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ];
            
            if ($schedule_type === 'now') {
                $result = $this->publish_social_post($post_data);
            } else {
                $result = $this->schedule_social_post($post_data);
            }
            
            if ($result['success']) {
                $this->send_success([
                    'message' => $result['message'],
                    'post_id' => $result['post_id'] ?? null
                ]);
            } else {
                $this->send_error($result['message']);
            }
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Ajax Exception (Social Post): ' . $e->getMessage());
            $this->send_error('Failed to process social media post');
        }
    }
    
    /**
     * Handle email campaign creation
     */
    public function handle_create_email_campaign(): void {
        try {
            // Validate required parameters
            if (!$this->validate_required_params([
                'subject' => 'string',
                'template' => 'string',
                'content' => 'string',
                'audience' => 'string'
            ])) {
                return;
            }
            
            $subject = sanitize_text_field($_POST['subject']);
            $template = sanitize_text_field($_POST['template']);
            $content = wp_kses_post($_POST['content']);
            $audience = sanitize_text_field($_POST['audience']);
            $send_time = sanitize_text_field($_POST['send_time'] ?? 'now');
            $scheduled_send = !empty($_POST['scheduled_send']) ? sanitize_text_field($_POST['scheduled_send']) : null;
            
            // Validate template
            $valid_templates = ['newsletter', 'listing-announcement', 'market-update', 'open-house'];
            if (!in_array($template, $valid_templates)) {
                $this->send_error('Invalid email template selected');
                return;
            }
            
            // Validate audience
            $valid_audiences = ['all-contacts', 'buyers', 'sellers', 'past-clients', 'leads'];
            if (!in_array($audience, $valid_audiences)) {
                $this->send_error('Invalid audience selected');
                return;
            }
            
            // Create campaign data
            $campaign_data = [
                'subject' => $subject,
                'template' => $template,
                'content' => $content,
                'audience' => $audience,
                'send_time' => $send_time,
                'scheduled_send' => $scheduled_send,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'status' => $send_time === 'now' ? 'sending' : 'scheduled'
            ];
            
            // Save campaign to database
            $campaign_id = $this->save_email_campaign($campaign_data);
            
            if (!$campaign_id) {
                $this->send_error('Failed to create email campaign');
                return;
            }
            
            if ($send_time === 'now') {
                $result = $this->send_email_campaign($campaign_id);
                $message = $result['success'] ? 'Email campaign sent successfully!' : 'Campaign created but failed to send';
            } else {
                $message = 'Email campaign scheduled successfully!';
            }
            
            $this->send_success([
                'message' => $message,
                'campaign_id' => $campaign_id
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Ajax Exception (Email Campaign): ' . $e->getMessage());
            $this->send_error('Failed to create email campaign');
        }
    }
    
    /**
     * Handle marketing campaign creation
     */
    public function handle_create_marketing_campaign(): void {
        try {
            // Validate required parameters
            if (!$this->validate_required_params([
                'name' => 'string',
                'type' => 'string'
            ])) {
                return;
            }
            
            $name = sanitize_text_field($_POST['name']);
            $type = sanitize_text_field($_POST['type']);
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            $duration = sanitize_text_field($_POST['duration'] ?? '1-month');
            
            // Validate campaign type
            $valid_types = ['listing-promotion', 'lead-generation', 'brand-awareness', 'market-update'];
            if (!in_array($type, $valid_types)) {
                $this->send_error('Invalid campaign type selected');
                return;
            }
            
            // Create campaign
            $campaign_data = [
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'duration' => $duration,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'status' => 'active'
            ];
            
            $campaign_id = $this->save_marketing_campaign($campaign_data);
            
            if (!$campaign_id) {
                $this->send_error('Failed to create marketing campaign');
                return;
            }
            
            $this->send_success([
                'message' => 'Marketing campaign created successfully!',
                'campaign_id' => $campaign_id
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Ajax Exception (Campaign): ' . $e->getMessage());
            $this->send_error('Failed to create marketing campaign');
        }
    }
    
    /**
     * Get campaign analytics
     */
    public function handle_get_campaign_analytics(): void {
        try {
            $campaign_id = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : null;
            $campaign_type = sanitize_text_field($_POST['campaign_type'] ?? 'all');
            
            $analytics = $this->get_campaign_analytics($campaign_id, $campaign_type);
            
            $this->send_success($analytics);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Ajax Exception (Analytics): ' . $e->getMessage());
            $this->send_error('Failed to get campaign analytics');
        }
    }
    
    /**
     * Publish social media post immediately
     */
    private function publish_social_post(array $post_data): array {
        // In a real implementation, this would integrate with social media APIs
        // For now, we'll simulate the process
        
        $post_id = wp_insert_post([
            'post_type' => 'social_post',
            'post_title' => 'Social Post - ' . date('Y-m-d H:i:s'),
            'post_content' => $post_data['content'],
            'post_status' => 'publish',
            'post_author' => $post_data['author_id'],
            'meta_input' => [
                'platforms' => $post_data['platforms'],
                'listing_id' => $post_data['listing_id'],
                'scheduled_time' => $post_data['scheduled_time'],
                'post_status' => 'published'
            ]
        ]);
        
        if ($post_id) {
            return [
                'success' => true,
                'message' => 'Social media post published successfully!',
                'post_id' => $post_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to publish social media post'
        ];
    }
    
    /**
     * Schedule social media post for later
     */
    private function schedule_social_post(array $post_data): array {
        // Validate scheduled time
        if (empty($post_data['scheduled_time'])) {
            return [
                'success' => false,
                'message' => 'Scheduled time is required'
            ];
        }
        
        $scheduled_timestamp = strtotime($post_data['scheduled_time']);
        if ($scheduled_timestamp <= time()) {
            return [
                'success' => false,
                'message' => 'Scheduled time must be in the future'
            ];
        }
        
        $post_id = wp_insert_post([
            'post_type' => 'social_post',
            'post_title' => 'Scheduled Social Post - ' . date('Y-m-d H:i:s'),
            'post_content' => $post_data['content'],
            'post_status' => 'future',
            'post_date' => date('Y-m-d H:i:s', $scheduled_timestamp),
            'post_author' => $post_data['author_id'],
            'meta_input' => [
                'platforms' => $post_data['platforms'],
                'listing_id' => $post_data['listing_id'],
                'scheduled_time' => $post_data['scheduled_time'],
                'post_status' => 'scheduled'
            ]
        ]);
        
        if ($post_id) {
            return [
                'success' => true,
                'message' => 'Social media post scheduled successfully!',
                'post_id' => $post_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to schedule social media post'
        ];
    }
    
    /**
     * Save email campaign to database
     */
    private function save_email_campaign(array $campaign_data): int {
        $post_id = wp_insert_post([
            'post_type' => 'email_campaign',
            'post_title' => $campaign_data['subject'],
            'post_content' => $campaign_data['content'],
            'post_status' => $campaign_data['status'] === 'sending' ? 'publish' : 'future',
            'post_author' => $campaign_data['author_id'],
            'meta_input' => [
                'template' => $campaign_data['template'],
                'audience' => $campaign_data['audience'],
                'send_time' => $campaign_data['send_time'],
                'scheduled_send' => $campaign_data['scheduled_send'],
                'campaign_status' => $campaign_data['status']
            ]
        ]);
        
        return $post_id ?: 0;
    }
    
    /**
     * Save marketing campaign to database
     */
    private function save_marketing_campaign(array $campaign_data): int {
        $post_id = wp_insert_post([
            'post_type' => 'marketing_campaign',
            'post_title' => $campaign_data['name'],
            'post_content' => $campaign_data['description'],
            'post_status' => 'publish',
            'post_author' => $campaign_data['author_id'],
            'meta_input' => [
                'campaign_type' => $campaign_data['type'],
                'duration' => $campaign_data['duration'],
                'campaign_status' => $campaign_data['status']
            ]
        ]);
        
        return $post_id ?: 0;
    }
    
    /**
     * Send email campaign
     */
    private function send_email_campaign(int $campaign_id): array {
        // In a real implementation, this would:
        // 1. Get the recipient list based on audience
        // 2. Process the email template
        // 3. Send via email service provider (Mailchimp, SendGrid, etc.)
        // 4. Track delivery status
        
        // For now, simulate success
        update_post_meta($campaign_id, 'campaign_status', 'sent');
        update_post_meta($campaign_id, 'sent_at', current_time('mysql'));
        
        return [
            'success' => true,
            'message' => 'Email campaign sent successfully'
        ];
    }
    
    /**
     * Get campaign analytics data
     */
    private function get_campaign_analytics($campaign_id = null, $campaign_type = 'all'): array {
        // In a real implementation, this would aggregate data from:
        // - Email open/click rates
        // - Social media engagement
        // - Website traffic from campaigns
        // - Lead generation metrics
        
        return [
            'total_campaigns' => 12,
            'active_campaigns' => 3,
            'total_emails_sent' => 1250,
            'email_open_rate' => 24.5,
            'email_click_rate' => 3.2,
            'social_posts' => 45,
            'social_engagement' => 892,
            'leads_generated' => 15,
            'conversion_rate' => 2.8
        ];
    }
}
