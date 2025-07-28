<?php
/**
 * Dashboard Marketing Section Template
 * 
 * Marketing tools and flyer generator for agent listings
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$current_agent_id = $current_user->ID;

// Get current listing ID if specified
$current_listing_id = intval($_GET['listing_id'] ?? 0);

// Get agent's listings for flyer generation
$listings_query = new WP_Query([
    'post_type' => 'listing',
    'author' => $current_agent_id,
    'post_status' => ['publish', 'draft'],
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get agent profile data for flyer templates
$agent_data = [
    'name' => $current_user->display_name,
    'email' => $current_user->user_email,
    'phone' => get_user_meta($current_agent_id, 'phone', true),
    'title' => get_user_meta($current_agent_id, 'title', true) ?: 'Real Estate Agent',
    'bio' => get_user_meta($current_agent_id, 'description', true),
    'photo' => get_avatar_url($current_agent_id, ['size' => 200]),
    'license' => get_user_meta($current_agent_id, 'license_number', true)
];
?>

<div class="hph-dashboard-marketing">
    
    <!-- Marketing Header -->
    <div class="hph-marketing-header">
        <div class="hph-marketing-title-group">
            <h2><?php esc_html_e('Marketing Tools', 'happy-place'); ?></h2>
            <p class="hph-marketing-description">
                <?php esc_html_e('Create professional marketing materials for your listings and grow your business.', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-marketing-actions">
            <button type="button" class="hph-btn hph-btn--outline" onclick="showTemplateGallery()">
                <i class="fas fa-images"></i>
                <?php esc_html_e('Template Gallery', 'happy-place'); ?>
            </button>
            <button type="button" class="hph-btn hph-btn--primary" onclick="startNewFlyer()">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Create New Flyer', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="hph-marketing-stats">
        <div class="hph-marketing-stat">
            <div class="hph-stat-icon">
                <i class="fas fa-palette"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo get_user_meta($current_agent_id, '_flyers_created', true) ?: 0; ?></h3>
                <p><?php esc_html_e('Flyers Created', 'happy-place'); ?></p>
            </div>
        </div>
        <div class="hph-marketing-stat">
            <div class="hph-stat-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo get_user_meta($current_agent_id, '_flyers_downloaded', true) ?: 0; ?></h3>
                <p><?php esc_html_e('Downloads', 'happy-place'); ?></p>
            </div>
        </div>
        <div class="hph-marketing-stat">
            <div class="hph-stat-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo $listings_query->found_posts; ?></h3>
                <p><?php esc_html_e('Available Listings', 'happy-place'); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Marketing Content -->
    <div class="hph-marketing-content">
        
        <!-- Flyer Generator Section -->
        <div class="hph-marketing-widget hph-marketing-widget--primary">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-magic"></i>
                    <?php esc_html_e('Flyer Generator', 'happy-place'); ?>
                </h3>
            </div>
            <div class="hph-widget-content">
                <?php
                // Use the existing flyer generator shortcode
                // Pass the current listing ID if available
                $flyer_atts = [];
                if ($current_listing_id) {
                    $flyer_atts['listing_id'] = $current_listing_id;
                }
                echo do_shortcode('[listing_flyer_generator' . 
                    ($current_listing_id ? ' listing_id="' . $current_listing_id . '"' : '') . 
                    ' template="parker_group"]');
                ?>
            </div>
        </div>

        <!-- Recent Flyers and Templates -->
        <div class="hph-marketing-sidebar">
            
            <!-- Recent Flyers -->
            <div class="hph-marketing-widget">
                <div class="hph-widget-header">
                    <h3 class="hph-widget-title">
                        <i class="fas fa-history"></i>
                        <?php esc_html_e('Recent Flyers', 'happy-place'); ?>
                    </h3>
                </div>
                <div class="hph-widget-content">
                    <div class="hph-recent-flyers" id="recent-flyers">
                        <!-- Recent flyers will be loaded here -->
                        <div class="hph-empty-state hph-empty-state--small">
                            <div class="hph-empty-state-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <p><?php esc_html_e('No flyers created yet', 'happy-place'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="hph-marketing-widget">
                <div class="hph-widget-header">
                    <h3 class="hph-widget-title">
                        <i class="fas fa-bolt"></i>
                        <?php esc_html_e('Quick Actions', 'happy-place'); ?>
                    </h3>
                </div>
                <div class="hph-widget-content">
                    <div class="hph-quick-marketing-actions">
                        <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-marketing-action">
                            <div class="hph-action-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="hph-action-content">
                                <h4><?php esc_html_e('Manage Listings', 'happy-place'); ?></h4>
                                <p><?php esc_html_e('Add and edit property listings', 'happy-place'); ?></p>
                            </div>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('section', 'performance')); ?>" class="hph-marketing-action">
                            <div class="hph-action-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="hph-action-content">
                                <h4><?php esc_html_e('View Analytics', 'happy-place'); ?></h4>
                                <p><?php esc_html_e('Track marketing performance', 'happy-place'); ?></p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
                                <p><?php esc_html_e('Edit listing details', 'happy-place'); ?></p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Marketing Tips -->
            <div class="hph-marketing-widget">
                <div class="hph-widget-header">
                    <h3 class="hph-widget-title">
                        <i class="fas fa-lightbulb"></i>
                        <?php esc_html_e('Marketing Tips', 'happy-place'); ?>
                    </h3>
                </div>
                <div class="hph-widget-content">
                    <div class="hph-marketing-tips">
                        <div class="hph-tip">
                            <h5><?php esc_html_e('High-Quality Photos', 'happy-place'); ?></h5>
                            <p><?php esc_html_e('Use bright, clear photos that showcase your property\'s best features.', 'happy-place'); ?></p>
                        </div>
                        
                        <div class="hph-tip">
                            <h5><?php esc_html_e('Consistent Branding', 'happy-place'); ?></h5>
                            <p><?php esc_html_e('Maintain consistent colors, fonts, and logo placement across all materials.', 'happy-place'); ?></p>
                        </div>
                        
                        <div class="hph-tip">
                            <h5><?php esc_html_e('Key Information', 'happy-place'); ?></h5>
                            <p><?php esc_html_e('Include price, bedrooms, bathrooms, and square footage prominently.', 'happy-place'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flyer Preview Modal -->
<div class="hph-modal" id="flyer-preview-modal" style="display: none;">
    <div class="hph-modal-content hph-modal-content--large">
        <div class="hph-modal-header">
            <h3><?php esc_html_e('Flyer Preview', 'happy-place'); ?></h3>
            <button type="button" class="hph-modal-close" onclick="closeFlyerPreview()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hph-modal-body">
            <div class="hph-preview-container">
                <canvas id="preview-canvas" width="400" height="500"></canvas>
            </div>
        </div>
        <div class="hph-modal-footer">
            <button type="button" class="hph-btn hph-btn--outline" onclick="closeFlyerPreview()">
                <?php esc_html_e('Close', 'happy-place'); ?>
            </button>
            <button type="button" class="hph-btn hph-btn--primary" onclick="downloadFromPreview()">
                <i class="fas fa-download"></i>
                <?php esc_html_e('Download', 'happy-place'); ?>
            </button>
        </div>
    </div>
</div>

<script>
// Marketing section JavaScript - minimal since flyer generator shortcode handles functionality
jQuery(document).ready(function($) {
    // Auto-refresh recent flyers if needed
    loadRecentFlyers();
});

function loadRecentFlyers() {
    // Load recent flyers via AJAX
    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_get_recent_flyers',
        agent_id: <?php echo $current_agent_id; ?>,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success && response.data.flyers.length > 0) {
            $('#recent-flyers').html(response.data.html);
        }
    });
}
</script>

<style>
/* Marketing Section Specific Styles */
.hph-dashboard-marketing {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-6);
}

