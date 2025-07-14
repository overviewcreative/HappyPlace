<?php

/**
 * Template Name: Agent Dashboard
 * 
 * This template is used for displaying the agent dashboard.
 *
 * @package HappyPlace
 */

// Verify user is logged in and has appropriate permissions
if (!is_user_logged_in() || (!current_user_can('agent') && !current_user_can('administrator'))) {
    get_header();
?>
    <div class="hph-container">
        <div class="hph-error">
            <p><?php _e('You must be logged in as an agent to view this page.', 'happy-place'); ?></p>
            <p><a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="hph-button"><?php _e('Log In', 'happy-place'); ?></a></p>
        </div>
    </div>
<?php
    get_footer();
    return;
}

// Get the current tab/section and action
$current_section = isset($_GET['section']) ? sanitize_key($_GET['section']) : 'overview';
$current_action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
$allowed_sections = ['overview', 'listings', 'leads', 'open-houses', 'performance', 'profile', 'settings'];
$allowed_actions = ['new-listing', 'edit-listing', 'new-open-house', 'edit-open-house', 'new-lead', 'edit-lead'];

// Add cache section for administrators
if (current_user_can('manage_options')) {
    $allowed_sections[] = 'cache';
}

if (!in_array($current_section, $allowed_sections)) {
    $current_section = 'overview';
}

// Get current agent data
$current_agent_id = get_current_user_id();
$current_user = wp_get_current_user();
$agent_data = [];

// Get ACF fields if available
if (function_exists('get_fields')) {
    $user_fields = get_fields('user_' . $current_agent_id);
    $agent_data = is_array($user_fields) ? $user_fields : [];
}

// Navigation items configuration
$nav_items = [
    'overview' => [
        'icon' => 'fa-home',
        'label' => __('Overview', 'happy-place'),
        'description' => __('Dashboard overview and quick stats', 'happy-place')
    ],
    'listings' => [
        'icon' => 'fa-list',
        'label' => __('Listings', 'happy-place'),
        'description' => __('Manage your property listings', 'happy-place')
    ],
    'leads' => [
        'icon' => 'fa-users',
        'label' => __('Leads', 'happy-place'),
        'description' => __('Manage leads and inquiries', 'happy-place')
    ],
    'open-houses' => [
        'icon' => 'fa-calendar-alt',
        'label' => __('Open Houses', 'happy-place'),
        'description' => __('Schedule and manage open houses', 'happy-place')
    ],
    'performance' => [
        'icon' => 'fa-chart-line',
        'label' => __('Performance', 'happy-place'),
        'description' => __('View analytics and performance metrics', 'happy-place')
    ],
    'profile' => [
        'icon' => 'fa-user',
        'label' => __('Profile', 'happy-place'),
        'description' => __('Edit your agent profile', 'happy-place')
    ],
    'settings' => [
        'icon' => 'fa-gear',
        'label' => __('Settings', 'happy-place'),
        'description' => __('Manage your preferences', 'happy-place')
    ]
];

// Add cache management for administrators
if (current_user_can('manage_options')) {
    $nav_items['cache'] = [
        'icon' => 'fa-sync-alt',
        'label' => __('Cache Management', 'happy-place'),
        'description' => __('Clear various WordPress caches', 'happy-place')
    ];
}

// Remove default title and add custom meta
remove_action('wp_head', '_wp_render_title_tag', 1);
add_action('wp_head', function () use ($current_section, $nav_items) {
    $section_title = $nav_items[$current_section]['label'] ?? __('Dashboard', 'happy-place');
    echo '<title>' . esc_html($section_title) . ' - ' . get_bloginfo('name') . '</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="robots" content="noindex, nofollow">';
}, 1);

get_header();

// Ensure dashboard assets are loaded
do_action('hph_before_dashboard');

