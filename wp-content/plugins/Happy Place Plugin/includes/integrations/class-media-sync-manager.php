<?php
/**
 * Media Sync Manager
 * 
 * Handles intelligent two-way media sync between WordPress and Airtable
 * with deduplication, categorization, and metadata management.
 * 
 * @package HappyPlace
 * @since 5.0.0
 */

namespace HappyPlace\Integrations;

class Media_Sync_Manager {
    
    private array $supported_mime_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf'
    ];
    
    private int $max_file_size = 10485760; // 10MB in bytes
    private string $upload_path;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->upload_path = $upload_dir['basedir'] . '/airtable-sync/';
        
        // Ensure upload directory exists
        if (!file_exists($this->upload_path)) {
            wp_mkdir_p($this->upload_path);
        }
    }
    
    /**
     * Process media field from Airtable to WordPress
     */
    public function process_airtable_media(array $airtable_attachments, array $field_config, int $listing_id): array {
        $processed_files = [];
        
        foreach ($airtable_attachments as $attachment) {
            $processed_file = $this->import_airtable_attachment($attachment, $field_config, $listing_id);
            
            if ($processed_file) {
                $processed_files[] = $processed_file;
            }
        }
        
        return $processed_files;
    }
    
    /**
     * Import single attachment from Airtable
     */
    private function import_airtable_attachment(array $attachment, array $field_config, int $listing_id): ?int {
        $url = $attachment['url'] ?? '';
        $filename = $attachment['filename'] ?? '';
        $airtable_id = $attachment['id'] ?? '';
        
        if (!$url || !$filename) {
            return null;
        }
        
        // Check if we've already imported this file
        $existing_attachment = $this->find_existing_attachment($airtable_id, $listing_id);
        if ($existing_attachment) {
            return $existing_attachment;
        }
        
        // Download and import the file
        try {
            $temp_file = $this->download_file($url, $filename);
            if (!$temp_file) {
                return null;
            }
            
            // Validate file
            if (!$this->validate_file($temp_file, $field_config)) {
                unlink($temp_file);
                return null;
            }
            
            // Import to WordPress media library
            $attachment_id = $this->import_to_media_library($temp_file, $filename, $listing_id);
            
            if ($attachment_id) {
                // Store Airtable reference
                update_post_meta($attachment_id, '_airtable_attachment_id', $airtable_id);
                update_post_meta($attachment_id, '_sync_source', 'airtable');
                update_post_meta($attachment_id, '_sync_date', current_time('c'));
                
                // Store original Airtable metadata
                if (isset($attachment['width'])) {
                    update_post_meta($attachment_id, '_airtable_width', $attachment['width']);
                }
                if (isset($attachment['height'])) {
                    update_post_meta($attachment_id, '_airtable_height', $attachment['height']);
                }
                if (isset($attachment['size'])) {
                    update_post_meta($attachment_id, '_airtable_size', $attachment['size']);
                }
            }
            
            // Clean up temp file
            unlink($temp_file);
            
            return $attachment_id;
            
        } catch (\Exception $e) {
            error_log('HPH Media Sync Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find existing attachment by Airtable ID
     */
    private function find_existing_attachment(string $airtable_id, int $listing_id): ?int {
        $query = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => '_airtable_attachment_id',
                    'value' => $airtable_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if (!empty($query->posts)) {
            return $query->posts[0]->ID;
        }
        
        return null;
    }
    
    /**
     * Download file from Airtable
     */
    private function download_file(string $url, string $filename): ?string {
        $temp_file = $this->upload_path . 'temp_' . uniqid() . '_' . sanitize_file_name($filename);
        
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'stream' => true,
            'filename' => $temp_file
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }
        
        return $temp_file;
    }
    
    /**
     * Validate downloaded file
     */
    private function validate_file(string $file_path, array $field_config): bool {
        // Check file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }
        
        // Check file size
        $file_size = filesize($file_path);
        if ($file_size > $this->max_file_size) {
            error_log("HPH Media Sync: File too large: {$file_size} bytes");
            return false;
        }
        
        // Check MIME type
        $mime_type = wp_check_filetype($file_path)['type'];
        if (!in_array($mime_type, array_keys($this->supported_mime_types))) {
            error_log("HPH Media Sync: Unsupported MIME type: {$mime_type}");
            return false;
        }
        
        // Additional validation for images
        if (strpos($mime_type, 'image/') === 0) {
            $image_info = getimagesize($file_path);
            if (!$image_info) {
                error_log("HPH Media Sync: Invalid image file");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Import file to WordPress media library
     */
    private function import_to_media_library(string $file_path, string $filename, int $listing_id): ?int {
        // Prepare file array for wp_handle_sideload
        $file_array = [
            'name' => sanitize_file_name($filename),
            'tmp_name' => $file_path,
            'error' => 0,
            'size' => filesize($file_path)
        ];
        
        // Import the file
        $result = wp_handle_sideload($file_array, [
            'test_form' => false,
            'test_upload' => true
        ]);
        
        if (isset($result['error'])) {
            error_log('HPH Media Sync Import Error: ' . $result['error']);
            return null;
        }
        
        // Create attachment post
        $attachment_data = [
            'post_title' => pathinfo($filename, PATHINFO_FILENAME),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $listing_id,
            'post_mime_type' => $result['type']
        ];
        
        $attachment_id = wp_insert_attachment($attachment_data, $result['file'], $listing_id);
        
        if ($attachment_id && !is_wp_error($attachment_id)) {
            // Generate attachment metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $result['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);
            
            return $attachment_id;
        }
        
        return null;
    }
    
    /**
     * Prepare WordPress media for upload to Airtable
     */
    public function prepare_wp_media_for_airtable($wp_media, array $field_config): array {
        if (empty($wp_media)) {
            return [];
        }
        
        $airtable_attachments = [];
        
        // Handle single vs multiple attachments
        $media_items = is_array($wp_media) ? $wp_media : [$wp_media];
        
        foreach ($media_items as $media_item) {
            $attachment_id = is_array($media_item) ? ($media_item['ID'] ?? null) : $media_item;
            
            if (!$attachment_id) {
                continue;
            }
            
            $attachment_data = $this->prepare_single_attachment($attachment_id);
            if ($attachment_data) {
                $airtable_attachments[] = $attachment_data;
            }
        }
        
        return $airtable_attachments;
    }
    
    /**
     * Prepare single attachment for Airtable
     */
    private function prepare_single_attachment(int $attachment_id): ?array {
        $file_url = wp_get_attachment_url($attachment_id);
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_url || !$file_path || !file_exists($file_path)) {
            return null;
        }
        
        $filename = basename($file_path);
        
        // Check if already synced to Airtable
        $airtable_id = get_post_meta($attachment_id, '_airtable_attachment_id', true);
        
        $attachment_data = [
            'url' => $file_url,
            'filename' => $filename
        ];
        
        // Include Airtable ID if it exists (for updates)
        if ($airtable_id) {
            $attachment_data['id'] = $airtable_id;
        }
        
        // Add metadata if available
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata)) {
            if (isset($metadata['width'])) {
                $attachment_data['width'] = $metadata['width'];
            }
            if (isset($metadata['height'])) {
                $attachment_data['height'] = $metadata['height'];
            }
            if (isset($metadata['filesize'])) {
                $attachment_data['size'] = $metadata['filesize'];
            }
        }
        
        return $attachment_data;
    }
    
    /**
     * Clean up orphaned media files
     */
    public function cleanup_orphaned_media(): array {
        $cleanup_stats = [
            'files_checked' => 0,
            'files_removed' => 0,
            'space_freed' => 0
        ];
        
        // Find attachments with Airtable sync metadata but no parent listing
        $orphaned_query = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => '_sync_source',
                    'value' => 'airtable',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        foreach ($orphaned_query->posts as $attachment) {
            $cleanup_stats['files_checked']++;
            
            $parent_id = $attachment->post_parent;
            
            // Check if parent listing still exists
            if (!$parent_id || get_post_status($parent_id) === false) {
                $file_path = get_attached_file($attachment->ID);
                $file_size = file_exists($file_path) ? filesize($file_path) : 0;
                
                // Delete the attachment
                wp_delete_attachment($attachment->ID, true);
                
                $cleanup_stats['files_removed']++;
                $cleanup_stats['space_freed'] += $file_size;
            }
        }
        
        return $cleanup_stats;
    }
    
    /**
     * Get media sync statistics
     */
    public function get_sync_statistics(): array {
        global $wpdb;
        
        // Count synced attachments
        $synced_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_sync_source' 
            AND pm.meta_value = 'airtable'
            AND p.post_type = 'attachment'
        ");
        
        // Calculate total file size
        $total_size = $wpdb->get_var("
            SELECT SUM(pm2.meta_value)
            FROM {$wpdb->postmeta} pm1
            JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '_sync_source' 
            AND pm1.meta_value = 'airtable'
            AND pm2.meta_key = '_airtable_size'
        ");
        
        return [
            'synced_files' => (int) $synced_count,
            'total_size_bytes' => (int) $total_size,
            'total_size_mb' => round((int) $total_size / 1048576, 2)
        ];
    }
}
