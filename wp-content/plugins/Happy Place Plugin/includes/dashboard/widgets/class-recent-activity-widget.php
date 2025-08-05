<?php
/**
 * Recent Activity Widget - Display recent user activity
 * 
 * Shows a chronological list of recent activities like listings added,
 * leads received, sales completed, etc.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Widgets
 */

namespace HappyPlace\Dashboard\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

class Recent_Activity_Widget extends Base_Dashboard_Widget {
    
    /**
     * Get widget identifier
     *
     * @return string Widget ID
     */
    protected function get_widget_id(): string {
        return 'recent-activity';
    }
    
    /**
     * Get widget title
     *
     * @return string Widget title
     */
    protected function get_widget_title(): string {
        return __('Recent Activity', 'happy-place');
    }
    
    /**
     * Get default widget configuration
     *
     * @return array Default config
     */
    protected function get_default_config(): array {
        $config = parent::get_default_config();
        
        return array_merge($config, [
            'description' => __('Your recent real estate activities', 'happy-place'),
            'icon' => 'fas fa-clock',
            'sections' => ['overview'],
            'size' => 'medium',
            'configurable' => true,
            'settings' => [
                'items_limit' => 10,
                'show_types' => ['listings', 'leads', 'sales', 'open_houses'],
                'time_format' => 'relative'
            ]
        ]);
    }
    
    /**
     * Load widget data
     *
     * @param array $args Data arguments
     * @return array Widget data
     */
    protected function load_widget_data(array $args = []): array {
        if (!$this->data_provider) {
            return $this->get_fallback_activity();
        }
        
        $user_id = get_current_user_id();
        $user_settings = $this->get_user_settings();
        $activity_settings = $user_settings['settings'] ?? $this->config['settings'];
        
        // Get recent activity
        $activity = $this->data_provider->get_recent_activity(
            $user_id, 
            $activity_settings['items_limit'] ?? 10
        );
        
        // Filter by activity types if configured
        if (!empty($activity_settings['show_types'])) {
            $activity = array_filter($activity, function($item) use ($activity_settings) {
                return in_array($item['type'], $activity_settings['show_types']);
            });
        }
        
        return [
            'activity' => array_slice($activity, 0, $activity_settings['items_limit'] ?? 10),
            'settings' => $activity_settings,
            'total_count' => count($activity)
        ];
    }
    
