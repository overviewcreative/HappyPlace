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

            // Check if listing exists
            $listing = get_post($listing_id);
            if (!$listing) {
                $this->send_error('Listing not found');
                return;
            }

            if ($listing->post_type !== 'listing') {
                $this->send_error('Post is not a listing');
                return;
            }

            if ($listing->post_status !== 'publish') {
                $this->send_error('Listing is not published');
                return;
            }

            // Get listing data using the proven method from Flyer_Generator_Clean
            $data = $this->get_basic_listing_data($listing_id);

            error_log("HPH Flyer Ajax: Success - returning data for listing {$listing_id}");
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
            $data = $this->get_basic_listing_data($listing_id);
            $this->send_success($data);

        } catch (\Exception $e) {
            error_log('HPH Flyer Ajax Exception: ' . $e->getMessage());
            $this->send_error('Server error occurred');
        }
    }

    /**
     * Get basic listing data (copied from working Flyer_Generator_Clean)
     */
    private function get_basic_listing_data(int $listing_id): array {
        $listing = get_post($listing_id);
        
        // Get basic ACF fields
        $price = get_field('price', $listing_id) ?: get_field('listing_price', $listing_id);
        $bedrooms = get_field('bedrooms', $listing_id) ?: get_field('beds', $listing_id);
        $bathrooms = get_field('bathrooms', $listing_id) ?: get_field('baths', $listing_id);
        $sqft = get_field('square_footage', $listing_id) ?: get_field('sqft', $listing_id);
        $address = get_field('address', $listing_id) ?: get_field('street_address', $listing_id);
        $city = get_field('city', $listing_id);
        $description = get_field('description', $listing_id) ?: get_field('property_description', $listing_id);

        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => $price ? '$' . number_format($price) : null,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'square_footage' => $sqft,
            'address' => $address,
            'city' => $city,
            'description' => $description,
            'url' => get_permalink($listing_id),
            'listing' => [
                'id' => $listing_id,
                'title' => get_the_title($listing_id),
                'price' => $price ? '$' . number_format($price) : null,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'square_footage' => $sqft,
                'address' => $address,
                'city' => $city,
                'description' => $description,
            ],
            'agent' => [
                'name' => 'The Parker Group',
                'phone' => '302.217.6692',
                'email' => 'info@theparkergroup.com',
                'image' => null
            ]
        ];
    }
}
