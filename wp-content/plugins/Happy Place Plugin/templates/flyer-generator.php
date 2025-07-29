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

// Template variables (set by shortcode)
$listing_id = $template_listing_id ?? 0;
$allowed_types = $template_allowed_types ?? ['listing'];
$atts = $template_atts ?? [];

// Add wrapper classes
$wrapper_classes = ['flyer-generator'];
if (!empty($atts['class'])) {
    $wrapper_classes[] = sanitize_html_class($atts['class']);
}

$wrapper_style = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';

// Debug mode check
$debug_mode = defined('WP_DEBUG') && WP_DEBUG;
if ($debug_mode) {
    echo '<div class="flyer-debug-info" style="background: #f0f8ff; padding: 10px; margin-bottom: 20px; border: 1px solid #0073aa; border-radius: 4px;">';
    echo '<strong>Flyer Generator Debug Info:</strong><br>';
    echo 'Template loaded successfully<br>';
    echo 'Listing ID: ' . ($listing_id ?: 'Not specified') . '<br>';
    echo 'Allowed types: ' . implode(', ', $allowed_types) . '<br>';
    echo 'Assets URL: ' . (defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : 'NOT DEFINED') . '<br>';
    echo '</div>';
}
?>

<div id="flyer-generator-container" class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_style; ?>>
    <div class="flyer-controls">
        <div class="control-group">
            <label for="listing-select"><?php _e('Select Listing:', 'happy-place'); ?></label>
            <select id="listing-select" name="listing_id" required>
                <option value=""><?php _e('Choose a listing...', 'happy-place'); ?></option>
                <?php
                $listings_query = new WP_Query([
                    'post_type' => 'listing',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'key' => 'listing_status',
                            'value' => ['active', 'pending', 'sold'],
                            'compare' => 'IN'
                        ],
                        [
                            'key' => 'listing_status',
                            'compare' => 'NOT EXISTS'
                        ]
                    ],
                    'orderby' => 'title',
                    'order' => 'ASC'
                ]);
                
                if ($listings_query->have_posts()) {
                    while ($listings_query->have_posts()) {
                        $listings_query->the_post();
                        $current_listing_id = get_the_ID();
                        
                        // Get listing data safely with proper function checks
                        $address = '';
                        $city = '';
                        $price = '';
                        
                        // Check if bridge functions exist before calling them
                        if (function_exists('hph_bridge_get_address')) {
                            try {
                                $address = hph_bridge_get_address($current_listing_id, 'street');
                                $city = hph_bridge_get_address($current_listing_id, 'city');
                            } catch (Exception $e) {
                                // Fallback to ACF if bridge fails
                                $address = get_field('address', $current_listing_id) ?: get_field('street_address', $current_listing_id);
                                $city = get_field('city', $current_listing_id);
                            }
                        } else {
                            // Fallback to ACF fields
                            $address = get_field('address', $current_listing_id) ?: get_field('street_address', $current_listing_id);
                            $city = get_field('city', $current_listing_id);
                        }
                        
                        if (function_exists('hph_bridge_get_price')) {
                            try {
                                $price = hph_bridge_get_price($current_listing_id, true);
                            } catch (Exception $e) {
                                // Fallback to ACF if bridge fails
                                $price_raw = get_field('price', $current_listing_id) ?: get_field('listing_price', $current_listing_id);
                                $price = $price_raw ? '$' . number_format($price_raw) : '';
                            }
                        } else {
                            $price_raw = get_field('price', $current_listing_id) ?: get_field('listing_price', $current_listing_id);
                            $price = $price_raw ? '$' . number_format($price_raw) : '';
                        }
                        
                        // Build display text
                        $display_parts = [get_the_title()];
                        if ($address || $city) {
                            $location = trim(($address ?: '') . ($address && $city ? ', ' : '') . ($city ?: ''));
                            if ($location) {
                                $display_parts[] = $location;
                            }
                        }
                        if ($price) {
                            $display_parts[] = $price;
                        }
                        
                        $display_text = implode(' - ', $display_parts);
                        $selected = ($listing_id && $current_listing_id === $listing_id) ? ' selected' : '';
                        
                        printf(
                            '<option value="%d"%s>%s</option>',
                            $current_listing_id,
                            $selected,
                            esc_html($display_text)
                        );
                    }
                } else {
                    echo '<option value="" disabled>' . __('No listings found', 'happy-place') . '</option>';
                }
                wp_reset_postdata();
                ?>
            </select>
        </div>

        <?php if (count($allowed_types) > 1): ?>
        <div class="control-group">
            <label for="flyer-type-select"><?php _e('Flyer Type:', 'happy-place'); ?></label>
            <select id="flyer-type-select" name="flyer_type">
                <?php
                $type_labels = [
                    'listing' => __('For Sale', 'happy-place'),
                    'open_house' => __('Open House', 'happy-place'),
                    'sold' => __('Sold', 'happy-place'),
                    'coming_soon' => __('Coming Soon', 'happy-place')
                ];
                
                foreach ($allowed_types as $type) {
                    if (isset($type_labels[$type])) {
                        printf(
                            '<option value="%s">%s</option>',
                            esc_attr($type),
                            esc_html($type_labels[$type])
                        );
                    }
                }
                ?>
            </select>
        </div>
        <?php else: ?>
        <input type="hidden" id="flyer-type-select" value="<?php echo esc_attr($allowed_types[0]); ?>">
        <?php endif; ?>

        <div class="control-group control-actions">
            <button id="generate-flyer" type="button" class="button button-primary">
                <?php _e('Generate Flyer', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <div class="flyer-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <p><?php _e('Generating flyer...', 'happy-place'); ?></p>
    </div>

    <div class="flyer-output">
        <div class="flyer-canvas-container">
            <canvas id="flyer-canvas" width="612" height="792"></canvas>
        </div>
        
        <div class="flyer-download-controls" style="display: none;">
            <button id="download-flyer" type="button" class="button button-secondary">
                <?php _e('Download PNG', 'happy-place'); ?>
            </button>
            <button id="download-pdf" type="button" class="button button-secondary">
                <?php _e('Download PDF', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <div class="flyer-error" style="display: none;">
        <p class="error-message"></p>
        <button type="button" class="button dismiss-error"><?php _e('Dismiss', 'happy-place'); ?></button>
    </div>
</div>

<style>
.flyer-generator {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.flyer-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.control-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.control-group select,
.control-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.control-actions {
    display: flex;
    align-items: end;
}

.flyer-loading {
    text-align: center;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 2rem;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.flyer-canvas-container {
    text-align: center;
    margin-bottom: 2rem;
}

#flyer-canvas {
    border: 1px solid #ddd;
    border-radius: 4px;
    max-width: 100%;
    height: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.flyer-download-controls {
    text-align: center;
    gap: 1rem;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
}

.flyer-error {
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 4px;
    padding: 1rem;
    margin-top: 1rem;
}

.flyer-error .error-message {
    color: #c33;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .flyer-generator {
        margin: 1rem;
        padding: 1rem;
    }
    
    .flyer-controls {
        grid-template-columns: 1fr;
    }
    
    .flyer-download-controls {
        flex-direction: column;
        align-items: center;
    }
    
    .flyer-download-controls .button {
        width: 100%;
        max-width: 200px;
    }
}
</style>
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