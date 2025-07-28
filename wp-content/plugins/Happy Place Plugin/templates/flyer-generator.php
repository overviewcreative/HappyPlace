<?php
/**
 * Flyer Generator Template
 * 
 * @package HappyPlace
 * @subpackage Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue flyer generator assets
wp_enqueue_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js', [], '5.3.0', true);
wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', [], '2.5.1', true);
wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
wp_enqueue_style('poppins-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', [], null);

wp_enqueue_style('flyer-generator-css', HPH_ASSETS_URL . 'css/flyer-generator.css', [], HPH_VERSION);
wp_enqueue_script('flyer-generator', HPH_ASSETS_URL . 'js/flyer-generator.js', ['jquery', 'fabric-js'], HPH_VERSION, true);

// Localize script for AJAX
wp_localize_script('flyer-generator', 'flyerGenerator', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('flyer_generator_nonce'),
    'pluginUrl' => HPH_PLUGIN_URL
]);
?>

<div id="flyer-generator-container" class="flyer-generator">
    <div class="flyer-controls">
        <div class="control-group">
            <label for="listing-select">Select Listing:</label>
            <select id="listing-select" name="listing_id">
                <option value="">Choose a listing...</option>
                <?php
                $listings = get_posts([
                    'post_type' => 'listing',
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ]);
                
                foreach ($listings as $listing) {
                    // Use bridge functions for data access
                    $address = hph_bridge_get_address($listing->ID, 'street');
                    $city = hph_bridge_get_address($listing->ID, 'city');
                    $price = hph_bridge_get_price($listing->ID, false);
                    
                    // Debug: Check if functions exist and data is available
                    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
                        error_log("Flyer Generator Debug - Listing {$listing->ID}: Address={$address}, City={$city}, Price={$price}");
                    }
                    
                    if ($address || $city || $price) {
                        $display_parts = array_filter([$address, $city]);
                        $display = implode(', ', $display_parts);
                        if ($price) {
                            $display .= ' - $' . number_format($price);
                        }
                        echo '<option value="' . $listing->ID . '">' . esc_html($display) . '</option>';
                    } else {
                        // Fallback to post title if bridge functions don't return data
                        echo '<option value="' . $listing->ID . '">' . esc_html($listing->post_title) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <div class="control-group">
            <label for="flyer-type-select">Flyer Type:</label>
            <select id="flyer-type-select" name="flyer_type">
                <option value="listing">Standard Listing Flyer</option>
                <option value="open_house">Open House Flyer</option>
            </select>
        </div>
        
        <div class="control-group">
            <button id="generate-flyer" class="hph-btn hph-btn--primary">Generate Flyer</button>
            <button id="download-flyer" class="hph-btn hph-btn--secondary" style="display:none;">Download PNG</button>
            <button id="download-pdf" class="hph-btn hph-btn--secondary" style="display:none;">Download PDF</button>
        </div>
    </div>

    <div class="flyer-preview">
        <canvas id="flyer-canvas" width="850" height="1100"></canvas>
    </div>
    
    <div class="flyer-loading" style="display:none;">
        <div class="loading-spinner"></div>
        <p>Generating your flyer...</p>
    </div>
</div>