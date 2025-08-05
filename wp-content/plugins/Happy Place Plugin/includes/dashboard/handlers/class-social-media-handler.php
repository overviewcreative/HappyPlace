<?php
/**
 * Social Media Handler
 * 
 * Handles the creation of social media posts for listings.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Handlers
 */

namespace HappyPlace\Dashboard\Handlers;

if (!defined('ABSPATH')) {
    exit;
}

class Social_Media_Handler {
    
    /**
     * Initialize handler
     */
    public function init(): void {
        // Hook into social media post generation
        add_action('hph_generate_social_posts', [$this, 'generate_posts'], 10, 2);
    }
    
    /**
     * Generate social media posts
     *
     * @param array $form_data Form submission data
     * @return array|WP_Error Generation result
     */
    public function generate(array $form_data) {
        $listing_id = intval($form_data['listing_id'] ?? 0);
        $platforms = array_map('sanitize_text_field', $form_data['platforms'] ?? []);
        $caption = sanitize_textarea_field($form_data['caption'] ?? '');
        $hashtags = sanitize_text_field($form_data['hashtags'] ?? '');
        $images = array_map('intval', $form_data['images'] ?? []);
        $post_type = sanitize_text_field($form_data['post_type'] ?? 'new_listing');
        $schedule = sanitize_text_field($form_data['schedule'] ?? 'now');
        
        if (empty($platforms)) {
            return new \WP_Error('no_platforms', __('At least one platform must be selected', 'happy-place'));
        }
        
        $posts_created = [];
        $errors = [];
        
        foreach ($platforms as $platform) {
            $post_data = [
                'platform' => $platform,
                'listing_id' => $listing_id,
                'caption' => $caption,
                'hashtags' => $hashtags,
                'images' => $images,
                'post_type' => $post_type,
                'schedule' => $schedule,
                'schedule_time' => sanitize_text_field($form_data['schedule_time'] ?? '')
            ];
            
            $result = $this->create_social_post($post_data);
            
            if (is_wp_error($result)) {
                $errors[$platform] = $result->get_error_message();
            } else {
                $posts_created[$platform] = $result;
            }
        }
        
        if (empty($posts_created)) {
            return new \WP_Error('no_posts_created', __('Failed to create any social media posts', 'happy-place'), $errors);
        }
        
        // Log activity
        $this->log_social_posts_creation($listing_id, $posts_created);
        
        $message = sprintf(
            __('Successfully created %d social media post(s)', 'happy-place'),
            count($posts_created)
        );
        
        if (!empty($errors)) {
            $message .= sprintf(
                __(' with %d error(s)', 'happy-place'),
                count($errors)
            );
        }
        
        return [
            'success' => true,
            'message' => $message,
            'posts_created' => $posts_created,
            'errors' => $errors
        ];
    }
    
    /**
     * Create social media post
     *
     * @param array $post_data Post data
     * @return array|WP_Error Creation result
     */
    private function create_social_post(array $post_data) {
        $platform = $post_data['platform'];
        
        // Validate platform
        $allowed_platforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
        if (!in_array($platform, $allowed_platforms)) {
            return new \WP_Error('invalid_platform', __('Invalid social media platform', 'happy-place'));
        }
        
        // Format caption for platform
        $formatted_caption = $this->format_caption_for_platform($post_data['caption'], $post_data['hashtags'], $platform);
        
        // Prepare images
        $image_urls = $this->prepare_images($post_data['images']);
        
        if ($post_data['schedule'] === 'now') {
            // Post immediately (placeholder - would integrate with social APIs)
            $post_result = $this->post_to_platform($platform, $formatted_caption, $image_urls);
        } else {
            // Schedule post
            $post_result = $this->schedule_post($platform, $formatted_caption, $image_urls, $post_data['schedule_time']);
        }
        
        if (is_wp_error($post_result)) {
            return $post_result;
        }
        
        return [
            'platform' => $platform,
            'post_id' => $post_result['post_id'] ?? null,
            'url' => $post_result['url'] ?? null,
            'scheduled' => $post_data['schedule'] !== 'now',
            'schedule_time' => $post_data['schedule_time'] ?? null
        ];
    }
    