.hph-marketing-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--hph-spacing-4);
}

.hph-marketing-title-group h2 {
    margin: 0 0 var(--hph-spacing-2) 0;
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
}

.hph-marketing-description {
    margin: 0;
    color: var(--hph-color-gray-600);
}

.hph-marketing-actions {
    display: flex;
    gap: var(--hph-spacing-3);
}

.hph-marketing-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--hph-spacing-4);
}

.hph-marketing-stat {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    padding: var(--hph-spacing-5);
    box-shadow: var(--hph-shadow-sm);
    border: 1px solid var(--hph-color-gray-200);
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-4);
}

.hph-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--hph-radius-lg);
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-lg);
}

.hph-stat-content h3 {
    font-size: var(--hph-font-size-xl);
    font-weight: var(--hph-font-bold);
    margin: 0 0 var(--hph-spacing-1) 0;
    color: var(--hph-color-gray-900);
}

.hph-stat-content p {
    margin: 0;
    color: var(--hph-color-gray-600);
    font-size: var(--hph-font-size-sm);
}

.hph-marketing-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--hph-spacing-6);
    align-items: start;
}

.hph-marketing-widget {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    box-shadow: var(--hph-shadow-sm);
    border: 1px solid var(--hph-color-gray-200);
    overflow: hidden;
    margin-bottom: var(--hph-spacing-4);
}

.hph-marketing-widget--primary {
    margin-bottom: 0;
}

.hph-marketing-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-recent-flyers {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-3);
}

.hph-quick-marketing-actions {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-3);
}

.hph-marketing-action {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-3);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    background: none;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.hph-marketing-action:hover {
    border-color: var(--hph-color-primary-300);
    background: var(--hph-color-primary-25);
}

.hph-action-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--hph-radius-lg);
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hph-action-content h4 {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
}

.hph-action-content p {
    margin: 0;
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-marketing-tips {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-tip h5 {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-900);
}

.hph-tip p {
    margin: 0;
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    line-height: 1.5;
}

@media (max-width: 1024px) {
    .hph-marketing-content {
        grid-template-columns: 1fr;
    }
    
    .hph-marketing-header {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 768px) {
    .hph-marketing-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hph-marketing-actions {
        flex-direction: column;
    }
}
</style>
