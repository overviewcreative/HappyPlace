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
        // Production PDF generation using WordPress-compatible methods
        // This integrates with the existing Canvas-based flyer system
        
        $listing = $flyer_data['listing'];
        $template = $flyer_data['template'] ?? 'default';
        
        // Check if PDF library is available
        if (class_exists('TCPDF')) {
            return $this->generate_tcpdf($flyer_data);
        } elseif (class_exists('Dompdf\Dompdf')) {
            return $this->generate_dompdf($flyer_data);
        } else {
            // Fallback: Generate HTML version for browser printing
            return $this->generate_html_flyer($flyer_data);
        }
    }
    
    private function generate_tcpdf(array $flyer_data): string {
        // TCPDF implementation would go here
        $listing = $flyer_data['listing'];
        
        // For now, return a basic PDF structure
        return $this->create_basic_pdf($listing);
    }
    
    private function generate_dompdf(array $flyer_data): string {
        // DOMPDF implementation would go here
        $listing = $flyer_data['listing'];
        
        return $this->create_basic_pdf($listing);
    }
    
    private function generate_html_flyer(array $flyer_data): string {
        // Generate HTML version that can be printed to PDF by browser
        $listing = $flyer_data['listing'];
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>Property Flyer - ' . esc_html($listing['address']) . '</title>
            <style>
                @page { size: A4; margin: 20mm; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                .flyer-container { width: 100%; max-width: 210mm; margin: 0 auto; }
                .header { text-align: center; padding: 20px 0; }
                .property-image { width: 100%; max-height: 200px; object-fit: cover; }
                .property-details { padding: 20px; }
                .price { font-size: 24px; font-weight: bold; color: #2c5aa0; }
                .address { font-size: 18px; margin: 10px 0; }
                .features { display: flex; justify-content: space-around; margin: 20px 0; }
                .qr-code { text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="flyer-container">
                <div class="header">
                    <h1>Property for Sale</h1>
                </div>
                <div class="property-details">
                    <div class="price">$' . number_format($listing['price'] ?? 0) . '</div>
                    <div class="address">' . esc_html($listing['address']) . '</div>
                    <div class="features">
                        <span>' . ($listing['bedrooms'] ?? 0) . ' Beds</span>
                        <span>' . ($listing['bathrooms'] ?? 0) . ' Baths</span>
                        <span>' . number_format($listing['sqft'] ?? 0) . ' Sq Ft</span>
                    </div>
                    <div class="qr-code">
                        <img src="' . $this->get_qr_code_url($listing) . '" alt="QR Code" style="width: 100px; height: 100px;" />
                        <p>Scan for more details</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function create_basic_pdf(array $listing): string {
        // Enhanced PDF structure with listing data
        $content = sprintf(
            "Property Flyer\n\n%s\n\nPrice: $%s\nBedrooms: %d\nBathrooms: %d\nSquare Feet: %s\n\nFor more information, visit our website.",
            $listing['address'] ?? 'Address not available',
            number_format($listing['price'] ?? 0),
            $listing['bedrooms'] ?? 0,
            $listing['bathrooms'] ?? 0,
            number_format($listing['sqft'] ?? 0)
        );
        
        return "%PDF-1.4\n" .
               "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n" .
               "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n" .
               "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n" .
               "/Contents 4 0 R\n>>\nendobj\n" .
               "4 0 obj\n<<\n/Length " . strlen($content) . "\n>>\nstream\n" .
               "BT\n/F1 12 Tf\n72 720 Td\n(" . $content . ") Tj\nET\n" .
               "endstream\nendobj\n" .
               "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n" .
               "0000000058 00000 n \n0000000115 00000 n \n0000000204 00000 n \n" .
               "trailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n350\n%%EOF";
    }
    
    private function get_qr_code_url(array $listing): string {
        $listing_url = home_url('/listing/' . ($listing['slug'] ?? $listing['id'] ?? ''));
        return 'https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=' . urlencode($listing_url);
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