    /**
     * Render widget content
     *
     * @param array $args Rendering arguments
     */
    public function render(array $args = []): void {
        // Check access
        if (!$this->can_access()) {
            $this->render_error_state(__('You do not have permission to view this widget.', 'happy-place'));
            return;
        }
        
        // Get widget data
        $data = $this->get_data($args);
        
        if (empty($data['activity'])) {
            $this->render_empty_state(
                __('No recent activity to display.', 'happy-place'),
                [
                    [
                        'title' => __('Add a Listing', 'happy-place'),
                        'url' => '#',
                        'icon' => 'fas fa-plus',
                        'class' => 'button button-primary hph-quick-action',
                        'data-form' => 'quick-listing'
                    ]
                ]
            );
            return;
        }
        
        $activity = $data['activity'];
        $settings = $data['settings'];
        
        ?>
        <div class="hph-recent-activity-widget">
            
            <div class="hph-activity-list">
                <?php foreach ($activity as $item): ?>
                    <div class="hph-activity-item hph-activity-<?php echo esc_attr($item['type']); ?>">
                        
                        <div class="hph-activity-icon">
                            <i class="<?php echo esc_attr($this->get_activity_icon($item['type'])); ?>"></i>
                        </div>
                        
                        <div class="hph-activity-content">
                            <div class="hph-activity-title">
                                <?php if (!empty($item['url'])): ?>
                                    <a href="<?php echo esc_url($item['url']); ?>" class="hph-activity-link">
                                        <?php echo esc_html($item['title']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($item['title']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($item['description'])): ?>
                            <div class="hph-activity-description">
                                <?php echo esc_html($item['description']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="hph-activity-meta">
                                <span class="hph-activity-time">
                                    <?php echo esc_html($this->format_activity_time($item['date'], $settings['time_format'] ?? 'relative')); ?>
                                </span>
                                
                                <?php if (!empty($item['priority'])): ?>
                                <span class="hph-activity-priority hph-priority-<?php echo esc_attr($item['priority']); ?>">
                                    <?php echo esc_html(ucfirst($item['priority'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($item['actions'])): ?>
                        <div class="hph-activity-actions">
                            <?php foreach ($item['actions'] as $action): ?>
                                <a href="<?php echo esc_url($action['url']); ?>" 
                                   class="hph-activity-action <?php echo esc_attr($action['class'] ?? ''); ?>"
                                   title="<?php echo esc_attr($action['title']); ?>">
                                    <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($data['total_count'] > count($activity)): ?>
            <div class="hph-activity-footer">
                <button type="button" class="hph-load-more-activity" 
                        data-widget="<?php echo esc_attr($this->widget_id); ?>"
                        data-offset="<?php echo esc_attr(count($activity)); ?>">
                    <?php esc_html_e('Load More', 'happy-place'); ?>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Get activity icon based on type
     *
     * @param string $type Activity type
     * @return string Icon class
     */
    private function get_activity_icon(string $type): string {
        $icons = [
            'listing_created' => 'fas fa-home',
            'listing_updated' => 'fas fa-edit',
            'listing_sold' => 'fas fa-handshake',
            'lead_received' => 'fas fa-user-plus',
            'lead_contacted' => 'fas fa-phone',
            'lead_converted' => 'fas fa-star',
            'open_house_scheduled' => 'fas fa-calendar-plus',
            'open_house_completed' => 'fas fa-calendar-check',
            'showing_scheduled' => 'fas fa-eye',
            'contract_signed' => 'fas fa-file-signature',
            'commission_received' => 'fas fa-dollar-sign',
            'profile_updated' => 'fas fa-user-edit',
            'agent_added' => 'fas fa-user-tie'
        ];
        
        return $icons[$type] ?? 'fas fa-circle';
    }
    
    /**
     * Format activity time based on settings
     *
     * @param string $date Activity date
     * @param string $format Time format (relative, absolute)
     * @return string Formatted time
     */
    private function format_activity_time(string $date, string $format): string {
        $timestamp = strtotime($date);
        
        switch ($format) {
            case 'absolute':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
                
            case 'relative':
            default:
                return human_time_diff($timestamp) . ' ' . __('ago', 'happy-place');
        }
    }
    
    /**
     * Get fallback activity when data provider is unavailable
     *
     * @return array Fallback activity data
     */
    private function get_fallback_activity(): array {
        return [
            'activity' => [],
            'settings' => $this->config['settings'],
            'total_count' => 0
        ];
    }
    
    /**
     * Handle custom AJAX actions
     *
     * @param string $action Action name
     * @param array $data Request data
     */
    protected function handle_custom_ajax_action(string $action, array $data): void {
        switch ($action) {
            case 'load_more':
                $this->handle_load_more($data);
                break;
                
            case 'filter_by_type':
                $this->handle_filter_by_type($data);
                break;
                
            case 'mark_as_read':
                $this->handle_mark_as_read($data);
                break;
                
            default:
                parent::handle_custom_ajax_action($action, $data);
                break;
        }
    }
    
    /**
     * Handle load more activity
     *
     * @param array $data Request data
     */
    private function handle_load_more(array $data): void {
        $offset = intval($data['offset'] ?? 0);
        $limit = intval($data['limit'] ?? 10);
        
        if (!$this->data_provider) {
            wp_send_json_error(['message' => __('Data provider not available', 'happy-place')]);
        }
        
        $user_id = get_current_user_id();
        $user_settings = $this->get_user_settings();
        $activity_settings = $user_settings['settings'] ?? $this->config['settings'];
        
        // Get more activity
        $all_activity = $this->data_provider->get_recent_activity($user_id, $offset + $limit + 10);
        $more_activity = array_slice($all_activity, $offset, $limit);
        
        // Filter by activity types if configured
        if (!empty($activity_settings['show_types'])) {
            $more_activity = array_filter($more_activity, function($item) use ($activity_settings) {
                return in_array($item['type'], $activity_settings['show_types']);
            });
        }
        
        ob_start();
        foreach ($more_activity as $item):
            ?>
            <div class="hph-activity-item hph-activity-<?php echo esc_attr($item['type']); ?>">
                <!-- Same structure as main render method -->
                <div class="hph-activity-icon">
                    <i class="<?php echo esc_attr($this->get_activity_icon($item['type'])); ?>"></i>
                </div>
                <div class="hph-activity-content">
                    <div class="hph-activity-title">
                        <?php if (!empty($item['url'])): ?>
                            <a href="<?php echo esc_url($item['url']); ?>" class="hph-activity-link">
                                <?php echo esc_html($item['title']); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($item['title']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['description'])): ?>
                    <div class="hph-activity-description">
                        <?php echo esc_html($item['description']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="hph-activity-meta">
                        <span class="hph-activity-time">
                            <?php echo esc_html($this->format_activity_time($item['date'], $activity_settings['time_format'] ?? 'relative')); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php
        endforeach;
        
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'offset' => $offset + count($more_activity),
            'has_more' => count($all_activity) > ($offset + count($more_activity))
        ]);
    }
    
    /**
     * Handle filter by activity type
     *
     * @param array $data Request data
     */
    private function handle_filter_by_type(array $data): void {
        $activity_types = array_map('sanitize_text_field', $data['types'] ?? []);
        
        $allowed_types = ['listings', 'leads', 'sales', 'open_houses', 'showings', 'contracts'];
        $activity_types = array_intersect($activity_types, $allowed_types);
        
        // Update user settings
        $user_id = get_current_user_id();
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        $user_widget_settings[$this->widget_id]['settings']['show_types'] = $activity_types;
        update_user_meta($user_id, 'hph_widget_settings', $user_widget_settings);
        
        // Clear cache and return fresh data
        $this->clear_cache();
        $fresh_data = $this->get_data(['show_types' => $activity_types]);
        
        wp_send_json_success([
            'message' => __('Filter updated', 'happy-place'),
            'data' => $fresh_data
        ]);
    }
    
    /**
     * Handle mark activity as read
     *
     * @param array $data Request data
     */
    private function handle_mark_as_read(array $data): void {
        $activity_id = sanitize_text_field($data['activity_id'] ?? '');
        
        if (empty($activity_id)) {
            wp_send_json_error(['message' => __('Activity ID required', 'happy-place')]);
        }
        
        // Store read activity
        $user_id = get_current_user_id();
        $read_activities = get_user_meta($user_id, 'hph_read_activities', true) ?: [];
        
        if (!in_array($activity_id, $read_activities)) {
            $read_activities[] = $activity_id;
            update_user_meta($user_id, 'hph_read_activities', $read_activities);
        }
        
        wp_send_json_success(['message' => __('Activity marked as read', 'happy-place')]);
    }
    
    /**
     * Validate widget settings
     *
     * @param array $settings Settings to validate
     * @return array|WP_Error Validated settings or error
     */
    protected function validate_settings(array $settings) {
        $validated = [];
        
        // Validate items limit
        if (isset($settings['items_limit'])) {
            $limit = intval($settings['items_limit']);
            if ($limit > 0 && $limit <= 50) {
                $validated['items_limit'] = $limit;
            }
        }
        
        // Validate show types
        if (isset($settings['show_types']) && is_array($settings['show_types'])) {
            $allowed_types = ['listings', 'leads', 'sales', 'open_houses', 'showings', 'contracts'];
            $validated['show_types'] = array_intersect($settings['show_types'], $allowed_types);
        }
        
        // Validate time format
        if (isset($settings['time_format'])) {
            $allowed_formats = ['relative', 'absolute'];
            if (in_array($settings['time_format'], $allowed_formats)) {
                $validated['time_format'] = $settings['time_format'];
            }
        }
        
        return $validated;
    }
}