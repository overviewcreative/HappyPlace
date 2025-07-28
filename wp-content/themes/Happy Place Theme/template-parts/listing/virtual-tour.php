<?php
/**
 * Virtual Tour & Floor Plans Template Part
 * Clean, modern implementation focusing on virtual tour and floor plans only
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID from parent template
$listing_id = $listing_id ?? get_the_ID();

// Use bridge functions for data retrieval
$virtual_tour_url = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'virtual_tour_url', '') : '';
$tour_type = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'tour_type', 'matterport') : 'matterport';
$tour_title = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'tour_title', 'Virtual Tour') : 'Virtual Tour';

// Get floor plans using bridge functions
$floor_plans = function_exists('get_field') ? get_field('floor_plan_images', $listing_id) : [];

// Get property address for context
$address = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'street') : get_the_title($listing_id);

// Add demo data for lewes-colonial if no real data
if (empty($virtual_tour_url) && empty($floor_plans)) {
    $post = get_post($listing_id);
    if ($post && $post->post_name === 'lewes-colonial') {
        $virtual_tour_url = 'https://my.matterport.com/show/?m=demo123';
        $floor_plans = [
            [
                'url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800',
                'alt' => 'Main Floor Plan',
                'title' => 'Main Level - 1,200 sq ft'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1558618047-3c8c3d4c3d3d?w=800', 
                'alt' => 'Second Floor Plan',
                'title' => 'Upper Level - 600 sq ft'
            ]
        ];
    }
}

// Only show section if we have tour URL or floor plans
if (!empty($virtual_tour_url) || !empty($floor_plans)) {
?>

<section class="hph-virtual-experience" id="virtual-experience">
    <div class="container">
        
        <!-- Section Header -->
        <div class="hph-section-header">
            <h2 class="hph-section-title">Virtual Experience</h2>
            <p class="hph-section-subtitle">Explore <?php echo esc_html($address); ?> from anywhere in the world</p>
        </div>

        <!-- Experience Tabs -->
        <div class="hph-experience-tabs">
            <?php if (!empty($virtual_tour_url)): ?>
            <button class="hph-tab-btn active" data-tab="virtual-tour">
                <i class="fas fa-vr-cardboard"></i>
                <span>Virtual Tour</span>
            </button>
            <?php endif; ?>
            
            <?php if (!empty($floor_plans)): ?>
            <button class="hph-tab-btn <?php echo empty($virtual_tour_url) ? 'active' : ''; ?>" data-tab="floor-plans">
                <i class="fas fa-drafting-compass"></i>
                <span>Floor Plans</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Tab Content -->
        <div class="hph-tab-content">
            
            <?php if (!empty($virtual_tour_url)): ?>
            <!-- Virtual Tour Tab -->
            <div class="hph-tab-panel active" id="virtual-tour-panel">
                <div class="hph-tour-container">
                    
                    <!-- Tour Player -->
                    <div class="hph-tour-player" id="tour-player">
                        
                        <!-- Preview State -->
                        <div class="hph-tour-preview" id="tour-preview">
                            <div class="hph-tour-preview-bg">
                                <div class="hph-preview-pattern"></div>
                            </div>
                            
                            <div class="hph-tour-preview-content">
                                <div class="hph-play-button" id="start-tour-btn" 
                                     data-tour-url="<?php echo esc_url($virtual_tour_url); ?>"
                                     data-tour-type="<?php echo esc_attr($tour_type); ?>">
                                    <div class="hph-play-icon">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                                
                                <div class="hph-tour-info">
                                    <h3 class="hph-tour-title"><?php echo esc_html($tour_title); ?></h3>
                                    <p class="hph-tour-description">Take a 360° interactive walkthrough</p>
                                </div>
                            </div>
                            
                            <!-- Loading State -->
                            <div class="hph-tour-loading" id="tour-loading" style="display: none;">
                                <div class="hph-loading-spinner">
                                    <div class="hph-spinner"></div>
                                </div>
                                <p>Loading virtual tour...</p>
                            </div>
                        </div>
                        
                        <!-- Tour Iframe (hidden initially) -->
                        <div class="hph-tour-iframe-container" id="tour-iframe-container" style="display: none;">
                            <iframe id="tour-iframe" 
                                    src="" 
                                    frameborder="0" 
                                    allowfullscreen 
                                    allow="vr; xr; accelerometer; magnetometer; gyroscope">
                            </iframe>
                            
                            <div class="hph-tour-controls">
                                <button class="hph-tour-control-btn" id="fullscreen-btn" title="Fullscreen">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="hph-tour-control-btn" id="close-tour-btn" title="Close tour">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tour Features -->
                    <div class="hph-tour-features">
                        <h4 class="hph-features-title">Tour Features</h4>
                        
                        <div class="hph-features-list">
                            <div class="hph-feature-item">
                                <i class="fas fa-mouse"></i>
                                <span>Interactive Navigation</span>
                            </div>
                            <div class="hph-feature-item">
                                <i class="fas fa-expand-arrows-alt"></i>
                                <span>360° Room Views</span>
                            </div>
                            <div class="hph-feature-item">
                                <i class="fas fa-cube"></i>
                                <span>3D Dollhouse View</span>
                            </div>
                            <div class="hph-feature-item">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Mobile Compatible</span>
                            </div>
                        </div>
                        
                        <div class="hph-tour-actions">
                            <button class="hph-btn hph-btn--primary" id="schedule-tour-btn">
                                <i class="fas fa-calendar-plus"></i>
                                Schedule In-Person Tour
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($floor_plans)): ?>
            <!-- Floor Plans Tab -->
            <div class="hph-tab-panel <?php echo empty($virtual_tour_url) ? 'active' : ''; ?>" id="floor-plans-panel">
                <div class="hph-floor-plans-container">
                    
                    <!-- Floor Plans Grid -->
                    <div class="hph-floor-plans-grid">
                        <?php foreach ($floor_plans as $index => $plan): ?>
                        <div class="hph-floor-plan-item" data-plan="<?php echo $index; ?>">
                            <div class="hph-plan-image">
                                <img src="<?php echo esc_url($plan['url']); ?>" 
                                     alt="<?php echo esc_attr($plan['alt'] ?? 'Floor Plan'); ?>" 
                                     loading="lazy">
                                <div class="hph-plan-overlay">
                                    <button class="hph-view-plan-btn" data-plan-index="<?php echo $index; ?>">
                                        <i class="fas fa-search-plus"></i>
                                        View Full Size
                                    </button>
                                </div>
                            </div>
                            <div class="hph-plan-info">
                                <h4 class="hph-plan-title"><?php echo esc_html($plan['title'] ?? 'Floor Plan ' . ($index + 1)); ?></h4>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Floor Plan Actions -->
                    <div class="hph-floor-plan-actions">
                        <button class="hph-btn hph-btn--outline" id="download-plans-btn">
                            <i class="fas fa-download"></i>
                            Download Floor Plans
                        </button>
                        <button class="hph-btn hph-btn--primary" id="request-info-btn">
                            <i class="fas fa-info-circle"></i>
                            Request More Information
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<!-- Floor Plan Modal -->
<?php if (!empty($floor_plans)): ?>
<div class="hph-modal" id="floor-plan-modal" style="display: none;">
    <div class="hph-modal-backdrop" id="modal-backdrop"></div>
    <div class="hph-modal-content">
        <div class="hph-modal-header">
            <h3 class="hph-modal-title" id="modal-plan-title">Floor Plan</h3>
            <button class="hph-modal-close" id="modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hph-modal-body">
            <img id="modal-plan-image" src="" alt="Floor Plan" />
        </div>
    </div>
</div>
<?php endif; ?>

<?php
} // End if virtual tour or floor plans exist
?>