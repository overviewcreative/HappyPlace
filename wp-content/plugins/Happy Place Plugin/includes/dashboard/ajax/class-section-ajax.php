<?php

/**
 * Section AJAX Handler
 * 
 * Handles dashboard section loading, navigation, and section-specific data requests.
 * This replaces the section loading functionality from the monolithic handler.
 * 
 * @package HappyPlace\Dashboard\Ajax
 * @since 3.0.0
 */

namespace HappyPlace\Dashboard\Ajax;

use Exception;
use WP_Query;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Section AJAX Handler Class
 * 
 * Handles:
 * - Dashboard section loading
 * - Section-specific data requests
 * - Navigation state management
 * - Progressive enhancement support
 */
class HPH_Section_Ajax extends HPH_Base_Ajax
{
    /**
     * @var array Available dashboard sections
     */
    private array $sections = [
        'overview',
        'listings',
        'leads',
        'open-houses',
        'performance',
        'profile',
        'settings',
        'cache'
    ];

    /**
     * @var array Section capabilities mapping
     */
    private array $section_capabilities = [
        'overview' => ['edit_posts'],
        'listings' => ['edit_posts'],
        'leads' => ['edit_posts'],
        'open-houses' => ['edit_posts'],
        'performance' => ['edit_posts'],
        'profile' => ['edit_posts'],
        'settings' => ['manage_options'],
        'cache' => ['manage_options']
    ];

    /**
     * Register AJAX actions for section handling
     */
    protected function register_ajax_actions(): void
    {
        // Section loading actions
        add_action('wp_ajax_hph_load_dashboard_section', [$this, 'load_section']);
        add_action('wp_ajax_hph_get_section_data', [$this, 'get_section_data']);
        add_action('wp_ajax_hph_refresh_section', [$this, 'refresh_section']);
        
        // Navigation actions
        add_action('wp_ajax_hph_update_navigation_state', [$this, 'update_navigation_state']);
        add_action('wp_ajax_hph_get_breadcrumbs', [$this, 'get_breadcrumbs']);
    }

    /**
     * Load dashboard section content
     */
    public function load_section(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        // Rate limiting
        if (!$this->check_rate_limit('load_section', 30, 60)) {
            $this->send_error($this->error_messages['rate_limit'], [], 429);
            return;
        }

        $section = sanitize_key($_POST['section'] ?? '');
        $enhanced = (bool)($_POST['enhanced'] ?? false);

        // Validate section
        if (!in_array($section, $this->sections)) {
            $this->send_error('Invalid section requested', ['section' => $section]);
            return;
        }

        // Check permissions
        $required_caps = $this->section_capabilities[$section] ?? ['edit_posts'];
        if (!$this->check_capabilities($required_caps)) {
            $this->handle_security_failure('capabilities');
            return;
        }

        // Log activity
        $this->log_activity('load_section', ['section' => $section, 'enhanced' => $enhanced]);

        try {
            // Load section content
            $content = $this->get_section_content($section, $enhanced);
            
            // Get section metadata
            $metadata = $this->get_section_metadata($section);

            $this->send_success([
                'section' => $section,
                'content' => $content,
                'metadata' => $metadata,
                'enhanced' => $enhanced,
                'timestamp' => current_time('mysql')
            ], "Section '{$section}' loaded successfully");

        } catch (Exception $e) {
            error_log('HPH Section Load Error: ' . $e->getMessage());
            $this->send_error('Failed to load section content');
        }
    }

    /**
     * Get section-specific data without full content reload
     */
    public function get_section_data(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $section = sanitize_key($_POST['section'] ?? '');
        $data_type = sanitize_key($_POST['data_type'] ?? 'default');
        $params = $_POST['params'] ?? [];

        // Validate section
        if (!in_array($section, $this->sections)) {
            $this->send_error('Invalid section requested');
            return;
        }

        // Check permissions
        $required_caps = $this->section_capabilities[$section] ?? ['edit_posts'];
        if (!$this->check_capabilities($required_caps)) {
            $this->handle_security_failure('capabilities');
            return;
        }

        try {
            $data = $this->fetch_section_data($section, $data_type, $params);
            
            $this->send_success([
                'section' => $section,
                'data_type' => $data_type,
                'data' => $data,
                'cache_time' => 300 // 5 minutes
            ]);

        } catch (Exception $e) {
            error_log('HPH Section Data Error: ' . $e->getMessage());
            $this->send_error('Failed to fetch section data');
        }
    }

