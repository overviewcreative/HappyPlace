<?php
/**
 * AJAX Handler Class
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_ajax_actions();
    }

    private function register_ajax_actions(): void {
        // UI and presentation-only actions
        $ajax_actions = [
            'hph_load_template_part' => 'load_template_part',
            'hph_toggle_view_mode' => 'toggle_view_mode',
            'hph_update_ui_state' => 'update_ui_state',
            'hph_load_more_listings' => 'load_more_listings_display',
            'hph_get_listing_card' => 'get_listing_card_html',
            'hph_refresh_map_view' => 'refresh_map_view',
            'hph_validate_form_field' => 'validate_form_field_display',
            // Agent actions
            'hph_filter_agents' => 'filter_agents',
            'hph_agent_contact' => 'agent_contact',
            'hph_agent_property_inquiry' => 'agent_property_inquiry',
            'hph_save_agent' => 'save_agent',
            'hph_unsave_agent' => 'unsave_agent',
            'hph_schedule_callback' => 'schedule_callback'
        ];

        foreach ($ajax_actions as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
        }
    }

    public function load_template_part(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $slug = sanitize_text_field($_POST['slug'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $args = isset($_POST['args']) ? $this->sanitize_template_args($_POST['args']) : [];

        if (!$slug) {
            wp_send_json_error('Invalid template parameters');
        }

        ob_start();
        Template_Manager::instance()->get_template_part($slug, $name, $args);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function toggle_view_mode(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $mode = sanitize_text_field($_POST['mode'] ?? '');
        if (!in_array($mode, ['grid', 'list', 'map'])) {
            wp_send_json_error('Invalid view mode');
        }

        // Store in user meta if logged in, otherwise in session
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'hph_view_mode', $mode);
        } else {
            if (!session_id()) {
                session_start();
            }
            $_SESSION['hph_view_mode'] = $mode;
        }

        wp_send_json_success();
    }

    public function update_ui_state(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $state_key = sanitize_text_field($_POST['key'] ?? '');
        $state_value = sanitize_text_field($_POST['value'] ?? '');

        if (!$state_key) {
            wp_send_json_error('Invalid state parameters');
        }

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), "hph_ui_state_{$state_key}", $state_value);
        } else {
            if (!session_id()) {
                session_start();
            }
            $_SESSION["hph_ui_state_{$state_key}"] = $state_value;
        }

        wp_send_json_success();
    }

    public function load_more_listings_display(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $page = intval($_POST['page'] ?? 1);
        $query_args = isset($_POST['query']) ? $this->sanitize_template_args($_POST['query']) : [];

        $query_args = wp_parse_args($query_args, [
            'post_type' => 'listing',
            'paged' => $page,
            'posts_per_page' => get_option('posts_per_page'),
        ]);

        $listings_query = new \WP_Query($query_args);

        ob_start();
        if ($listings_query->have_posts()) {
            while ($listings_query->have_posts()) {
                $listings_query->the_post();
                Template_Manager::instance()->get_template_part('template-parts/cards/listing-card', '', [
                    'post_id' => get_the_ID(),
                    'size' => 'medium',
                    'show_agent' => true
                ]);
            }
        }
        $html = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success([
            'html' => $html,
            'hasMore' => $listings_query->max_num_pages > $page,
            'nextPage' => $page + 1
        ]);
    }

    public function get_listing_card_html(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        if (!$listing_id) {
            wp_send_json_error('Invalid listing ID');
        }

        ob_start();
        Template_Manager::instance()->get_template_part('template-parts/cards/listing-card', '', [
            'post_id' => $listing_id,
            'size' => sanitize_text_field($_POST['size'] ?? 'medium'),
            'show_agent' => (bool)($_POST['show_agent'] ?? true)
        ]);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function refresh_map_view(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $bounds = $_POST['bounds'] ?? null;
        if (!$bounds || !is_array($bounds)) {
            wp_send_json_error('Invalid map bounds');
        }

        $query_args = [
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'latitude',
                    'value' => [$bounds['south'], $bounds['north']],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ],
                [
                    'key' => 'longitude',
                    'value' => [$bounds['west'], $bounds['east']],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ]
            ]
        ];

        $listings_query = new \WP_Query($query_args);
        $markers = [];

        while ($listings_query->have_posts()) {
            $listings_query->the_post();
            $markers[] = [
                'id' => get_the_ID(),
                'lat' => get_post_meta(get_the_ID(), 'latitude', true),
                'lng' => get_post_meta(get_the_ID(), 'longitude', true),
                'title' => get_the_title(),
                'price' => get_post_meta(get_the_ID(), 'price', true),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'url' => get_permalink()
            ];
        }

        wp_reset_postdata();

        wp_send_json_success(['markers' => $markers]);
    }

    public function validate_form_field_display(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $field_value = sanitize_text_field($_POST['field_value'] ?? '');

        if (!$field_name) {
            wp_send_json_error('Invalid field parameters');
        }

        $validation = $this->validate_field_for_display($field_name, $field_value);
        wp_send_json_success($validation);
    }

    private function sanitize_template_args(array $args): array {
        $sanitized = [];
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_template_args($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        return $sanitized;
    }

    private function validate_field_for_display(string $field_name, string $field_value): array {
        $validation = [
            'isValid' => true,
            'message' => ''
        ];

        switch ($field_name) {
            case 'email':
                if (!is_email($field_value)) {
                    $validation['isValid'] = false;
                    $validation['message'] = __('Please enter a valid email address.', 'happy-place');
                }
                break;
            case 'phone':
                if (!preg_match('/^[\d\s\-\(\)]+$/', $field_value)) {
                    $validation['isValid'] = false;
                    $validation['message'] = __('Please enter a valid phone number.', 'happy-place');
                }
                break;
            // Add more field validations as needed
        }

        return $validation;
    }

    public static function get_current_view_mode(): string {
        if (is_user_logged_in()) {
            $mode = get_user_meta(get_current_user_id(), 'hph_view_mode', true);
        } else {
            if (!session_id()) {
                session_start();
            }
            $mode = $_SESSION['hph_view_mode'] ?? '';
        }

        return $mode ?: 'grid';
    }

    public static function get_ui_state(string $state_key, $default = null) {
        if (is_user_logged_in()) {
            return get_user_meta(get_current_user_id(), "hph_ui_state_{$state_key}", true) ?: $default;
        } else {
            if (!session_id()) {
                session_start();
            }
            return $_SESSION["hph_ui_state_{$state_key}"] ?? $default;
        }
    }
}
