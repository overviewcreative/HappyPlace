<?php
/**
 * Template for displaying single property listings
 *
 * @package Happy_Place_Theme
 */

get_header();

// Load property data with corrected field names
$price = get_field('price');
$status = get_field('status') ?: 'Active'; // Default to Active if not set
$status_class = sanitize_html_class(strtolower($status));
$status_display = esc_html($status);
$street_address = get_field('street_address');
$city = get_field('city');
$state = get_field('region'); // Changed from state
$zip = get_field('zip_code'); // Changed from zip
$bedrooms = get_field('bedrooms');
$bathrooms = get_field('bathrooms');
$square_feet = get_field('square_footage'); // Changed from square_feet
$lot_size = get_field('lot_size');
$year_built = get_field('year_built');
$description = get_field('short_description'); // Changed from description
$virtual_tour = get_field('virtual_tour_link'); // Changed from virtual_tour_url
$agent_id = get_field('agent');
$mls_number = get_field('mls_number');
$price_per_sqft = get_field('price_per_sqft');

// Get main photo or gallery image for hero
$main_photo = get_field('main_photo');
$hero_image = '';

if ($main_photo) {
    $hero_image = $main_photo;
} elseif (!empty($gallery_images)) {
    $hero_image = $gallery_images[0]['url'];
} elseif (has_post_thumbnail()) {
    $hero_image = get_the_post_thumbnail_url(get_the_ID(), 'full');
} else {
    $hero_image = get_theme_file_uri('assets/images/property-placeholder.jpg');
}

// Get address fields
$full_address = get_field('full_address');
if (empty($full_address)) {
    $full_address = $street_address;
    if ($city) $full_address .= ', ' . $city;
    if ($region) $full_address .= ', ' . $region;
    if ($zip) $full_address .= ' ' . $zip_code;
}

// Get feature fields
$features = get_field('features') ?: array(); // Primary features field
$exterior_features = get_field('exterior_features') ?: array();
$utility_features = get_field('utility_features') ?: array();

// Ensure arrays and clean empty values
$features = is_array($features) ? array_filter($features) : array();
$exterior_features = is_array($exterior_features) ? array_filter($exterior_features) : array();
$utility_features = is_array($utility_features) ? array_filter($utility_features) : array();

// Get gallery images
$gallery = get_field('photo_gallery');
$gallery_images = !empty($gallery) ? $gallery : array();

// Get current user favorites
$favorites = array();
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user_favorites = get_user_meta($user_id, 'hph_favorites', true);
    if (!empty($user_favorites)) {
        $favorites = explode(',', $user_favorites);
    }
}

// Check if this listing is in favorites
$is_favorite = in_array(get_the_ID(), $favorites);
?>

