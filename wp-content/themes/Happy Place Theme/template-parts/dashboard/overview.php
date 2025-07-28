<?php
/**
 * Dashboard Overview Section Template
 * 
 * Main overview/entry page for the agent dashboard
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

// Get listings data
$listings_query = new WP_Query([
    'post_type' => 'listing',
    'author' => $current_agent_id,
    'posts_per_page' => 5,
    'post_status' => ['publish', 'draft', 'pending'],
    'meta_key' => '_listing_date',
    'orderby' => 'meta_value date',
    'order' => 'DESC'
]);

// Calculate stats
$total_listings = wp_count_posts('listing');
$agent_active_listings = get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'post_status' => 'publish',
    'numberposts' => -1
]);

$agent_pending_listings = get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing', 
    'post_status' => 'draft',
    'numberposts' => -1
]);

// Mock data for leads and open houses (replace with actual queries)
$leads_count = get_user_meta($current_agent_id, '_leads_count', true) ?: 0;
$this_month_views = get_user_meta($current_agent_id, '_monthly_views', true) ?: 0;
?>

<div class="hph-dashboard-overview">
    
    <!-- Welcome Section -->
    <div class="hph-welcome-section">
        <div class="hph-welcome-content">
            <h2><?php printf(esc_html__('Welcome back, %s!', 'happy-place'), esc_html($current_user->display_name)); ?></h2>
            <p class="hph-welcome-description">
                <?php esc_html_e('Here\'s an overview of your real estate business performance and quick access to your most important tools.', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-welcome-actions">
            <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
               class="hph-btn hph-btn--primary">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Add New Listing', 'happy-place'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" 
               class="hph-btn hph-btn--outline">
                <i class="fas fa-list"></i>
                <?php esc_html_e('Manage Listings', 'happy-place'); ?>
            </a>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="hph-stats-grid">
        <div class="hph-stat-card">
            <div class="hph-stat-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo count($agent_active_listings); ?></h3>
                <p><?php esc_html_e('Active Listings', 'happy-place'); ?></p>
            </div>
            <div class="hph-stat-change hph-stat-change--positive">
                <i class="fas fa-arrow-up"></i>
                <span>+<?php echo count($agent_pending_listings); ?> pending</span>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo number_format($this_month_views); ?></h3>
                <p><?php esc_html_e('Monthly Views', 'happy-place'); ?></p>
            </div>
            <div class="hph-stat-change hph-stat-change--positive">
                <i class="fas fa-arrow-up"></i>
                <span>+12% from last month</span>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo $leads_count; ?></h3>
                <p><?php esc_html_e('Active Leads', 'happy-place'); ?></p>
            </div>
            <div class="hph-stat-change hph-stat-change--neutral">
                <i class="fas fa-minus"></i>
                <span>No change</span>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="hph-stat-content">
                <h3>$<?php echo number_format(get_user_meta($current_agent_id, '_monthly_revenue', true) ?: 0); ?></h3>
                <p><?php esc_html_e('Monthly Revenue', 'happy-place'); ?></p>
            </div>
            <div class="hph-stat-change hph-stat-change--positive">
                <i class="fas fa-arrow-up"></i>
                <span>+8% from last month</span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="hph-overview-grid">
        
        <!-- Recent Listings -->
        <div class="hph-overview-widget">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-home"></i>
                    <?php esc_html_e('Recent Listings', 'happy-place'); ?>
                </h3>
                <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-widget-action">
                    <?php esc_html_e('View All', 'happy-place'); ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="hph-widget-content">
                <?php if ($listings_query->have_posts()) : ?>
                    <div class="hph-listing-preview-list">
                        <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                            <?php
                            $listing_id = get_the_ID();
                            $price = get_field('price', $listing_id);
                            $status = get_field('status', $listing_id);
                            $property_type = get_field('property_type', $listing_id);
                            $bedrooms = get_field('bedrooms', $listing_id);
                            $bathrooms = get_field('bathrooms', $listing_id);
                            ?>
                            <div class="hph-listing-preview">
                                <div class="hph-listing-preview-image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    <?php else : ?>
                                        <div class="hph-placeholder-image">
                                            <i class="fas fa-home"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="hph-listing-preview-content">
                                    <h4 class="hph-listing-preview-title"><?php the_title(); ?></h4>
                                    <div class="hph-listing-preview-meta">
                                        <?php if ($price) : ?>
                                            <span class="hph-listing-price">$<?php echo number_format($price); ?></span>
                                        <?php endif; ?>
                                        <?php if ($bedrooms || $bathrooms) : ?>
                                            <span class="hph-listing-details">
                                                <?php echo $bedrooms ? $bedrooms . ' bed' : ''; ?>
                                                <?php echo ($bedrooms && $bathrooms) ? ' • ' : ''; ?>
                                                <?php echo $bathrooms ? $bathrooms . ' bath' : ''; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hph-listing-preview-status">
                                        <span class="hph-listing-status hph-listing-status--<?php echo esc_attr($status ?: 'draft'); ?>">
                                            <?php echo esc_html(ucfirst($status ?: 'Draft')); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="hph-listing-preview-actions">
                                    <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'edit', 'listing_id' => $listing_id])); ?>" 
                                       class="hph-btn hph-btn--sm hph-btn--outline">
                                        <i class="fas fa-edit"></i>
                                        <?php esc_html_e('Edit', 'happy-place'); ?>
                                    </a>
                                    <button type="button" 
                                            class="hph-btn hph-btn--sm hph-btn--primary"
                                            onclick="openFlyerGenerator(<?php echo $listing_id; ?>)">
                                        <i class="fas fa-palette"></i>
                                        <?php esc_html_e('Flyer', 'happy-place'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state hph-empty-state--small">
                        <div class="hph-empty-state-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h4><?php esc_html_e('No listings yet', 'happy-place'); ?></h4>
                        <p><?php esc_html_e('Create your first listing to get started.', 'happy-place'); ?></p>
                        <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
                           class="hph-btn hph-btn--primary hph-btn--sm">
                            <i class="fas fa-plus"></i>
                            <?php esc_html_e('Add Listing', 'happy-place'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="hph-overview-widget hph-overview-widget--sidebar">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-bolt"></i>
                    <?php esc_html_e('Quick Actions', 'happy-place'); ?>
                </h3>
            </div>
            <div class="hph-widget-content">
                <div class="hph-quick-actions">
                    <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
                       class="hph-quick-action">
                        <div class="hph-quick-action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="hph-quick-action-content">
                            <h4><?php esc_html_e('Add New Listing', 'happy-place'); ?></h4>
                            <p><?php esc_html_e('Create a new property listing', 'happy-place'); ?></p>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(add_query_arg(['section' => 'open-houses', 'action' => 'new'])); ?>" 
                       class="hph-quick-action">
                        <div class="hph-quick-action-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="hph-quick-action-content">
                            <h4><?php esc_html_e('Schedule Open House', 'happy-place'); ?></h4>
                            <p><?php esc_html_e('Plan your next open house event', 'happy-place'); ?></p>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(add_query_arg('section', 'marketing')); ?>" 
                       class="hph-quick-action">
                        <div class="hph-quick-action-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="hph-quick-action-content">
                            <h4><?php esc_html_e('Marketing Tools', 'happy-place'); ?></h4>
                            <p><?php esc_html_e('Create flyers and marketing materials', 'happy-place'); ?></p>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(add_query_arg('section', 'performance')); ?>" 
                       class="hph-quick-action">
                        <div class="hph-quick-action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="hph-quick-action-content">
                            <h4><?php esc_html_e('View Performance', 'happy-place'); ?></h4>
                            <p><?php esc_html_e('Check your sales analytics', 'happy-place'); ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="hph-overview-widget hph-overview-widget--full">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-clock"></i>
                    <?php esc_html_e('Recent Activity', 'happy-place'); ?>
                </h3>
                <div class="hph-widget-actions">
                    <button type="button" class="hph-btn hph-btn--sm hph-btn--outline" onclick="refreshActivityFeed()">
                        <i class="fas fa-refresh"></i>
                        <?php esc_html_e('Refresh', 'happy-place'); ?>
                    </button>
                </div>
            </div>
            <div class="hph-widget-content">
                <div class="hph-activity-feed" id="activity-feed">
                    <!-- Activity items will be loaded here via AJAX -->
                    <div class="hph-activity-item">
                        <div class="hph-activity-icon hph-activity-icon--listing">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="hph-activity-content">
                            <p><strong><?php esc_html_e('Listing updated', 'happy-place'); ?></strong></p>
                            <p><?php esc_html_e('You updated the details for "Beautiful Downtown Condo"', 'happy-place'); ?></p>
                            <span class="hph-activity-time"><?php esc_html_e('2 hours ago', 'happy-place'); ?></span>
                        </div>
                    </div>

                    <div class="hph-activity-item">
                        <div class="hph-activity-icon hph-activity-icon--view">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="hph-activity-content">
                            <p><strong><?php esc_html_e('New listing view', 'happy-place'); ?></strong></p>
                            <p><?php esc_html_e('Your listing "Modern Family Home" received 3 new views', 'happy-place'); ?></p>
                            <span class="hph-activity-time"><?php esc_html_e('4 hours ago', 'happy-place'); ?></span>
                        </div>
                    </div>

                    <div class="hph-activity-item">
                        <div class="hph-activity-icon hph-activity-icon--inquiry">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="hph-activity-content">
                            <p><strong><?php esc_html_e('New inquiry', 'happy-place'); ?></strong></p>
                            <p><?php esc_html_e('John Smith is interested in "Luxury Waterfront Property"', 'happy-place'); ?></p>
                            <span class="hph-activity-time"><?php esc_html_e('Yesterday', 'happy-place'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Overview-specific JavaScript
function openFlyerGenerator(listingId) {
    // Redirect to marketing section with the selected listing
    window.location.href = '<?php echo esc_url(add_query_arg('section', 'marketing')); ?>&listing_id=' + listingId;
}

function refreshActivityFeed() {
    const feedElement = document.getElementById('activity-feed');
    if (!feedElement) return;
    
    // Add loading state
    feedElement.classList.add('loading');
    
    // AJAX call to refresh activity
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_get_agent_activity',
        agent_id: <?php echo $current_agent_id; ?>,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            feedElement.innerHTML = response.data.html;
        }
    }).always(function() {
        feedElement.classList.remove('loading');
    });
}

// Initialize overview features
jQuery(document).ready(function($) {
    // Auto-refresh activity feed every 5 minutes
    setInterval(refreshActivityFeed, 300000);
    
    // Initialize tooltips if needed
    $('.hph-stat-change').hover(function() {
        // Add tooltip functionality if needed
    });
});
</script>

<style>
/* Overview Section Specific Styles */
.hph-dashboard-overview {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-6);
}