    /**
     * Refresh section content and clear any cached data
     */
    public function refresh_section(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $section = sanitize_key($_POST['section'] ?? '');

        // Validate section
        if (!in_array($section, $this->sections)) {
            $this->send_error('Invalid section requested');
            return;
        }

        // Check permissions
        $required_caps = $this->section_capabilities[$section] ?? ['edit_posts'];
        if (!$this->check_capabilities($required_caps)) {
            $this->handle_security_failure('capabilities');
            return;
        }

        try {
            // Clear section cache
            $this->clear_section_cache($section);
            
            // Get fresh content
            $content = $this->get_section_content($section, true);
            $metadata = $this->get_section_metadata($section);

            $this->send_success([
                'section' => $section,
                'content' => $content,
                'metadata' => $metadata,
                'refreshed' => true,
                'timestamp' => current_time('mysql')
            ], "Section '{$section}' refreshed successfully");

        } catch (Exception $e) {
            error_log('HPH Section Refresh Error: ' . $e->getMessage());
            $this->send_error('Failed to refresh section');
        }
    }

    /**
     * Update navigation state for better UX
     */
    public function update_navigation_state(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $section = sanitize_key($_POST['section'] ?? '');
        $subsection = sanitize_key($_POST['subsection'] ?? '');
        $filters = $_POST['filters'] ?? [];

        // Store navigation state in user meta
        $user_id = get_current_user_id();
        $nav_state = [
            'current_section' => $section,
            'current_subsection' => $subsection,
            'active_filters' => $filters,
            'timestamp' => time()
        ];

        update_user_meta($user_id, 'hph_dashboard_nav_state', $nav_state);

        $this->send_success([
            'nav_state' => $nav_state
        ], 'Navigation state updated');
    }

