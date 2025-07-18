<?php

namespace HappyPlace\Utilities;

/**
 * FontAwesome Icon Helper
 * 
 * Provides consistent icon handling and mappings for the theme
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FontAwesome_Icons {
    
    private static ?self $instance = null;
    
    /**
     * Icon mappings for common theme elements
     */
    private static array $icon_mappings = [
        // Property features
        'bedrooms' => 'fas fa-bed',
        'bathrooms' => 'fas fa-bath',
        'square_feet' => 'fas fa-ruler-combined',
        'location' => 'fas fa-map-marker-alt',
        'price' => 'fas fa-dollar-sign',
        'garage' => 'fas fa-car',
        'pool' => 'fas fa-swimmer',
        'garden' => 'fas fa-leaf',
        'fireplace' => 'fas fa-fire',
        'air_conditioning' => 'fas fa-snowflake',
        'heating' => 'fas fa-thermometer-half',
        'security' => 'fas fa-shield-alt',
        'balcony' => 'fas fa-building',
        'terrace' => 'fas fa-mountain',
        
        // Actions
        'view' => 'fas fa-eye',
        'contact' => 'fas fa-envelope',
        'phone' => 'fas fa-phone',
        'email' => 'fas fa-envelope',
        'favorite' => 'far fa-heart',
        'favorite_filled' => 'fas fa-heart',
        'share' => 'fas fa-share-alt',
        'save' => 'fas fa-bookmark',
        'download' => 'fas fa-download',
        'print' => 'fas fa-print',
        'schedule' => 'fas fa-calendar',
        'tour' => 'fas fa-route',
        'virtual_tour' => 'fas fa-vr-cardboard',
        
        // Navigation & UI
        'home' => 'fas fa-home',
        'search' => 'fas fa-search',
        'filter' => 'fas fa-filter',
        'sort' => 'fas fa-sort',
        'menu' => 'fas fa-bars',
        'close' => 'fas fa-times',
        'arrow_left' => 'fas fa-arrow-left',
        'arrow_right' => 'fas fa-arrow-right',
        'arrow_up' => 'fas fa-arrow-up',
        'arrow_down' => 'fas fa-arrow-down',
        'chevron_left' => 'fas fa-chevron-left',
        'chevron_right' => 'fas fa-chevron-right',
        'chevron_up' => 'fas fa-chevron-up',
        'chevron_down' => 'fas fa-chevron-down',
        
        // Social & Contact
        'facebook' => 'fab fa-facebook-f',
        'twitter' => 'fab fa-twitter',
        'instagram' => 'fab fa-instagram',
        'linkedin' => 'fab fa-linkedin-in',
        'youtube' => 'fab fa-youtube',
        'pinterest' => 'fab fa-pinterest',
        'website' => 'fas fa-globe',
        
        // Agent & Professional
        'agent' => 'fas fa-user-tie',
        'team' => 'fas fa-users',
        'license' => 'fas fa-certificate',
        'experience' => 'fas fa-award',
        'specialties' => 'fas fa-star',
        
        // Status & Alerts
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'error' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle',
        'loading' => 'fas fa-spinner fa-spin',
        
        // Dashboard & Admin
        'dashboard' => 'fas fa-tachometer-alt',
        'settings' => 'fas fa-cog',
        'analytics' => 'fas fa-chart-bar',
        'reports' => 'fas fa-file-alt',
        'edit' => 'fas fa-edit',
        'delete' => 'fas fa-trash',
        'add' => 'fas fa-plus',
        'upload' => 'fas fa-upload',
        
        // Property Types
        'residential' => 'fas fa-home',
        'commercial' => 'fas fa-building',
        'condo' => 'fas fa-city',
        'townhouse' => 'fas fa-home',
        'land' => 'fas fa-map',
        'investment' => 'fas fa-chart-line',
    ];

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Get icon class for a given key
     */
    public static function get_icon(string $key, string $default = 'fas fa-question'): string {
        return self::$icon_mappings[$key] ?? $default;
    }

    /**
     * Render an icon with optional additional classes
     */
    public static function render_icon(string $key, array $extra_classes = [], array $attributes = []): string {
        $icon_class = self::get_icon($key);
        
        // Add extra classes
        if (!empty($extra_classes)) {
            $icon_class .= ' ' . implode(' ', $extra_classes);
        }
        
        // Build attributes
        $attr_string = '';
        if (!empty($attributes)) {
            foreach ($attributes as $attr => $value) {
                $attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
        }
        
        return '<i class="' . esc_attr($icon_class) . '"' . $attr_string . '></i>';
    }

    /**
     * Get all available icons
     */
    public static function get_all_icons(): array {
        return self::$icon_mappings;
    }

    /**
     * Check if an icon exists
     */
    public static function has_icon(string $key): bool {
        return isset(self::$icon_mappings[$key]);
    }

    /**
     * Add or update an icon mapping
     */
    public static function set_icon(string $key, string $icon_class): void {
        self::$icon_mappings[$key] = $icon_class;
    }

    /**
     * Remove an icon mapping
     */
    public static function remove_icon(string $key): void {
        unset(self::$icon_mappings[$key]);
    }

    /**
     * Get icons by category
     */
    public static function get_property_icons(): array {
        return array_filter(self::$icon_mappings, function($key) {
            return in_array($key, ['bedrooms', 'bathrooms', 'square_feet', 'garage', 'pool', 'garden', 'fireplace']);
        }, ARRAY_FILTER_USE_KEY);
    }

    public static function get_action_icons(): array {
        return array_filter(self::$icon_mappings, function($key) {
            return in_array($key, ['view', 'contact', 'phone', 'email', 'favorite', 'share', 'save']);
        }, ARRAY_FILTER_USE_KEY);
    }

    public static function get_social_icons(): array {
        return array_filter(self::$icon_mappings, function($key) {
            return in_array($key, ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest']);
        }, ARRAY_FILTER_USE_KEY);
    }
}

// Backward compatibility functions
if (!function_exists('hph_icon')) {
    function hph_icon(string $key, array $extra_classes = [], array $attributes = []): string {
        return FontAwesome_Icons::render_icon($key, $extra_classes, $attributes);
    }
}

if (!function_exists('hph_get_icon_class')) {
    function hph_get_icon_class(string $key, string $default = 'fas fa-question'): string {
        return FontAwesome_Icons::get_icon($key, $default);
    }
}