.hph-welcome-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--hph-spacing-6);
    background: linear-gradient(135deg, var(--hph-color-primary-500), var(--hph-color-primary-600));
    border-radius: var(--hph-radius-xl);
    color: white;
}

.hph-welcome-content h2 {
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
    margin: 0 0 var(--hph-spacing-2) 0;
}

.hph-welcome-description {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

.hph-welcome-actions {
    display: flex;
    gap: var(--hph-spacing-3);
    align-items: center;
}

.hph-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--hph-spacing-4);
}

.hph-stat-card {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    padding: var(--hph-spacing-5);
    box-shadow: var(--hph-shadow-sm);
    border: 1px solid var(--hph-color-gray-200);
    display: flex;
    align-items: flex-start;
    gap: var(--hph-spacing-4);
    position: relative;
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
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
    margin: 0 0 var(--hph-spacing-1) 0;
    color: var(--hph-color-gray-900);
}

.hph-stat-content p {
    color: var(--hph-color-gray-600);
    margin: 0;
    font-size: var(--hph-font-size-sm);
}

.hph-stat-change {
    position: absolute;
    top: var(--hph-spacing-3);
    right: var(--hph-spacing-3);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
}

.hph-stat-change--positive {
    color: var(--hph-color-success-600);
}

.hph-stat-change--negative {
    color: var(--hph-color-danger-600);
}

