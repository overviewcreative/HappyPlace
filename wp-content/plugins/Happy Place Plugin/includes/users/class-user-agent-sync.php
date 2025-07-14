<?php

namespace HappyPlace\Users;

/**
 * Handles synchronization between WordPress users and Agent CPT entries
 */
class User_Agent_Sync
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // When a user is updated
        add_action('profile_update', [$this, 'sync_user_to_agent'], 10, 2);

        // When user meta is updated
        add_action('updated_user_meta', [$this, 'sync_user_meta_to_agent'], 10, 4);

        // When an agent CPT is updated
        add_action('acf/save_post', [$this, 'sync_agent_to_user'], 20);

        // When a user is assigned the agent role
        add_action('add_user_role', [$this, 'handle_role_change'], 10, 2);
        add_action('remove_user_role', [$this, 'handle_role_change'], 10, 2);
        add_action('set_user_role', [$this, 'handle_role_change'], 10, 3);
    }

    /**
     * Get or create the linked agent post for a user
     */
    public function get_or_create_agent_post(int $user_id): ?int
    {
        // First try to find existing agent post
        $existing_agents = get_posts([
            'post_type' => 'agent',
            'meta_key' => '_linked_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);

        if (!empty($existing_agents)) {
            return $existing_agents[0]->ID;
        }

        // If no agent post exists and user has agent role, create one
        $user = get_userdata($user_id);
        if (!$user || !in_array('hph_agent', $user->roles)) {
            return null;
        }

        // Create new agent post
        $post_data = [
            'post_title' => $user->display_name,
            'post_type' => 'agent',
            'post_status' => 'publish',
        ];

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            error_log('Failed to create agent post: ' . $post_id->get_error_message());
            return null;
        }

        // Link the agent post to the user
        update_post_meta($post_id, '_linked_user_id', $user_id);

        return $post_id;
    }

    /**
     * Sync user data to agent post
     */
    public function sync_user_to_agent($user_id, $old_user_data = null): void
    {
        $agent_id = $this->get_or_create_agent_post($user_id);
        if (!$agent_id) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // Update agent post with user data
        wp_update_post([
            'ID' => $agent_id,
            'post_title' => $user->display_name
        ]);

        // Sync ACF fields
        if (function_exists('update_field')) {
            $fields_to_sync = [
                'name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user_id, 'hph_phone', true),
                'title' => get_user_meta($user_id, 'hph_title', true),
                'bio' => get_user_meta($user_id, 'hph_bio', true),
                'license_number' => get_user_meta($user_id, 'hph_license_number', true)
            ];

            foreach ($fields_to_sync as $field => $value) {
                if ($value) {
                    update_field($field, $value, $agent_id);
                }
            }
        }
    }

    /**
     * Sync user meta updates to agent post
     */
    public function sync_user_meta_to_agent($meta_id, $user_id, $meta_key, $meta_value): void
    {
        if (!in_array($meta_key, ['hph_phone', 'hph_title', 'hph_bio', 'hph_license_number'])) {
            return;
        }

        $this->sync_user_to_agent($user_id);
    }

    /**
     * Sync agent post data back to user
     */
    public function sync_agent_to_user($post_id): void
    {
        if (get_post_type($post_id) !== 'agent') {
            return;
        }

        $user_id = get_post_meta($post_id, '_linked_user_id', true);
        if (!$user_id) {
            return;
        }

        // Only sync if fields are modified through ACF
        if (function_exists('get_field')) {
            $fields_to_sync = [
                'phone' => 'hph_phone',
                'title' => 'hph_title',
                'bio' => 'hph_bio',
                'license_number' => 'hph_license_number'
            ];

            foreach ($fields_to_sync as $acf_field => $user_meta) {
                $value = get_field($acf_field, $post_id);
                if ($value !== false) {
                    update_user_meta($user_id, $user_meta, $value);
                }
            }
        }
    }

    /**
     * Handle user role changes
     */
    public function handle_role_change($user_id, $role, $old_roles = []): void
    {
        if ($role === 'hph_agent' || (is_array($old_roles) && in_array('hph_agent', $old_roles))) {
            $this->sync_user_to_agent($user_id);
        }
    }
}

// Initialize
User_Agent_Sync::get_instance();
