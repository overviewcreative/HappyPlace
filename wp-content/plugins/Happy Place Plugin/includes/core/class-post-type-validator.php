<?php
/**
 * Post Type Validation and Enhancement
 * Ensures all post types are properly configured with current standards
 * 
 * @package HappyPlace
 * @subpackage Core
 * @since 4.5.0
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Post_Type_Validator
{
    private static ?self $instance = null;
    private array $required_post_types = [];
    private array $validation_results = [];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->define_required_post_types();
        add_action('admin_init', [$this, 'validate_post_types']);
        add_action('wp_ajax_hph_validate_post_types', [$this, 'ajax_validate_post_types']);
    }

    /**
     * Define required post types and their expected configurations
     */
    private function define_required_post_types(): void
    {
        $this->required_post_types = [
            'listing' => [
                'labels' => [
                    'name' => 'Listings',
                    'singular_name' => 'Listing',
                    'menu_name' => 'Listings'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-admin-home',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields', 'author'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'listings'],
                'capability_type' => 'listing',
                'map_meta_cap' => true
            ],
            'agent' => [
                'labels' => [
                    'name' => 'Agents',
                    'singular_name' => 'Agent',
                    'menu_name' => 'Agents'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-businessman',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'agents']
            ],
            'community' => [
                'labels' => [
                    'name' => 'Communities',
                    'singular_name' => 'Community',
                    'menu_name' => 'Communities'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-location-alt',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'communities']
            ],
            'city' => [
                'labels' => [
                    'name' => 'Cities',
                    'singular_name' => 'City',
                    'menu_name' => 'Cities'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-building',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'cities']
            ],
            'transaction' => [
                'labels' => [
                    'name' => 'Transactions',
                    'singular_name' => 'Transaction',
                    'menu_name' => 'Transactions'
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'edit.php?post_type=listing',
                'supports' => ['title', 'custom-fields'],
                'capability_type' => 'listing',
                'map_meta_cap' => true
            ],
            'open-house' => [
                'labels' => [
                    'name' => 'Open Houses',
                    'singular_name' => 'Open House',
                    'menu_name' => 'Open Houses'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => 'edit.php?post_type=listing',
                'supports' => ['title', 'custom-fields', 'author'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'open-houses'],
                'capability_type' => 'post',
                'map_meta_cap' => true
            ],
            'local-place' => [
                'labels' => [
                    'name' => 'Local Places',
                    'singular_name' => 'Local Place',
                    'menu_name' => 'Local Places'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-location',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'local-places']
            ],
            'team' => [
                'labels' => [
                    'name' => 'Teams',
                    'singular_name' => 'Team',
                    'menu_name' => 'Teams'
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-groups',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'teams']
            ]
        ];
    }

    /**
     * Validate all post types against requirements
     */
    public function validate_post_types(): void
    {
        if (!current_user_can('administrator')) {
            return;
        }

        $this->validation_results = [];

        foreach ($this->required_post_types as $post_type => $config) {
            $this->validation_results[$post_type] = $this->validate_single_post_type($post_type, $config);
        }
    }

    /**
     * Validate a single post type
     */
    private function validate_single_post_type(string $post_type, array $expected_config): array
    {
        $result = [
            'exists' => false,
            'properly_configured' => false,
            'issues' => [],
            'post_type' => $post_type
        ];

        // Check if post type exists
        if (!post_type_exists($post_type)) {
            $result['issues'][] = 'Post type does not exist';
            return $result;
        }

        $result['exists'] = true;
        $post_type_object = get_post_type_object($post_type);

        // Check configuration
        $config_issues = [];

        // Check public setting
        if (isset($expected_config['public']) && $post_type_object->public !== $expected_config['public']) {
            $config_issues[] = "Public setting mismatch (expected: {$expected_config['public']}, actual: {$post_type_object->public})";
        }

        // Check show_ui setting
        if (isset($expected_config['show_ui']) && $post_type_object->show_ui !== $expected_config['show_ui']) {
            $config_issues[] = "Show UI setting mismatch (expected: {$expected_config['show_ui']}, actual: {$post_type_object->show_ui})";
        }

        // Check show_in_rest setting
        if (isset($expected_config['show_in_rest']) && $post_type_object->show_in_rest !== $expected_config['show_in_rest']) {
            $config_issues[] = "Show in REST setting mismatch (expected: {$expected_config['show_in_rest']}, actual: {$post_type_object->show_in_rest})";
        }

        // Check has_archive setting
        if (isset($expected_config['has_archive']) && $post_type_object->has_archive !== $expected_config['has_archive']) {
            $config_issues[] = "Has archive setting mismatch (expected: {$expected_config['has_archive']}, actual: {$post_type_object->has_archive})";
        }

        // Check supports
        if (isset($expected_config['supports'])) {
            $actual_supports = get_all_post_type_supports($post_type);
            $expected_supports = array_flip($expected_config['supports']);
            
            $missing_supports = array_diff_key($expected_supports, $actual_supports);
            if (!empty($missing_supports)) {
                $config_issues[] = 'Missing supports: ' . implode(', ', array_keys($missing_supports));
            }
        }

        $result['issues'] = $config_issues;
        $result['properly_configured'] = empty($config_issues);

        return $result;
    }

    /**
     * Get validation results
     */
    public function get_validation_results(): array
    {
        if (empty($this->validation_results)) {
            $this->validate_post_types();
        }
        
        return $this->validation_results;
    }

    /**
     * Get post type statistics
     */
    public function get_post_type_statistics(): array
    {
        $stats = [];
        
        foreach (array_keys($this->required_post_types) as $post_type) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $stats[$post_type] = [
                    'total' => array_sum((array) $count),
                    'published' => $count->publish ?? 0,
                    'draft' => $count->draft ?? 0,
                    'private' => $count->private ?? 0
                ];
            } else {
                $stats[$post_type] = [
                    'total' => 0,
                    'published' => 0,
                    'draft' => 0,
                    'private' => 0
                ];
            }
        }
        
        return $stats;
    }

    /**
     * AJAX handler for post type validation
     */
    public function ajax_validate_post_types(): void
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error('Access denied');
        }

        $this->validate_post_types();
        $validation_results = $this->get_validation_results();
        $statistics = $this->get_post_type_statistics();

        wp_send_json_success([
            'validation_results' => $validation_results,
            'statistics' => $statistics,
            'summary' => $this->get_validation_summary()
        ]);
    }

    /**
     * Get validation summary
     */
    public function get_validation_summary(): array
    {
        $validation_results = $this->get_validation_results();
        
        $summary = [
            'total_post_types' => count($this->required_post_types),
            'existing_post_types' => 0,
            'properly_configured' => 0,
            'issues_count' => 0,
            'overall_status' => 'good'
        ];

        foreach ($validation_results as $result) {
            if ($result['exists']) {
                $summary['existing_post_types']++;
            }
            
            if ($result['properly_configured']) {
                $summary['properly_configured']++;
            }
            
            $summary['issues_count'] += count($result['issues']);
        }

        // Determine overall status
        if ($summary['existing_post_types'] === $summary['total_post_types'] && $summary['properly_configured'] === $summary['total_post_types']) {
            $summary['overall_status'] = 'excellent';
        } elseif ($summary['existing_post_types'] === $summary['total_post_types']) {
            $summary['overall_status'] = 'good';
        } elseif ($summary['existing_post_types'] > ($summary['total_post_types'] / 2)) {
            $summary['overall_status'] = 'needs_attention';
        } else {
            $summary['overall_status'] = 'critical';
        }

        return $summary;
    }

    /**
     * Fix post type capabilities
     */
    public function fix_post_type_capabilities(): void
    {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return;
        }

        // Add listing capabilities
        $listing_caps = [
            'edit_listings',
            'edit_others_listings',
            'publish_listings',
            'read_private_listings',
            'delete_listings',
            'delete_private_listings',
            'delete_published_listings',
            'delete_others_listings',
            'edit_private_listings',
            'edit_published_listings'
        ];

        foreach ($listing_caps as $cap) {
            $admin_role->add_cap($cap);
        }

        // Add open house capabilities
        $open_house_caps = [
            'manage_open_houses',
            'view_analytics',
            'manage_leads'
        ];

        foreach ($open_house_caps as $cap) {
            $admin_role->add_cap($cap);
        }

        error_log('✅ Post type capabilities updated');
    }
}

// Initialize the validator
add_action('init', function() {
    Post_Type_Validator::get_instance();
});