    /**
     * Format caption for specific platform
     *
     * @param string $caption Base caption
     * @param string $hashtags Hashtags
     * @param string $platform Platform name
     * @return string Formatted caption
     */
    private function format_caption_for_platform(string $caption, string $hashtags, string $platform): string {
        $formatted = $caption;
        
        // Add hashtags if provided
        if (!empty($hashtags)) {
            $formatted .= "\n\n" . $hashtags;
        }
        
        // Platform-specific formatting
        switch ($platform) {
            case 'twitter':
                // Truncate to fit Twitter's character limit
                if (strlen($formatted) > 280) {
                    $formatted = substr($formatted, 0, 277) . '...';
                }
                break;
                
            case 'linkedin':
                // LinkedIn prefers more professional tone
                $formatted = str_replace('🏡', '', $formatted);
                $formatted = str_replace('💰', '', $formatted);
                break;
                
            case 'instagram':
                // Instagram allows longer captions
                break;
                
            case 'facebook':
                // Facebook allows longer posts
                break;
        }
        
        return $formatted;
    }
    
    /**
     * Prepare images for posting
     *
     * @param array $image_ids Image attachment IDs
     * @return array Image URLs
     */
    private function prepare_images(array $image_ids): array {
        $image_urls = [];
        
        foreach ($image_ids as $image_id) {
            $url = wp_get_attachment_url($image_id);
            if ($url) {
                $image_urls[] = $url;
            }
        }
        
        return $image_urls;
    }
    
    /**
     * Post to platform immediately
     *
     * @param string $platform Platform name
     * @param string $caption Post caption
     * @param array $image_urls Image URLs
     * @return array|WP_Error Post result
     */
    private function post_to_platform(string $platform, string $caption, array $image_urls) {
        // This is a placeholder implementation
        // In a real system, this would integrate with platform APIs:
        // - Facebook Graph API
        // - Instagram Basic Display API
        // - Twitter API v2
        // - LinkedIn API
        
        // For now, save as draft post for manual posting
        $post_id = $this->save_social_draft($platform, $caption, $image_urls);
        
        if (!$post_id) {
            return new \WP_Error('post_failed', sprintf(__('Failed to create %s post', 'happy-place'), $platform));
        }
        
        return [
            'post_id' => $post_id,
            'url' => null, // Would be actual post URL from API
            'status' => 'draft' // Would be 'published' with real API
        ];
    }
    
    /**
     * Schedule post for later
     *
     * @param string $platform Platform name
     * @param string $caption Post caption
     * @param array $image_urls Image URLs
     * @param string $schedule_time Schedule time
     * @return array|WP_Error Schedule result
     */
    private function schedule_post(string $platform, string $caption, array $image_urls, string $schedule_time) {
        // Validate schedule time
        $timestamp = strtotime($schedule_time);
        if (!$timestamp || $timestamp <= time()) {
            return new \WP_Error('invalid_schedule', __('Invalid schedule time', 'happy-place'));
        }
        
        // Save as scheduled post
        $post_id = $this->save_social_draft($platform, $caption, $image_urls, $schedule_time);
        
        if (!$post_id) {
            return new \WP_Error('schedule_failed', sprintf(__('Failed to schedule %s post', 'happy-place'), $platform));
        }
        
        // Schedule WordPress cron job (in real implementation, would use platform scheduling)
        wp_schedule_single_event($timestamp, 'hph_publish_social_post', [$post_id]);
        
        return [
            'post_id' => $post_id,
            'scheduled_time' => $schedule_time,
            'status' => 'scheduled'
        ];
    }
    
    /**
     * Save social media post as draft
     *
     * @param string $platform Platform name
     * @param string $caption Post caption
     * @param array $image_urls Image URLs
     * @param string $schedule_time Optional schedule time
     * @return int|false Post ID or false on failure
     */
    private function save_social_draft(string $platform, string $caption, array $image_urls, string $schedule_time = null) {
        $post_data = [
            'post_title' => sprintf(__('%s Post - %s', 'happy-place'), ucfirst($platform), date('Y-m-d H:i')),
            'post_content' => $caption,
            'post_status' => 'draft',
            'post_type' => 'social_post', // Custom post type for social posts
            'post_author' => get_current_user_id(),
            'meta_input' => [
                'social_platform' => $platform,
                'social_images' => $image_urls,
                'social_status' => $schedule_time ? 'scheduled' : 'draft',
                'social_schedule_time' => $schedule_time
            ]
        ];
        
        return wp_insert_post($post_data);
    }
    
    /**
     * Log social posts creation activity
     *
     * @param int $listing_id Listing ID
     * @param array $posts_created Created posts
     */
    private function log_social_posts_creation(int $listing_id, array $posts_created): void {
        if (function_exists('hph_log_activity')) {
            $platforms = array_keys($posts_created);
            
            hph_log_activity([
                'type' => 'social_posts_created',
                'object_type' => 'listing',
                'object_id' => $listing_id,
                'description' => sprintf(
                    __('Social media posts created for %s', 'happy-place'),
                    implode(', ', $platforms)
                ),
                'metadata' => [
                    'platforms' => $platforms,
                    'posts' => $posts_created
                ]
            ]);
        }
    }
}