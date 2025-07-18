<?php
/**
 * Listing Swipe Card Component - Design Standard
 * 
 * The flagship component that establishes visual and functional standards
 * for all other components in the Happy Place theme.
 *
 * @package HappyPlace\Components\Cards
 * @since 2.0.0
 */

namespace HappyPlace\Components\Cards;

use HappyPlace\Components\Base_Component;

class Listing_Swipe_Card extends Base_Component {
    
    /**
     * Component defaults
     */
    protected function get_defaults() {
        return [
            'variant' => 'default',
            'context' => 'grid',
            'features' => ['price', 'beds', 'baths', 'sqft'],
            'interactions' => ['favorite', 'contact', 'share'],
            'lazy_load' => true,
            'link_target' => '_self',
            'image_size' => 'medium_large',
            'tracking' => ['view', 'click'],
            'cache_duration' => 3600,
            'schema_markup' => true,
            'show_agent' => false,
            'show_badges' => true,
            'animation_delay' => 0,
            'hover_effect' => true
        ];
    }
    
    /**
     * Component prop definitions for validation
     */
    protected function get_prop_definitions() {
        return [
            'variant' => [
                'type' => 'string',
                'required' => false,
                'enum' => ['default', 'featured', 'compact', 'minimal', 'premium'],
                'description' => 'Visual variant of the card'
            ],
            'context' => [
                'type' => 'string',
                'required' => false,
                'enum' => ['grid', 'list', 'search', 'related', 'featured', 'carousel'],
                'description' => 'Usage context for styling adjustments'
            ],
            'features' => [
                'type' => 'array',
                'required' => false,
                'enum_items' => ['price', 'beds', 'baths', 'sqft', 'agent', 'badges', 'description', 'location'],
                'description' => 'Features to display on the card'
            ],
            'interactions' => [
                'type' => 'array',
                'required' => false,
                'enum_items' => ['favorite', 'contact', 'share', 'compare', 'tour', 'calculate', 'view_details'],
                'description' => 'Interactive elements to include'
            ],
            'lazy_load' => [
                'type' => 'boolean',
                'required' => false,
                'description' => 'Enable lazy loading for images'
            ],
            'tracking' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Analytics events to track'
            ],
            'show_agent' => [
                'type' => 'boolean',
                'required' => false,
                'description' => 'Whether to show agent information'
            ],
            'show_badges' => [
                'type' => 'boolean',
                'required' => false,
                'description' => 'Whether to show status badges'
            ],
            'animation_delay' => [
                'type' => 'number',
                'required' => false,
                'min' => 0,
                'max' => 2000,
                'description' => 'Animation delay in milliseconds'
            ],
            'hover_effect' => [
                'type' => 'boolean',
                'required' => false,
                'description' => 'Enable hover animations'
            ]
        ];
    }
    
    /**
     * ACF field mapping
     */
    protected function get_acf_mapping() {
        return [
            'post_title' => 'title',
            'listing_price' => 'price',
            'bedrooms' => 'beds',
            'bathrooms' => 'baths',
            'square_footage' => 'sqft',
            'listing_images' => 'images',
            'listing_status' => 'status',
            'listing_agent' => 'agent',
            'property_features' => 'features',
            'listing_address' => 'location',
            'listing_description' => 'description',
            'listing_type' => 'type',
            'year_built' => 'year_built',
            'lot_size' => 'lot_size',
            'garage_spaces' => 'garage',
            'mls_number' => 'mls_id'
        ];
    }
    
    /**
     * Render the component content
     */
    protected function render_content() {
        $listing = $this->data;
        $props = $this->props;
        
        if (empty($listing['title']) && empty($listing['id'])) {
            return $this->render_placeholder();
        }
        
        // Track component view
        $this->track_interaction('view');
        
        $component_id = 'listing-card-' . ($listing['id'] ?? uniqid());
        $animation_style = $props['animation_delay'] > 0 ? 
            'style="animation-delay: ' . intval($props['animation_delay']) . 'ms;"' : '';
        
        ob_start();
        ?>
        <div 
            id="<?php echo esc_attr($component_id); ?>"
            class="<?php echo $this->get_css_classes(); ?>"
            data-listing-id="<?php echo esc_attr($listing['id'] ?? ''); ?>"
            data-component="listing-swipe-card"
            data-variant="<?php echo esc_attr($props['variant']); ?>"
            data-context="<?php echo esc_attr($props['context']); ?>"
            <?php echo $animation_style; ?>
            <?php if ($props['schema_markup']) echo $this->get_schema_markup(); ?>
            <?php if ($props['hover_effect']): ?>
            data-hover-effect="true"
            <?php endif; ?>
        >
            <div class="card-inner">
                <?php $this->render_image_section(); ?>
                <?php $this->render_content_section(); ?>
                <?php if (!empty($props['interactions'])): ?>
                    <?php $this->render_actions_section(); ?>
                <?php endif; ?>
            </div>
            
            <?php if ($props['tracking']): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof HPH !== 'undefined' && HPH.components) {
                        HPH.components.trackCardView(<?php echo json_encode([
                            'listing_id' => $listing['id'] ?? null,
                            'variant' => $props['variant'],
                            'context' => $props['context'],
                            'component_id' => $component_id
                        ]); ?>);
                    }
                });
            </script>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render image section with carousel
     */
    private function render_image_section() {
        $images = $this->data['images'] ?? [];
        $lazy_load = $this->props['lazy_load'];
        $variant = $this->props['variant'];
        
        echo '<div class="card-image-container">';
        
        if (empty($images)) {
            $this->render_image_placeholder();
        } else {
            $this->render_image_carousel($images, $lazy_load);
        }
        
        // Status badges
        if ($this->props['show_badges']) {
            $this->render_status_badges();
        }
        
        // Favorite button
        if (in_array('favorite', $this->props['interactions'])) {
            $this->render_favorite_button();
        }
        
        echo '</div>';
    }
    
    /**
     * Render image carousel
     */
    private function render_image_carousel($images, $lazy_load) {
        $has_multiple = count($images) > 1;
        
        echo '<div class="card-image-carousel"' . 
             ($lazy_load ? ' data-lazy-load="true"' : '') . 
             ($has_multiple ? ' data-carousel="true"' : '') . '>';
        
        foreach ($images as $index => $image) {
            $is_active = $index === 0;
            $img_classes = ['card-image'];
            
            if ($is_active) {
                $img_classes[] = 'active';
            }
            
            if ($lazy_load && !$is_active) {
                $img_classes[] = 'lazy';
            }
            
            $img_attrs = [
                'class' => implode(' ', $img_classes),
                'alt' => $this->get_image_alt($image, $index),
                'data-index' => $index
            ];
            
            if ($lazy_load && !$is_active) {
                $img_attrs['data-src'] = $this->get_optimized_image_url($image);
                $img_attrs['src'] = $this->get_placeholder_image();
            } else {
                $img_attrs['src'] = $this->get_optimized_image_url($image);
            }
            
            echo '<img ' . $this->build_attributes($img_attrs) . '>';
        }
        
        if ($has_multiple) {
            $this->render_carousel_controls(count($images));
        }
        
        echo '</div>';
    }
    
    /**
     * Render carousel controls
     */
    private function render_carousel_controls($image_count) {
        echo '<div class="carousel-controls">';
        echo '<button class="carousel-btn carousel-prev" aria-label="Previous image" type="button">';
        echo '<svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>';
        echo '</button>';
        echo '<button class="carousel-btn carousel-next" aria-label="Next image" type="button">';
        echo '<svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';
        echo '</button>';
        
        echo '<div class="carousel-indicators">';
        for ($i = 0; $i < $image_count; $i++) {
            $active_class = $i === 0 ? ' active' : '';
            echo '<button class="indicator' . $active_class . '" data-index="' . $i . '" aria-label="Go to image ' . ($i + 1) . '" type="button"></button>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render content section
     */
    private function render_content_section() {
        echo '<div class="card-content">';
        
        $this->render_title_section();
        
        if (in_array('price', $this->props['features'])) {
            $this->render_price_section();
        }
        
        $this->render_key_features();
        
        if (in_array('location', $this->props['features'])) {
            $this->render_location_section();
        }
        
        if (in_array('description', $this->props['features']) && $this->props['variant'] !== 'compact') {
            $this->render_description_section();
        }
        
        if ($this->props['show_agent'] && !empty($this->data['agent'])) {
            $this->render_agent_section();
        }
        
        echo '</div>';
    }
    
    /**
     * Render title with link
     */
    private function render_title_section() {
        $title = $this->data['title'] ?? 'Property Listing';
        $listing_url = $this->get_listing_url();
        
        echo '<div class="card-title-section">';
        
        if ($listing_url) {
            echo '<h3 class="card-title">';
            echo '<a href="' . esc_url($listing_url) . '" class="card-title-link" target="' . esc_attr($this->props['link_target']) . '">';
            echo esc_html($title);
            echo '</a>';
            echo '</h3>';
        } else {
            echo '<h3 class="card-title">' . esc_html($title) . '</h3>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render price display
     */
    private function render_price_section() {
        $price = $this->data['price'] ?? null;
        
        if (!$price) return;
        
        $formatted_price = $this->format_price($price);
        $price_label = $this->get_price_label();
        
        echo '<div class="card-price-section">';
        echo '<div class="card-price">' . esc_html($formatted_price) . '</div>';
        if ($price_label) {
            echo '<div class="card-price-label">' . esc_html($price_label) . '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render key features (beds, baths, sqft)
     */
    private function render_key_features() {
        $features = [];
        
        if (in_array('beds', $this->props['features']) && !empty($this->data['beds'])) {
            $features[] = [
                'value' => $this->data['beds'],
                'label' => $this->data['beds'] == 1 ? 'bed' : 'beds',
                'icon' => 'bed'
            ];
        }
        
        if (in_array('baths', $this->props['features']) && !empty($this->data['baths'])) {
            $features[] = [
                'value' => $this->data['baths'],
                'label' => $this->data['baths'] == 1 ? 'bath' : 'baths',
                'icon' => 'bath'
            ];
        }
        
        if (in_array('sqft', $this->props['features']) && !empty($this->data['sqft'])) {
            $features[] = [
                'value' => number_format($this->data['sqft']),
                'label' => 'sq ft',
                'icon' => 'square'
            ];
        }
        
        if (empty($features)) return;
        
        echo '<div class="card-features">';
        foreach ($features as $feature) {
            echo '<div class="card-feature">';
            echo '<span class="feature-icon" data-icon="' . esc_attr($feature['icon']) . '"></span>';
            echo '<span class="feature-value">' . esc_html($feature['value']) . '</span>';
            echo '<span class="feature-label">' . esc_html($feature['label']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render status badges
     */
    private function render_status_badges() {
        $status = $this->data['status'] ?? null;
        $type = $this->data['type'] ?? null;
        
        if (!$status && !$type) return;
        
        echo '<div class="card-badges">';
        
        if ($status) {
            $badge_class = $this->get_status_badge_class($status);
            echo '<span class="status-badge ' . esc_attr($badge_class) . '">' . esc_html(ucfirst($status)) . '</span>';
        }
        
        if ($type && $type !== 'listing') {
            echo '<span class="type-badge">' . esc_html(ucfirst($type)) . '</span>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render favorite button
     */
    private function render_favorite_button() {
        $listing_id = $this->data['id'] ?? null;
        $is_favorited = $this->is_listing_favorited($listing_id);
        
        $button_class = 'favorite-btn' . ($is_favorited ? ' favorited' : '');
        
        echo '<button class="' . esc_attr($button_class) . '" ';
        echo 'data-listing-id="' . esc_attr($listing_id) . '" ';
        echo 'aria-label="' . ($is_favorited ? 'Remove from favorites' : 'Add to favorites') . '" ';
        echo 'type="button">';
        echo '<svg class="heart-icon" viewBox="0 0 24 24" fill="currentColor">';
        echo '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>';
        echo '</svg>';
        echo '</button>';
    }
    
    /**
     * Render actions section
     */
    private function render_actions_section() {
        $interactions = $this->props['interactions'];
        
        if (empty($interactions)) return;
        
        echo '<div class="card-actions">';
        
        foreach ($interactions as $action) {
            if ($action === 'favorite') continue; // Already rendered in image section
            
            $this->render_action_button($action);
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual action button
     */
    private function render_action_button($action) {
        $buttons = [
            'contact' => [
                'label' => 'Contact Agent',
                'icon' => 'phone',
                'class' => 'btn-contact btn-primary'
            ],
            'share' => [
                'label' => 'Share',
                'icon' => 'share',
                'class' => 'btn-share btn-secondary'
            ],
            'tour' => [
                'label' => 'Schedule Tour',
                'icon' => 'calendar',
                'class' => 'btn-tour btn-secondary'
            ],
            'calculate' => [
                'label' => 'Calculate',
                'icon' => 'calculator',
                'class' => 'btn-calculate btn-secondary'
            ],
            'view_details' => [
                'label' => 'View Details',
                'icon' => 'arrow-right',
                'class' => 'btn-details btn-outline'
            ]
        ];
        
        if (!isset($buttons[$action])) return;
        
        $button = $buttons[$action];
        $listing_id = $this->data['id'] ?? null;
        
        echo '<button class="action-btn ' . esc_attr($button['class']) . '" ';
        echo 'data-action="' . esc_attr($action) . '" ';
        echo 'data-listing-id="' . esc_attr($listing_id) . '" ';
        echo 'aria-label="' . esc_attr($button['label']) . '" ';
        echo 'type="button">';
        echo '<span class="btn-icon" data-icon="' . esc_attr($button['icon']) . '"></span>';
        echo '<span class="btn-text">' . esc_html($button['label']) . '</span>';
        echo '</button>';
    }
    
    /**
     * Render placeholder when no data
     */
    private function render_placeholder() {
        return '<div class="' . $this->get_css_classes() . ' card-placeholder">
            <div class="card-inner">
                <div class="card-image-container">
                    <div class="placeholder-image">
                        <span class="placeholder-icon">🏠</span>
                    </div>
                </div>
                <div class="card-content">
                    <h3 class="card-title">Property Listing</h3>
                    <div class="card-price">Price Available</div>
                    <div class="card-features">
                        <div class="card-feature">
                            <span class="feature-label">Details coming soon</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Helper methods
     */
    private function get_listing_url() {
        if (!empty($this->data['id'])) {
            return get_permalink($this->data['id']);
        }
        return null;
    }
    
    private function format_price($price) {
        return '$' . number_format($price);
    }
    
    private function get_price_label() {
        $status = $this->data['status'] ?? null;
        
        switch ($status) {
            case 'sold':
                return 'Sold';
            case 'pending':
                return 'Pending';
            case 'rent':
                return 'per month';
            default:
                return null;
        }
    }
    
    private function get_optimized_image_url($image) {
        if (is_array($image) && isset($image['url'])) {
            return $image['url'];
        } elseif (is_string($image)) {
            return $image;
        }
        return $this->get_placeholder_image();
    }
    
    private function get_placeholder_image() {
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
                <rect width="400" height="300" fill="#f3f4f6"/>
                <text x="200" y="150" text-anchor="middle" fill="#9ca3af" font-family="Arial" font-size="14">Loading...</text>
            </svg>'
        );
    }
    
    private function get_image_alt($image, $index) {
        if (is_array($image) && !empty($image['alt'])) {
            return $image['alt'];
        }
        
        $title = $this->data['title'] ?? 'Property';
        return $title . ' - Image ' . ($index + 1);
    }
    
    private function get_status_badge_class($status) {
        $classes = [
            'active' => 'badge-success',
            'sold' => 'badge-error',
            'pending' => 'badge-warning',
            'off-market' => 'badge-gray'
        ];
        
        return $classes[$status] ?? 'badge-gray';
    }
    
    private function is_listing_favorited($listing_id) {
        // This would integrate with user favorites system
        return false;
    }
    
    private function track_interaction($event) {
        if (in_array($event, $this->props['tracking'])) {
            do_action('hph_component_interaction', $event, 'listing-swipe-card', $this->data['id'] ?? null, $this->props);
        }
    }
    
    private function get_schema_markup() {
        if (!$this->props['schema_markup']) {
            return '';
        }
        
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'RealEstateListing',
            'name' => $this->data['title'] ?? '',
            'url' => $this->get_listing_url()
        ];
        
        if (!empty($this->data['price'])) {
            $schema['price'] = $this->data['price'];
        }
        
        if (!empty($this->data['beds'])) {
            $schema['numberOfRooms'] = $this->data['beds'];
        }
        
        if (!empty($this->data['sqft'])) {
            $schema['floorSize'] = [
                '@type' => 'QuantitativeValue',
                'value' => $this->data['sqft'],
                'unitCode' => 'FTK'
            ];
        }
        
        return 'data-schema="' . esc_attr(json_encode($schema)) . '"';
    }
}
