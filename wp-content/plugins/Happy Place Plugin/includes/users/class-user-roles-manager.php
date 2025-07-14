<?php

namespace HappyPlace\Users;

class User_Roles_Manager
{
    private static ?self $instance = null;

    // Custom user roles
    private $custom_roles = [
        'hph_client' => [
            'name' => 'Client',
            'capabilities' => [
                'read' => true,
                'view_listings' => true,
                'save_favorites' => true,
                'submit_inquiries' => true,
            ]
        ],
        'hph_agent' => [
            'name' => 'Real Estate Agent',
            'capabilities' => [
                'read' => true,
                // Listing capabilities
                'create_listings' => true,
                'edit_listings' => true,
                'publish_listings' => true,
                'delete_listings' => true,
                // Client capabilities
                'create_clients' => true,
                'edit_clients' => true,
                'view_client_info' => true,
                'manage_client_data' => true,
                // Transaction capabilities
                'create_transactions' => true,
                'edit_transactions' => true,
                'view_transactions' => true,
                // Community and Place capabilities
                'create_communities' => true,
                'edit_communities' => true,
                'create_places' => true,
                'edit_places' => true,
                // Open House capabilities
                'create_open_houses' => true,
                'edit_open_houses' => true,
                'delete_open_houses' => true,
                // Profile management
                'manage_agent_profile' => true,
                'upload_files' => true
            ]
        ],
        'hph_broker' => [
            'name' => 'Broker',
            'capabilities' => [
                'read' => true,
                'edit_listings' => true,
                'publish_listings' => true,
                'manage_agents' => true,
                'view_all_transactions' => true,
                'manage_agency_settings' => true,
            ]
        ]
    ];

    // Capability mappings
    private $capability_groups = [
        'listings' => [
            'view_listings',
            'create_listings',
            'edit_listings',
            'publish_listings',
            'delete_listings'
        ],
        'agents' => [
            'view_agents',
            'edit_agents',
            'manage_agents'
        ],
        'transactions' => [
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions'
        ],
        'clients' => [
            'view_client_info',
            'create_clients',
            'edit_clients',
            'manage_client_data',
            'delete_clients'
        ],
        'communities' => [
            'view_communities',
            'create_communities',
            'edit_communities',
            'delete_communities'
        ],
        'places' => [
            'view_places',
            'create_places',
            'edit_places',
            'delete_places'
        ],
        'open_houses' => [
            'view_open_houses',
            'create_open_houses',
            'edit_open_houses',
            'delete_open_houses'
        ],
        'media' => [
            'upload_files',
            'edit_files',
            'delete_files'
        ]
    ];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_custom_roles']);
        add_action('user_register', [$this, 'assign_default_role']);
        add_action('wp_ajax_update_user_role', [$this, 'ajax_update_user_role']);
        add_filter('user_has_cap', [$this, 'filter_user_capabilities'], 10, 3);

        // Auto-assign agent to their created content
        add_action('acf/save_post', [$this, 'auto_assign_agent_to_content'], 20);

