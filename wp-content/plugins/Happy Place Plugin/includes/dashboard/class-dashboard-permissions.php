<?php
/**
 * Dashboard Permissions - Role-based access control for dashboard system
 * 
 * Manages user permissions for dashboard access, sections, and widgets
 * based on WordPress roles and custom capabilities.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Permissions {
    
    /**
     * @var array Role-based dashboard access rules
     */
    private array $access_rules = [];
    
    /**
     * @var array Default capabilities for each role
     */
    private array $default_capabilities = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_access_rules();
        $this->init_default_capabilities();
    }
    
    /**
     * Initialize access rules
     */
    private function init_access_rules(): void {
        $this->access_rules = [
            // Administrator - full access
            'administrator' => [
                'dashboard_access' => true,
                'sections' => [
                    'overview' => true,
                    'listings' => true,
                    'profile' => true,
                    'analytics' => true,
                    'leads' => true,
                    'calendar' => true,
                    'marketing' => true,
                    'settings' => true,
                    'admin' => true
                ],
                'widgets' => ['*'], // All widgets
                'capabilities' => ['manage_options', 'edit_posts', 'edit_others_posts', 'publish_posts']
            ],
            
            // Estate Agent - agent-focused access
            'estate_agent' => [
                'dashboard_access' => true,
                'sections' => [
                    'overview' => true,
                    'listings' => true,
                    'profile' => true,
                    'analytics' => true,
                    'leads' => true,
                    'calendar' => true,
                    'marketing' => true,
                    'settings' => true
                ],
                'widgets' => [
                    'quick-stats', 'recent-activity', 'performance', 
                    'quick-actions', 'listing-stats', 'lead-stats',
                    'calendar-widget', 'market-trends'
                ],
                'capabilities' => ['edit_posts', 'publish_posts', 'upload_files']
            ],
            
            // Broker - enhanced agent access
            'broker' => [
                'dashboard_access' => true,
                'sections' => [
                    'overview' => true,
                    'listings' => true,
                    'profile' => true,
                    'analytics' => true,
                    'leads' => true,
                    'calendar' => true,
                    'marketing' => true,
                    'settings' => true,
                    'team' => true
                ],
                'widgets' => [
                    'quick-stats', 'recent-activity', 'performance', 
                    'quick-actions', 'listing-stats', 'lead-stats',
                    'calendar-widget', 'market-trends', 'team-performance'
                ],
                'capabilities' => ['edit_posts', 'edit_others_posts', 'publish_posts', 'upload_files', 'manage_categories']
            ],
            
            // Office Manager - office management access
            'office_manager' => [
                'dashboard_access' => true,
                'sections' => [
                    'overview' => true,
                    'listings' => true,
                    'profile' => true,
                    'analytics' => true,
                    'leads' => true,
                    'calendar' => true,
                    'marketing' => true,
                    'settings' => true,
                    'office' => true,
                    'team' => true
                ],
                'widgets' => [
                    'quick-stats', 'recent-activity', 'performance', 
                    'quick-actions', 'listing-stats', 'lead-stats',
                    'calendar-widget', 'market-trends', 'team-performance',
                    'office-stats'
                ],
                'capabilities' => ['edit_posts', 'edit_others_posts', 'publish_posts', 'upload_files', 'manage_categories', 'edit_users']
            ],
            
            // Client - limited access to client portal
            'client' => [
                'dashboard_access' => true,
                'sections' => [
                    'overview' => true,
                    'profile' => true,
                    'saved-searches' => true,
                    'favorites' => true,
                    'inquiries' => true,
                    'settings' => true
                ],
                'widgets' => [
                    'quick-stats', 'recent-activity', 'saved-properties',
                    'market-updates', 'agent-contact'
                ],
                'capabilities' => ['read']
            ],
            
            // Default for other roles
            'default' => [
                'dashboard_access' => false,
                'sections' => [],
                'widgets' => [],
                'capabilities' => ['read']
            ]
        ];
        
        // Allow customization via filters
        $this->access_rules = apply_filters('hph_dashboard_access_rules', $this->access_rules);
    }
    
    /**
     * Initialize default capabilities
     */
    private function init_default_capabilities(): void {
        $this->default_capabilities = [
            'dashboard_access' => 'read',
            'view_analytics' => 'edit_posts',
            'manage_listings' => 'edit_posts',
            'manage_leads' => 'edit_posts',
            'access_marketing_tools' => 'edit_posts',
            'view_team_data' => 'edit_others_posts',
            'manage_office' => 'manage_options',
            'export_data' => 'edit_posts',
            'import_data' => 'edit_posts'
        ];
        
        $this->default_capabilities = apply_filters('hph_dashboard_default_capabilities', $this->default_capabilities);
    }
    
    /**
     * Check if user can access dashboard
     *
     * @param int $user_id User ID
     * @return bool True if user can access dashboard
     */
    public function user_can_access_dashboard(int $user_id): bool {
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user has any dashboard access
        $user_rules = $this->get_user_access_rules($user);
        if (!$user_rules['dashboard_access']) {
            return false;
        }
        
        // Check basic capability
        if (!user_can($user_id, 'read')) {
            return false;
        }
        
        // Allow filtering
        return apply_filters('hph_user_can_access_dashboard', true, $user_id, $user);
    }
    
    /**
     * Check if user can access specific section
     *
     * @param int $user_id User ID
     * @param string $section_id Section identifier
     * @param array $section_config Section configuration
     * @return bool True if user can access section
     */
    public function user_can_access_section(int $user_id, string $section_id, array $section_config = []): bool {
        if (!$this->user_can_access_dashboard($user_id)) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Get user access rules
        $user_rules = $this->get_user_access_rules($user);
        
        // Check section access
        if (!isset($user_rules['sections'][$section_id]) || !$user_rules['sections'][$section_id]) {
            return false;
        }
        
        // Check section-specific capabilities
        if (!empty($section_config['capabilities'])) {
            foreach ($section_config['capabilities'] as $capability) {
                if (!user_can($user_id, $capability)) {
                    return false;
                }
            }
        }
        
        // Allow filtering
        return apply_filters('hph_user_can_access_section', true, $user_id, $section_id, $section_config);
    }
    
    /**
     * Check if user can access specific widget
     *
     * @param int $user_id User ID
     * @param string $widget_id Widget identifier
     * @param array $widget_config Widget configuration
     * @return bool True if user can access widget
     */
    public function user_can_access_widget(int $user_id, string $widget_id, array $widget_config = []): bool {
        if (!$this->user_can_access_dashboard($user_id)) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Get user access rules
        $user_rules = $this->get_user_access_rules($user);
        
        // Check widget access
        $user_widgets = $user_rules['widgets'];
        if (!in_array('*', $user_widgets) && !in_array($widget_id, $user_widgets)) {
            return false;
        }
        
        // Check widget-specific capabilities
        if (!empty($widget_config['capabilities'])) {
            foreach ($widget_config['capabilities'] as $capability) {
                if (!user_can($user_id, $capability)) {
                    return false;
                }
            }
        }
        
        // Allow filtering
        return apply_filters('hph_user_can_access_widget', true, $user_id, $widget_id, $widget_config);
    }
    
    /**
     * Check if user has specific dashboard capability
     *
     * @param int $user_id User ID
     * @param string $capability Dashboard capability
     * @return bool True if user has capability
     */
    public function user_can(int $user_id, string $capability): bool {
        if (!$user_id) {
            return false;
        }
        
        // Check WordPress capability first
        if (isset($this->default_capabilities[$capability])) {
            $wp_capability = $this->default_capabilities[$capability];
            if (!user_can($user_id, $wp_capability)) {
                return false;
            }
        }
        
        // Check custom dashboard capabilities
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $user_rules = $this->get_user_access_rules($user);
        $user_capabilities = $user_rules['capabilities'];
        
        // Check if user has the capability
        if (in_array($capability, $user_capabilities)) {
            return true;
        }
        
        // Check if user has a role that grants this capability
        foreach ($user->roles as $role) {
            if (isset($this->access_rules[$role]['capabilities']) && 
                in_array($capability, $this->access_rules[$role]['capabilities'])) {
                return true;
            }
        }
        
        // Allow filtering
        return apply_filters('hph_user_can_dashboard_capability', false, $user_id, $capability);
    }
    
    /**
     * Get user access rules based on their roles
     *
     * @param \WP_User $user WordPress user object
     * @return array User access rules
     */
    private function get_user_access_rules(\WP_User $user): array {
        $combined_rules = [
            'dashboard_access' => false,
            'sections' => [],
            'widgets' => [],
            'capabilities' => []
        ];
        
        // Process each user role
        foreach ($user->roles as $role) {
            $role_rules = $this->access_rules[$role] ?? $this->access_rules['default'];
            
            // Dashboard access - any role with access grants access
            if ($role_rules['dashboard_access']) {
                $combined_rules['dashboard_access'] = true;
            }
            
            // Sections - combine all accessible sections
            foreach ($role_rules['sections'] as $section => $access) {
                if ($access) {
                    $combined_rules['sections'][$section] = true;
                }
            }
            
            // Widgets - combine all accessible widgets
            if (in_array('*', $role_rules['widgets'])) {
                $combined_rules['widgets'] = ['*'];
            } else {
                $combined_rules['widgets'] = array_unique(array_merge(
                    $combined_rules['widgets'],
                    $role_rules['widgets']
                ));
            }
            
            // Capabilities - combine all capabilities
            $combined_rules['capabilities'] = array_unique(array_merge(
                $combined_rules['capabilities'],
                $role_rules['capabilities']
            ));
        }
        
        return $combined_rules;
    }
    
    /**
     * Get sections available to user role
     *
     * @param string $role User role
     * @return array Available sections
     */
    public function get_role_sections(string $role): array {
        $role_rules = $this->access_rules[$role] ?? $this->access_rules['default'];
        return array_keys(array_filter($role_rules['sections']));
    }
    
    /**
     * Get widgets available to user role
     *
     * @param string $role User role
     * @return array Available widgets
     */
    public function get_role_widgets(string $role): array {
        $role_rules = $this->access_rules[$role] ?? $this->access_rules['default'];
        return $role_rules['widgets'];
    }
    
    /**
     * Add custom access rule for role
     *
     * @param string $role User role
     * @param array $rules Access rules
     */
    public function add_role_rules(string $role, array $rules): void {
        $this->access_rules[$role] = $rules;
    }
    
    /**
     * Grant section access to role
     *
     * @param string $role User role
     * @param string $section_id Section identifier
     */
    public function grant_section_access(string $role, string $section_id): void {
        if (!isset($this->access_rules[$role])) {
            $this->access_rules[$role] = $this->access_rules['default'];
        }
        
        $this->access_rules[$role]['sections'][$section_id] = true;
    }
    
    /**
     * Revoke section access from role
     *
     * @param string $role User role
     * @param string $section_id Section identifier
     */
    public function revoke_section_access(string $role, string $section_id): void {
        if (isset($this->access_rules[$role]['sections'][$section_id])) {
            $this->access_rules[$role]['sections'][$section_id] = false;
        }
    }
    
    /**
     * Grant widget access to role
     *
     * @param string $role User role
     * @param string $widget_id Widget identifier
     */
    public function grant_widget_access(string $role, string $widget_id): void {
        if (!isset($this->access_rules[$role])) {
            $this->access_rules[$role] = $this->access_rules['default'];
        }
        
        if (!in_array($widget_id, $this->access_rules[$role]['widgets'])) {
            $this->access_rules[$role]['widgets'][] = $widget_id;
        }
    }
    
    /**
     * Revoke widget access from role
     *
     * @param string $role User role
     * @param string $widget_id Widget identifier
     */
    public function revoke_widget_access(string $role, string $widget_id): void {
        if (isset($this->access_rules[$role]['widgets'])) {
            $key = array_search($widget_id, $this->access_rules[$role]['widgets']);
            if ($key !== false) {
                unset($this->access_rules[$role]['widgets'][$key]);
                $this->access_rules[$role]['widgets'] = array_values($this->access_rules[$role]['widgets']);
            }
        }
    }
    
    /**
     * Check if current user can perform action
     *
     * @param string $action Action identifier
     * @param array $args Action arguments
     * @return bool True if user can perform action
     */
    public function current_user_can(string $action, array $args = []): bool {
        $user_id = get_current_user_id();
        
        switch ($action) {
            case 'view_dashboard':
                return $this->user_can_access_dashboard($user_id);
                
            case 'view_section':
                $section_id = $args['section_id'] ?? '';
                $section_config = $args['section_config'] ?? [];
                return $this->user_can_access_section($user_id, $section_id, $section_config);
                
            case 'view_widget':
                $widget_id = $args['widget_id'] ?? '';
                $widget_config = $args['widget_config'] ?? [];
                return $this->user_can_access_widget($user_id, $widget_id, $widget_config);
                
            case 'edit_listing':
                $listing_id = $args['listing_id'] ?? 0;
                return $this->user_can_edit_listing($user_id, $listing_id);
                
            case 'view_analytics':
                return $this->user_can($user_id, 'view_analytics');
                
            case 'manage_leads':
                return $this->user_can($user_id, 'manage_leads');
                
            case 'export_data':
                return $this->user_can($user_id, 'export_data');
                
            default:
                return apply_filters('hph_dashboard_current_user_can', false, $action, $args);
        }
    }
    
    /**
     * Check if user can edit specific listing
     *
     * @param int $user_id User ID
     * @param int $listing_id Listing ID
     * @return bool True if user can edit listing
     */
    private function user_can_edit_listing(int $user_id, int $listing_id): bool {
        if (!$listing_id) {
            return user_can($user_id, 'edit_posts');
        }
        
        // Check if user can edit any listings
        if (!user_can($user_id, 'edit_posts')) {
            return false;
        }
        
        // Check if user can edit others' listings
        if (user_can($user_id, 'edit_others_posts')) {
            return true;
        }
        
        // Check if user is the listing agent
        $listing_agent = get_field('listing_agent', $listing_id);
        $user_agent_id = get_user_meta($user_id, 'agent_post_id', true);
        
        return $listing_agent == $user_agent_id;
    }
    
    /**
     * Get all access rules
     *
     * @return array All access rules
     */
    public function get_access_rules(): array {
        return $this->access_rules;
    }
    
    /**
     * Get default capabilities
     *
     * @return array Default capabilities
     */
    public function get_default_capabilities(): array {
        return $this->default_capabilities;
    }
}