.hph-stat-change--neutral {
    color: var(--hph-color-gray-500);
}

.hph-overview-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--hph-spacing-6);
    align-items: start;
}

.hph-overview-widget--full {
    grid-column: 1 / -1;
}

.hph-overview-widget {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    box-shadow: var(--hph-shadow-sm);
    border: 1px solid var(--hph-color-gray-200);
    overflow: hidden;
}

.hph-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--hph-spacing-5);
    border-bottom: 1px solid var(--hph-color-gray-200);
    background: var(--hph-color-gray-25);
}

.hph-widget-title {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    margin: 0;
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
}

.hph-widget-action {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
    color: var(--hph-color-primary-600);
    text-decoration: none;
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    transition: color 0.2s ease;
}

.hph-widget-action:hover {
    color: var(--hph-color-primary-700);
}

.hph-widget-content {
    padding: var(--hph-spacing-5);
}

.hph-listing-preview-list {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-listing-preview {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    gap: var(--hph-spacing-4);
    align-items: center;
    padding: var(--hph-spacing-4);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    transition: all 0.2s ease;
}

.hph-listing-preview:hover {
    border-color: var(--hph-color-primary-300);
    box-shadow: var(--hph-shadow-sm);
}

.hph-listing-preview-image {
    width: 80px;
    height: 60px;
    border-radius: var(--hph-radius-md);
    overflow: hidden;
}

.hph-listing-preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hph-placeholder-image {
    width: 100%;
    height: 100%;
    background: var(--hph-color-gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--hph-color-gray-400);
}

.hph-listing-preview-title {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-medium);
}

