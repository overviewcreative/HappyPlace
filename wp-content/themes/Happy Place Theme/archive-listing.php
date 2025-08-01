
<?php
/**
 * Import required component classes
 */
use HappyPlace\Components\Listing\Hero;
use HappyPlace\Components\Listing\Gallery;
use HappyPlace\Components\Listing\Card;
use HappyPlace\Components\Agent\Card as AgentCard;
use HappyPlace\Components\Agent\Profile;
use HappyPlace\Components\Tools\Mortgage_Calculator;
use HappyPlace\Components\UI\Button;
use HappyPlace\Components\UI\Modal;

// Safety check for component availability
if (!class_exists('HappyPlace\Components\Listing\Hero')) {
    // Load theme manager to ensure components are available
    if (class_exists('HappyPlace\Core\Theme_Manager')) {
        HappyPlace\Core\Theme_Manager::get_instance();
    }
}

/**
 * Archive template for listings
 * 
 * @package Happy_Place_Theme
 * @since 1.0.0
 */

get_header();

// Get archive data using existing bridge functions
$archive_data = function_exists('hph_bridge_get_archive_data') 
    ? hph_bridge_get_archive_data('listing') 
    : ['title' => 'Listings', 'description' => ''];

// Get listings using existing bridge functions
$listings = function_exists('hph_bridge_get_listings') 
    ? hph_bridge_get_listings([
        'status' => 'publish',
        'posts_per_page' => get_option('posts_per_page', 12)
    ])
    : get_posts(['post_type' => 'listing', 'numberposts' => 12]);

// Pagination data
$pagination_data = function_exists('hph_bridge_get_pagination_data')
    ? hph_bridge_get_pagination_data()
    : [];

$args = [
    'listings' => $listings,
    'archive_data' => $archive_data,
    'pagination' => $pagination_data
];
?>

<div class="archive-listings">
    <!-- Archive Header -->
    <div class="archive-header">
        <div class="container">
            <h1 class="archive-title"><?php echo esc_html($archive_data['title'] ?? 'Listings'); ?></h1>
            <?php if (!empty($archive_data['description'])): ?>
                <p class="archive-description"><?php echo esc_html($archive_data['description']); ?></p>
            <?php endif; ?>
            
            <div class="archive-stats">
                <?php
                $total_listings = wp_count_posts('listing');
                if ($total_listings && $total_listings->publish > 0): ?>
                    <span class="listings-count"><?php echo esc_html($total_listings->publish); ?> listings available</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Search & Filters -->
    <div class="archive-filters">
        <div class="container">
            <?php
            // Use existing search form component if available
            if (class_exists('HappyPlace\Components\UI\Search_Form')) {
                $search_form = new HappyPlace\Components\UI\Search_Form([
                    'search_fields' => ['location', 'price', 'bedrooms', 'property_type'],
                    'layout' => 'horizontal',
                    'submit_method' => 'ajax'
                ]);
                $search_form->display();
            } else {
                // Fallback search form
                ?>
                <form class="search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="hidden" name="post_type" value="listing">
                    <input type="search" name="s" placeholder="Search listings..." value="<?php echo get_search_query(); ?>">
                    <button type="submit">Search</button>
                </form>
                <?php
            }
            ?>
        </div>
    </div>
    
    <!-- Listings Grid -->
    <div class="archive-content">
        <div class="container">
            <?php if (!empty($listings)): ?>
                <div class="listings-grid">
                    <?php
                    // Use existing listing grid component if available
                    if (class_exists('HappyPlace\Components\Listing\Listing_Grid')) {
                        $listing_grid = new HappyPlace\Components\Listing\Listing_Grid([
                            'listings' => $listings,
                            'columns' => 3,
                            'gap' => 'medium',
                            'pagination' => true
                        ]);
                        $listing_grid->display();
                    } else {
                        // Fallback grid using listing cards
                        foreach ($listings as $listing):
                            if (class_exists('HappyPlace\Components\Listing\Listing_Card')) {
                                $listing_card = new HappyPlace\Components\Listing\Listing_Card([
                                    'listing_id' => $listing->ID ?? $listing,
                                    'variant' => 'default',
                                    'context' => 'archive'
                                ]);
                                $listing_card->display();
                            } else {
                                // Ultimate fallback - basic listing display
                                $listing_id = is_object($listing) ? $listing->ID : $listing;
                                echo '<div class="listing-card">';
                                echo '<h3><a href="' . get_permalink($listing_id) . '">' . get_the_title($listing_id) . '</a></h3>';
                                if (has_post_thumbnail($listing_id)) {
                                    echo get_the_post_thumbnail($listing_id, 'medium');
                                }
                                echo '<div class="listing-excerpt">' . get_the_excerpt($listing_id) . '</div>';
                                echo '</div>';
                            }
                        endforeach;
                    }
                    ?>
                </div>
                
                <!-- Pagination -->
                <div class="archive-pagination">
                    <?php
                    // Use pagination component if available or fall back to WordPress pagination
                    if (class_exists('HappyPlace\Components\UI\Pagination') && !empty($pagination_data)) {
                        $pagination = new HappyPlace\Components\UI\Pagination($pagination_data);
                        $pagination->display();
                    } else {
                        the_posts_pagination([
                            'prev_text' => '← Previous',
                            'next_text' => 'Next →',
                            'mid_size' => 2
                        ]);
                    }
                    ?>
                </div>
                
            <?php else: ?>
                <div class="no-listings">
                    <h2>No listings found</h2>
                    <p>There are currently no listings available. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
