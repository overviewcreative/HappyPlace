<?php

namespace HappyPlace\Listings;

/**
 * Listing Display Class
 * 
 * Handles the rendering of listing elements with enhanced functionality
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Display {
    
    public static function render_status_badge($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $status = get_field('listing_status', $listing_id);
        if ($status): ?>
            <div class="listing-status-badge status-<?php echo esc_attr($status); ?>">
                <?php echo esc_html(ucfirst($status)); ?>
            </div>
        <?php endif;
    }

    public static function render_price($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $price = get_field('price', $listing_id);
        if ($price): ?>
            <div class="listing-price">
                <?php echo esc_html(Helper::instance()->format_price($price)); ?>
            </div>
        <?php endif;
    }

    public static function render_property_details($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $beds = get_field('bedrooms', $listing_id);
        $baths = Helper::instance()->get_bathrooms($listing_id);
        $sqft = get_field('square_footage', $listing_id);
        ?>
        <div class="property-details">
            <?php if ($beds): ?>
                <span class="beds">
                    <i class="fas fa-bed"></i>
                    <?php echo esc_html($beds); ?> <?php _e('Beds', 'happy-place'); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($baths): ?>
                <span class="baths">
                    <i class="fas fa-bath"></i>
                    <?php echo esc_html(Helper::instance()->format_bathrooms($listing_id)); ?> <?php _e('Baths', 'happy-place'); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($sqft): ?>
                <span class="sqft">
                    <i class="fas fa-ruler-combined"></i>
                    <?php echo esc_html(Helper::instance()->format_sqft($sqft)); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_location($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $location = Helper::instance()->format_address($listing_id);
        if ($location): ?>
            <div class="listing-location">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo esc_html($location); ?>
            </div>
        <?php endif;
    }

    public static function render_agent_info($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $agent_id = get_field('listing_agent', $listing_id);
        if ($agent_id): 
            $agent = get_post($agent_id);
            ?>
            <div class="agent-info">
                <?php if (has_post_thumbnail($agent_id)): ?>
                    <div class="agent-image">
                        <?php echo get_the_post_thumbnail($agent_id, 'thumbnail'); ?>
                    </div>
                <?php endif; ?>
                <div class="agent-details">
                    <h3 class="agent-name"><?php echo esc_html($agent->post_title); ?></h3>
                    <?php 
                    $phone = get_field('phone', $agent_id);
                    if ($phone): ?>
                        <p class="agent-phone">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                        </p>
                    <?php endif; ?>
                    
                    <?php 
                    $email = get_field('email', $agent_id);
                    if ($email): ?>
                        <p class="agent-email">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;
    }

    public static function render_listing_date($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $list_date = get_field('listing_dates_date_listed', $listing_id);
        if ($list_date): ?>
            <div class="listing-date">
                <i class="fas fa-calendar"></i>
                <span class="label"><?php _e('Listed:', 'happy-place'); ?></span>
                <span class="date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($list_date))); ?></span>
            </div>
        <?php endif;
    }

    public static function render_action_buttons($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        ?>
        <div class="listing-actions">
            <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" 
               class="action-btn action-btn--primary">
                <i class="fas fa-eye"></i>
                <?php _e('View Details', 'happy-place'); ?>
            </a>
            
            <button class="action-btn action-btn--outline contact-btn" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="fas fa-envelope"></i>
                <?php _e('Contact Agent', 'happy-place'); ?>
            </button>
            
            <button class="action-btn action-btn--outline favorite-btn" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="far fa-heart"></i>
                <?php _e('Save', 'happy-place'); ?>
            </button>
            
            <button class="action-btn action-btn--outline share-btn" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="fas fa-share-alt"></i>
                <?php _e('Share', 'happy-place'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render complete listing card
     */
    public static function render_listing_card($listing_id, $style = 'default'): void {
        $classes = ['hph-listing-card', "hph-listing-card--{$style}"];
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-listing-id="<?php echo esc_attr($listing_id); ?>">
            <?php if (has_post_thumbnail($listing_id)): ?>
                <div class="listing-image">
                    <a href="<?php echo esc_url(get_permalink($listing_id)); ?>">
                        <?php echo get_the_post_thumbnail($listing_id, 'listing-card'); ?>
                    </a>
                    <?php self::render_status_badge($listing_id); ?>
                </div>
            <?php endif; ?>
            
            <div class="listing-content">
                <div class="listing-header">
                    <?php self::render_price($listing_id); ?>
                    <h3 class="listing-title">
                        <a href="<?php echo esc_url(get_permalink($listing_id)); ?>">
                            <?php echo esc_html(get_the_title($listing_id)); ?>
                        </a>
                    </h3>
                    <?php self::render_location($listing_id); ?>
                </div>
                
                <?php self::render_property_details($listing_id); ?>
                
                <div class="listing-footer">
                    <?php self::render_agent_info($listing_id); ?>
                    <?php self::render_listing_date($listing_id); ?>
                </div>
                
                <?php self::render_action_buttons($listing_id); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render listing features list
     */
    public static function render_features($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $features = Helper::instance()->get_features($listing_id);
        if (empty($features)) {
            return;
        }
        ?>
        <div class="listing-features">
            <h4><?php _e('Features', 'happy-place'); ?></h4>
            <ul class="features-list">
                <?php foreach ($features as $feature): ?>
                    <li class="feature-item">
                        <i class="fas fa-check"></i>
                        <?php echo esc_html($feature); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render highlight badges
     */
    public static function render_badges($listing_id = null): void {
        if (!$listing_id) {
            $listing_id = get_the_ID();
        }
        
        $badges = Helper::instance()->get_highlight_badges($listing_id);
        if (empty($badges)) {
            return;
        }
        ?>
        <div class="listing-badges">
            <?php foreach ($badges as $badge): ?>
                <span class="listing-badge listing-badge--<?php echo esc_attr($badge); ?>">
                    <?php echo esc_html(ucwords(str_replace('_', ' ', $badge))); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
