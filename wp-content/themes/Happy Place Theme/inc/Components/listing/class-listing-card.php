<?php
/**
 * Listing Card Component
 *
 * Primary listing display component for grids, archives, and related listings.
 * Supports multiple variants and contexts with comprehensive data display.
 *
 * @package HappyPlace\Components\Listing
 * @since 2.0.0
 */

namespace HappyPlace\Components\Listing;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Card extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'listing-card';
    }
    
    /**
     * Get default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'listing_id' => 0,
            'variant' => 'default',        // default, featured, compact, minimal
            'context' => 'archive',        // archive, widget, related, search
            'show_features' => true,       // Show bed/bath/sqft
            'show_status' => true,         // Show listing status
            'show_agent' => false,         // Show agent info
            'show_price' => true,          // Show price
            'show_address' => true,        // Show address
            'show_description' => false,   // Show excerpt
            'link_title' => true,          // Make title clickable
            'image_size' => 'medium_large',
            'lazy_load' => true,           // Lazy load images
            'track_views' => false,        // Analytics tracking
            'css_classes' => '',           // Additional CSS classes
            'show_favorite_btn' => false,  // Favorite/save functionality
            'show_share_btn' => false,     // Share functionality
            'show_virtual_tour' => true,   // Virtual tour button
            'enable_hover_effects' => true // CSS hover animations
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        if (empty($this->get_prop('listing_id'))) {
            $this->add_validation_error('Listing ID is required');
            return;
        }
        
        $valid_variants = ['default', 'featured', 'compact', 'minimal'];
        if (!in_array($this->get_prop('variant'), $valid_variants)) {
            $this->add_validation_error('Invalid variant specified');
        }
        
        $valid_contexts = ['archive', 'widget', 'related', 'search', 'agent-profile'];
        if (!in_array($this->get_prop('context'), $valid_contexts)) {
            $this->add_validation_error('Invalid context specified');
        }
    }
    
    /**
     * Initialize component
     */
    protected function init() {
        // Pre-load listing data for performance
        $this->listing_data = $this->get_listing_data();
    }
    
    /**
     * Render the listing card component
     *
     * @return string
     */
    protected function render() {
        if (empty($this->listing_data)) {
            return $this->render_error_state();
        }
        
        $listing_id = $this->get_prop('listing_id');
        $variant = $this->get_prop('variant');
        $context = $this->get_prop('context');
        
        // Build CSS classes
        $css_classes = $this->build_css_classes($variant, $context);
        
        // Track view if enabled
        if ($this->get_prop('track_views')) {
            $this->track_component_view($listing_id);
        }
        
        ob_start();
        ?>
        <article class="<?php echo esc_attr($css_classes); ?>" 
                 data-listing-id="<?php echo esc_attr($listing_id); ?>"
                 data-component="listing-card"
                 data-variant="<?php echo esc_attr($variant); ?>"
                 data-context="<?php echo esc_attr($context); ?>"
                 itemscope itemtype="https://schema.org/RealEstateListing">
            
            <!-- Image Container -->
            <div class="hph-listing-card__image">
                <?php $this->render_listing_image(); ?>
                <?php $this->render_image_overlays(); ?>
                <?php $this->render_image_actions(); ?>
            </div>
            
            <!-- Content Container -->
            <div class="hph-listing-card__content">
                <?php $this->render_card_header(); ?>
                <?php $this->render_card_body(); ?>
                <?php if ($this->get_prop('show_agent')): ?>
                    <?php $this->render_agent_info(); ?>
                <?php endif; ?>
            </div>
            
            <!-- Card Footer (if needed) -->
            <?php if ($this->should_render_footer()): ?>
                <div class="hph-listing-card__footer">
                    <?php $this->render_card_footer(); ?>
                </div>
            <?php endif; ?>
            
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get listing data using bridge functions
     *
     * @return array|false
     */
    private function get_listing_data() {
        $listing_id = $this->get_prop('listing_id');
        
        // Use bridge functions for data access
        $data = [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'permalink' => get_permalink($listing_id),
            'price' => hph_get_listing_price($listing_id, true),
            'price_raw' => hph_get_listing_price($listing_id, false),
            'status' => hph_get_listing_status($listing_id),
            'address' => hph_get_listing_address($listing_id, 'full'),
            'short_address' => hph_get_listing_address($listing_id, 'short'),
            'bedrooms' => hph_get_listing_bedrooms($listing_id),
            'bathrooms' => hph_get_listing_bathrooms($listing_id),
            'square_footage' => hph_get_listing_square_footage($listing_id),
            'lot_size' => hph_get_listing_lot_size($listing_id),
            'year_built' => hph_get_listing_year_built($listing_id),
            'property_type' => hph_get_listing_property_type($listing_id),
            'featured_image' => hph_get_listing_featured_image($listing_id, $this->get_prop('image_size')),
            'gallery_count' => hph_get_listing_gallery_count($listing_id),
            'virtual_tour' => hph_get_listing_virtual_tour($listing_id),
            'description' => hph_get_listing_description($listing_id),
            'excerpt' => hph_get_listing_excerpt($listing_id, 25),
            'days_on_market' => hph_get_listing_days_on_market($listing_id),
            'is_featured' => hph_is_listing_featured($listing_id),
            'is_new' => hph_is_listing_new($listing_id),
            'mls_number' => hph_get_listing_mls_number($listing_id)
        ];
        
        // Get agent data if needed
        if ($this->get_prop('show_agent')) {
            $agent_data = hph_get_listing_agent($listing_id);
            $data['agent'] = $agent_data;
        }
        
        return $data;
    }
    
    /**
     * Build CSS classes for the card
     *
     * @param string $variant
     * @param string $context
     * @return string
     */
    private function build_css_classes($variant, $context) {
        $classes = [
            'hph-listing-card',
            'hph-listing-card--' . $variant,
            'hph-listing-card--' . $context
        ];
        
        // Add status classes
        if ($this->listing_data['status']) {
            $classes[] = 'hph-listing-card--status-' . sanitize_html_class(strtolower($this->listing_data['status']));
        }
        
        // Add feature classes
        if ($this->listing_data['is_featured']) {
            $classes[] = 'hph-listing-card--featured-listing';
        }
        
        if ($this->listing_data['is_new']) {
            $classes[] = 'hph-listing-card--new-listing';
        }
        
        if ($this->get_prop('enable_hover_effects')) {
            $classes[] = 'hph-listing-card--hover-enabled';
        }
        
        // Add custom classes
        if ($this->get_prop('css_classes')) {
            $classes[] = $this->get_prop('css_classes');
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Render listing image
     */
    private function render_listing_image() {
        $image_url = $this->listing_data['featured_image'];
        $title = $this->listing_data['title'];
        $permalink = $this->listing_data['permalink'];
        
        if ($image_url) {
            if ($this->get_prop('lazy_load')) {
                ?>
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" 
                     data-src="<?php echo esc_url($image_url); ?>" 
                     alt="<?php echo esc_attr($title); ?>"
                     class="hph-listing-card__img lazy-load"
                     itemprop="image">
                <?php
            } else {
                ?>
                <img src="<?php echo esc_url($image_url); ?>" 
                     alt="<?php echo esc_attr($title); ?>"
                     class="hph-listing-card__img"
                     itemprop="image">
                <?php
            }
        } else {
            ?>
            <div class="hph-listing-card__img-placeholder">
                <i class="fas fa-home"></i>
                <span class="sr-only">No image available</span>
            </div>
            <?php
        }
        
        // Image link overlay
        ?>
        <a href="<?php echo esc_url($permalink); ?>" 
           class="hph-listing-card__image-link" 
           aria-label="View details for <?php echo esc_attr($title); ?>"
           itemprop="url">
            <span class="sr-only">View listing details</span>
        </a>
        <?php
    }
    
    /**
     * Render image overlays (status, badges, etc.)
     */
    private function render_image_overlays() {
        ?>
        <div class="hph-listing-card__overlays">
            
            <!-- Status Badge -->
            <?php if ($this->get_prop('show_status') && $this->listing_data['status']): ?>
                <div class="hph-listing-card__status hph-status--<?php echo esc_attr(strtolower($this->listing_data['status'])); ?>">
                    <?php echo esc_html($this->listing_data['status']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Featured Badge -->
            <?php if ($this->listing_data['is_featured']): ?>
                <div class="hph-listing-card__badge hph-badge--featured">
                    <i class="fas fa-star"></i>
                    <span>Featured</span>
                </div>
            <?php endif; ?>
            
            <!-- New Listing Badge -->
            <?php if ($this->listing_data['is_new']): ?>
                <div class="hph-listing-card__badge hph-badge--new">
                    <span>New</span>
                </div>
            <?php endif; ?>
            
            <!-- Price Overlay -->
            <?php if ($this->get_prop('show_price') && $this->listing_data['price']): ?>
                <div class="hph-listing-card__price-overlay" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <span class="hph-price" itemprop="price" content="<?php echo esc_attr($this->listing_data['price_raw']); ?>">
                        <?php echo esc_html($this->listing_data['price']); ?>
                    </span>
                    <meta itemprop="priceCurrency" content="USD">
                </div>
            <?php endif; ?>
            
            <!-- Gallery Count -->
            <?php if ($this->listing_data['gallery_count'] > 1): ?>
                <div class="hph-listing-card__gallery-count">
                    <i class="fas fa-images"></i>
                    <span><?php echo esc_html($this->listing_data['gallery_count']); ?></span>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render image action buttons
     */
    private function render_image_actions() {
        if (!($this->get_prop('show_favorite_btn') || $this->get_prop('show_share_btn') || $this->get_prop('show_virtual_tour'))) {
            return;
        }
        
        ?>
        <div class="hph-listing-card__actions">
            
            <!-- Favorite Button -->
            <?php if ($this->get_prop('show_favorite_btn')): ?>
                <button class="hph-listing-card__action hph-action--favorite" 
                        data-listing-id="<?php echo esc_attr($this->listing_data['id']); ?>"
                        data-action="toggle-favorite"
                        aria-label="Add to favorites">
                    <i class="far fa-heart"></i>
                </button>
            <?php endif; ?>
            
            <!-- Share Button -->
            <?php if ($this->get_prop('show_share_btn')): ?>
                <button class="hph-listing-card__action hph-action--share" 
                        data-listing-id="<?php echo esc_attr($this->listing_data['id']); ?>"
                        data-action="share-listing"
                        aria-label="Share listing">
                    <i class="fas fa-share"></i>
                </button>
            <?php endif; ?>
            
            <!-- Virtual Tour Button -->
            <?php if ($this->get_prop('show_virtual_tour') && $this->listing_data['virtual_tour']): ?>
                <a href="<?php echo esc_url($this->listing_data['virtual_tour']); ?>" 
                   class="hph-listing-card__action hph-action--virtual-tour"
                   target="_blank" 
                   rel="noopener noreferrer"
                   aria-label="View virtual tour">
                    <i class="fas fa-vr-cardboard"></i>
                </a>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render card header (price, address)
     */
    private function render_card_header() {
        ?>
        <div class="hph-listing-card__header">
            
            <!-- Price (if not in overlay) -->
            <?php if ($this->get_prop('show_price') && $this->listing_data['price'] && $this->get_prop('variant') !== 'overlay-price'): ?>
                <div class="hph-listing-card__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <span class="hph-price" itemprop="price" content="<?php echo esc_attr($this->listing_data['price_raw']); ?>">
                        <?php echo esc_html($this->listing_data['price']); ?>
                    </span>
                    <meta itemprop="priceCurrency" content="USD">
                </div>
            <?php endif; ?>
            
            <!-- Address/Title -->
            <?php if ($this->get_prop('show_address')): ?>
                <div class="hph-listing-card__address">
                    <?php if ($this->get_prop('link_title')): ?>
                        <h3 class="hph-listing-card__title" itemprop="name">
                            <a href="<?php echo esc_url($this->listing_data['permalink']); ?>" itemprop="url">
                                <?php echo esc_html($this->get_display_address()); ?>
                            </a>
                        </h3>
                    <?php else: ?>
                        <h3 class="hph-listing-card__title" itemprop="name">
                            <?php echo esc_html($this->get_display_address()); ?>
                        </h3>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- MLS Number -->
            <?php if ($this->listing_data['mls_number'] && $this->get_prop('variant') !== 'minimal'): ?>
                <div class="hph-listing-card__mls">
                    <span class="hph-mls-label">MLS#</span>
                    <span class="hph-mls-number"><?php echo esc_html($this->listing_data['mls_number']); ?></span>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render card body (features, description)
     */
    private function render_card_body() {
        ?>
        <div class="hph-listing-card__body">
            
            <!-- Property Features -->
            <?php if ($this->get_prop('show_features')): ?>
                <div class="hph-listing-card__features">
                    <?php $this->render_property_features(); ?>
                </div>
            <?php endif; ?>
            
            <!-- Description Excerpt -->
            <?php if ($this->get_prop('show_description') && $this->listing_data['excerpt']): ?>
                <div class="hph-listing-card__description" itemprop="description">
                    <?php echo esc_html($this->listing_data['excerpt']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Additional Details for certain variants -->
            <?php if ($this->get_prop('variant') === 'detailed'): ?>
                <?php $this->render_additional_details(); ?>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render property features (bed/bath/sqft)
     */
    private function render_property_features() {
        $features = [];
        
        // Bedrooms
        if ($this->listing_data['bedrooms']) {
            $features[] = [
                'icon' => 'fas fa-bed',
                'value' => $this->listing_data['bedrooms'],
                'label' => ($this->listing_data['bedrooms'] == 1) ? 'bed' : 'beds',
                'full_label' => $this->listing_data['bedrooms'] . ' ' . (($this->listing_data['bedrooms'] == 1) ? 'bedroom' : 'bedrooms')
            ];
        }
        
        // Bathrooms
        if ($this->listing_data['bathrooms']) {
            $features[] = [
                'icon' => 'fas fa-bath',
                'value' => $this->listing_data['bathrooms'],
                'label' => ($this->listing_data['bathrooms'] == 1) ? 'bath' : 'baths',
                'full_label' => $this->listing_data['bathrooms'] . ' ' . (($this->listing_data['bathrooms'] == 1) ? 'bathroom' : 'bathrooms')
            ];
        }
        
        // Square Footage
        if ($this->listing_data['square_footage']) {
            $features[] = [
                'icon' => 'fas fa-ruler-combined',
                'value' => number_format($this->listing_data['square_footage']),
                'label' => 'sq ft',
                'full_label' => number_format($this->listing_data['square_footage']) . ' square feet'
            ];
        }
        
        // Lot Size (for non-compact variants)
        if ($this->listing_data['lot_size'] && $this->get_prop('variant') !== 'compact') {
            $features[] = [
                'icon' => 'fas fa-expand-arrows-alt',
                'value' => $this->listing_data['lot_size'],
                'label' => 'lot',
                'full_label' => $this->listing_data['lot_size'] . ' lot size'
            ];
        }
        
        // Year Built (for detailed variants)
        if ($this->listing_data['year_built'] && $this->get_prop('variant') === 'detailed') {
            $features[] = [
                'icon' => 'fas fa-calendar-alt',
                'value' => $this->listing_data['year_built'],
                'label' => 'built',
                'full_label' => 'Built in ' . $this->listing_data['year_built']
            ];
        }
        
        if (!empty($features)):
        ?>
        <div class="hph-property-features">
            <?php foreach ($features as $feature): ?>
                <div class="hph-feature" title="<?php echo esc_attr($feature['full_label']); ?>">
                    <i class="<?php echo esc_attr($feature['icon']); ?>" aria-hidden="true"></i>
                    <span class="hph-feature-value"><?php echo esc_html($feature['value']); ?></span>
                    <span class="hph-feature-label"><?php echo esc_html($feature['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        endif;
    }
    
    /**
     * Render additional details for detailed variants
     */
    private function render_additional_details() {
        ?>
        <div class="hph-listing-card__additional-details">
            
            <!-- Property Type -->
            <?php if ($this->listing_data['property_type']): ?>
                <div class="hph-detail-item">
                    <span class="hph-detail-label">Type:</span>
                    <span class="hph-detail-value"><?php echo esc_html($this->listing_data['property_type']); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Days on Market -->
            <?php if ($this->listing_data['days_on_market']): ?>
                <div class="hph-detail-item">
                    <span class="hph-detail-label">Days on Market:</span>
                    <span class="hph-detail-value"><?php echo esc_html($this->listing_data['days_on_market']); ?></span>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render agent information
     */
    private function render_agent_info() {
        if (empty($this->listing_data['agent'])) {
            return;
        }
        
        $agent = $this->listing_data['agent'];
        ?>
        <div class="hph-listing-card__agent">
            <div class="hph-agent-info">
                
                <!-- Agent Photo -->
                <div class="hph-agent-photo">
                    <?php if (!empty($agent['photo'])): ?>
                        <img src="<?php echo esc_url($agent['photo']); ?>" 
                             alt="<?php echo esc_attr($agent['name']); ?>"
                             class="hph-agent-avatar">
                    <?php else: ?>
                        <div class="hph-agent-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Agent Details -->
                <div class="hph-agent-details">
                    <div class="hph-agent-name">
                        <?php if (!empty($agent['permalink'])): ?>
                            <a href="<?php echo esc_url($agent['permalink']); ?>">
                                <?php echo esc_html($agent['name']); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($agent['name']); ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($agent['title'])): ?>
                        <div class="hph-agent-title">
                            <?php echo esc_html($agent['title']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Agent Contact -->
                <div class="hph-agent-contact">
                    <?php if (!empty($agent['phone'])): ?>
                        <a href="tel:<?php echo esc_attr($agent['phone']); ?>" 
                           class="hph-contact-btn hph-contact-btn--phone"
                           title="Call <?php echo esc_attr($agent['name']); ?>">
                            <i class="fas fa-phone"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($agent['email'])): ?>
                        <a href="mailto:<?php echo esc_attr($agent['email']); ?>" 
                           class="hph-contact-btn hph-contact-btn--email"
                           title="Email <?php echo esc_attr($agent['name']); ?>">
                            <i class="fas fa-envelope"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Render card footer
     */
    private function render_card_footer() {
        ?>
        <div class="hph-listing-card__footer-content">
            
            <!-- View Details Button -->
            <a href="<?php echo esc_url($this->listing_data['permalink']); ?>" 
               class="hph-btn hph-btn--primary hph-btn--small hph-btn--block">
                <span>View Details</span>
                <i class="fas fa-arrow-right"></i>
            </a>
            
            <!-- Secondary Actions -->
            <div class="hph-listing-card__secondary-actions">
                <?php if ($this->listing_data['virtual_tour']): ?>
                    <a href="<?php echo esc_url($this->listing_data['virtual_tour']); ?>" 
                       class="hph-btn hph-btn--outline hph-btn--small"
                       target="_blank" 
                       rel="noopener noreferrer">
                        <i class="fas fa-vr-cardboard"></i>
                        <span>Virtual Tour</span>
                    </a>
                <?php endif; ?>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Get display address based on variant and context
     *
     * @return string
     */
    private function get_display_address() {
        $variant = $this->get_prop('variant');
        
        if ($variant === 'compact' || $variant === 'minimal') {
            return $this->listing_data['short_address'] ?: $this->listing_data['title'];
        }
        
        return $this->listing_data['address'] ?: $this->listing_data['title'];
    }
    
    /**
     * Check if footer should be rendered
     *
     * @return bool
     */
    private function should_render_footer() {
        $show_footer_variants = ['detailed', 'featured'];
        return in_array($this->get_prop('variant'), $show_footer_variants);
    }
    
    /**
     * Track component view for analytics
     *
     * @param int $listing_id
     */
    private function track_component_view($listing_id) {
        if (function_exists('hph_track_listing_view')) {
            hph_track_listing_view($listing_id, 'card-view', $this->get_prop('context'));
        }
    }
    
    /**
     * Render error state when listing data is unavailable
     *
     * @return string
     */
    private function render_error_state() {
        ob_start();
        ?>
        <div class="hph-listing-card hph-listing-card--error" data-component="listing-card">
            <div class="hph-listing-card__error">
                <div class="hph-error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="hph-error-message">
                    <h4>Listing Unavailable</h4>
                    <p>This listing could not be loaded. It may have been removed or is temporarily unavailable.</p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}