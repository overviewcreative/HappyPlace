<?php

/**
 * Dashboard Functions
 *
 * Core functions for the Happy Place Plugin dashboard
 *
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get dashboard section content
 */
function get_dashboard_section(string $section, array $args = []): string
{
    $template_loader = \HappyPlace\Core\Template_Loader::instance();
    return $template_loader->load_dashboard_section($section, $args);
}

/**
 * Check if current page is dashboard
 */
function is_dashboard_page(): bool
{
    $template_loader = \HappyPlace\Core\Template_Loader::instance();
    return $template_loader->is_dashboard_page();
}

/**
 * Get dashboard menu items
 */
function get_dashboard_menu_items(): array
{
    $default_items = [
        'overview' => [
            'title' => __('Overview', 'happy-place'),
            'icon' => 'dashboard',
            'capability' => 'read'
        ],
        'listings' => [
            'title' => __('Listings', 'happy-place'),
            'icon' => 'home',
            'capability' => 'edit_posts'
        ],
        'agents' => [
            'title' => __('Agents', 'happy-place'),
            'icon' => 'groups',
            'capability' => 'edit_posts'
        ],
        'communities' => [
            'title' => __('Communities', 'happy-place'),
            'icon' => 'location_city',
            'capability' => 'edit_posts'
        ],
        'settings' => [
            'title' => __('Settings', 'happy-place'),
            'icon' => 'settings',
            'capability' => 'manage_options'
        ]
    ];

    return apply_filters('hph_dashboard_menu_items', $default_items);
}

/**
 * Get dashboard section title
 */
function get_dashboard_section_title(string $section): string
{
    $menu_items = get_dashboard_menu_items();
    return $menu_items[$section]['title'] ?? ucfirst($section);
}

/**
 * Check if user can access dashboard section
 */
function can_access_dashboard_section(string $section): bool
{
    $menu_items = get_dashboard_menu_items();
    $capability = $menu_items[$section]['capability'] ?? 'read';
    return current_user_can($capability);
}

/**
 * Get dashboard statistics
 */
function get_dashboard_stats(): array
{
    $stats = [
        'total_listings' => wp_count_posts('listing')->publish,
        'total_agents' => wp_count_posts('agent')->publish,
        'total_communities' => wp_count_posts('community')->publish,
        'total_cities' => wp_count_posts('city')->publish
    ];

    return apply_filters('hph_dashboard_stats', $stats);
}

/**
 * Get recent activity for dashboard
 */
function get_dashboard_activity(int $limit = 5): array
{
    $activity = [];

    // Get recent posts across all CPTs
    $post_types = ['listing', 'agent', 'community', 'city'];
    foreach ($post_types as $post_type) {
        $recent_posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        foreach ($recent_posts as $post) {
            $activity[] = [
                'type' => $post_type,
                'title' => $post->post_title,
                'link' => get_permalink($post),
                'date' => $post->post_modified,
                'author' => get_the_author_meta('display_name', $post->post_author)
            ];
        }
    }

    // Sort by date
    usort($activity, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    return array_slice($activity, 0, $limit);
}

/**
 * Get dashboard notifications
 */
function get_dashboard_notifications(): array
{
    $notifications = [];

    // Check for pending items
    $pending_listings = wp_count_posts('listing')->pending;
    if ($pending_listings > 0) {
        $notifications[] = [
            'type' => 'info',
            'message' => sprintf(
                _n(
                    'There is %d listing pending review',
                    'There are %d listings pending review',
                    $pending_listings,
                    'happy-place'
                ),
                $pending_listings
            )
        ];
    }

    return apply_filters('hph_dashboard_notifications', $notifications);
}
