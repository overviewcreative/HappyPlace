<?php

/**
 * Social Media Graphics Generator
 *
 * @package HappyPlace
 * @subpackage Graphics
 */

namespace HappyPlace\Graphics;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Social Media Graphics Generator Class
 */
class Social_Media_Graphics
{
    /**
     * Image dimensions for different social media platforms
     *
     * @var array
     */
    private $dimensions = array(
        'facebook' => array(
            'listing'    => array(1200, 630),
            'profile'    => array(1200, 900),
            'story'      => array(1080, 1920),
        ),
        'instagram' => array(
            'square'     => array(1080, 1080),
            'portrait'   => array(1080, 1350),
            'story'      => array(1080, 1920),
        ),
        'twitter' => array(
            'inline'     => array(1200, 675),
            'header'     => array(1500, 500),
        ),
    );

    /**
     * Generate social media graphic for listing
     *
     * @param int    $listing_id Listing post ID.
     * @param string $platform   Social media platform (facebook, instagram, twitter).
     * @param string $type      Type of graphic for the platform.
     * @return string|WP_Error Path to generated image or error.
     */
    public function generate_listing_graphic($listing_id, $platform = 'facebook', $type = 'listing')
    {
        if (! isset($this->dimensions[$platform]) || ! isset($this->dimensions[$platform][$type])) {
            return new \WP_Error('invalid_dimensions', 'Invalid platform or type specified.');
        }

        // Get listing data
        $listing = get_post($listing_id);
        if (! $listing) {
            return new \WP_Error('invalid_listing', 'Invalid listing ID.');
        }

        // Get listing details
        $price = get_post_meta($listing_id, 'listing_price', true);
        $beds = get_post_meta($listing_id, 'listing_bedrooms', true);
        $baths = get_post_meta($listing_id, 'listing_bathrooms', true);
        $sqft = get_post_meta($listing_id, 'listing_square_feet', true);
        $address = get_post_meta($listing_id, 'listing_address', true);

        // Get listing image
        $image_id = get_post_thumbnail_id($listing_id);
        if (! $image_id) {
            return new \WP_Error('no_image', 'Listing has no featured image.');
        }

        // Get image dimensions
        list($width, $height) = $this->dimensions[$platform][$type];

        // Create image canvas
        $image = imagecreatetruecolor($width, $height);

        // Load and resize listing image
        $listing_image = wp_get_attachment_image_src($image_id, 'full');
        if (! $listing_image) {
            return new \WP_Error('image_load_failed', 'Failed to load listing image.');
        }

        $source = imagecreatefromstring(file_get_contents($listing_image[0]));
        imagecopyresampled(
            $image,         // Destination image
            $source,        // Source image
            0,             // Destination X
            0,             // Destination Y
            0,             // Source X
            0,             // Source Y
            $width,        // Destination width
            $height,       // Destination height
            imagesx($source), // Source width
            imagesy($source)  // Source height
        );

        // Add overlay
        $overlay = imagecreatetruecolor($width, $height);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        $transparent = imagecolorallocatealpha($overlay, 0, 0, 0, 80);
        imagefilledrectangle($overlay, 0, 0, $width, $height, $transparent);
        imagecopy($image, $overlay, 0, 0, 0, 0, $width, $height);

        // Add text
        $white = imagecolorallocate($image, 255, 255, 255);
        $font_path = plugin_dir_path(__FILE__) . '../../assets/fonts/OpenSans-Bold.ttf';

        // Format price
        $formatted_price = '$' . number_format($price);

        // Add price
        $font_size = $width * 0.05; // 5% of width
        imagettftext($image, $font_size, 0, $width * 0.1, $height * 0.2, $white, $font_path, $formatted_price);

        // Add details
        $details = "{$beds} Beds | {$baths} Baths | " . number_format($sqft) . " sq.ft.";
        $font_size = $width * 0.03; // 3% of width
        imagettftext($image, $font_size, 0, $width * 0.1, $height * 0.3, $white, $font_path, $details);

        // Add address
        imagettftext($image, $font_size, 0, $width * 0.1, $height * 0.4, $white, $font_path, $address);

        // Add branding
        $logo_path = plugin_dir_path(__FILE__) . '../../assets/images/logo.png';
        if (file_exists($logo_path)) {
            $logo = imagecreatefrompng($logo_path);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);

            // Calculate logo size (20% of graphic width)
            $new_logo_width = $width * 0.2;
            $new_logo_height = ($logo_height / $logo_width) * $new_logo_width;

            // Position logo in bottom right corner with padding
            $logo_x = $width - $new_logo_width - ($width * 0.05);
            $logo_y = $height - $new_logo_height - ($height * 0.05);

            imagecopyresampled(
                $image,
                $logo,
                $logo_x,
                $logo_y,
                0,
                0,
                $new_logo_width,
                $new_logo_height,
                $logo_width,
                $logo_height
            );
        }

        // Save image
        $upload_dir = wp_upload_dir();
        $filename = "listing-{$listing_id}-{$platform}-{$type}.jpg";
        $filepath = $upload_dir['path'] . '/' . $filename;

        imagejpeg($image, $filepath, 90);

        // Clean up
        imagedestroy($image);
        imagedestroy($source);
        imagedestroy($overlay);
        if (isset($logo)) {
            imagedestroy($logo);
        }

        // Return URL
        return $upload_dir['url'] . '/' . $filename;
    }
}
