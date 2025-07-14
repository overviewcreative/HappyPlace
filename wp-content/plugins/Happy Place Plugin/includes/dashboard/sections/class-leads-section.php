/**
     * Initialize the section
     */
    public function __construct() {
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
        
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? '');
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
        
        $leads = $this->query_leads($user_id, [
            'page' => $page,
            'per_page' => $per_page,
            'status' => $status,
            'source' => $source,
            'date_range' => $date_range,
            'search' => $search,
            'sort' => $sort
        ]);
        
        wp_send_json_success($leads);
    }
    
    /**
     * Query leads with filters
     */
    private function query_leads(int $user_id, array $args): array {
        global $wpdb;
        
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'status' => '',
            'source' => '',
            'date_range' => '30',
            'search' => '',
            'sort' => 'date_desc'
        ];
        
        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'hph_leads';
        
        // Build WHERE clause
        $where_clauses = ["agent_id = %d"];
        $where_values = [$user_id];
        
        // Status filter
        if ($args['status']) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        // Source filter
        if ($args['source']) {
            $where_clauses[] = "source = %s";
            $where_values[] = $args['source'];
        }
        
        // Date range filter
        if ($args['date_range'] !== 'all') {
            $days = intval($args['date_range']);
            $where_clauses[] = "created_date >= DATE_SUB(NOW(), INTERVAL %d DAY)";
            $where_values[] = $days;
        }
        
        // Search filter
        if ($args['search']) {
            $where_clauses[] = "(name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values = array_merge($where_values, [$search_term, $search_term, $search_term, $search_term]);
        }
        
        $where_clause = implode(' AND ', $where_clauses);
        
        // Build ORDER BY clause
        $order_clause = $this->get_order_clause($args['sort']);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
        $total = $wpdb->get_var($wpdb->prepare($count_query, ...$where_values));
        
        // Get leads with pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        $leads_query = "SELECT * FROM {$table_name} WHERE {$where_clause} {$order_clause} LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, [$args['per_page'], $offset]);
        
        $leads = $wpdb->get_results($wpdb->prepare($leads_query, ...$query_values));
        
        // Format leads data
        $formatted_leads = [];
        foreach ($leads as $lead) {
            $formatted_leads[] = $this->format_lead_data($lead);
        }
        
        return [
            'leads' => $formatted_leads,
            'pagination' => [
                'total' => (int) $total,
                'pages' => ceil($total / $args['per_page']),
                'current_page' => $args['page'],
                'per_page' => $args['per_page']
            ]
        ];
    }
    
    /**
     * Get order clause for SQL query
     */
    private function get_order_clause(string $sort): string {
        switch ($sort) {
            case 'name_asc':
                return 'ORDER BY name ASC';
            case 'name_desc':
                return 'ORDER BY name DESC';
            case 'status_asc':
                return 'ORDER BY status ASC';
            case 'status_desc':
                return 'ORDER BY status DESC';
            case 'source_asc':
                return 'ORDER BY source ASC';
            case 'source_desc':
                return 'ORDER BY source DESC';
            case 'date_asc':
                return 'ORDER BY created_date ASC';
            case 'date_desc':
            default:
                return 'ORDER BY created_date DESC';
        }
    }
    
    /**
     * Format lead data for API response
     */
    private function format_lead_data(object $lead): array {
        // Get associated listing
        $listing = null;
        if ($lead->listing_id) {
            $listing_post = get_post($lead->listing_id);
            if ($listing_post) {
                $listing = [
                    'id' => $listing_post->ID,
                    'title' => $listing_post->post_title,
                    'address' => get_field('listing_address', $listing_post->ID),
                    'price' => get_field('listing_price', $listing_post->ID),
                    'url' => get_permalink($listing_post->ID)
                ];
            }
        }
        
        // Get lead notes
        $notes = $this->get_lead_notes($lead->id);
        
        // Get follow-up reminders
        $follow_ups = $this->get_lead_follow_ups($lead->id);
        
        return [
            'id' => (int) $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => $lead->status,
            'source' => $lead->source,
            'message' => $lead->message,
            'listing' => $listing,
            'created_date' => $lead->created_date,
            'updated_date' => $lead->updated_date,
            'notes' => $notes,
            'follow_ups' => $follow_ups,
            'time_since_contact' => human_time_diff(strtotime($lead->updated_date), current_time('timestamp')),
            'priority' => $this->calculate_lead_priority($lead)
        ];
    }
    
    /**
     * Get lead notes
     */
    private function get_lead_notes(int $lead_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_lead_notes';
        
        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE lead_id = %d ORDER BY created_date DESC",
            $lead_id
        ));
        
        $formatted_notes = [];
        foreach ($notes as $note) {
            $user = get_user_by('ID', $note->user_id);
            $formatted_notes[] = [
                'id' => (int) $note->id,
                'content' => $note->note_content,
                'created_date' => $note->created_date,
                'author' => $user ? $user->display_name : __('Unknown', 'happy-place'),
                'time_ago' => human_time_diff(strtotime($note->created_date), current_time('timestamp'))
            ];
        }
        
        return $formatted_notes;
    }
    
    /**
     * Get lead follow-ups
     */
    private function get_lead_follow_ups(int $lead_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_lead_follow_ups';
        
        $follow_ups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE lead_id = %d AND follow_up_date >= CURDATE() ORDER BY follow_up_date ASC",
            $lead_id
        ));
        
        $formatted_follow_ups = [];
        foreach ($follow_ups as $follow_up) {
            $formatted_follow_ups[] = [
                'id' => (int) $follow_up->id,
                'type' => $follow_up->follow_up_type,
                'date' => $follow_up->follow_up_date,
                'notes' => $follow_up->notes,
                'completed' => (bool) $follow_up->completed,
                'days_until' => ceil((strtotime($follow_up->follow_up_date) - current_time('timestamp')) / DAY_IN_SECONDS)
            ];
        }
        
        return $formatted_follow_ups;
    }
    
    /**
     * Calculate lead priority based on various factors
     */
    private function calculate_lead_priority(object $lead): string {
        $priority_score = 0;
        
        // Source priority
        $source_priority = [
            'referral' => 50,
            'open_house' => 40,
            'phone' => 35,
            'website' => 30,
            'social' => 25,
            'email' => 20
        ];
        
        $priority_score += $source_priority[$lead->source] ?? 15;
        
        // Recency (higher score for newer leads)
        $days_old = ceil((current_time('timestamp') - strtotime($lead->created_date)) / DAY_IN_SECONDS);
        if ($days_old <= 1) {
            $priority_score += 30;
        } elseif ($days_old <= 3) {
            $priority_score += 20;
        } elseif ($days_old <= 7) {
            $priority_score += 10;
        }
        
        // Status priority
        if ($lead->status === 'new') {
            $priority_score += 40;
        } elseif ($lead->status === 'contacted') {
            $priority_score += 20;
        }
        
        // Associated listing (if they're interested in specific property)
        if ($lead->listing_id) {
            $priority_score += 15;
        }
        
        // Determine priority level
        if ($priority_score >= 80) {
            return 'high';
        } elseif ($priority_score >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Add new lead
     */
    public function add_lead(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? 'manual');
        $status = sanitize_text_field($_POST['status'] ?? 'new');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$name || !$email) {
            wp_send_json_error(['message' => __('Name and email are required', 'happy-place')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_leads';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'agent_id' => $user_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'source' => $source,
                'status' => $status,
                'message' => $message,
                'listing_id' => $listing_id ?: null,
                'created_date' => current_time('mysql'),
                'updated_date' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to add lead', 'happy-place')]);
        }
        
        $lead_id = $wpdb->insert_id;
        
        // Add initial note if message provided
        if ($message) {
            $this->add_lead_note_internal($lead_id, $message, $user_id, 'initial');
        }
        
        // Send notification to agent
        $this->send_new_lead_notification($user_id, $lead_id);
        
        wp_send_json_success([
            'message' => __('Lead added successfully', 'happy-place'),
            'lead_id' => $lead_id
        ]);
    }
    
    /**
     * Update existing lead
     */
    public function update_lead(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lead_id = intval($_POST['lead_id'] ?? 0);
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$lead_id) {
            wp_send_json_error(['message' => __('Invalid lead ID', 'happy-place')]);
        }
        
        // Verify ownership
        if (!$this->verify_lead_ownership($lead_id, $user_id)) {
            wp_send_json_error(['message' => __('Lead not found or access denied', 'happy-place')]);
        }
        
        $updates = [];
        $update_formats = [];
        
        // Update fields
        $fields = ['name', 'email', 'phone', 'status', 'source', 'message'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'email') {
                    $updates[$field] = sanitize_email($_POST[$field]);
                } elseif ($field === 'message') {
                    $updates[$field] = sanitize_textarea_field($_POST[$field]);
                } else {
                    $updates[$field] = sanitize_text_field($_POST[$field]);
                }
                $update_formats[] = '%s';
            }
        }
        
        if (isset($_POST['listing_id'])) {
            $updates['listing_id'] = intval($_POST['listing_id']) ?: null;
            $update_formats[] = '%d';
        }
        
        if (empty($updates)) {
            wp_send_json_error(['message' => __('No fields to update', 'happy-place')]);
        }
        
        $updates['updated_date'] = current_time('mysql');
        $update_formats[] = '%s';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_leads';
        
        $result = $wpdb->update(
            $table_name,
            $updates,
            ['id' => $lead_id, 'agent_id' => $user_id],
            $update_formats,
            ['%d', '%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to update lead', 'happy-place')]);
        }
        
        wp_send_json_success([
            'message' => __('Lead updated successfully', 'happy-place')
        ]);
    }
    
    /**
     * Update lead status
     */
    public function update_lead_status(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        $note = sanitize_textarea_field($_POST['note'] ?? '');
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$lead_id || !$new_status) {
            wp_send_json_error(['message' => __('Invalid parameters', 'happy-place')]);
        }
        
        // Verify ownership
        if (!$this->verify_lead_ownership($lead_id, $user_id)) {
            wp_send_json_error(['message' => __('Lead not found or access denied', 'happy-place')]);
        }
        
        // Validate status
        $valid_statuses = ['new', 'contacted', 'qualified', 'nurturing', 'closed', 'lost'];
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(['message' => __('Invalid status', 'happy-place')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_leads';
        
        // Get current status for logging
        $current_lead = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$table_name} WHERE id = %d AND agent_id = %d",
            $lead_id,
            $user_id
        ));
        
        if (!$current_lead) {
            wp_send_json_error(['message' => __('Lead not found', 'happy-place')]);
        }
        
        $old_status = $current_lead->status;
        
        // Update status
        $result = $wpdb->update(
            $table_name,
            [
                'status' => $new_status,
                'updated_date' => current_time('mysql')
            ],
            ['id' => $lead_id, 'agent_id' => $user_id],
            ['%s', '%s'],
            ['%d', '%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to update status', 'happy-place')]);
        }
        
        // Add status change note
        $status_note = sprintf(
            __('Status changed from %s to %s', 'happy-place'),
            ucfirst($old_status),
            ucfirst($new_status)
        );
        
        if ($note) {
            $status_note .= "\n\n" . $note;
        }
        
        $this->add_lead_note_internal($lead_id, $status_note, $user_id, 'status_change');
        
        wp_send_json_success([
            'message' => sprintf(__('Status updated to %s', 'happy-place'), ucfirst($new_status))
        ]);
    }
    
    /**
     * Delete lead
     */
    public function delete_lead(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lead_id = intval($_POST['lead_id'] ?? 0);
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$lead_id) {
            wp_send_json_error(['message' => __('Invalid lead ID', 'happy-place')]);
        }
        
        // Verify ownership
        if (!$this->verify_lead_ownership($lead_id, $user_id)) {
            wp_send_json_error(['message' => __('Lead not found or access denied', 'happy-place')]);
        }
        
        global $wpdb;
        
        // Delete related data first
        $this->cleanup_lead_data($lead_id);
        
        // Delete lead
        $table_name = $wpdb->prefix . 'hph_leads';
        $result = $wpdb->delete(
            $table_name,
            ['id' => $lead_id, 'agent_id' => $user_id],
            ['%d', '%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to delete lead', 'happy-place')]);
        }
        
        wp_send_json_success([
            'message' => __('Lead deleted successfully', 'happy-place')
        ]);
    }
    
    /**
     * Add note to lead
     */
    public function add_lead_note(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $note_content = sanitize_textarea_field($_POST['note'] ?? '');
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$lead_id || !$note_content) {
            wp_send_json_error(['message' => __('Lead ID and note content are required', 'happy-place')]);
        }
        
        // Verify ownership
        if (!$this->verify_lead_ownership($lead_id, $user_id)) {
            wp_send_json_error(['message' => __('Lead not found or access denied', 'happy-place')]);
        }
        
        $note_id = $this->add_lead_note_internal($lead_id, $note_content, $user_id, 'manual');
        
        if (!$note_id) {
            wp_send_json_error(['message' => __('Failed to add note', 'happy-place')]);
        }
        
        wp_send_json_success([
            'message' => __('Note added successfully', 'happy-place'),
            'note_id' => $note_id
        ]);
    }
    
    /**
     * Internal method to add lead note
     */
    private function add_lead_note_internal(int $lead_id, string $content, int $user_id, string $type = 'manual'): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_lead_notes';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'lead_id' => $lead_id,
                'user_id' => $user_id,
                'note_content' => $content,
                'note_type' => $type,
                'created_date' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : 0;
    }
    
    /**
     * Schedule follow-up for lead
     */
    public function schedule_follow_up(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $follow_up_date = sanitize_text_field($_POST['follow_up_date'] ?? '');
        $follow_up_type = sanitize_text_field($_POST['follow_up_type'] ?? 'call');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$lead_id || !$follow_up_date) {
            wp_send_json_error(['message' => __('Lead ID and follow-up date are required', 'happy-place')]);
        }
        
        // Verify ownership
        if (!$this->verify_lead_ownership($lead_id, $user_id)) {
            wp_send_json_error(['message' => __('Lead not found or access denied', 'happy-place')]);
        }
        
        // Validate date
        if (strtotime($follow_up_date) < current_time('timestamp')) {
            wp_send_json_error(['message' => __('Follow-up date must be in the future', 'happy-place')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_lead_follow_ups';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'lead_id' => $lead_id,
                'user_id' => $user_id,
                'follow_up_date' => $follow_up_date,
                'follow_up_type' => $follow_up_type,
                'notes' => $notes,
                'completed' => 0,
                'created_date' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to schedule follow-up', 'happy-place')]);
        }
        
        wp_send_json_success([
            'message' => __('Follow-up scheduled successfully', 'happy-place'),
            'follow_up_id' => $wpdb->insert_id
        ]);
    }
    
    /**
     * Get lead statistics
     */
    public function get_lead_statistics(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        $stats = $this->calculate_lead_statistics($user_id, $date_range);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Calculate lead statistics
     */
    private function calculate_lead_statistics(int $user_id, string $date_range): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_leads';
        
        // Date range condition
        $date_condition = '';
        $date_values = [];
        
        if ($date_range !== 'all') {
            $days = intval($date_range);
            $date_condition = "AND created_date >= DATE_SUB(NOW(), INTERVAL %d DAY)";
            $date_values[] = $days;
        }
        
        // Total leads
        $total_leads = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE agent_id = %d {$date_condition}",
            array_merge([$user_id], $date_values)
        ));
        
        // Leads by status
        $status_query = "SELECT status, COUNT(*) as count FROM {$table_name} WHERE agent_id = %d {$date_condition} GROUP BY status";
        $status_results = $wpdb->get_results($wpdb->prepare($status_query, array_merge([$user_id], $date_values)), OBJECT_K);
        
        $by_status = [
            'new' => 0,
            'contacted' => 0,
            'qualified' => 0,
            'nurturing' => 0,
            'closed' => 0,
            'lost' => 0
        ];
        
        foreach ($status_results as $status => $data) {
            $by_status[$status] = (int) $data->count;
        }
        
        // Leads by source
        $source_query = "SELECT source, COUNT(*) as count FROM {$table_name} WHERE agent_id = %d {$date_condition} GROUP BY source";
        $source_results = $wpdb->get_results($wpdb->prepare($source_query, array_merge([$user_id], $date_values)), OBJECT_K);
        
        $by_source = [];
        foreach ($source_results as $source => $data) {
            $by_source[$source] = [
                'count' => (int) $data->count,
                'percentage' => $total_leads > 0 ? round(($data->count / $total_leads) * 100, 1) : 0
            ];
        }
        
        // Conversion metrics
        $conversion_rate = $total_leads > 0 ? round(($by_status['closed'] / $total_leads) * 100, 1) : 0;
        $response_rate = $total_leads > 0 ? round((($by_status['contacted'] + $by_status['qualified'] + $by_status['nurturing'] + $by_status['closed']) / $total_leads) * 100, 1) : 0;
        
        // Recent trends (compare with previous period)
        $previous_period_leads = 0;
        if ($date_range !== 'all') {
            $days = intval($date_range);
            $previous_period_leads = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                WHERE agent_id = %d 
                AND created_date >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                AND created_date < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $user_id,
                $days * 2,
                $days
            ));
        }
        
        $trend_percentage = 0;
        if ($previous_period_leads > 0) {
            $trend_percentage = round((($total_leads - $previous_period_leads) / $previous_period_leads) * 100, 1);
        } elseif ($total_leads > 0) {
            $trend_percentage = 100;
        }
        
        return [
            'total_leads' => (int) $total_leads,
            'by_status' => $by_status,
            'by_source' => $by_source,
            'conversion_rate' => $conversion_rate,
            'response_rate' => $response_rate,
            'trend' => [
                'current_period' => (int) $total_leads,
                'previous_period' => (int) $previous_period_leads,
                'percentage_change' => $trend_percentage
            ],
            'date_range' => $date_range
        ];
    }
    
    /**
     * Handle bulk lead actions
     */
    public function bulk_lead_actions(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $action = sanitize_text_field($_POST['action'] ?? '');
        $lead_ids = array_map('intval', $_POST['lead_ids'] ?? []);
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$action || empty($lead_ids)) {
            wp_send_json_error(['message' => __('Invalid parameters', 'happy-place')]);
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($lead_ids as $lead_id) {
            // Verify ownership
            if (!$this->verify_lead_ownership($lead_id, $user_id)) {
                $results[] = [
                    'id' => $lead_id,
                    'success' => false,
                    'message' => __('Access denied', 'happy-place')
                ];
                $error_count++;
                continue;
            }
            
            $result = $this->execute_bulk_lead_action($action, $lead_id, $user_id);
            $results[] = $result;
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(
                __('%d leads processed successfully, %d failed', 'happy-place'),
                $success_count,
                $error_count
            ),
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
    }
    
    /**
     * Execute bulk action on single lead
     */
    private function execute_bulk_lead_action(string $action, int $lead_id, int $user_id): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_leads';
        
        switch ($action) {
            case 'mark_contacted':
                $result = $wpdb->update(
                    $table_name,
                    ['status' => 'contacted', 'updated_date' => current_time('mysql')],
                    ['id' => $lead_id, 'agent_id' => $user_id],
                    ['%s', '%s'],
                    ['%d', '%d']
                );
                
                if ($result !== false) {
                    $this->add_lead_note_internal($lead_id, __('Marked as contacted via bulk action', 'happy-place'), $user_id, 'bulk_action');
                }
                
                return [
                    'id' => $lead_id,
                    'success' => $result !== false,
                    'message' => $result !== false ? __('Marked as contacted', 'happy-place') : __('Update failed', 'happy-place')
                ];
                
            case 'mark_qualified':
                $result = $wpdb->update(
                    $table_name,
                    ['status' => 'qualified', 'updated_date' => current_time('mysql')],
                    ['id' => $lead_id, 'agent_id' => $user_id],
                    ['%s', '%s'],
                    ['%d', '%d']
                );
                
                if ($result !== false) {
                    $this->add_lead_note_internal($lead_id, __('Marked as qualified via bulk action', 'happy-place'), $user_id, 'bulk_action');
                }
                
                return [
                    'id' => $lead_id,
                    'success' => $result !== false,
                    'message' => $result !== false ? __('Marked as qualified', 'happy-place') : __('Update failed', 'happy-place')
                ];
                
            case 'mark_lost':
                $result = $wpdb->update(
                    $table_name,
                    ['status' => 'lost', 'updated_date' => current_time('mysql')],
                    ['id' => $lead_id, 'agent_id' => $user_id],
                    ['%s', '%s'],
                    ['%d', '%d']
                );
                
                if ($result !== false) {
                    $this->add_lead_note_internal($lead_id, __('Marked as lost via bulk action', 'happy-place'), $user_id, 'bulk_action');
                }
                
                return [
                    'id' => $lead_id,
                    'success' => $result !== false,
                    'message' => $result !== false ? __('Marked as lost', 'happy-place') : __('Update failed', 'happy-place')
                ];
                
            case 'delete':
                $this->cleanup_lead_data($lead_id);
                $result = $wpdb->delete(
                    $table_name,
                    ['id' => $lead_id, 'agent_id' => $user_id],
                    ['%d', '%d']
                );
                
                return [
                    'id' => $lead_id,
                    'success' => $result !== false,
                    'message' => $result !== false ? __('Deleted', 'happy-place') : __('Delete failed', 'happy-place')
                ];
                
            default:
                return [
                    'id' => $lead_id,
                    'success' => false,
                    'message' => __('Unknown action', 'happy-place')
                ];
        }
    }
    
    /**
     * Export leads to CSV
     */
    public function export_leads(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        $status = sanitize_text_field($_POST['status'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? '');
        $date_range = sanitize_text_field($_POST['date_range'] ?? '');
        
        // Get leads for export
        $leads_data = $this->query_leads($user_id, [
            'page' => 1,
            'per_page' => -1, // Get all leads
            'status' => $status,
            'source' => $source,
            'date_range' => $date_range,
            'search' => '',
            'sort' => 'date_desc'
        ]);
        
        if (empty($leads_data['leads'])) {
            wp_send_json_error(['message' => __('No leads found to export', 'happy-place')]);
        }
        
        // Generate CSV content
        $csv_content = $this->generate_leads_csv($leads_data['leads']);
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'leads_export_' . date('Y-m-d_H-i-s') . '.csv';
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($file_path, $csv_content) === false) {
            wp_send_json_error(['message' => __('Failed to create export file', 'happy-place')]);
        }
        
        $download_url = $upload_dir['url'] . '/' . $filename;
        
        // Schedule file cleanup (delete after 1 hour)
        wp_schedule_single_event(time() + 3600, 'hph_cleanup_export_file', [$file_path]);
        
        wp_send_json_success([
            'message' => sprintf(__('Export ready. %d leads exported.', 'happy-place'), count($leads_data['leads'])),
            'download_url' => $download_url,
            'filename' => $filename
        ]);
    }
    
    /**
     * Generate CSV content from leads data
     */
    private function generate_leads_csv(array $leads): string {
        $output = fopen('php://temp', 'r+');
        
        // CSV headers
        $headers = [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Status',
            'Source',
            'Listing',
            'Message',
            'Created Date',
            'Last Updated',
            'Priority'
        ];
        
        fputcsv($output, $headers);
        
        // CSV data
        foreach ($leads as $lead) {
            $row = [
                $lead['id'],
                $lead['name'],
                $lead['email'],
                $lead['phone'],
                ucfirst($lead['status']),
                ucfirst($lead['source']),
                $lead['listing'] ? $lead['listing']['title'] : '',
                $lead['message'],
                $lead['created_date'],
                $lead['updated_date'],
                ucfirst($lead['priority'])
            ];
            
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    /**
     * Verify lead ownership
     */
    private function verify_lead_ownership(int $lead_id, int $user_id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_leads';
        
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE id = %d AND agent_id = %d",
            $lead_id,
            $user_id
        ));
        
        return $lead !== null;
    }
    
    /**
     * Clean up lead data (notes, follow-ups, etc.)
     */
    private function cleanup_lead_data(int $lead_id): void {
        global $wpdb;
        
        // Delete lead notes
        $notes_table = $wpdb->prefix . 'hph_lead_notes';
        $wpdb->delete($notes_table, ['lead_id' => $lead_id], ['%d']);
        
        // Delete follow-ups
        $follow_ups_table = $wpdb->prefix . 'hph_lead_follow_ups';
        $wpdb->delete($follow_ups_table, ['lead_id' => $lead_id], ['%d']);
        
        // Delete any other related data
        do_action('hph_cleanup_lead_data', $lead_id);
    }
    
    /**
     * Send new lead notification
     */
    private function send_new_lead_notification(int $user_id, int $lead_id): void {
        global $wpdb;
        
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'hph_leads';
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $lead_id
        ));
        
        if (!$lead) {
            return;
        }
        
        $subject = sprintf(__('New Lead: %s', 'happy-place'), $lead->name);
        
        $message = sprintf(
            __("You have a new lead!\n\nName: %s\nEmail: %s\nPhone: %s\nSource: %s\n\nMessage:\n%s\n\nView lead: %s", 'happy-place'),
            $lead->name,
            $lead->email,
            $lead->phone,
            ucfirst($lead->source),
            $lead->message,
            admin_url("admin.php?page=hph-leads&lead_id={$lead_id}")
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        // Send push notification if enabled
        do_action('hph_send_lead_notification', $user_id, $lead_id);
    }
}<?php
/**
 * Leads Section Handler
 * 
 * Handles backend logic for the dashboard leads section
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 * @since 1.0.0
 */

namespace HappyPlace\Dashboard\Sections;

if (!defined('ABSPATH')) {
    exit;
}

class Leads_Section {
    
    /**
     * Section identifier
     */
    const SECTION_ID = 'leads';
    
    /**
     * Initialize the section
     */
    public function __construct() {
        add_