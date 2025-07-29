<?php
/**
 * Simple Flyer Generator Template - Backup/Testing Version
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
    echo 'Script localization: ' . (wp_script_is('flyer-generator', 'registered') ? 'Registered' : 'Not registered') . '<br>';
    echo '</div>';
}
?>

<div id="flyer-generator-container" class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_style; ?>>
    
    <!-- Header with Parker Group branding like the working version -->
    <div class="flyer-header" style="text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #007cba 0%, #00a0d2 100%); color: white; padding: 20px; border-radius: 8px;">
        <h1 style="margin: 0; font-size: 2.5em; font-weight: bold;">The Parker Group</h1>
        <p style="margin: 10px 0 0 0; font-size: 1.1em;">Real Estate Flyer Generator</p>
    </div>

    <div class="flyer-controls" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div class="control-row" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
            
            <div class="control-group" style="flex: 1; min-width: 250px;">
                <label for="listing-select" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                    <?php _e('Select Listing:', 'happy-place'); ?>
                </label>
                <select id="listing-select" name="listing_id" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    <option value=""><?php _e('Choose a listing...', 'happy-place'); ?></option>
                    <?php
                    // Simple query for listings
                    $listings_query = new WP_Query([
                        'post_type' => 'listing',
                        'posts_per_page' => 20,
                        'post_status' => 'publish',
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ]);
                    
                    if ($listings_query->have_posts()) {
                        while ($listings_query->have_posts()) {
                            $listings_query->the_post();
                            $current_listing_id = get_the_ID();
                            
                            // Simple display - just title and basic info
                            $title = get_the_title();
                            $price_field = get_field('price', $current_listing_id) ?: get_field('listing_price', $current_listing_id);
                            $price = $price_field ? ' - $' . number_format($price_field) : '';
                            
                            $display_text = $title . $price;
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
            <div class="control-group" style="min-width: 150px;">
                <label for="flyer-type-select" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                    <?php _e('Flyer Type:', 'happy-place'); ?>
                </label>
                <select id="flyer-type-select" name="flyer_type" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
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

            <div class="control-group">
                <button id="generate-flyer" type="button" class="button button-primary" style="padding: 12px 24px; font-size: 16px; font-weight: bold; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <?php _e('Generate Flyer', 'happy-place'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="flyer-loading" style="display: none; text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
        <div class="loading-spinner" style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <p style="font-size: 18px; color: #555;"><?php _e('Generating flyer...', 'happy-place'); ?></p>
    </div>

    <div class="flyer-output">
        <div class="flyer-canvas-container" style="text-align: center; margin-bottom: 20px;">
            <canvas id="flyer-canvas" width="612" height="792" style="border: 1px solid #ddd; border-radius: 4px; max-width: 100%; height: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.1);"></canvas>
        </div>
        
        <div class="flyer-download-controls" style="display: none; text-align: center; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <button id="download-flyer" type="button" class="button button-secondary" style="padding: 12px 24px; font-size: 16px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;">
                <?php _e('Download PNG', 'happy-place'); ?>
            </button>
            <button id="download-pdf" type="button" class="button button-secondary" style="padding: 12px 24px; font-size: 16px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;">
                <?php _e('Download PDF', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <div class="flyer-error" style="display: none; background: #fff2f2; border: 1px solid #dc3232; color: #dc3232; padding: 15px; border-radius: 4px; margin: 20px 0;">
        <p class="error-message" style="margin: 0 0 10px;"></p>
        <button type="button" class="dismiss-error button" style="padding: 8px 16px; background: #dc3232; color: white; border: none; border-radius: 4px; cursor: pointer;">
            <?php _e('Dismiss', 'happy-place'); ?>
        </button>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.flyer-download-controls {
    display: flex !important;
}

.flyer-generator .button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .control-row {
        flex-direction: column !important;
    }
    
    .control-group {
        min-width: 100% !important;
    }
    
    #flyer-canvas {
        max-width: 100% !important;
        height: auto !important;
    }
}
</style>

<script>
// Simple initialization check
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== FLYER GENERATOR DEBUG ===');
    console.log('Template loaded');
    console.log('Dependencies check:');
    console.log('- jQuery:', typeof jQuery !== 'undefined' ? '✅ Available' : '❌ Missing');
    console.log('- Fabric.js:', typeof fabric !== 'undefined' ? '✅ Available' : '❌ Missing');
    console.log('- flyerGenerator object:', typeof flyerGenerator !== 'undefined' ? '✅ Available' : '❌ Missing');
    
    if (typeof flyerGenerator !== 'undefined') {
        console.log('flyerGenerator config:', flyerGenerator);
    }
    
    // Check if generate button exists and add click handler
    const generateBtn = document.getElementById('generate-flyer');
    if (generateBtn) {
        console.log('Generate button found');
        generateBtn.addEventListener('click', function() {
            console.log('Generate button clicked!');
            
            // Basic test without dependencies
            const listingId = document.getElementById('listing-select')?.value;
            console.log('Selected listing ID:', listingId);
            
            if (!listingId) {
                alert('Please select a listing first!');
                return;
            }
            
            // Simple AJAX test
            if (typeof flyerGenerator !== 'undefined' && flyerGenerator.ajaxUrl) {
                console.log('Making AJAX request...');
                
                const formData = new FormData();
                formData.append('action', 'generate_flyer');
                formData.append('listing_id', listingId);
                formData.append('flyer_type', 'listing');
                formData.append('nonce', flyerGenerator.nonce);
                
                fetch(flyerGenerator.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('AJAX Response:', data);
                    if (data.success) {
                        alert('Data received! Check console for details.');
                    } else {
                        alert('Error: ' + (data.data?.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    alert('Network error: ' + error.message);
                });
            } else {
                alert('Configuration missing - check console');
            }
        });
    } else {
        console.log('❌ Generate button not found');
    }
    
    console.log('=== END DEBUG ===');
});
</script>
