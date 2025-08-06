<?php

/**
 * Integration between Happy Place Plugin and T// ACF Integration - Make post types available in ACF UI
function hph_acf_get_post_types($post_types) {
    if (post_type_exists('listing')) {
        $list_type = get_post_type_object('listing');
        $post_types['listing'] = $list_type->labels->singular_name;
    }
    if (post_type_exists('open_house')) {
        $oh_type = get_post_type_object('open_house');
        $post_types['open_house'] = $oh_type->labels->singular_name;
    }
    return $post_types;
}
add_filter('acf/get_post_types', 'hph_acf_get_post_types', 20);

// ACF Integration - Include post types in location rules
function hph_acf_location_rules_types($choices) {
    if (post_type_exists('listing')) {
        $choices['post_type']['listing'] = 'Listing';
    }
    if (post_type_exists('open_house')) {
        $choices['post_type']['open_house'] = 'Open House';
    }
    return $choices;
}
add_filter('acf/location/rule_types', 'hph_acf_location_rules_types', 20);

// ACF Integration - Include taxonomies in location rules
function hph_acf_location_rules_values($choices) {
    if (taxonomy_exists('property_type')) {
        $tax = get_taxonomy('property_type');
        $choices['property_type'] = $tax->labels->singular_name;
    }
    if (taxonomy_exists('listing_location')) {
        $tax = get_taxonomy('listing_location');
        $choices['listing_location'] = $tax->labels->singular_name;
    }
    return $choices;
}
add_filter('acf/location/rule_values/taxonomy', 'hph_acf_location_rules_values', 20);

// ACF Integration - Add custom post types to ACF settings page
function hph_acf_settings_post_types($post_types) {
    $post_types[] = 'listing';
    $post_types[] = 'open_house';
    return array_unique($post_types);
}
add_filter('acf/settings/post_types', 'hph_acf_settings_post_types', 20);oard
 */

function hph_ensure_plugin_integration()
{
    // Check if plugin is active and classes are loaded
    if (!class_exists('\\HappyPlace\\Core\\Post_Types')) {
        return false;
    }

    // Add necessary support for custom fields if not already present
    $post_types = ['listing', 'open_house'];
    foreach ($post_types as $post_type) {
        if (post_type_exists($post_type) && !post_type_supports($post_type, 'custom-fields')) {
            add_post_type_support($post_type, ['custom-fields', 'author']);
        }
    }

    // Ensure taxonomies are properly connected
    $taxonomies = ['property_type', 'listing_location'];
    foreach ($taxonomies as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            register_taxonomy_for_object_type($taxonomy, 'listing');
        }
    }

    // Ensure the agent role has necessary capabilities
    $agent_role = get_role('agent');
    if ($agent_role) {
        $capabilities = [
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,
            'upload_files' => true,
            'edit_listings' => true,
            'publish_listings' => true,
            'delete_listings' => true,
            'manage_open_houses' => true,
            'view_analytics' => true,
            'manage_leads' => true
        ];

        foreach ($capabilities as $cap => $grant) {
            if (!$agent_role->has_cap($cap)) {
                $agent_role->add_cap($cap);
            }
        }
    }

    return true;
}

// Run integration check on init after plugin loads
add_action('init', 'hph_ensure_plugin_integration', 20);

// Add compatibility layer for ACF
add_filter('acf/get_post_types', function ($post_types) {
    if (post_type_exists('listing')) {
        $post_types['listing'] = 'listing';
    }
    if (post_type_exists('open_house')) {
        $post_types['open_house'] = 'open_house';
    }
    return $post_types;
}, 20);

// Ensure our dashboard can find plugin's post types
add_filter('register_post_type_args', function ($args, $post_type) {
    if (in_array($post_type, ['listing', 'open_house'])) {
        $args['show_in_rest'] = true;
        $args['supports'][] = 'custom-fields';
        $args['supports'][] = 'author';
    }
    return $args;
}, 20, 2);
