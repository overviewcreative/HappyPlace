<?php

namespace HappyPlace\Integration;

/**
 * Airtable API Client
 *
 * Handles communication with Airtable API
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Airtable_API_Client {
    
    /**
     * API key
     * @var string
     */
    protected $api_key;
    
    /**
     * Base ID
     * @var string
     */
    protected $base_id;
    
    /**
     * API base URL
     * @var string
     */
    protected $api_base = 'https://api.airtable.com/v0/';
    
    /**
     * Constructor
     * 
     * @param array $config Configuration
     */
    public function __construct($config) {
        $this->api_key = $config['api_key'];
        $this->base_id = $config['base_id'];
    }
    
    /**
     * Get records from a table
     * 
     * @param string $table_name Table name
     * @param array $params Query parameters
     * @return array Records
     */
    public function get_records($table_name, $params = []) {
        $url = $this->build_url($table_name, $params);
        return $this->make_request('GET', $url);
    }
    
    /**
     * Get specific records by IDs
     * 
     * @param string $table_name Table name
     * @param array $record_ids Record IDs
     * @return array Records
     */
    public function get_records_by_ids($table_name, $record_ids) {
        $records = [];
        
        // Airtable allows up to 10 records per request
        $chunks = array_chunk($record_ids, 10);
        
        foreach ($chunks as $chunk) {
            $params = ['records[]' => $chunk];
            $url = $this->build_url($table_name, $params);
            $response = $this->make_request('GET', $url);
            
            if (isset($response['records'])) {
                $records = array_merge($records, $response['records']);
            }
        }
        
        return $records;
    }
    
    /**
     * Create a new record
     * 
     * @param string $table_name Table name
     * @param array $fields Record fields
     * @return array Created record
     */
    public function create_record($table_name, $fields) {
        $url = $this->build_url($table_name);
        $data = ['fields' => $fields];
        
        return $this->make_request('POST', $url, $data);
    }
    
    /**
     * Update an existing record
     * 
     * @param string $table_name Table name
     * @param string $record_id Record ID
     * @param array $fields Record fields
     * @return array Updated record
     */
    public function update_record($table_name, $record_id, $fields) {
        $url = $this->build_url($table_name) . '/' . $record_id;
        $data = ['fields' => $fields];
        
        return $this->make_request('PATCH', $url, $data);
    }
    
    /**
     * Delete a record
     * 
     * @param string $table_name Table name
     * @param string $record_id Record ID
     * @return array Deletion result
     */
    public function delete_record($table_name, $record_id) {
        $url = $this->build_url($table_name) . '/' . $record_id;
        
        return $this->make_request('DELETE', $url);
    }
    
    /**
     * Test API connection
     * 
     * @return bool Connection successful
     * @throws Integration_Exception If connection fails
     */
    public function test_connection() {
        try {
            // Try to fetch just one record to test connection
            $url = $this->build_url('', ['maxRecords' => 1]);
            $this->make_request('GET', $url);
            return true;
        } catch (\Exception $e) {
            throw new Integration_Exception('Airtable API connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Build API URL
     * 
     * @param string $table_name Table name
     * @param array $params Query parameters
     * @return string API URL
     */
    protected function build_url($table_name, $params = []) {
        $url = $this->api_base . $this->base_id;
        
        if (!empty($table_name)) {
            $url .= '/' . urlencode($table_name);
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Make HTTP request to Airtable API
     * 
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Request data
     * @return array Response data
     * @throws Integration_Exception If request fails
     */
    protected function make_request($method, $url, $data = null) {
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if ($data && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('Airtable API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code >= 400) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : 'Unknown API error';
                
            throw new Integration_Exception("Airtable API error ({$status_code}): {$error_message}");
        }
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Integration_Exception('Invalid JSON response from Airtable API');
        }
        
        return $decoded;
    }
}
