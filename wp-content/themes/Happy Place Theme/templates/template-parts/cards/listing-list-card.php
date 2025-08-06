<?php
/**
 * Template Part: Listing List Card
 * 
 * Displays a property listing in horizontal card format
 * Used for list view and map sidebar
 * 
 * @package HappyPlace
 * 
 * @param array $args {
 *     Optional arguments for the card
 *     @type int    $post_id      Post ID to display (uses current post if not provided)
 *     @type string $size         Card size: 'default', 'compact' (default: 'default')
 *     @type bool   $show_agent   Whether to display agent info (default: false)
 * }
 */

// Get arguments
$args = wp_parse_args($args, [
    'post_id' => get_the_ID(),
    'size' => 'default',
    'show_agent' => false
]);

// Get listing data
$listing_id = $args['post_id'];
$price = get_field('price', $listing_id);
$bedrooms = get_field('bedrooms', $listing_id);
$bathrooms = get_field('bathrooms', $listing_id);
$square_footage = get_field('square_footage', $listing_id);
$status = get_field('status', $listing_id);
$property_type = get_field('property_type', $listing_id);

// Address fields
$street_address = get_field('street_address', $listing_id);
$city = get_field('city', $listing_id);
$state = get_field('state', $listing_id);
$zip_code = get_field('zip_code', $listing_id);
$full_address = get_field('full_address', $listing_id);

// Location and status display
$display_address = '';
if ($full_address) {
    $display_address = $full_address;
} else {
    if ($street_address) {
        $display_address = $street_address;
        if ($city) $display_address .= ', ' . $city;
        if ($state) $display_address .= ', ' . $state;
        if ($zip_code) $display_address .= ' ' . $zip_code;
    }
}

// Get main photo
$main_photo = '';
$gallery = get_field('photo_gallery', $listing_id);
if ($gallery && !empty($gallery)) {
    // If gallery is an array of IDs
    if (is_array($gallery) && isset($gallery[0]) && is_numeric($gallery[0])) {
        $main_photo = wp_get_attachment_image_url($gallery[0], 'medium');
    } else {
        // ACF "Image Array" return format
        $main_photo = $gallery[0]['sizes']['medium'] ?? $gallery[0]['url'];
    }
} else if (has_post_thumbnail($listing_id)) {
    $main_photo = get_the_post_thumbnail_url($listing_id, 'medium');
} else {
    $main_photo = get_theme_file_uri('assets/images/property-placeholder.jpg');
}

// Agent information
$agent_id = get_field('agent', $listing_id);
$agent_name = '';
$agent_photo = '';
if ($agent_id && $args['show_agent']) {
    $agent_name = get_the_title($agent_id);
    $agent_photo = get_field('profile_photo', $agent_id);
}

// Card classes
$card_classes = ['hph-listing-list-card'];
if ($args['size'] === 'compact') {
    $card_classes[] = 'hph-listing-list-card--compact';
}

// Status classes and labels
$status_class = 'default';
$status_label = __('Available', 'happy-place');
if ($status === 'pending') {
    $status_class = 'warning';
    $status_label = __('Pending', 'happy-place');
} elseif ($status === 'sold') {
    $status_class = 'danger';
    $status_label = __('Sold', 'happy-place');
} elseif ($status === 'coming-soon') {
    $status_class = 'info';
    $status_label = __('Coming Soon', 'happy-place');
}
?>

<article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" 
         data-listing-id="<?php echo esc_attr($listing_id); ?>">
    
    <!-- Card Image -->
    <div class="hph-list-card-image">
        <a href="<?php echo esc_url(get_permalink($listing_id)); ?>">
            <img src="<?php echo esc_url($main_photo); ?>" 
                 alt="<?php echo esc_attr(get_the_title($listing_id)); ?>"
                 class="hph-list-card-photo">
        </a>
        
        <!-- Status Badge -->
        <?php if ($status) : ?>
        <div class="hph-property-status hph-property-status--<?php echo esc_attr($status_class); ?>">
            <?php echo esc_html($status_label); ?>
        </div>
        <?php endif; ?>
        
        <!-- Favorite Button -->
        <button class="hph-action-btn hph-favorite-btn" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>"
                aria-label="<?php esc_attr_e('Save property', 'happy-place'); ?>">
            <i class="far fa-heart"></i>
        </button>
    </div>
    
    <!-- Card Content -->
    <div class="hph-list-card-content">
        <!-- Property Title and Price -->
        <div class="hph-list-card-header">
            <h3 class="hph-property-title">
                <a href="<?php echo esc_url(get_permalink($listing_id)); ?>">
                    <?php echo esc_html(get_the_title($listing_id)); ?>
                </a>
            </h3>
            <?php if ($price) : ?>
            <div class="hph-property-price">
                <?php echo esc_html('$' . number_format($price)); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Property Address -->
        <?php if ($display_address) : ?>
        <div class="hph-property-location">
            <i class="fas fa-map-marker-alt"></i>
            <?php echo esc_html($display_address); ?>
        </div>
        <?php endif; ?>
        
        <!-- Property Stats -->
        <div class="hph-property-stats">
            <?php if ($bedrooms) : ?>
            <div class="hph-property-stat">
                <i class="fas fa-bed"></i>
                <span><?php echo esc_html($bedrooms); ?> <?php echo _n('Bed', 'Beds', $bedrooms, 'happy-place'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($bathrooms) : ?>
            <div class="hph-property-stat">
                <i class="fas fa-bath"></i>
                <span><?php echo esc_html($bathrooms); ?> <?php echo _n('Bath', 'Baths', $bathrooms, 'happy-place'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($square_footage) : ?>
            <div class="hph-property-stat">
                <i class="fas fa-ruler-combined"></i>
                <span><?php echo esc_html(number_format($square_footage)); ?> <?php esc_html_e('Sq Ft', 'happy-place'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($property_type) : ?>
            <div class="hph-property-stat">
                <i class="fas fa-home"></i>
                <span><?php echo esc_html($property_type); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Agent Info (if enabled) -->
        <?php if ($agent_id && $args['show_agent'] && $agent_name) : ?>
        <div class="hph-list-card-agent">
            <?php if ($agent_photo) : ?>
            <div class="hph-agent-photo">
                <img src="<?php echo esc_url($agent_photo); ?>" alt="<?php echo esc_attr($agent_name); ?>">
            </div>
            <?php endif; ?>
            <div class="hph-agent-name">
                <?php echo esc_html($agent_name); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Card Actions -->
        <div class="hph-list-card-actions">
            <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" class="hph-btn hph-btn-sm">
                <?php esc_html_e('View Details', 'happy-place'); ?>
            </a>
        </div>
    </div>
</article>