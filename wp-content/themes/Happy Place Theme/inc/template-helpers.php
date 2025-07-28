<?php
/**
 * Template Helper Functions
 * 
 * Convenience functions that work with HPH_Template_Loader class
 * to easily load templates from organized structure.
 * 
 * These functions provide a simplified API for common template operations
 * while leveraging the existing HPH_Template_Loader infrastructure.
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced get_template_part that works with your HPH_Template_Loader
 * 
 * @param string $slug Template slug (e.g., 'cards/listing-card')
 * @param string $name Optional template name
 * @param array $args Optional arguments to pass to template
 */
if (!function_exists('hph_get_template_part')) {
    function hph_get_template_part(string $slug, ?string $name = null, array $args = []): void
    {
        // Cache key for template resolution
        static $template_cache = [];
        $cache_key = $slug . ($name ? "-{$name}" : '') . '-' . get_post_type();
        
        // Debug template part loading
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Template Helper: Loading template part - slug: {$slug}, name: " . ($name ?: 'none'));
        }
        
        // Use existing template loader if available
        if (class_exists('\\HappyPlace\\Core\\Template_Loader')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HPH Template Helper: Using Template_Loader class");
            }
            $template_loader = \HappyPlace\Core\Template_Loader::get_instance();
            
            // Use the existing template loader's method with correct parameters
            $template_loader->get_template_part($slug, $name ?: '', $args);
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Template Helper: Template_Loader not available, using fallback");
        }
    
    // Fallback to standard WordPress approach
    $templates = [];
    $name = (string) $name;
    
    if ($name !== '') {
        $templates[] = "template-parts/listing/{$slug}-{$name}.php";
        $templates[] = "template-parts/{$slug}-{$name}.php";
        $templates[] = "templates/listing/{$slug}-{$name}.php";  // Legacy fallback
        $templates[] = "templates/{$slug}-{$name}.php";
        $templates[] = "{$slug}-{$name}.php";
    }
    
    $templates[] = "template-parts/listing/{$slug}.php";
    $templates[] = "template-parts/{$slug}.php";
    $templates[] = "templates/listing/{$slug}.php";  // Legacy fallback
    $templates[] = "templates/{$slug}.php";
    $templates[] = "{$slug}.php";
    
    $located = locate_template($templates);
    
    if ($located) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Template Helper: Loading template: {$located}");
        }
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        include $located;
    } else {
        error_log("HPH Theme: Template not found for slug: {$slug}" . ($name ? "-{$name}" : ''));
        // Show a visible error for debugging
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            echo '<div style="background: #ffcccc; padding: 10px; margin: 10px; border: 1px solid #ff0000;">';
            echo '<strong>Template Part Missing:</strong> ' . esc_html($slug . ($name ? "-{$name}" : ''));
            echo '</div>';
        }
    }
    }
}

/**
 * Load listing card templates
 * 
 * @param int $listing_id Listing post ID
 * @param string $card_type Type of card (list, swipe, mini)
 * @param array $args Additional arguments
 */
if (!function_exists('hph_get_listing_card')) {
    function hph_get_listing_card(int $listing_id, string $card_type = 'list', array $args = []): void
    {
    $args['listing_id'] = $listing_id;
    $args['listing'] = get_post($listing_id);
    
    hph_get_template_part("cards/listing-{$card_type}-card", null, $args);
}
}

/**
 * Load agent card template
 * 
 * @param int $agent_id Agent user ID or post ID
 * @param string $card_type Type of card (mini, full, contact)
 * @param array $args Additional arguments
 */
function hph_get_agent_card(int $agent_id, string $card_type = 'mini', array $args = []): void
{
    $args['agent_id'] = $agent_id;
    
    hph_get_template_part("cards/agent-{$card_type}-card", null, $args);
}

/**
 * Load community card template
 * 
 * @param int $community_id Community post ID
 * @param string $card_type Type of card (thumb, hero, stats)
 * @param array $args Additional arguments
 */
function hph_get_community_card(int $community_id, string $card_type = 'thumb', array $args = []): void
{
    $args['community_id'] = $community_id;
    $args['community'] = get_post($community_id);
    
    hph_get_template_part("cards/community-{$card_type}-card", null, $args);
}

/**
 * Load dashboard section template using existing template loader
 * 
 * @param string $section Section name (overview, listings, etc.)
 * @param array $args Arguments to pass to template
 */
function hph_get_dashboard_section(string $section, array $args = []): void
{
    // Use Template_Loader instead of the missing load_dashboard_section method
    hph_get_template_part("dashboard/section-{$section}", null, $args);
}

/**
 * Load form template
 * 
 * @param string $form_type Type of form (contact, inquiry, search)
 * @param array $args Arguments to pass to form
 */
function hph_get_form_template(string $form_type, array $args = []): void
{
    hph_get_template_part("forms/{$form_type}-form", null, $args);
}

/**
 * Load filter template
 * 
 * @param string $filter_type Type of filter (sidebar, chips, dropdown)
 * @param array $args Arguments to pass to filter
 */
function hph_get_filter_template(string $filter_type, array $args = []): void
{
    hph_get_template_part("filters/{$filter_type}-filter", null, $args);
}

/**
 * Load navigation template
 * 
 * @param string $nav_type Type of navigation (breadcrumbs, pagination, menu)
 * @param array $args Arguments to pass to navigation
 */
function hph_get_navigation_template(string $nav_type, array $args = []): void
{
    hph_get_template_part("navigation/{$nav_type}", null, $args);
}