<main class="hph-main hph-single-listing">
    <!-- Property Header -->
    <div class="hph-listing-hero">
        <img src="<?php echo esc_url($hero_image); ?>" 
             alt="<?php echo esc_attr(get_the_title()); ?>" 
             class="hph-listing-hero-image"
             loading="eager">
        
        <div class="hph-listing-hero-overlay">
            <div class="hph-container">
                <div class="hph-listing-status hph-listing-status--<?php echo $status_class; ?>">
                    <?php echo $status_display; ?>
                </div>
                
                <h1 class="hph-listing-title"><?php the_title(); ?></h1>
                
                <div class="hph-listing-address">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo esc_html($full_address); ?>
                </div>
                
                <div class="hph-listing-price">
                    $<?php echo number_format($price); ?>
                </div>
                
                <div class="hph-listing-hero-stats">
                    <span><i class="fas fa-bed"></i> <?php echo esc_html($bedrooms); ?> beds</span>
                    <span><i class="fas fa-bath"></i> <?php echo esc_html($bathrooms); ?> baths</span>
                    <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($square_feet); ?> sq ft</span>
                    <?php if ($lot_size) : ?>
                        <span><i class="fas fa-tree"></i> <?php echo esc_html($lot_size); ?> acres</span>
                    <?php endif; ?>
                </div>
                
                <div class="hph-listing-actions">
                    <button class="hph-btn hph-btn-primary hph-btn-schedule">
                        <i class="far fa-calendar-alt"></i> Schedule Showing
                    </button>
                    
                    <button class="hph-btn hph-btn-outline hph-btn-favorite <?php echo $is_favorite ? 'is-favorite' : ''; ?>" 
                            data-id="<?php echo get_the_ID(); ?>" 
                            data-nonce="<?php echo wp_create_nonce('hph_favorite_nonce'); ?>">
                        <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span><?php echo $is_favorite ? 'Saved' : 'Save'; ?></span>
                    </button>
                    
                    <button class="hph-btn hph-btn-outline hph-btn-share">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="hph-container">
        <div class="hph-listing-content-wrapper">
            <!-- Left Column (Main Content) -->
            <div class="hph-listing-main">
                <!-- Gallery Section -->
                <div class="hph-listing-gallery-section">
                    <div class="hph-gallery">
                        <div class="hph-gallery-grid">
                            <?php if ($gallery_images) : ?>
                                <?php $count = 0; ?>
                                <?php foreach ($gallery_images as $image) : ?>
                                    <div class="hph-gallery-item <?php echo ($count === 0) ? 'hph-gallery-main' : ''; ?>">
                                        <img src="<?php echo esc_url($image['url']); ?>" 
                                             alt="<?php echo esc_attr($image['alt']); ?>"
                                             data-full="<?php echo esc_url($image['url']); ?>">
                                    </div>
                                    <?php $count++; ?>
                                    <?php if ($count >= 5) break; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($gallery_images) > 5) : ?>
                                    <div class="hph-gallery-more">
                                        <span>+<?php echo count($gallery_images) - 5; ?> Photos</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Description Section -->
                <div class="hph-card hph-listing-description">
                    <h2>About This Property</h2>
                    <div class="hph-listing-description-content">
                        <?php echo wpautop($description); ?>
                    </div>
                </div>
                
                <!-- Property Details Section -->
                <div class="hph-card hph-listing-details-section">
                    <h2>Property Details</h2>
                    
                    <div class="hph-property-details-grid">
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Property Type</div>
                            <div class="hph-property-detail-value">
                                <?php 
                                if (!empty($property_types)) {
                                    echo esc_html(implode(', ', array_map('ucwords', $property_types)));
                                } else {
                                    echo esc_html__('Not Specified', 'happy-place');
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Year Built</div>
                            <div class="hph-property-detail-value"><?php echo esc_html($year_built); ?></div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Square Footage</div>
                            <div class="hph-property-detail-value"><?php echo number_format($square_feet); ?> sq ft</div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Lot Size</div>
                            <div class="hph-property-detail-value"><?php echo esc_html($lot_size); ?> acres</div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Bedrooms</div>
                            <div class="hph-property-detail-value"><?php echo esc_html($bedrooms); ?></div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Bathrooms</div>
                            <div class="hph-property-detail-value"><?php echo esc_html($bathrooms); ?></div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">Garage</div>
                            <div class="hph-property-detail-value"><?php echo esc_html($garage); ?> car</div>
                        </div>
                        
                        <div class="hph-property-detail">
                            <div class="hph-property-detail-label">MLS#</div>
                            <div class="hph-property-detail-value"><?php echo esc_html($mls_number); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Features Section -->
                <div class="hph-card hph-listing-features">
                    <h2>Features & Amenities</h2>
                    
                    <?php if (!empty($features)) : ?>
                        <h3>Interior Features</h3>
                        <ul>
                            <?php foreach ($features as $feature) : 
                                if (empty($feature)) continue;
                                $display_name = str_replace(['_', '-'], ' ', $feature);
                                $display_name = ucwords($display_name);
                                $icon_class = $feature_icons[sanitize_title($feature)] ?? $feature_icons['default'];
                            ?>
                                <li>
                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                    <?php echo esc_html($display_name); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($exterior_features)) : ?>
                        <h3>Exterior Features</h3>
                        <ul>
                            <?php foreach ($exterior_features as $feature) : 
                                $icon_class = $feature_icons[$feature] ?? $feature_icons['default'];
                            ?>
                                <li>
                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($utility_features)) : ?>
                        <h3>Utility Features</h3>
                        <ul>
                            <?php foreach ($utility_features as $feature) : 
                                $icon_class = $feature_icons[$feature] ?? $feature_icons['default'];
                            ?>
                                <li>
                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- Location Section -->
                <?php 
                $latitude = get_field('latitude');
                $longitude = get_field('longitude');
                if ($latitude && $longitude) : 
                ?>
                    <div class="hph-card hph-listing-map">
                        <h2>Location</h2>
                        
                        <div id="property-map" 
                             class="hph-property-map" 
                             data-lat="<?php echo esc_attr($latitude); ?>" 
                             data-lng="<?php echo esc_attr($longitude); ?>"
                             data-title="<?php echo esc_attr(get_the_title()); ?>"
                             data-address="<?php echo esc_attr($full_address); ?>">
                        </div>
                        
                        <div class="hph-nearby-places">
                            <h3>Explore Nearby</h3>
                            <div class="hph-nearby-places-buttons">
                                <button class="hph-nearby-places-btn" data-type="restaurant">
                                    <i class="fas fa-utensils"></i> Restaurants
                                </button>
                                <button class="hph-nearby-places-btn" data-type="school">
                                    <i class="fas fa-school"></i> Schools
                                </button>
                                <button class="hph-nearby-places-btn" data-type="shopping_mall">
                                    <i class="fas fa-shopping-bag"></i> Shopping
                                </button>
                                <button class="hph-nearby-places-btn" data-type="park">
                                    <i class="fas fa-tree"></i> Parks
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Virtual Tour Section -->
                <?php if ($virtual_tour) : ?>
                    <div class="hph-card hph-virtual-tour-section">
                        <h2>Virtual Tour</h2>
                        
                        <div class="hph-virtual-tour-container">
                            <iframe src="<?php echo esc_url($virtual_tour); ?>" 
                                    class="hph-virtual-tour-iframe" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column (Sidebar) -->
            <div class="hph-listing-sidebar">
                <!-- Agent Card -->
                <?php 
                // Get agents who manage this listing
                $args = array(
                    'post_type' => 'agent',
                    'posts_per_page' => 1,
                    'meta_query' => array(
                        array(
                            'key' => 'managed_listings',
                            'value' => '"' . get_the_ID() . '"',
                            'compare' => 'LIKE'
                        )
                    )
                );
                
                $agent_query = new WP_Query($args);
                
                if ($agent_query->have_posts()) : 
                    $agent_query->the_post();
                    $agent_id = get_the_ID();
                    // Get agent data with fallbacks
                    $agent_name = get_the_title();
                    $profile_photo = get_field('profile_photo');
                    $agent_image = $profile_photo ? $profile_photo['url'] : '';
                    $agent_phone = get_field('phone');
                    $agent_email = get_field('email');
                    $agent_license = get_field('license_number');
                    $agent_license_state = get_field('license_state');
                    $contact_prefs = get_field('contact_preferences');
                    $service_areas = get_field('service_areas');
                    $social_links = get_field('social_links');
                    $certifications = get_field('certifications');
                ?>
                    <div class="hph-card hph-agent-card">
                        <div class="hph-agent-header">
                            <div class="hph-agent-image">
                                <?php if ($agent_image) : ?>
                                    <img src="<?php echo esc_url($agent_image); ?>" 
                                         alt="<?php echo esc_attr($agent_name); ?>"
                                         loading="lazy">
                                <?php else : ?>
                                    <div class="hph-agent-placeholder">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="hph-agent-info">
                                <h3 class="hph-agent-name"><?php echo esc_html($agent_name); ?></h3>
                                <div class="hph-agent-meta">
                                    <?php if ($agent_license) : ?>
                                        <div class="hph-agent-license">
                                            License #<?php echo esc_html($agent_license); ?>
                                            <?php if ($agent_license_state) : ?>
                                                (<?php echo esc_html(strtoupper($agent_license_state)); ?>)
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($certifications) : ?>
                                        <div class="hph-agent-certifications">
                                            <?php foreach ($certifications as $cert) : ?>
                                                <span class="hph-certification">
                                                    <?php echo esc_html($cert['name']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="hph-agent-contact">
                            <?php 
                            // Get contact preferences
                            $contact_prefs = get_field('contact_preferences', $agent_id) ?: [];
                            $phone = get_field('phone', $agent_id);
                            $email = get_field('email', $agent_id);
                            $schedule_link = get_field('schedule_link', $agent_id);
                            $chat_link = get_field('chat_link', $agent_id);
                            ?>

                            <?php if ($schedule_link) : ?>
                                <a href="<?php echo esc_url($schedule_link); ?>" 
                                   class="hph-btn hph-btn-primary hph-btn-block"
                                   target="_blank">
                                    <i class="far fa-calendar-alt"></i> Schedule Showing
                                </a>
                            <?php endif; ?>

                            <?php if ($phone && in_array('phone_ok', $contact_prefs)) : ?>
                                <a href="tel:<?php echo esc_attr($phone); ?>" 
                                   class="hph-btn hph-btn-outline hph-btn-block">
                                    <i class="fas fa-phone"></i> <?php echo esc_html($phone); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($chat_link && in_array('text_ok', $contact_prefs)) : ?>
                                <a href="<?php echo esc_url($chat_link); ?>" 
                                   class="hph-btn hph-btn-outline hph-btn-block"
                                   target="_blank">
                                    <i class="fas fa-comment"></i> Chat Now
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($email && in_array('email_ok', $contact_prefs)) : ?>
                                <a href="mailto:<?php echo esc_attr($email); ?>" 
                                   class="hph-btn hph-btn-outline hph-btn-block">
                                    <i class="fas fa-envelope"></i> Email Agent
                                </a>
                            <?php endif; ?>

                            <a href="<?php echo get_permalink($agent_id); ?>" 
                               class="hph-btn hph-btn-outline hph-btn-block">
                                View Full Profile
                            </a>
                        </div>

                        <?php if ($social_links) : ?>
                            <div class="hph-agent-social">
                                <?php foreach ($social_links as $social) : 
                                    if (empty($social['url'])) continue;
                                ?>
                                    <a href="<?php echo esc_url($social['url']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="hph-agent-social-link">
                                        <i class="fab fa-<?php echo esc_attr($social['platform']); ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($service_areas) : ?>
                            <div class="hph-agent-service-areas">
                                <h4>Service Areas</h4>
                                <div class="hph-service-area-tags">
                                    <?php foreach ($service_areas as $area) : ?>
                                        <span class="hph-service-area-tag">
                                            <?php echo esc_html(str_replace('_', ' ', ucwords($area))); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php 
                wp_reset_postdata();
                endif; 
                ?>

                <!-- Contact Form -->
                <div class="hph-card hph-contact-form-card">
                    <h3>Interested in this property?</h3>
                    
                    <form class="hph-listing-contact-form" id="property-inquiry-form">
                        <input type="hidden" name="property_id" value="<?php echo get_the_ID(); ?>">
                        
                        <div class="hph-form-group">
                            <label for="inquiry-name">Your Name</label>
                            <input type="text" id="inquiry-name" name="name" required>
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="inquiry-email">Email Address</label>
                            <input type="email" id="inquiry-email" name="email" required>
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="inquiry-phone">Phone Number</label>
                            <input type="tel" id="inquiry-phone" name="phone">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="inquiry-message">Message</label>
                            <textarea id="inquiry-message" name="message" rows="4" required>I'm interested in this property. Please contact me with more information.</textarea>
                        </div>
                        
                        <div class="hph-form-group hph-form-checkbox">
                            <input type="checkbox" id="inquiry-consent" name="consent" required>
                            <label for="inquiry-consent">
                                I consent to receiving communications about this property and related services.
                            </label>
                        </div>
                        
                        <button type="submit" class="hph-btn hph-btn-primary hph-btn-block">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Mortgage Calculator -->
                <div class="hph-card hph-mortgage-calculator">
                    <h3>Mortgage Calculator</h3>
                    
                    <form class="hph-mortgage-calculator-form" id="mortgage-calculator-form">
                        <input type="hidden" id="property-price" value="<?php echo esc_attr($price); ?>">
                        
                        <div class="hph-form-group">
                            <label for="down-payment">Down Payment</label>
                            <div class="hph-input-group">
                                <span class="hph-input-group-text">$</span>
                                <input type="number" id="down-payment" value="<?php echo round($price * 0.2); ?>">
                            </div>
                            <input type="range" id="down-payment-slider" min="0" max="<?php echo esc_attr($price); ?>" value="<?php echo round($price * 0.2); ?>" step="1000">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="interest-rate">Interest Rate</label>
                            <div class="hph-input-group">
                                <input type="number" id="interest-rate" value="5.5" step="0.1">
                                <span class="hph-input-group-text">%</span>
                            </div>
                            <input type="range" id="interest-rate-slider" min="2" max="10" value="5.5" step="0.1">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="loan-term">Loan Term</label>
                            <div class="hph-input-group">
                                <input type="number" id="loan-term" value="30">
                                <span class="hph-input-group-text">years</span>
                            </div>
                            <input type="range" id="loan-term-slider" min="5" max="30" value="30" step="5">
                        </div>
                        
                        <div class="hph-mortgage-result">
                            <div class="hph-mortgage-payment">
                                <span class="hph-mortgage-payment-label">Monthly Payment:</span>
                                <span class="hph-mortgage-payment-value" id="monthly-payment">$0</span>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Similar Properties -->
                <?php
                // Get property type
                $property_types = get_the_terms(get_the_ID(), 'property_type');
                
                if ($property_types) {
                    $property_type_ids = array();
                    foreach ($property_types as $property_type) {
                        $property_type_ids[] = $property_type->term_id;
                    }
                    
                    // Similar properties query
                    $similar_args = array(
                        'post_type' => 'listing',
                        'posts_per_page' => 3,
                        'post__not_in' => array(get_the_ID()),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'property_type',
                                'field' => 'term_id',
                                'terms' => $property_type_ids
                            )
                        ),
                        'meta_query' => array(
                            array(
                                'key' => 'bedrooms',
                                'value' => $bedrooms,
                                'compare' => 'BETWEEN',
                                'type' => 'NUMERIC',
                                'value' => array($bedrooms - 1, $bedrooms + 1)
                            ),
                            array(
                                'key' => 'price',
                                'value' => array($price * 0.8, $price * 1.2),
                                'compare' => 'BETWEEN',
                                'type' => 'NUMERIC'
                            )
                        )
                    );
                    
                    $similar_query = new WP_Query($similar_args);
                    
                    if ($similar_query->have_posts()) :
                    ?>
                        <div class="hph-card hph-similar-properties">
                            <h3>Similar Properties</h3>
                            
                            <div class="hph-similar-properties-list">
                                <?php while ($similar_query->have_posts()) : $similar_query->the_post(); ?>
                                    <?php
                                    $similar_price = get_field('price');
                                    $similar_bedrooms = get_field('bedrooms');
                                    $similar_bathrooms = get_field('bathrooms');
                                    $similar_sqft = get_field('square_feet');
                                    ?>
                                    
                                    <div class="hph-similar-property">
                                        <a href="<?php the_permalink(); ?>" class="hph-similar-property-link">
                                            <div class="hph-similar-property-image">
                                                <?php if (has_post_thumbnail()) : ?>
                                                    <?php the_post_thumbnail('medium'); ?>
                                                <?php else : ?>
                                                    <div class="hph-similar-property-placeholder"></div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="hph-similar-property-info">
                                                <div class="hph-similar-property-price">$<?php echo number_format($similar_price); ?></div>
                                                <h4 class="hph-similar-property-title"><?php the_title(); ?></h4>
                                                <div class="hph-similar-property-meta">
                                                    <span><?php echo esc_html($similar_bedrooms); ?> bd</span>
                                                    <span><?php echo esc_html($similar_bathrooms); ?> ba</span>
                                                    <span><?php echo number_format($similar_sqft); ?> sq ft</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php } ?>
            </div>
        </div>
    </div>
</main>

<!-- Gallery Modal -->
<div class="hph-gallery-modal" id="gallery-modal">
    <div class="hph-gallery-modal-header">
        <h3 class="hph-gallery-modal-title"><?php the_title(); ?> - Photo Gallery</h3>
        <button class="hph-gallery-modal-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="hph-gallery-modal-content">
        <div class="hph-gallery-modal-main">
            <img src="" alt="" class="hph-gallery-modal-image">
            
            <button class="hph-gallery-nav hph-gallery-prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <button class="hph-gallery-nav hph-gallery-next">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="hph-gallery-modal-thumbs">
            <?php if ($gallery_images) : ?>
                <?php foreach ($gallery_images as $index => $image) : ?>
                    <div class="hph-gallery-thumb" data-index="<?php echo esc_attr($index); ?>">
                        <img src="<?php echo esc_url($image['sizes']['thumbnail']); ?>" 
                             alt="<?php echo esc_attr($image['alt']); ?>"
                             data-full="<?php echo esc_url($image['url']); ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contact Form Success Message -->
<div class="hph-form-message success" id="inquiry-success" style="display: none;">
    <i class="fas fa-check-circle"></i> 
    Your message has been sent successfully! We'll be in touch soon.
</div>

<?php get_footer(); ?>