// Debug: Check if dashboard styles are enqueued (remove in production)
if (defined('WP_DEBUG') && WP_DEBUG && function_exists('wp_styles')) {
    global $wp_styles;
    $dashboard_styles = [];
    foreach ($wp_styles->queue as $handle) {
        if (strpos($handle, 'hph-dashboard') !== false || strpos($handle, 'dashboard') !== false) {
            $dashboard_styles[] = $handle;
        }
    }
    if (!empty($dashboard_styles)) {
        echo '<!-- Dashboard styles loaded: ' . implode(', ', $dashboard_styles) . ' -->' . "\n";
    } else {
        echo '<!-- WARNING: No dashboard styles found in queue -->' . "\n";
    }

    // Check if hph_is_dashboard() returns true
    if (function_exists('hph_is_dashboard')) {
        echo '<!-- hph_is_dashboard(): ' . (hph_is_dashboard() ? 'true' : 'false') . ' -->' . "\n";
    }

    // Show all enqueued styles for debugging
    echo '<!-- All styles: ' . implode(', ', $wp_styles->queue) . ' -->' . "\n";
}
?>

<div class="hph-dashboard">
    <!-- Mobile Header -->
    <header class="hph-mobile-header">
        <button class="hph-mobile-menu-btn" aria-label="<?php esc_attr_e('Toggle menu', 'happy-place'); ?>">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="hph-mobile-title">
            <?php echo esc_html($nav_items[$current_section]['label'] ?? __('Dashboard', 'happy-place')); ?>
        </h1>
        <div class="hph-mobile-actions">
            <!-- Optional: Add mobile-specific actions here -->
        </div>
    </header>

    <!-- Mobile Overlay -->
    <div class="hph-mobile-overlay"></div>

    <!-- Dashboard Sidebar -->
    <aside class="hph-dashboard-sidebar">
        <!-- Dashboard User Profile -->
        <div class="hph-dashboard-user">
            <?php
            $agent_photo = '';
            if (!empty($agent_data['agent_photo'])) {
                if (is_array($agent_data['agent_photo'])) {
                    $agent_photo = $agent_data['agent_photo']['url'] ?? '';
                } else {
                    $agent_photo = $agent_data['agent_photo'];
                }
            }

            if ($agent_photo) : ?>
                <img src="<?php echo esc_url($agent_photo); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="hph-dashboard-avatar">
            <?php else : ?>
                <div class="hph-dashboard-avatar hph-user-avatar">
                    <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
                </div>
            <?php endif; ?>
            <div class="hph-dashboard-user-info">
                <h3 class="hph-dashboard-user-name"><?php echo esc_html($current_user->display_name); ?></h3>
                <?php if (!empty($agent_data['title'])) : ?>
                    <p><?php echo esc_html($agent_data['title']); ?></p>
                <?php elseif (!empty($agent_data['agent_title'])) : ?>
                    <p><?php echo esc_html($agent_data['agent_title']); ?></p>
                <?php else : ?>
                    <p><?php _e('Real Estate Professional', 'happy-place'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dashboard Navigation -->
        <nav class="hph-dashboard-nav" role="tablist">
            <?php foreach ($nav_items as $section => $item) :
                $is_active = $current_section === $section;
            ?>
                <a href="<?php echo esc_url(add_query_arg('section', $section, get_permalink())); ?>"
                    class="hph-dashboard-nav-item<?php echo $is_active ? ' hph-dashboard-nav-item--active' : ''; ?>"
                    data-section="<?php echo esc_attr($section); ?>"
                    role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr($section); ?>">
                    <i class="fas <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Dashboard Main Content -->
    <main class="hph-dashboard-main">
        <!-- Dashboard Header -->
        <header class="hph-dashboard-header">
            <div class="hph-dashboard-welcome">
                <h1 class="hph-dashboard-title">
                    <?php
                    $greeting = '';
                    $hour = (int)current_time('H');
                    if ($hour < 12) {
                        $greeting = __('Good Morning', 'happy-place');
                    } elseif ($hour < 17) {
                        $greeting = __('Good Afternoon', 'happy-place');
                    } else {
                        $greeting = __('Good Evening', 'happy-place');
                    }
                    echo esc_html($greeting) . ', ' . esc_html(explode(' ', $current_user->display_name)[0]);
                    ?>
                </h1>
                <p class="hph-dashboard-subtitle">
                    <?php _e('Welcome to your agent dashboard', 'happy-place'); ?>
                </p>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="hph-dashboard-content">
            <?php
            // Check if we're in a form action mode
            if (!empty($current_action) && in_array($current_action, $allowed_actions)) {
                // Handle form actions
                switch ($current_action) {
                    case 'new-listing':
                    case 'edit-listing':
                        $form_template = locate_template('templates/template-parts/dashboard/form-listing.php');
                        if ($form_template) {
                            include $form_template;
                        } else {
                            echo '<div class="hph-error">Form template not found: form-listing.php</div>';
                        }
                        break;

                    case 'new-open-house':
                    case 'edit-open-house':
                        $form_template = locate_template('templates/template-parts/dashboard/form-open-house.php');
                        if ($form_template) {
                            include $form_template;
                        } else {
                            echo '<div class="hph-error">Form template not found: form-open-house.php</div>';
                        }
                        break;

                    case 'new-lead':
                    case 'edit-lead':
                        $form_template = locate_template('templates/template-parts/dashboard/form-lead.php');
                        if ($form_template) {
                            include $form_template;
                        } else {
                            echo '<div class="hph-error">Form template not found: form-lead.php</div>';
                        }
                        break;

                    default:
                        // Fallback to section content if action is not recognized
                        $current_action = '';
                        break;
                }
            }
            
            // Show section content (either normal view or fallback from above)
            if (empty($current_action)) {
                foreach ($nav_items as $section => $item) :
                    $is_active = $section === $current_section;
                    ?>
                    <div id="<?php echo esc_attr($section); ?>"
                        class="hph-dashboard-section<?php echo $is_active ? ' hph-dashboard-section--active' : ''; ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr($section); ?>-tab"
                        <?php echo $is_active ? 'data-loaded="true"' : ''; ?>>
                        <?php
                        if ($is_active) {
                            // Try to get section data from the correct class
                            $section_data = [];
                            
                            // Check for the correct AJAX handler class
                            if (class_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler')) {
                                $handler = \HappyPlace\Dashboard\HPH_Dashboard_Ajax_Handler::instance();
                                if (method_exists($handler, 'get_section_data')) {
                                    $section_data = $handler->get_section_data([], $section);
                                }
                            } elseif (class_exists('HPH_Dashboard_Ajax_Handler') && method_exists('HPH_Dashboard_Ajax_Handler', 'get_section_data')) {
                                $section_data = HPH_Dashboard_Ajax_Handler::get_section_data($section);
                            }

                            // Try to load section template
                            $section_template_paths = [
                                'templates/template-parts/dashboard/section-' . $section . '.php',
                                'template-parts/dashboard/section-' . $section . '.php'
                            ];
                            
                            $template_found = false;
                            foreach ($section_template_paths as $template_path) {
                                $full_path = locate_template($template_path);
                                if ($full_path) {
                                    // Make variables available to template
                                    $args = [
                                        'section' => $section,
                                        'section_data' => $section_data,
                                        'current_user' => $current_user,
                                        'user_id' => $current_agent_id
                                    ];
                                    
                                    include $full_path;
                                    $template_found = true;
                                    break;
                                }
                            }
                            
                            if (!$template_found) {
                                // Show fallback content
                                echo '<div class="hph-section-placeholder">';
                                echo '<h2>' . esc_html($item['label']) . '</h2>';
                                echo '<p>' . esc_html($item['description']) . '</p>';
                                echo '<p><em>Template not found: section-' . esc_html($section) . '.php</em></p>';
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    echo '<div class="hph-debug">';
                                    echo '<strong>Debug Info:</strong><br>';
                                    echo 'Current section: ' . esc_html($section) . '<br>';
                                    echo 'Section data: ' . (empty($section_data) ? 'Empty' : 'Available') . '<br>';
                                    echo 'Template paths checked:<br>';
                                    foreach ($section_template_paths as $path) {
                                        echo '- ' . esc_html($path) . '<br>';
                                    }
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                <?php endforeach;
            } ?>
        </div>
    </main>
</div>

<?php
do_action('hph_after_dashboard');

// Add JavaScript configuration for the dashboard
?>
<style>
    /* Dashboard layout adjustments for site wrapper integration */
    .hph-dashboard {
        min-height: calc(100vh - 200px);
        margin: 0;
        display: grid !important;
        grid-template-columns: 280px 1fr !important;
        background-color: #f8f9fa !important;
        position: relative !important;
        z-index: 1 !important;
    }

    /* Ensure dashboard takes full width within site wrapper */
    .hph-site-main .hph-dashboard {
        max-width: none;
        padding: 0;
    }

    /* Remove site wrapper constraints for dashboard */
    body.page-template-agent-dashboard .hph-site-main,
    body.hph-dashboard-page .hph-site-main {
        padding: 0 !important;
        max-width: none !important;
    }

    body.page-template-agent-dashboard .hph-container,
    body.hph-dashboard-page .hph-container {
        max-width: none !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* Force dashboard to use full viewport width */
    .hph-dashboard {
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
    }

    /* Ensure active dashboard section is visible */
    .hph-dashboard-section--active {
        display: block !important;
        opacity: 1 !important;
    }

    /* Ensure dashboard and main content are visible */
    .hph-dashboard-sidebar,
    .hph-dashboard-main {
        display: block !important;
        background: white !important;
    }

    /* Ensure sidebar is visible */
    .hph-dashboard-sidebar {
        width: 280px !important;
        min-height: 500px !important;
    }

    /* Ensure main content area is visible */
    .hph-dashboard-main {
        min-height: 500px !important;
        padding: 20px !important;
    }

    /* Placeholder styling for missing templates */
    .hph-section-placeholder {
        padding: 40px;
        text-align: center;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .hph-debug {
        background: #f0f0f0;
        padding: 15px;
        margin-top: 20px;
        border-radius: 4px;
        font-size: 12px;
        text-align: left;
    }

    .hph-error {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 4px;
        margin: 20px 0;
    }
</style>

<script>
    // Configure AJAX settings for dashboard functionality
    window.dashboardAjax = {
        ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('hph_dashboard_nonce'); ?>'
    };

    window.hphAjaxEnabled = true;
    window.hphAjax = {
        ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('hph_nonce'); ?>'
    };

    // Debug logging (remove in production)
    <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
    console.log('Dashboard configuration loaded:', {
        hphAjax: window.hphAjax,
        dashboardAjax: window.dashboardAjax,
        hphAjaxEnabled: window.hphAjaxEnabled
    });

    window.addEventListener('load', function() {
        console.log('Window loaded, HphDashboard available:', typeof window.HphDashboard);
        if (window.HphDashboard) {
            console.log('HphDashboard methods:', Object.keys(window.HphDashboard));
        }

        console.log('=== DASHBOARD DIAGNOSTIC ===');
        console.log('Dashboard element exists:', !!document.querySelector('.hph-dashboard'));
        console.log('Sidebar element exists:', !!document.querySelector('.hph-dashboard-sidebar'));
        console.log('Main element exists:', !!document.querySelector('.hph-dashboard-main'));
        console.log('Active section exists:', !!document.querySelector('.hph-dashboard-section--active'));

        const dashboard = document.querySelector('.hph-dashboard');
        if (dashboard) {
            const styles = window.getComputedStyle(dashboard);
            console.log('Dashboard display:', styles.display);
            console.log('Dashboard grid-template-columns:', styles.gridTemplateColumns);
            console.log('Dashboard visibility:', styles.visibility);
        }

        const sidebar = document.querySelector('.hph-dashboard-sidebar');
        if (sidebar) {
            const rect = sidebar.getBoundingClientRect();
            console.log('Sidebar dimensions:', {
                width: rect.width,
                height: rect.height,
                top: rect.top,
                left: rect.left
            });
        }
    });
    <?php endif; ?>
</script>

<!-- Essential containers for dashboard functionality -->
<div class="hph-loading-overlay" id="hph-loading-overlay" style="display: none;" aria-hidden="true">
    <div class="hph-loading-content">
        <div class="hph-loading-spinner"></div>
        <h3 class="hph-loading-title"><?php esc_html_e('Loading...', 'happy-place'); ?></h3>
        <p class="hph-loading-message"><?php esc_html_e('Please wait while we load your dashboard.', 'happy-place'); ?></p>
    </div>
</div>

<div id="hph-modal-container" class="hph-modal-container"></div>
<div id="hph-toast-container" class="hph-toast-container"></div>

<?php get_footer(); ?>