<?php
/**
 * Flyer Generator Handler
 * 
 * Handles the generation of marketing flyers for listings.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Handlers
 */

namespace HappyPlace\Dashboard\Handlers;

if (!defined('ABSPATH')) {
    exit;
}

class Flyer_Generator_Handler {
    
    /**
     * Initialize handler
     */
    public function init(): void {
        // Hook into marketing material generation
        add_action('hph_generate_flyer', [$this, 'generate_flyer'], 10, 2);
    }
    
    /**
     * Generate marketing flyer
     *
     * @param array $form_data Form submission data
     * @return array|WP_Error Generation result
     */
    public function generate(array $form_data) {
        $listing_id = intval($form_data['listing_id'] ?? 0);
        $template = sanitize_text_field($form_data['template'] ?? 'modern');
        $features = array_map('sanitize_text_field', $form_data['features'] ?? []);
        $photos = array_map('intval', $form_data['photos'] ?? []);
        
        if (!$listing_id) {
            return new \WP_Error('missing_listing', __('Listing ID is required', 'happy-place'));
        }
        
        // Get listing data
        $listing_data = $this->get_listing_data($listing_id);
        if (!$listing_data) {
            return new \WP_Error('listing_not_found', __('Listing not found', 'happy-place'));
        }
        
        // Generate flyer
        $flyer_data = [
            'listing' => $listing_data,
            'template' => $template,
            'features' => $features,
            'photos' => $photos,
            'headline' => sanitize_text_field($form_data['headline'] ?? ''),
            'description' => sanitize_textarea_field($form_data['description'] ?? ''),
            'call_to_action' => sanitize_text_field($form_data['call_to_action'] ?? ''),
            'include_agent_photo' => !empty($form_data['include_agent_photo']),
            'include_office_logo' => !empty($form_data['include_office_logo'])
        ];
        
        // Create flyer file
        $flyer_result = $this->create_flyer($flyer_data);
        
        if (is_wp_error($flyer_result)) {
            return $flyer_result;
        }
        
        // Log activity
        $this->log_flyer_generation($listing_id, $flyer_result['file_path']);
        
        return [
            'success' => true,
            'message' => __('Flyer generated successfully!', 'happy-place'),
            'download_url' => $flyer_result['download_url'],
            'file_path' => $flyer_result['file_path'],
            'preview_url' => $flyer_result['preview_url'] ?? null
        ];
    }
    
    /**
     * Create flyer file
     *
     * @param array $flyer_data Flyer data
     * @return array|WP_Error File creation result
     */
    private function create_flyer(array $flyer_data) {
        // For now, create a placeholder PDF
        // In a real implementation, this would use a PDF generation library
        // like TCPDF, DOMPDF, or call an external service
        
        $upload_dir = wp_upload_dir();
        $flyer_dir = $upload_dir['basedir'] . '/flyers';
        
        // Create flyers directory if it doesn't exist
        if (!is_dir($flyer_dir)) {
            wp_mkdir_p($flyer_dir);
        }
        
        $filename = 'flyer-' . $flyer_data['listing']['id'] . '-' . time() . '.pdf';
        $file_path = $flyer_dir . '/' . $filename;
        $download_url = $upload_dir['baseurl'] . '/flyers/' . $filename;
        
        // Create placeholder PDF content
        $pdf_content = $this->generate_pdf_placeholder($flyer_data);
        
        // Write file
        $bytes_written = file_put_contents($file_path, $pdf_content);
        
        if ($bytes_written === false) {
            return new \WP_Error('file_creation_failed', __('Failed to create flyer file', 'happy-place'));
        }
        
        return [
            'file_path' => $file_path,
            'download_url' => $download_url,
            'filename' => $filename
        ];
    }
    
    /**
     * Generate PDF placeholder content
     *
     * @param array $flyer_data Flyer data
     * @return string PDF content placeholder
     */
    private function generate_pdf_placeholder(array $flyer_data): string {
        // This is a placeholder. In a real implementation, you would:
        // 1. Use a PDF library like TCPDF or DOMPDF
        // 2. Load the selected template
        // 3. Populate with listing data and photos
        // 4. Generate the actual PDF
        
        $listing = $flyer_data['listing'];
        
        return "%PDF-1.4\n" .
               "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n" .
               "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n" .
               "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n" .
               "/Contents 4 0 R\n>>\nendobj\n" .
               "4 0 obj\n<<\n/Length 44\n>>\nstream\n" .
               "BT\n/F1 12 Tf\n72 720 Td\n(" . $listing['address'] . ") Tj\nET\n" .
               "endstream\nendobj\n" .
               "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n" .
               "0000000058 00000 n \n0000000115 00000 n \n0000000204 00000 n \n" .
               "trailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n298\n%%EOF";
    }
    
    /**
     * Get listing data
     *
     * @param int $listing_id Listing ID
     * @return array|null Listing data
     */
    private function get_listing_data(int $listing_id): ?array {
        if (function_exists('hph_bridge_get_listing_data')) {
            return hph_bridge_get_listing_data($listing_id);
        }
        
        $post = get_post($listing_id);
        if (!$post || $post->post_type !== 'listing') {
            return null;
        }
        
        return [
            'id' => $listing_id,
            'title' => $post->post_title,
            'address' => get_post_meta($listing_id, 'listing_address', true),
            'price' => get_post_meta($listing_id, 'listing_price', true),
            'description' => $post->post_content
        ];
    }
    
    /**
     * Log flyer generation activity
     *
     * @param int $listing_id Listing ID
     * @param string $file_path Generated file path
     */
    private function log_flyer_generation(int $listing_id, string $file_path): void {
        if (function_exists('hph_log_activity')) {
            hph_log_activity([
                'type' => 'flyer_generated',
                'object_type' => 'listing',
                'object_id' => $listing_id,
                'description' => __('Marketing flyer generated', 'happy-place'),
                'metadata' => [
                    'file_path' => $file_path
                ]
            ]);
        }
    }
}