.hph-listing-preview-meta {
    display: flex;
    gap: var(--hph-spacing-2);
    margin-bottom: var(--hph-spacing-2);
}

.hph-listing-price {
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-primary-600);
}

.hph-listing-details {
    color: var(--hph-color-gray-600);
    font-size: var(--hph-font-size-sm);
}

.hph-listing-status {
    display: inline-block;
    padding: var(--hph-spacing-1) var(--hph-spacing-2);
    border-radius: var(--hph-radius-md);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-listing-status--publish {
    background: var(--hph-color-success-100);
    color: var(--hph-color-success-700);
}

.hph-listing-status--draft {
    background: var(--hph-color-warning-100);
    color: var(--hph-color-warning-700);
}

.hph-listing-status--pending {
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-700);
}

.hph-listing-preview-actions {
    display: flex;
    gap: var(--hph-spacing-2);
}

.hph-quick-actions {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-3);
}

.hph-quick-action {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-4);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.hph-quick-action:hover {
    border-color: var(--hph-color-primary-300);
    background: var(--hph-color-primary-25);
}

.hph-quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--hph-radius-lg);
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
}

.hph-quick-action-content h4 {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
}

.hph-quick-action-content p {
    margin: 0;
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-activity-feed {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-activity-item {
    display: flex;
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-3);
    border-radius: var(--hph-radius-lg);
    transition: background-color 0.2s ease;
}

.hph-activity-item:hover {
    background: var(--hph-color-gray-25);
}

.hph-activity-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--hph-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-sm);
    flex-shrink: 0;
}

.hph-activity-icon--listing {
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-600);
}

.hph-activity-icon--view {
    background: var(--hph-color-success-100);
    color: var(--hph-color-success-600);
}

.hph-activity-icon--inquiry {
    background: var(--hph-color-warning-100);
    color: var(--hph-color-warning-600);
}

.hph-activity-content p {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-sm);
}

.hph-activity-time {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
}

@media (max-width: 768px) {
    .hph-welcome-section {
        flex-direction: column;
        gap: var(--hph-spacing-4);
        align-items: flex-start;
    }
    
    .hph-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hph-overview-grid {
        grid-template-columns: 1fr;
    }
    
    .hph-listing-preview {
        grid-template-columns: 60px 1fr;
        gap: var(--hph-spacing-3);
    }
    
    .hph-listing-preview-actions {
        grid-column: 1 / -1;
        margin-top: var(--hph-spacing-3);
        padding-top: var(--hph-spacing-3);
        border-top: 1px solid var(--hph-color-gray-200);
    }
}
</style>
