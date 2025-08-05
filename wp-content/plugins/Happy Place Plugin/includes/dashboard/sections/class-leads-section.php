<?php
/**
 * Leads Section - Manages lead tracking and management
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (!defined('ABSPATH')) {
    exit;
}

class Leads_Section extends Base_Dashboard_Section {
    
    /**
     * Section configuration
     */
    protected array $config = [
        'id' => 'leads',
        'title' => 'Lead Management',
        'icon' => 'fas fa-users',
        'priority' => 40,
        'capability' => 'edit_posts'
    ];
    
    /**
     * Initialize the section
     */
    public function __construct() {
        parent::__construct();
        
        add_action('wp_ajax_hph_get_leads', [$this, 'get_leads']);
        add_action('wp_ajax_hph_add_lead', [$this, 'add_lead']);
        add_action('wp_ajax_hph_update_lead', [$this, 'update_lead']);
        add_action('wp_ajax_hph_update_lead_status', [$this, 'update_lead_status']);
        add_action('wp_ajax_hph_delete_lead', [$this, 'delete_lead']);
        add_action('wp_ajax_hph_export_leads', [$this, 'export_leads']);
        add_action('wp_ajax_hph_get_lead_statistics', [$this, 'get_lead_statistics']);
        add_action('wp_ajax_hph_bulk_lead_actions', [$this, 'bulk_lead_actions']);
        add_action('wp_ajax_hph_add_lead_note', [$this, 'add_lead_note']);
        add_action('wp_ajax_hph_schedule_follow_up', [$this, 'schedule_follow_up']);
    }
    
    /**
     * Get leads with filters and pagination
     */
    public function get_leads(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to access this resource.', 'happy-place'));
        }

        $filters = [
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'source' => sanitize_text_field($_POST['source'] ?? ''),
            'agent_id' => absint($_POST['agent_id'] ?? 0),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? '')
        ];
        
        $page = max(1, absint($_POST['page'] ?? 1));
        $per_page = max(1, min(100, absint($_POST['per_page'] ?? 20)));
        
        $leads = $this->query_leads($filters, $page, $per_page);
        
        wp_send_json_success([
            'leads' => $leads['items'],
            'total' => $leads['total'],
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($leads['total'] / $per_page)
        ]);
    }
    
    /**
     * Add new lead
     */
    public function add_lead(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $lead_data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'source' => sanitize_text_field($_POST['source'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'new'),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'property_interest' => sanitize_text_field($_POST['property_interest'] ?? ''),
            'budget_min' => absint($_POST['budget_min'] ?? 0),
            'budget_max' => absint($_POST['budget_max'] ?? 0),
            'assigned_agent' => absint($_POST['assigned_agent'] ?? get_current_user_id())
        ];
        
        // Validate required fields
        if (empty($lead_data['name']) || empty($lead_data['email'])) {
            wp_send_json_error(['message' => __('Name and email are required.', 'happy-place')]);
        }
        
        $lead_id = $this->create_lead($lead_data);
        
        if ($lead_id) {
            wp_send_json_success([
                'message' => __('Lead created successfully.', 'happy-place'),
                'lead_id' => $lead_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to create lead.', 'happy-place')]);
        }
    }
    
    /**
     * Update existing lead
     */
    public function update_lead(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $lead_id = absint($_POST['lead_id'] ?? 0);
        if (!$lead_id) {
            wp_send_json_error(['message' => __('Invalid lead ID.', 'happy-place')]);
        }
        
        $lead_data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'source' => sanitize_text_field($_POST['source'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'property_interest' => sanitize_text_field($_POST['property_interest'] ?? ''),
            'budget_min' => absint($_POST['budget_min'] ?? 0),
            'budget_max' => absint($_POST['budget_max'] ?? 0),
            'assigned_agent' => absint($_POST['assigned_agent'] ?? 0)
        ];
        
        $updated = $this->update_lead_data($lead_id, $lead_data);
        
        if ($updated) {
            wp_send_json_success(['message' => __('Lead updated successfully.', 'happy-place')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update lead.', 'happy-place')]);
        }
    }
    
    /**
     * Update lead status
     */
    public function update_lead_status(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $lead_id = absint($_POST['lead_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!$lead_id || !$status) {
            wp_send_json_error(['message' => __('Invalid lead ID or status.', 'happy-place')]);
        }
        
        $updated = $this->update_lead_data($lead_id, ['status' => $status]);
        
        if ($updated) {
            wp_send_json_success(['message' => __('Lead status updated successfully.', 'happy-place')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update lead status.', 'happy-place')]);
        }
    }
    
    /**
     * Delete lead
     */
    public function delete_lead(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $lead_id = absint($_POST['lead_id'] ?? 0);
        if (!$lead_id) {
            wp_send_json_error(['message' => __('Invalid lead ID.', 'happy-place')]);
        }
        
        $deleted = $this->delete_lead_data($lead_id);
        
        if ($deleted) {
            wp_send_json_success(['message' => __('Lead deleted successfully.', 'happy-place')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete lead.', 'happy-place')]);
        }
    }
    
    /**
     * Export leads
     */
    public function export_leads(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $filters = [
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'source' => sanitize_text_field($_POST['source'] ?? ''),
            'agent_id' => absint($_POST['agent_id'] ?? 0),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? '')
        ];
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        
        $export_data = $this->generate_export($filters, $format);
        
        wp_send_json_success(['export_url' => $export_data]);
    }
    
    /**
     * Get lead statistics
     */
    public function get_lead_statistics(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_die(__('You do not have permission to access this resource.', 'happy-place'));
        }

        $period = sanitize_text_field($_POST['period'] ?? 'month');
        $agent_id = absint($_POST['agent_id'] ?? 0);
        
        $stats = $this->calculate_lead_statistics($period, $agent_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Bulk lead actions
     */
    public function bulk_lead_actions(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $action = sanitize_text_field($_POST['action'] ?? '');
        $lead_ids = array_map('absint', $_POST['lead_ids'] ?? []);
        
        if (empty($action) || empty($lead_ids)) {
            wp_send_json_error(['message' => __('Invalid action or lead IDs.', 'happy-place')]);
        }
        
        $result = $this->process_bulk_action($action, $lead_ids);
        
        if ($result) {
            wp_send_json_success(['message' => __('Bulk action completed successfully.', 'happy-place')]);
        } else {
            wp_send_json_error(['message' => __('Failed to complete bulk action.', 'happy-place')]);
        }
    }
    
    /**
     * Add lead note
     */
    public function add_lead_note(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $lead_id = absint($_POST['lead_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');
        
        if (!$lead_id || empty($note)) {
            wp_send_json_error(['message' => __('Invalid lead ID or note.', 'happy-place')]);
        }
        
        $note_id = $this->add_lead_note_data($lead_id, $note);
        
        if ($note_id) {
            wp_send_json_success([
                'message' => __('Note added successfully.', 'happy-place'),
                'note_id' => $note_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to add note.', 'happy-place')]);
        }
    }
    
    /**
     * Schedule follow up
     */
    public function schedule_follow_up(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'happy-place'));
        }

        $lead_id = absint($_POST['lead_id'] ?? 0);
        $follow_up_date = sanitize_text_field($_POST['follow_up_date'] ?? '');
        $follow_up_note = sanitize_textarea_field($_POST['follow_up_note'] ?? '');
        
        if (!$lead_id || empty($follow_up_date)) {
            wp_send_json_error(['message' => __('Invalid lead ID or follow-up date.', 'happy-place')]);
        }
        
        $scheduled = $this->schedule_lead_follow_up($lead_id, $follow_up_date, $follow_up_note);
        
        if ($scheduled) {
            wp_send_json_success(['message' => __('Follow-up scheduled successfully.', 'happy-place')]);
        } else {
            wp_send_json_error(['message' => __('Failed to schedule follow-up.', 'happy-place')]);
        }
    }
    
    /**
     * Render section content
     */
    public function render(array $args = []): void {
        $data = $this->get_section_data($args);
        ?>
        <div class="hph-leads-section hph-dashboard-container">
            
            <!-- Section Header -->
            <div class="hph-dashboard-card">
                <div class="hph-dashboard-card-header">
                    <h2 class="hph-dashboard-card-title">
                        <i class="fas fa-users"></i>
                        <?php echo esc_html($this->config['title']); ?>
                    </h2>
                    <div class="hph-dashboard-card-actions">
                        <button type="button" class="hph-btn hph-btn--primary" id="add-lead-btn">
                            <i class="fas fa-plus"></i> <?php _e('Add Lead', 'happy-place'); ?>
                        </button>
                        <button type="button" class="hph-btn hph-btn--secondary" id="export-leads-btn">
                            <i class="fas fa-download"></i> <?php _e('Export', 'happy-place'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="hph-dashboard-card">
                <div class="hph-dashboard-card-body">
                    <div class="hph-dashboard-grid hph-dashboard-grid--4-col">
                        <select id="status-filter" class="hph-form-control">
                            <option value=""><?php _e('All Statuses', 'happy-place'); ?></option>
                            <option value="new"><?php _e('New', 'happy-place'); ?></option>
                            <option value="contacted"><?php _e('Contacted', 'happy-place'); ?></option>
                            <option value="qualified"><?php _e('Qualified', 'happy-place'); ?></option>
                            <option value="converted"><?php _e('Converted', 'happy-place'); ?></option>
                            <option value="lost"><?php _e('Lost', 'happy-place'); ?></option>
                        </select>
                        
                        <select id="source-filter" class="hph-form-control">
                            <option value=""><?php _e('All Sources', 'happy-place'); ?></option>
                            <option value="website"><?php _e('Website', 'happy-place'); ?></option>
                            <option value="referral"><?php _e('Referral', 'happy-place'); ?></option>
                            <option value="social"><?php _e('Social Media', 'happy-place'); ?></option>
                            <option value="advertisement"><?php _e('Advertisement', 'happy-place'); ?></option>
                        </select>
                        
                        <input type="text" id="search-leads" class="hph-form-control" placeholder="<?php _e('Search leads...', 'happy-place'); ?>">
                        <button type="button" class="hph-btn hph-btn--secondary" id="filter-leads-btn"><?php _e('Filter', 'happy-place'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Leads Table Card -->
            <div class="hph-dashboard-card">
                <div class="hph-dashboard-card-body">
                    <div class="hph-table-container">
                        <table class="hph-table">
                            <thead>
                                <tr>
                                    <th class="hph-table-check"><input type="checkbox" id="select-all-leads"></th>
                                    <th><?php _e('Name', 'happy-place'); ?></th>
                                    <th><?php _e('Email', 'happy-place'); ?></th>
                                    <th><?php _e('Phone', 'happy-place'); ?></th>
                                    <th><?php _e('Status', 'happy-place'); ?></th>
                                    <th><?php _e('Source', 'happy-place'); ?></th>
                                    <th><?php _e('Date', 'happy-place'); ?></th>
                                    <th><?php _e('Actions', 'happy-place'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="leads-table-body">
                                <!-- Leads data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="hph-dashboard-card-footer">
                    <div class="hph-pagination">
                        <!-- Pagination will be generated via JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Form Modal -->
        <div id="lead-modal" class="hph-modal" style="display: none;">
            <div class="hph-modal-content">
                <div class="hph-modal-header">
                    <h3 id="lead-modal-title"><?php _e('Add Lead', 'happy-place'); ?></h3>
                    <button type="button" class="hph-modal-close">&times;</button>
                </div>
                <div class="hph-modal-body">
                    <form id="lead-form">
                        <input type="hidden" id="lead-id" name="lead_id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lead-name"><?php _e('Name *', 'happy-place'); ?></label>
                                <input type="text" id="lead-name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="lead-email"><?php _e('Email *', 'happy-place'); ?></label>
                                <input type="email" id="lead-email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lead-phone"><?php _e('Phone', 'happy-place'); ?></label>
                                <input type="tel" id="lead-phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="lead-status"><?php _e('Status', 'happy-place'); ?></label>
                                <select id="lead-status" name="status">
                                    <option value="new"><?php _e('New', 'happy-place'); ?></option>
                                    <option value="contacted"><?php _e('Contacted', 'happy-place'); ?></option>
                                    <option value="qualified"><?php _e('Qualified', 'happy-place'); ?></option>
                                    <option value="converted"><?php _e('Converted', 'happy-place'); ?></option>
                                    <option value="lost"><?php _e('Lost', 'happy-place'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lead-source"><?php _e('Source', 'happy-place'); ?></label>
                                <select id="lead-source" name="source">
                                    <option value="website"><?php _e('Website', 'happy-place'); ?></option>
                                    <option value="referral"><?php _e('Referral', 'happy-place'); ?></option>
                                    <option value="social"><?php _e('Social Media', 'happy-place'); ?></option>
                                    <option value="advertisement"><?php _e('Advertisement', 'happy-place'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="lead-property-interest"><?php _e('Property Interest', 'happy-place'); ?></label>
                                <input type="text" id="lead-property-interest" name="property_interest">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lead-budget-min"><?php _e('Budget Min', 'happy-place'); ?></label>
                                <input type="number" id="lead-budget-min" name="budget_min" min="0">
                            </div>
                            <div class="form-group">
                                <label for="lead-budget-max"><?php _e('Budget Max', 'happy-place'); ?></label>
                                <input type="number" id="lead-budget-max" name="budget_max" min="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="lead-notes"><?php _e('Notes', 'happy-place'); ?></label>
                            <textarea id="lead-notes" name="notes" rows="4"></textarea>
                        </div>
                    </form>
                </div>
                <div class="hph-modal-footer">
                    <button type="button" class="button" id="cancel-lead"><?php _e('Cancel', 'happy-place'); ?></button>
                    <button type="button" class="button button-primary" id="save-lead"><?php _e('Save Lead', 'happy-place'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get section ID
     */
    protected function get_section_id(): string {
        return $this->config['id'];
    }
    
    /**
     * Get section title
     */
    protected function get_section_title(): string {
        return $this->config['title'];
    }
    
    /**
     * Get section data
     */
    protected function get_section_data(array $args = []): array {
        // Return initial data for the section
        return [
            'leads_count' => $this->get_leads_count(),
            'status_counts' => $this->get_status_counts(),
            'source_counts' => $this->get_source_counts()
        ];
    }
    
    /**
     * Query leads from database (placeholder implementation)
     */
    private function query_leads(array $filters, int $page, int $per_page): array {
        // This would integrate with your lead storage system
        // For now, returning empty structure
        return [
            'items' => [],
            'total' => 0
        ];
    }
    
    /**
     * Create new lead (placeholder implementation)
     */
    private function create_lead(array $lead_data): int {
        // This would create a lead in your storage system
        return 0;
    }
    
    /**
     * Update lead data (placeholder implementation)
     */
    private function update_lead_data(int $lead_id, array $lead_data): bool {
        // This would update lead in your storage system
        return false;
    }
    
    /**
     * Delete lead data (placeholder implementation)
     */
    private function delete_lead_data(int $lead_id): bool {
        // This would delete lead from your storage system
        return false;
    }
    
    /**
     * Generate export (placeholder implementation)
     */
    private function generate_export(array $filters, string $format): string {
        // This would generate and return export URL
        return '';
    }
    
    /**
     * Calculate lead statistics (placeholder implementation)
     */
    private function calculate_lead_statistics(string $period, int $agent_id): array {
        return [
            'total_leads' => 0,
            'new_leads' => 0,
            'converted_leads' => 0,
            'conversion_rate' => 0
        ];
    }
    
    /**
     * Process bulk action (placeholder implementation)
     */
    private function process_bulk_action(string $action, array $lead_ids): bool {
        return false;
    }
    
    /**
     * Add lead note (placeholder implementation)
     */
    private function add_lead_note_data(int $lead_id, string $note): int {
        return 0;
    }
    
    /**
     * Schedule lead follow up (placeholder implementation)
     */
    private function schedule_lead_follow_up(int $lead_id, string $date, string $note): bool {
        return false;
    }
    
    /**
     * Get leads count (placeholder implementation)
     */
    private function get_leads_count(): int {
        return 0;
    }
    
    /**
     * Get status counts (placeholder implementation)
     */
    private function get_status_counts(): array {
        return [
            'new' => 0,
            'contacted' => 0,
            'qualified' => 0,
            'converted' => 0,
            'lost' => 0
        ];
    }
    
    /**
     * Get source counts (placeholder implementation)
     */
    private function get_source_counts(): array {
        return [
            'website' => 0,
            'referral' => 0,
            'social' => 0,
            'advertisement' => 0
        ];
    }
}