/**
 * Check if a template exists in the organized structure
 * 
 * @param string $template_path Relative path to template
 * @return bool True if template exists
 */
function hph_template_exists(string $template_path): bool
{
    $full_paths = [
        get_template_directory() . "/templates/{$template_path}",
        get_template_directory() . "/{$template_path}"
    ];
    
    foreach ($full_paths as $path) {
        if (file_exists($path)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get template hierarchy for debugging
 * 
 * @param string $slug Template slug
 * @param string $name Optional template name
 * @return array Array of template paths in order of preference
 */
function hph_get_template_hierarchy(string $slug, ?string $name = null): array
{
    $templates = [];
    $name = (string) $name;
    
    if ($name !== '') {
        $templates[] = "templates/template-parts/{$slug}-{$name}.php";
        $templates[] = "template-parts/{$slug}-{$name}.php";
        $templates[] = "{$slug}-{$name}.php";
    }
    
    $templates[] = "templates/template-parts/{$slug}.php";
    $templates[] = "template-parts/{$slug}.php";
    $templates[] = "{$slug}.php";
    
    return $templates;
}

/**
 * Load content template based on post type
 * 
 * @param string $post_type Post type
 * @param string $context Context (archive, single, card)
 * @param array $args Arguments to pass to template
 */
function hph_get_content_template(string $post_type, string $context = 'single', array $args = []): void
{
    global $post;
    
    $templates = [
        "templates/{$post_type}/content-{$post_type}-{$context}.php",
        "templates/content/content-{$post_type}-{$context}.php",
        "template-parts/content/content-{$post_type}-{$context}.php",
        "content-{$post_type}-{$context}.php",
        "templates/content/content-{$context}.php",
        "template-parts/content/content-{$context}.php",
        "content-{$context}.php"
    ];
    
    $located = locate_template($templates);
    
    if ($located) {
        // Make current post and args available
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        include $located;
    } else {
        // Fallback to default content
        echo '<div class="hph-content-fallback">';
        echo '<h2>' . get_the_title() . '</h2>';
        echo '<div class="entry-content">' . get_the_content() . '</div>';
        echo '</div>';
    }
}

// =============================================================================
// SCORE HELPER FUNCTIONS
// =============================================================================

/**
 * Get walk score description based on score
 */
if (!function_exists('hph_get_walk_score_description')) {
    function hph_get_walk_score_description($score) {
        $score = (int) $score;
        
        if ($score >= 90) return __('Walker\'s Paradise', 'happy-place');
        if ($score >= 70) return __('Very Walkable', 'happy-place');
        if ($score >= 50) return __('Somewhat Walkable', 'happy-place');
        if ($score >= 25) return __('Car-Dependent', 'happy-place');
        return __('Car-Dependent', 'happy-place');
    }
}

/**
 * Get transit score description based on score
 */
if (!function_exists('hph_get_transit_score_description')) {
    function hph_get_transit_score_description($score) {
        $score = (int) $score;
        
        if ($score >= 90) return __('Excellent Transit', 'happy-place');
        if ($score >= 70) return __('Great Transit', 'happy-place');
        if ($score >= 50) return __('Good Transit', 'happy-place');
        if ($score >= 25) return __('Some Transit', 'happy-place');
        return __('Minimal Transit', 'happy-place');
    }
}

/**
 * Get bike score description based on score
 */
if (!function_exists('hph_get_bike_score_description')) {
    function hph_get_bike_score_description($score) {
        $score = (int) $score;
        
        if ($score >= 90) return __('Biker\'s Paradise', 'happy-place');
        if ($score >= 70) return __('Very Bikeable', 'happy-place');
        if ($score >= 50) return __('Bikeable', 'happy-place');
        if ($score >= 25) return __('Somewhat Bikeable', 'happy-place');
        return __('Not Bikeable', 'happy-place');
    }
}

/**
 * Format amenity name for display
 */
if (!function_exists('hph_format_amenity_name')) {
    function hph_format_amenity_name($amenity) {
        if (is_array($amenity)) {
            return $amenity['name'] ?? $amenity['type'] ?? __('Amenity', 'happy-place');
        }
        
        return ucwords(str_replace(['_', '-'], ' ', $amenity));
    }
}

/**
 * Parse virtual tour URL to get provider info and embed URL
 */
if (!function_exists('hph_parse_virtual_tour_url')) {
    function hph_parse_virtual_tour_url($url) {
        if (empty($url)) {
            return [
                'provider' => 'generic',
                'tour_id' => '',
                'embed_url' => ''
            ];
        }
        
        // Matterport
        if (strpos($url, 'matterport.com') !== false) {
            preg_match('/\/m\/([a-zA-Z0-9]+)/', $url, $matches);
            $tour_id = $matches[1] ?? '';
            return [
                'provider' => 'matterport',
                'tour_id' => $tour_id,
                'embed_url' => $tour_id ? "https://my.matterport.com/show/?m={$tour_id}" : $url
            ];
        }
        
        // YouTube
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches);
            $video_id = $matches[1] ?? '';
            return [
                'provider' => 'youtube',
                'tour_id' => $video_id,
                'embed_url' => $video_id ? "https://www.youtube.com/embed/{$video_id}" : $url
            ];
        }
        
        // Generic fallback
        return [
            'provider' => 'generic',
            'tour_id' => '',
            'embed_url' => $url
        ];
    }
}