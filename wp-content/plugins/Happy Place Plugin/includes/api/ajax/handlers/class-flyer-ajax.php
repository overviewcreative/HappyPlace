<?php
/**
 * Flyer AJAX Handler - PDF Generation
 * 
 * File: includes/api/ajax/handlers/class-flyer-ajax.php
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Flyer_Ajax extends Base_Ajax_Handler {
    
    protected function get_actions(): array {
        return [
            'generate_flyer' => [
                'callback' => 'handle_generate_flyer',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            'get_listing_data_for_flyer' => [
                'callback' => 'handle_get_listing_data',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 1800
            ]
        ];
    }
    
    public function handle_generate_flyer(): void {
        try {
            // Validate required parameters
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $flyer_type = sanitize_text_field($_POST['flyer_type'] ?? 'listing');

            // Check if listing exists
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing' || $listing->post_status !== 'publish') {
                $this->send_error('Listing not found or not available');
                return;
            }

            // Get the comprehensive Flyer Generator instance
            $flyer_generator = \HappyPlace\Graphics\Flyer_Generator::get_instance();
            
            // Use the comprehensive get_listing_data method
            $data = $flyer_generator->get_listing_data($listing_id, $flyer_type);
            
            if (empty($data)) {
                $this->send_error('Unable to retrieve listing data');
                return;
            }

            error_log("HPH Flyer Ajax: Success - returning comprehensive data for listing {$listing_id}");
            $this->send_success($data);

        } catch (\Exception $e) {
            error_log('HPH Flyer Ajax Exception: ' . $e->getMessage());
            $this->send_error('Server error occurred');
        }
    }
    
    public function handle_get_listing_data(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            
            // Use the comprehensive Flyer Generator
            $flyer_generator = \HappyPlace\Graphics\Flyer_Generator::get_instance();
            $data = $flyer_generator->get_listing_data($listing_id);
            
            $this->send_success($data);

        } catch (\Exception $e) {
            error_log('HPH Flyer Ajax Exception: ' . $e->getMessage());
            $this->send_error('Server error occurred');
        }
    }
}