        // Register meta boxes for agent assignment display
        add_action('add_meta_boxes', [$this, 'register_agent_meta_boxes']);
    }

    /**
     * Register custom user roles
     */
    public function register_custom_roles(): void
    {
        foreach ($this->custom_roles as $role_key => $role_info) {
            if (!get_role($role_key)) {
                add_role(
                    $role_key,
                    $role_info['name'],
                    $role_info['capabilities']
                );
            }
        }
    }

    /**
     * Assign default role to new users
     */
    public function assign_default_role($user_id): void
    {
        $user = get_userdata($user_id);

        // Default role assignment logic
        if (in_array('customer', $user->roles)) {
            $user->set_role('hph_client');
        }
    }

    /**
     * AJAX handler for role updates
     */
    public function ajax_update_user_role(): void
    {
        // Verify nonce and user permissions
        check_ajax_referer('hph_user_role_update', 'security');

        // Check if current user has permission to change roles
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = intval($_POST['user_id']);
        $new_role = sanitize_text_field($_POST['new_role']);

        // Validate role
        if (!array_key_exists($new_role, $this->custom_roles)) {
            wp_send_json_error('Invalid role');
        }

        // Update user role
        $user = get_user_by('ID', $user_id);
        $user->set_role($new_role);

        wp_send_json_success([
            'message' => 'User role updated successfully',
            'new_role' => $new_role
        ]);
    }

    /**
     * Custom capability filtering
     */
    public function filter_user_capabilities($capabilities, $requested_caps, $args): array
    {
        $user = wp_get_current_user();
        $user_id = $user->ID;

        // Broker has full access
        if (in_array('hph_broker', $user->roles)) {
            foreach ($requested_caps as $cap) {
                $capabilities[$cap] = true;
            }
            return $capabilities;
        }

        // Agent-specific capability checks
        if (in_array('hph_agent', $user->roles)) {
            foreach ($requested_caps as $cap) {
                // Get the object ID being accessed (if any)
                $object_id = $args[2] ?? null;

                switch (true) {
                    // Listing permissions
                    case strpos($cap, '_listing') !== false:
                        if ($object_id) {
                            $capabilities[$cap] = $this->agent_can_manage_listing($user_id, $object_id);
                        } else {
                            $capabilities[$cap] = true; // Allow creating new listings
                        }
                        break;

                    // Client permissions
                    case strpos($cap, '_client') !== false:
                        if ($object_id) {
                            $capabilities[$cap] = $this->agent_can_manage_client($user_id, $object_id);
                        } else {
                            $capabilities[$cap] = true; // Allow creating new clients
                        }
                        break;

                    // Transaction permissions
                    case strpos($cap, '_transaction') !== false:
                        if ($object_id) {
                            $capabilities[$cap] = $this->agent_can_manage_transaction($user_id, $object_id);
                        } else {
                            $capabilities[$cap] = true; // Allow creating new transactions
                        }
                        break;

                    // Open House permissions
                    case strpos($cap, '_open_house') !== false:
                        if ($object_id) {
                            $capabilities[$cap] = $this->agent_can_manage_open_house($user_id, $object_id);
                        } else {
                            $capabilities[$cap] = true; // Allow creating new open houses
                        }
                        break;

                    // Community permissions
                    case strpos($cap, '_communit') !== false:
                        $capabilities[$cap] = true; // Agents can manage communities
                        break;

                    // Place permissions
                    case strpos($cap, '_place') !== false:
                        $capabilities[$cap] = true; // Agents can manage local places
                        break;

                    // Media permissions
                    case 'upload_files':
                    case 'edit_files':
                    case 'delete_files':
                        $capabilities[$cap] = true;
                        break;
                }
            }
        }

        return $capabilities;
    }

    /**
     * Check if agent can manage a specific listing
     */
    private function agent_can_manage_listing(int $agent_id, int $listing_id): bool
    {
        // Check if agent is the listing agent
        $listing_agent = get_field('listing_agent', $listing_id);
        if ($listing_agent && $listing_agent['ID'] == $agent_id) {
            return true;
        }

        // Check if agent is part of the listing team
        $listing_team = get_field('listing_team', $listing_id);
        if ($listing_team && is_array($listing_team)) {
            foreach ($listing_team as $team_member) {
                if ($team_member['ID'] == $agent_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if agent can manage a specific client
     */
    private function agent_can_manage_client(int $agent_id, int $client_id): bool
    {
        // Check if agent is the assigned agent
        $assigned_agent = get_field('assigned_agent', $client_id);
        if ($assigned_agent && $assigned_agent['ID'] == $agent_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if agent can manage a specific transaction
     */
    private function agent_can_manage_transaction(int $agent_id, int $transaction_id): bool
    {
        // Check if agent is involved in the transaction
        $listing_agent = get_field('listing_agent', $transaction_id);
        $buyers_agent = get_field('buyers_agent', $transaction_id);

        if (($listing_agent && $listing_agent['ID'] == $agent_id) ||
            ($buyers_agent && $buyers_agent['ID'] == $agent_id)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if agent can manage a specific open house
     */
    private function agent_can_manage_open_house(int $agent_id, int $open_house_id): bool
    {
        // Check if agent is the host
        $host_agent = get_field('host_agent', $open_house_id);
        if ($host_agent && $host_agent['ID'] == $agent_id) {
            return true;
        }

        // Check if agent owns the related listing
        $listing_id = get_field('listing', $open_house_id);
        if ($listing_id) {
            return $this->agent_can_manage_listing($agent_id, $listing_id);
        }

        return false;
    }

    /**
     * Get available roles for user assignment
     */
    public function get_assignable_roles(): array
    {
        $roles = [];
        foreach ($this->custom_roles as $role_key => $role_info) {
            $roles[$role_key] = $role_info['name'];
        }
        return $roles;
    }

    /**
     * Create user profile extension
     */
    public function extend_user_profile($user): void
    {
?>
        <h3>Happy Place Real Estate Profile</h3>
        <table class="form-table">
            <tr>
                <th><label for="hph_phone">Phone Number</label></th>
                <td>
                    <input
                        type="tel"
                        name="hph_phone"
                        id="hph_phone"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'hph_phone', true)); ?>"
                        class="regular-text">
                </td>
            </tr>
            <?php if (in_array('hph_agent', $user->roles)): ?>
                <tr>
                    <th><label for="hph_license_number">License Number</label></th>
                    <td>
                        <input
                            type="text"
                            name="hph_license_number"
                            id="hph_license_number"
                            value="<?php echo esc_attr(get_user_meta($user->ID, 'hph_license_number', true)); ?>"
                            class="regular-text">
                    </td>
                </tr>
            <?php endif; ?>
        </table>
<?php
    }

    /**
     * Save additional user profile fields
     */
    public function save_user_profile($user_id): void
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Save phone number
        if (isset($_POST['hph_phone'])) {
            update_user_meta(
                $user_id,
                'hph_phone',
                sanitize_text_field($_POST['hph_phone'])
            );
        }

        // Save license number for agents
        if (isset($_POST['hph_license_number'])) {
            update_user_meta(
                $user_id,
                'hph_license_number',
                sanitize_text_field($_POST['hph_license_number'])
            );
        }
    }

    /**
     * Initialize hooks for user profile extensions
     */
    public function init_user_profile_hooks(): void
    {
        add_action('show_user_profile', [$this, 'extend_user_profile']);
        add_action('edit_user_profile', [$this, 'extend_user_profile']);
        add_action('personal_options_update', [$this, 'save_user_profile']);
        add_action('edit_user_profile_update', [$this, 'save_user_profile']);
    }

    /**
     * Synchronize user with CRM
     */
    public function sync_user_to_crm(\WP_User $user): void
    {
        // Placeholder for CRM sync logic
        $crm_data = [
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->roles[0] ?? 'client',
            'phone' => get_user_meta($user->ID, 'hph_phone', true)
        ];

        // Implement CRM sync (Follow Up Boss, etc.)
        do_action('hph_user_crm_sync', $user, $crm_data);
    }

    /**
     * Get user dashboard based on role
     */
    public function get_user_dashboard($user = null): string
    {
        if (!$user) {
            $user = wp_get_current_user();
        }

        // Dashboard routing based on user role
        switch (true) {
            case in_array('hph_broker', $user->roles):
                return 'broker-dashboard';
            case in_array('hph_agent', $user->roles):
                return 'agent-dashboard';
            case in_array('hph_client', $user->roles):
                return 'client-dashboard';
            default:
                return 'default-dashboard';
        }
    }

    /**
     * Automatically assign agent to content they create
     */
    public function auto_assign_agent_to_content($post_id): void
    {
        // Only run once, when first saving
        if (get_post_meta($post_id, '_agent_assigned', true)) {
            return;
        }

        // Get current user
        $current_user = wp_get_current_user();
        if (!in_array('hph_agent', $current_user->roles)) {
            return;
        }

        $post_type = get_post_type($post_id);

        switch ($post_type) {
            case 'listing':
                // Assign as listing agent
                update_field('listing_agent', $current_user->ID, $post_id);

                // Initialize empty listing team with current agent
                update_field('listing_team', [$current_user->ID], $post_id);
                break;

            case 'transaction':
                // Determine role based on transaction type
                $transaction_type = get_field('transaction_type', $post_id);
                if ($transaction_type === 'listing') {
                    update_field('listing_agent', $current_user->ID, $post_id);
                } else {
                    update_field('buyers_agent', $current_user->ID, $post_id);
                }
                break;

            case 'client':
                // Assign as primary agent
                update_field('assigned_agent', $current_user->ID, $post_id);

                // Set initial client status
                update_field('client_status', 'active', $post_id);
                break;

            case 'open_house':
                // Assign as host agent
                update_field('host_agent', $current_user->ID, $post_id);

                // If listing is selected, verify agent has permission
                $listing_id = get_field('listing', $post_id);
                if ($listing_id && !$this->agent_can_manage_listing($current_user->ID, $listing_id)) {
                    wp_die(__('You do not have permission to create open houses for this listing.', 'happy-place'));
                }
                break;
        }

        // Mark as processed
        update_post_meta($post_id, '_agent_assigned', true);
    }

    /**
     * Register meta boxes to display agent information
     */
    public function register_agent_meta_boxes(): void
    {
        add_meta_box(
            'hph_agent_info',
            __('Agent Information', 'happy-place'),
            [$this, 'render_agent_meta_box'],
            ['listing', 'transaction', 'client', 'open_house'],
            'side',
            'high'
        );
    }

    /**
     * Render agent meta box content
     */
    public function render_agent_meta_box($post): void
    {
        $post_type = get_post_type($post);

        switch ($post_type) {
            case 'listing':
                $agent_id = get_field('listing_agent', $post->ID);
                $role = __('Listing Agent', 'happy-place');
                break;
            case 'transaction':
                $transaction_type = get_field('transaction_type', $post->ID);
                $agent_id = $transaction_type === 'listing'
                    ? get_field('listing_agent', $post->ID)
                    : get_field('buyers_agent', $post->ID);
                $role = $transaction_type === 'listing'
                    ? __('Listing Agent', 'happy-place')
                    : __('Buyer\'s Agent', 'happy-place');
                break;
            case 'client':
                $agent_id = get_field('assigned_agent', $post->ID);
                $role = __('Assigned Agent', 'happy-place');
                break;
            case 'open_house':
                $agent_id = get_field('host_agent', $post->ID);
                $role = __('Host Agent', 'happy-place');
                break;
            default:
                return;
        }

        if ($agent_id) {
            $agent = get_user_by('ID', $agent_id);
            if ($agent) {
                echo '<p><strong>' . esc_html($role) . ':</strong><br>';
                echo esc_html($agent->display_name) . '</p>';

                $phone = get_user_meta($agent_id, 'agent_phone', true);
                if ($phone) {
                    echo '<p><strong>' . __('Phone:', 'happy-place') . '</strong><br>';
                    echo esc_html($phone) . '</p>';
                }

                echo '<p><strong>' . __('Email:', 'happy-place') . '</strong><br>';
                echo esc_html($agent->user_email) . '</p>';
            }
        }
    }
}

// Initialize User Roles Manager
$user_roles_manager = User_Roles_Manager::get_instance();
$user_roles_manager->init_user_profile_hooks();
