<?php

namespace HappyPlace\Integration;

/**
 * CRM Integration
 *
 * Base CRM integration with support for multiple CRM systems
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class CRM_Integration extends Base_Integration {
    
    /**
     * CRM type
     * @var string
     */
    protected $crm_type;
    
    /**
     * CRM client
     * @var mixed
     */
    protected $crm_client;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration
     */
    public function __construct($config = []) {
        $this->crm_type = $config['crm_type'] ?? 'generic';
        parent::__construct($config);
    }
    
    /**
     * Get integration type
     * 
     * @return string
     */
    protected function get_integration_type() {
        return 'crm';
    }
    
    /**
     * Get default configuration
     * 
     * @return array
     */
    protected function get_defaults() {
        return [
            'crm_type' => 'generic',
            'api_endpoint' => '',
            'api_key' => '',
            'sync_direction' => 'bidirectional',
            'sync_leads' => true,
            'sync_contacts' => true,
            'sync_agents' => false,
            'lead_source' => 'Happy Place Website'
        ];
    }
    
    /**
     * Initialize CRM client
     */
    protected function init_api_client() {
        // For now, use a generic HTTP client
        $this->api_client = new \WP_Http();
    }
    
    /**
     * Get rate limits
     * 
     * @return array
     */
    protected function get_rate_limits() {
        return [
            'requests_per_second' => 2,
            'requests_per_minute' => 100,
            'requests_per_hour' => 1000
        ];
    }
    
    /**
     * Get webhook configuration
     * 
     * @return array
     */
    protected function get_webhook_config() {
        return [
            'endpoint' => '/crm-webhook',
            'secret' => get_option('hph_crm_webhook_secret', ''),
            'events' => ['lead_created', 'lead_updated', 'contact_updated']
        ];
    }
    
    /**
     * Transform incoming CRM data
     * 
     * @param array $data CRM data
     * @return array WordPress formatted data
     */
    protected function transform_incoming_data($data) {
        // Transform CRM data to WordPress format
        return [
            'crm_id' => $data['id'] ?? '',
            'email' => sanitize_email($data['email'] ?? ''),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'lead_source' => sanitize_text_field($data['source'] ?? $this->config['lead_source']),
            'status' => sanitize_text_field($data['status'] ?? 'new'),
            'notes' => wp_kses_post($data['notes'] ?? ''),
            'last_sync' => current_time('mysql')
        ];
    }
    
    /**
     * Transform outgoing WordPress data
     * 
     * @param array $data WordPress data
     * @return array CRM formatted data
     */
    protected function transform_outgoing_data($data) {
        // Transform WordPress data to CRM format
        return [
            'email' => $data['email'] ?? '',
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'source' => $data['lead_source'] ?? $this->config['lead_source'],
            'status' => $data['status'] ?? 'new',
            'notes' => $data['notes'] ?? '',
            'custom_fields' => [
                'website_source' => 'Happy Place',
                'sync_date' => current_time('mysql')
            ]
        ];
    }
    
    /**
     * Sync WordPress leads to CRM
     * 
     * @return array Sync results
     */
    public function sync_leads_to_crm() {
        $leads = $this->get_wordpress_leads();
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];
        
        foreach ($leads as $lead) {
            try {
                $crm_data = $this->transform_outgoing_data($lead);
                
                if ($this->lead_exists_in_crm($lead)) {
                    $result = $this->update_crm_lead($lead, $crm_data);
                    $results['updated']++;
                } else {
                    $result = $this->create_crm_lead($crm_data);
                    $results['created']++;
                    
                    // Store CRM ID back to WordPress
                    if (isset($result['id'])) {
                        update_post_meta($lead['ID'], 'crm_id', $result['id']);
                    }
                }
                
                $results['processed']++;
                
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'lead_id' => $lead['ID'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get WordPress leads that need syncing
     * 
     * @return array WordPress leads
     */
    protected function get_wordpress_leads() {
        // Get contact form submissions, user registrations, etc.
        $leads = [];
        
        // Example: Get contact form entries
        $contact_forms = get_posts([
            'post_type' => 'contact_submission',
            'posts_per_page' => 100,
            'meta_query' => [
                [
                    'key' => 'synced_to_crm',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        foreach ($contact_forms as $form) {
            $leads[] = [
                'ID' => $form->ID,
                'email' => get_post_meta($form->ID, 'email', true),
                'first_name' => get_post_meta($form->ID, 'first_name', true),
                'last_name' => get_post_meta($form->ID, 'last_name', true),
                'phone' => get_post_meta($form->ID, 'phone', true),
                'message' => get_post_meta($form->ID, 'message', true),
                'lead_source' => 'Contact Form',
                'status' => 'new'
            ];
        }
        
        return $leads;
    }
    
    /**
     * Check if lead exists in CRM
     * 
     * @param array $lead WordPress lead data
     * @return bool Lead exists
     */
    protected function lead_exists_in_crm($lead) {
        $crm_id = get_post_meta($lead['ID'], 'crm_id', true);
        return !empty($crm_id);
    }
    
    /**
     * Create lead in CRM
     * 
     * @param array $crm_data CRM formatted data
     * @return array CRM response
     */
    protected function create_crm_lead($crm_data) {
        if (empty($this->config['api_endpoint'])) {
            throw new Integration_Exception('CRM API endpoint not configured');
        }
        
        $response = wp_remote_post($this->config['api_endpoint'] . '/leads', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($crm_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('CRM API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) >= 400) {
            throw new Integration_Exception('CRM API error: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        return $data;
    }
    
    /**
     * Update lead in CRM
     * 
     * @param array $lead WordPress lead data
     * @param array $crm_data CRM formatted data
     * @return array CRM response
     */
    protected function update_crm_lead($lead, $crm_data) {
        $crm_id = get_post_meta($lead['ID'], 'crm_id', true);
        
        if (empty($crm_id)) {
            throw new Integration_Exception('No CRM ID found for lead');
        }
        
        $response = wp_remote_request($this->config['api_endpoint'] . '/leads/' . $crm_id, [
            'method' => 'PATCH',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($crm_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('CRM API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) >= 400) {
            throw new Integration_Exception('CRM API error: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        return $data;
    }
    
    /**
     * Process webhook data
     * 
     * @param array $data Webhook data
     * @return bool Success
     */
    protected function process_webhook_data($data) {
        // Process CRM webhook (lead updates, etc.)
        
        if (isset($data['crm_id'])) {
            // Find WordPress post by CRM ID
            $posts = get_posts([
                'post_type' => 'contact_submission',
                'meta_key' => 'crm_id',
                'meta_value' => $data['crm_id'],
                'posts_per_page' => 1
            ]);
            
            if (!empty($posts)) {
                $post = $posts[0];
                
                // Update WordPress data with CRM changes
                update_post_meta($post->ID, 'crm_status', $data['status'] ?? '');
                update_post_meta($post->ID, 'last_crm_sync', current_time('mysql'));
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate configuration
     * 
     * @return bool Configuration valid
     */
    protected function validate_config() {
        return !empty($this->config['api_endpoint']) && 
               !empty($this->config['api_key']);
    }
    
    /**
     * Test API connection
     * 
     * @return bool API reachable
     */
    protected function test_api_connection() {
        if (!$this->validate_config()) {
            return false;
        }
        
        try {
            $response = wp_remote_get($this->config['api_endpoint'] . '/health', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key']
                ],
                'timeout' => 10
            ]);
            
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get cache keys for data invalidation
     * 
     * @param array $data Updated data
     * @return array Cache keys
     */
    protected function get_cache_keys_for_data($data) {
        return [
            'crm_leads',
            'crm_contacts',
            'lead_statistics'
        ];
    }
}
