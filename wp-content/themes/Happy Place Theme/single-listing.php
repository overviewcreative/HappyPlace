<?php
/**
 * Single Listing Template
 * 
 * Displays individual listings with complete component integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="single-listing-wrapper">
    <?php while (have_posts()) : the_post(); ?>
        
        <article id="listing-<?php the_ID(); ?>" <?php post_class('single-listing'); ?>>
            
            <?php
            // Hero Component
            if (class_exists('HPH_Hero_Component')) {
                $hero_component = new HPH_Hero_Component();
                $hero_data = function_exists('hph_bridge_get_hero_data') 
                    ? hph_bridge_get_hero_data(get_the_ID())
                    : hph_fallback_get_hero_data(get_the_ID());
                echo $hero_component->render($hero_data);
            }
            ?>

            <div class="listing-content-wrapper">
                <div class="container">
                    <div class="row">
                        
                        <!-- Main Content Column -->
                        <div class="col-lg-8">
                            
                            <?php
                            // Property Gallery Component
                            if (class_exists('HPH_Property_Gallery_Component')) {
                                $gallery_component = new HPH_Property_Gallery_Component();
                                $gallery_data = function_exists('hph_bridge_get_gallery_data')
                                    ? hph_bridge_get_gallery_data(get_the_ID())
                                    : hph_fallback_get_gallery_data(get_the_ID());
                                echo $gallery_component->render($gallery_data);
                            }
                            ?>

                            <?php
                            // Property Details Component
                            if (class_exists('HPH_Property_Details_Component')) {
                                $details_component = new HPH_Property_Details_Component();
                                $details_data = function_exists('hph_bridge_get_property_details')
                                    ? hph_bridge_get_property_details(get_the_ID())
                                    : hph_fallback_get_property_details(get_the_ID());
                                echo $details_component->render($details_data);
                            }
                            ?>

                            <?php
                            // Property Features Component
                            if (class_exists('HPH_Property_Features_Component')) {
                                $features_component = new HPH_Property_Features_Component();
                                $features_data = function_exists('hph_bridge_get_features')
                                    ? hph_bridge_get_features(get_the_ID())
                                    : hph_fallback_get_features(get_the_ID());
                                echo $features_component->render($features_data);
                            }
                            ?>

                            <!-- Description Section -->
                            <div class="listing-description-section">
                                <h2>Description</h2>
                                <div class="description-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>

                            <?php
                            // Contact Form Component
                            if (class_exists('HPH_Contact_Form_Component')) {
                                $contact_component = new HPH_Contact_Form_Component();
                                $contact_data = [
                                    'listing_id' => get_the_ID(),
                                    'listing_title' => get_the_title(),
                                    'listing_url' => get_permalink(),
                                    'form_title' => 'Inquire About This Property'
                                ];
                                echo $contact_component->render($contact_data);
                            }
                            ?>

                        </div>

                        <!-- Sidebar Column -->
                        <div class="col-lg-4">
                            <div class="listing-sidebar">

                                <?php
                                // Financial Calculator Component
                                if (class_exists('HPH_Financial_Calculator_Component')) {
                                    $calculator_component = new HPH_Financial_Calculator_Component();
                                    $financial_data = function_exists('hph_bridge_get_financial_data')
                                        ? hph_bridge_get_financial_data(get_the_ID())
                                        : hph_fallback_get_financial_data(get_the_ID());
                                    echo $calculator_component->render($financial_data);
                                }
                                ?>

                                <?php
                                // Agent Card Component
                                if (class_exists('HPH_Agent_Card_Component')) {
                                    $agent_component = new HPH_Agent_Card_Component();
                                    $agent_data = function_exists('hph_bridge_get_agent_data')
                                        ? hph_bridge_get_agent_data(get_the_ID())
                                        : hph_fallback_get_agent_data(get_the_ID());
                                    echo $agent_component->render($agent_data);
                                }
                                ?>

                                <!-- Quick Stats -->
                                <div class="listing-quick-stats widget">
                                    <h3>Quick Facts</h3>
                                    <div class="stats-grid">
                                        <?php
                                        $details = function_exists('hph_bridge_get_property_details')
                                            ? hph_bridge_get_property_details(get_the_ID())
                                            : hph_fallback_get_property_details(get_the_ID());
                                        ?>
                                        <div class="stat-item">
                                            <span class="stat-label">Bedrooms</span>
                                            <span class="stat-value"><?php echo esc_html($details['bedrooms']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Bathrooms</span>
                                            <span class="stat-value"><?php echo esc_html($details['bathrooms']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Square Feet</span>
                                            <span class="stat-value"><?php echo number_format($details['square_footage']); ?></span>
                                        </div>
                                        <?php if (!empty($details['year_built'])) : ?>
                                        <div class="stat-item">
                                            <span class="stat-label">Year Built</span>
                                            <span class="stat-value"><?php echo esc_html($details['year_built']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($details['mls_number'])) : ?>
                                        <div class="stat-item">
                                            <span class="stat-label">MLS #</span>
                                            <span class="stat-value"><?php echo esc_html($details['mls_number']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Share/Save Options -->
                                <div class="listing-actions widget">
                                    <h3>Actions</h3>
                                    <div class="action-buttons">
                                        <button class="btn btn-outline save-listing" data-listing-id="<?php the_ID(); ?>">
                                            <i class="fas fa-heart"></i> Save Listing
                                        </button>
                                        <button class="btn btn-outline share-listing">
                                            <i class="fas fa-share"></i> Share
                                        </button>
                                        <button class="btn btn-outline print-listing">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    </div>
                                </div>

                                <?php
                                // Dynamic sidebar for additional widgets
                                if (is_active_sidebar('listing-sidebar')) {
                                    dynamic_sidebar('listing-sidebar');
                                }
                                ?>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <?php
            // Related Listings Component
            if (class_exists('HPH_Related_Listings_Component')) {
                $related_component = new HPH_Related_Listings_Component();
                $related_data = [
                    'current_listing_id' => get_the_ID(),
                    'limit' => 3,
                    'title' => 'Similar Properties'
                ];
                echo $related_component->render($related_data);
            }
            ?>

        </article>

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