    /**
     * Get breadcrumb navigation for current section
     */
    public function get_breadcrumbs(): void
    {
        $section = sanitize_key($_POST['section'] ?? '');
        $subsection = sanitize_key($_POST['subsection'] ?? '');

        $breadcrumbs = $this->build_breadcrumbs($section, $subsection);

        $this->send_success([
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    /**
     * Get section content based on type and enhancement level
     */
    private function get_section_content(string $section, bool $enhanced = false): string
    {
        // Check cache first
        $cache_key = "hph_section_{$section}_" . ($enhanced ? 'enhanced' : 'basic');
        $cached = get_transient($cache_key);
        
        if ($cached !== false && !$this->is_cache_expired($cached)) {
            return $cached['content'];
        }

        // Generate content based on section
        $content = match($section) {
            'overview' => $this->render_overview_section($enhanced),
            'listings' => $this->render_listings_section($enhanced),
            'leads' => $this->render_leads_section($enhanced),
            'open-houses' => $this->render_open_houses_section($enhanced),
            'performance' => $this->render_performance_section($enhanced),
            'profile' => $this->render_profile_section($enhanced),
            'settings' => $this->render_settings_section($enhanced),
            'cache' => $this->render_cache_section($enhanced),
            default => ''
        };

        // Cache the content
        set_transient($cache_key, [
            'content' => $content,
            'generated' => time()
        ], 300); // 5 minutes

        return $content;
    }

    /**
     * Fetch section-specific data
     */
    private function fetch_section_data(string $section, string $data_type, array $params): array
    {
        return match($section) {
            'overview' => $this->get_overview_data($data_type, $params),
            'listings' => $this->get_listings_data($data_type, $params),
            'leads' => $this->get_leads_data($data_type, $params),
            'open-houses' => $this->get_open_houses_data($data_type, $params),
            'performance' => $this->get_performance_data($data_type, $params),
            'profile' => $this->get_profile_data($data_type, $params),
            'settings' => $this->get_settings_data($data_type, $params),
            default => []
        };
    }

    /**
     * Get section metadata for enhanced responses
     */
    private function get_section_metadata(string $section): array
    {
        $user_context = $this->get_user_context();
        
        return [
            'section_title' => $this->get_section_title($section),
            'permissions' => $this->section_capabilities[$section] ?? [],
            'user_can_edit' => $this->check_capabilities($this->section_capabilities[$section] ?? []),
            'last_updated' => $this->get_section_last_updated($section, $user_context['user_id']),
            'has_notifications' => $this->section_has_notifications($section, $user_context['user_id']),
            'quick_actions' => $this->get_section_quick_actions($section)
        ];
    }

    /**
     * Render overview section
     */
    private function render_overview_section(bool $enhanced): string
    {
        $user_context = $this->get_user_context();
        
        ob_start();
        ?>
        <div class="dashboard-section overview-section" data-section="overview" <?php echo $enhanced ? 'data-enhanced="true"' : ''; ?>>
            <div class="section-header">
                <h2>Dashboard Overview</h2>
                <div class="section-actions">
                    <button class="btn btn-sm btn-secondary refresh-section" data-section="overview">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="overview-stats">
                <div class="stat-grid">
                    <div class="stat-card listings-stat">
                        <div class="stat-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-listings">-</h3>
                            <p>Active Listings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card leads-stat">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-leads">-</h3>
                            <p>New Leads</p>
                        </div>
                    </div>
                    
                    <div class="stat-card views-stat">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-views">-</h3>
                            <p>Total Views</p>
                        </div>
                    </div>
                    
                    <div class="stat-card inquiries-stat">
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-inquiries">-</h3>
                            <p>Inquiries</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overview-charts">
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <div class="recent-activity">
                <h3>Recent Activity</h3>
                <div id="activity-feed" class="activity-list">
                    <!-- Activity items will be loaded via AJAX -->
                </div>
            </div>
        </div>

        <?php if ($enhanced): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.DashboardOverview) {
                window.dashboardComponents = window.dashboardComponents || {};
                window.dashboardComponents.overview = new DashboardOverview('.overview-section');
            }
        });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Render listings section
     */
    private function render_listings_section(bool $enhanced): string
    {
        $user_context = $this->get_user_context();
        
        ob_start();
        ?>
        <div class="dashboard-section listings-section" data-section="listings" <?php echo $enhanced ? 'data-enhanced="true"' : ''; ?>>
            <div class="section-header">
                <h2>My Listings</h2>
                <div class="section-actions">
                    <a href="/wp-admin/post-new.php?post_type=listing" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Listing
                    </a>
                    <div class="view-toggle">
                        <button class="view-btn grid-view active" data-view="grid">
                            <i class="fas fa-th"></i> Grid
                        </button>
                        <button class="view-btn list-view" data-view="list">
                            <i class="fas fa-list"></i> List
                        </button>
                    </div>
                </div>
            </div>

            <div class="section-filters">
                <div class="filter-row">
                    <input type="search" class="dashboard-search" placeholder="Search listings..." data-filter="search">
                    <select class="dashboard-filter" data-filter="status">
                        <option value="">All Status</option>
                        <option value="publish">Active</option>
                        <option value="pending">Pending</option>
                        <option value="sold">Sold</option>
                        <option value="draft">Draft</option>
                    </select>
                    <select class="dashboard-filter" data-filter="type">
                        <option value="">All Types</option>
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                        <option value="land">Land</option>
                    </select>
                    <button class="btn btn-sm btn-outline reset-filters">Reset</button>
                </div>
            </div>

            <div class="listings-container grid-view" id="listings-container">
                <!-- Listings will be loaded here -->
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i> Loading listings...
                </div>
            </div>

            <div class="listings-pagination">
                <!-- Pagination will be generated here -->
            </div>
        </div>

        <?php if ($enhanced): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.ListingsSection) {
                window.dashboardComponents = window.dashboardComponents || {};
                window.dashboardComponents.listings = new ListingsSection('.listings-section');
            }
        });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Clear section cache
     */
    private function clear_section_cache(string $section): void
    {
        delete_transient("hph_section_{$section}_enhanced");
        delete_transient("hph_section_{$section}_basic");
        delete_transient("hph_section_data_{$section}");
    }

    /**
     * Check if cached content is expired
     */
    private function is_cache_expired(array $cached): bool
    {
        return (time() - $cached['generated']) > 300; // 5 minutes
    }

    /**
     * Get section title for display
     */
    private function get_section_title(string $section): string
    {
        return match($section) {
            'overview' => 'Dashboard Overview',
            'listings' => 'My Listings',
            'leads' => 'Leads & Contacts',
            'open-houses' => 'Open Houses',
            'performance' => 'Performance & Analytics',
            'profile' => 'Agent Profile',
            'settings' => 'Settings',
            'cache' => 'Cache Management',
            default => ucwords(str_replace('-', ' ', $section))
        };
    }

    /**
     * Get when section was last updated
     */
    private function get_section_last_updated(string $section, int $user_id): ?string
    {
        // This would typically query the database for the most recent update
        // For now, return current time as placeholder
        return current_time('mysql');
    }

    /**
     * Check if section has notifications
     */
    private function section_has_notifications(string $section, int $user_id): bool
    {
        // This would check for section-specific notifications
        return false;
    }

    /**
     * Get quick actions for section
     */
    private function get_section_quick_actions(string $section): array
    {
        return match($section) {
            'listings' => [
                ['label' => 'Add Listing', 'url' => '/wp-admin/post-new.php?post_type=listing'],
                ['label' => 'Import Listings', 'action' => 'import_listings']
            ],
            'leads' => [
                ['label' => 'Add Lead', 'action' => 'add_lead'],
                ['label' => 'Export Leads', 'action' => 'export_leads']
            ],
            default => []
        };
    }

    /**
     * Build breadcrumb navigation
     */
    private function build_breadcrumbs(string $section, string $subsection = ''): array
    {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => '/dashboard/']
        ];

        if (!empty($section)) {
            $breadcrumbs[] = [
                'label' => $this->get_section_title($section),
                'url' => "/dashboard/?section={$section}"
            ];
        }

        if (!empty($subsection)) {
            $breadcrumbs[] = [
                'label' => ucwords(str_replace('-', ' ', $subsection)),
                'url' => "/dashboard/?section={$section}&subsection={$subsection}"
            ];
        }

        return $breadcrumbs;
    }

    // Placeholder methods for other sections and data fetching
    private function render_leads_section(bool $enhanced): string { return '<div>Leads section placeholder</div>'; }
    private function render_open_houses_section(bool $enhanced): string { return '<div>Open Houses section placeholder</div>'; }
    private function render_performance_section(bool $enhanced): string { return '<div>Performance section placeholder</div>'; }
    private function render_profile_section(bool $enhanced): string { return '<div>Profile section placeholder</div>'; }
    private function render_settings_section(bool $enhanced): string { return '<div>Settings section placeholder</div>'; }
    private function render_cache_section(bool $enhanced): string { return '<div>Cache section placeholder</div>'; }
    
    private function get_overview_data(string $data_type, array $params): array { return []; }
    private function get_listings_data(string $data_type, array $params): array { return []; }
    private function get_leads_data(string $data_type, array $params): array { return []; }
    private function get_open_houses_data(string $data_type, array $params): array { return []; }
    private function get_performance_data(string $data_type, array $params): array { return []; }
    private function get_profile_data(string $data_type, array $params): array { return []; }
    private function get_settings_data(string $data_type, array $params): array { return []; }
}
