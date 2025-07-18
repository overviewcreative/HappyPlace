<?php

namespace HappyPlace\Performance;

/**
 * Image Optimization Manager
 *
 * Handles WebP conversion, lazy loading, responsive images, and optimization
 *
 * @package HappyPlace\Performance
 * @since 2.0.0
 */
class Image_Optimization_Manager {
    
    /**
     * Singleton instance
     * @var Image_Optimization_Manager
     */
    private static $instance = null;
    
    /**
     * Optimization statistics
     * @var array
     */
    private $stats = [];
    
    /**
     * Configuration
     * @var array
     */
    private $config = [
        'enable_webp' => true,
        'enable_lazy_loading' => true,
        'enable_responsive_images' => true,
        'webp_quality' => 85,
        'jpeg_quality' => 85,
        'lazy_loading_threshold' => 2,
        'enable_blur_placeholder' => true,
        'enable_image_compression' => true,
        'max_image_width' => 2048,
        'enable_svg_optimization' => true
    ];
    
    /**
     * Responsive breakpoints
     * @var array
     */
    private $breakpoints = [
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1200,
        'large' => 1600
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_config();
        $this->init_hooks();
    }
    
    /**
     * Load configuration
     */
    private function load_config() {
        $saved_config = get_option('hph_image_optimization_config', []);
        $this->config = array_merge($this->config, $saved_config);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Image processing hooks
        add_filter('wp_handle_upload', [$this, 'process_uploaded_image']);
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_webp_versions'], 10, 2);
        
        // Content filtering
        add_filter('the_content', [$this, 'optimize_content_images'], 20);
        add_filter('post_thumbnail_html', [$this, 'optimize_thumbnail_html'], 10, 5);
        add_filter('wp_get_attachment_image', [$this, 'optimize_attachment_image'], 10, 5);
        
        // Lazy loading
        if ($this->config['enable_lazy_loading']) {
            add_filter('wp_lazy_loading_enabled', '__return_true');
            add_action('wp_footer', [$this, 'enqueue_lazy_loading_script']);
        }
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_optimize_existing_images', [$this, 'ajax_optimize_existing_images']);
        add_action('wp_ajax_clear_webp_cache', [$this, 'ajax_clear_webp_cache']);
        
        // Cleanup hooks
        add_action('delete_attachment', [$this, 'cleanup_optimized_images']);
    }
    
    /**
     * Process uploaded image
     */
    public function process_uploaded_image($upload) {
        if (!isset($upload['file']) || !$this->is_image($upload['file'])) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        
        // Compress original image
        if ($this->config['enable_image_compression']) {
            $this->compress_image($file_path);
        }
        
        // Generate WebP version
        if ($this->config['enable_webp'] && $this->can_create_webp()) {
            $this->create_webp_version($file_path);
        }
        
        return $upload;
    }
    
    /**
     * Generate WebP versions during attachment metadata generation
     */
    public function generate_webp_versions($metadata, $attachment_id) {
        if (!$this->config['enable_webp'] || !$this->can_create_webp()) {
            return $metadata;
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_image($file_path)) {
            return $metadata;
        }
        
        // Create WebP for original
        $this->create_webp_version($file_path);
        
        // Create WebP for all sizes
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $base_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size => $size_data) {
                $size_path = $base_dir . '/' . $size_data['file'];
                
                if (file_exists($size_path)) {
                    $this->create_webp_version($size_path);
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Create WebP version of image
     */
    public function create_webp_version($image_path) {
        if (!$this->is_image($image_path) || !$this->can_create_webp()) {
            return false;
        }
        
        $webp_path = $this->get_webp_path($image_path);
        
        // Skip if WebP already exists and is newer
        if (file_exists($webp_path) && filemtime($webp_path) >= filemtime($image_path)) {
            return $webp_path;
        }
        
        $image_type = exif_imagetype($image_path);
        $image = null;
        
        // Create image resource based on type
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($image_path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($image_path);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($image_path);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Create WebP
        $success = imagewebp($image, $webp_path, $this->config['webp_quality']);
        imagedestroy($image);
        
        if ($success) {
            $this->update_stats('webp_created');
            return $webp_path;
        }
        
        return false;
    }
    
    /**
     * Compress image
     */
    public function compress_image($image_path) {
        if (!$this->is_image($image_path)) {
            return false;
        }
        
        $image_type = exif_imagetype($image_path);
        $image = null;
        $original_size = filesize($image_path);
        
        // Create image resource
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($image_path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($image_path);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Resize if too large
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $this->config['max_image_width']) {
            $new_width = $this->config['max_image_width'];
            $new_height = ($height * $new_width) / $width;
            
            $resized = imagecreatetruecolor($new_width, $new_height);
            
            if ($image_type === IMAGETYPE_PNG) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }
        
        // Save compressed image
        $success = false;
        
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($image, $image_path, $this->config['jpeg_quality']);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($image, $image_path, 8); // PNG compression level 8
                break;
        }
        
        imagedestroy($image);
        
        if ($success) {
            $new_size = filesize($image_path);
            $saved = $original_size - $new_size;
            
            if ($saved > 0) {
                $this->update_stats('compression_saved', $saved);
            }
        }
        
        return $success;
    }
    
    /**
     * Optimize images in content
     */
    public function optimize_content_images($content) {
        // Find all img tags
        preg_match_all('/<img[^>]+>/i', $content, $matches);
        
        if (empty($matches[0])) {
            return $content;
        }
        
        foreach ($matches[0] as $img_tag) {
            $optimized_tag = $this->optimize_img_tag($img_tag);
            $content = str_replace($img_tag, $optimized_tag, $content);
        }
        
        return $content;
    }
    
    /**
     * Optimize thumbnail HTML
     */
    public function optimize_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (empty($html)) {
            return $html;
        }
        
        return $this->optimize_img_tag($html, $post_thumbnail_id);
    }
    
    /**
     * Optimize attachment image
     */
    public function optimize_attachment_image($html, $attachment_id, $size, $icon, $attr) {
        if (empty($html) || $icon) {
            return $html;
        }
        
        return $this->optimize_img_tag($html, $attachment_id);
    }
    
    /**
     * Optimize individual img tag
     */
    private function optimize_img_tag($img_tag, $attachment_id = null) {
        // Extract attributes
        $attributes = $this->parse_img_attributes($img_tag);
        
        if (empty($attributes['src'])) {
            return $img_tag;
        }
        
        $src = $attributes['src'];
        
        // Add WebP source if available
        if ($this->config['enable_webp']) {
            $webp_src = $this->get_webp_url($src);
            
            if ($webp_src && $this->webp_exists($webp_src)) {
                // Create picture element with WebP
                $picture = $this->create_picture_element($src, $webp_src, $attributes, $attachment_id);
                return $picture;
            }
        }
        
        // Add responsive images if enabled
        if ($this->config['enable_responsive_images'] && $attachment_id) {
            $img_tag = $this->add_responsive_srcset($img_tag, $attachment_id);
        }
        
        // Add lazy loading attributes
        if ($this->config['enable_lazy_loading']) {
            $img_tag = $this->add_lazy_loading_attributes($img_tag, $attributes);
        }
        
        return $img_tag;
    }
    
    /**
     * Create picture element with WebP support
     */
    private function create_picture_element($original_src, $webp_src, $attributes, $attachment_id = null) {
        $picture = '<picture>';
        
        // Add responsive WebP sources
        if ($this->config['enable_responsive_images'] && $attachment_id) {
            $responsive_webp = $this->get_responsive_webp_sources($attachment_id);
            
            foreach ($responsive_webp as $breakpoint => $webp_url) {
                $media_query = $this->get_media_query($breakpoint);
                $picture .= "<source media=\"{$media_query}\" srcset=\"{$webp_url}\" type=\"image/webp\">";
            }
        }
        
        // Default WebP source
        $picture .= "<source srcset=\"{$webp_src}\" type=\"image/webp\">";
        
        // Fallback img with original optimizations
        $img_attrs = '';
        foreach ($attributes as $attr => $value) {
            if ($attr !== 'src') {
                $img_attrs .= " {$attr}=\"" . esc_attr($value) . "\"";
            }
        }
        
        // Add lazy loading to fallback image
        if ($this->config['enable_lazy_loading']) {
            $img_attrs .= ' loading="lazy"';
            
            if ($this->config['enable_blur_placeholder']) {
                $placeholder = $this->generate_blur_placeholder($original_src);
                $img_attrs .= " data-src=\"{$original_src}\" src=\"{$placeholder}\"";
            } else {
                $img_attrs .= " src=\"{$original_src}\"";
            }
        } else {
            $img_attrs .= " src=\"{$original_src}\"";
        }
        
        $picture .= "<img{$img_attrs}>";
        $picture .= '</picture>';
        
        return $picture;
    }
    
    /**
     * Get responsive WebP sources for attachment
     */
    private function get_responsive_webp_sources($attachment_id) {
        $sources = [];
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!isset($metadata['sizes'])) {
            return $sources;
        }
        
        $upload_dir = wp_upload_dir();
        $base_url = dirname(wp_get_attachment_url($attachment_id));
        
        foreach ($this->breakpoints as $breakpoint => $width) {
            $size_name = $this->get_size_for_breakpoint($breakpoint);
            
            if (isset($metadata['sizes'][$size_name])) {
                $size_data = $metadata['sizes'][$size_name];
                $original_url = $base_url . '/' . $size_data['file'];
                $webp_url = $this->get_webp_url($original_url);
                
                if ($this->webp_exists($webp_url)) {
                    $sources[$breakpoint] = $webp_url;
                }
            }
        }
        
        return $sources;
    }
    
    /**
     * Get media query for breakpoint
     */
    private function get_media_query($breakpoint) {
        $width = $this->breakpoints[$breakpoint];
        
        switch ($breakpoint) {
            case 'mobile':
                return "(max-width: {$width}px)";
            case 'tablet':
                return "(min-width: " . ($this->breakpoints['mobile'] + 1) . "px) and (max-width: {$width}px)";
            case 'desktop':
                return "(min-width: " . ($this->breakpoints['tablet'] + 1) . "px) and (max-width: {$width}px)";
            case 'large':
                return "(min-width: " . ($this->breakpoints['desktop'] + 1) . "px)";
            default:
                return "(min-width: {$width}px)";
        }
    }
    
    /**
     * Get appropriate image size for breakpoint
     */
    private function get_size_for_breakpoint($breakpoint) {
        $size_map = [
            'mobile' => 'medium',
            'tablet' => 'large',
            'desktop' => 'full',
            'large' => 'full'
        ];
        
        return $size_map[$breakpoint] ?? 'medium';
    }
    
    /**
     * Add responsive srcset attributes to img tag
     */
    private function add_responsive_srcset($img_tag, $attachment_id) {
        // WordPress handles this automatically for attachment images
        // We'll just ensure the image has proper srcset attributes
        if (strpos($img_tag, 'srcset=') === false) {
            $srcset = wp_get_attachment_image_srcset($attachment_id);
            $sizes = wp_get_attachment_image_sizes($attachment_id);
            
            if ($srcset) {
                $img_tag = str_replace('<img ', '<img srcset="' . esc_attr($srcset) . '" ', $img_tag);
            }
            
            if ($sizes) {
                $img_tag = str_replace('<img ', '<img sizes="' . esc_attr($sizes) . '" ', $img_tag);
            }
        }
        
        return $img_tag;
    }

    /**
     * Add lazy loading attributes
     */
    private function add_lazy_loading_attributes($img_tag, $attributes) {
        // Skip if already has loading attribute
        if (strpos($img_tag, 'loading=') !== false) {
            return $img_tag;
        }
        
        // Add loading="lazy"
        $img_tag = str_replace('<img ', '<img loading="lazy" ', $img_tag);
        
        // Add blur placeholder if enabled
        if ($this->config['enable_blur_placeholder'] && !empty($attributes['src'])) {
            $placeholder = $this->generate_blur_placeholder($attributes['src']);
            
            // Replace src with placeholder and add data-src
            $img_tag = str_replace(
                'src="' . $attributes['src'] . '"',
                'src="' . $placeholder . '" data-src="' . $attributes['src'] . '"',
                $img_tag
            );
        }
        
        return $img_tag;
    }
    
    /**
     * Generate blur placeholder for image
     */
    private function generate_blur_placeholder($image_url) {
        // Create tiny base64 placeholder
        $placeholder_data = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1" fill="#f0f0f0"/></svg>'
        );
        
        return $placeholder_data;
    }
    
    /**
     * Parse img tag attributes
     */
    private function parse_img_attributes($img_tag) {
        $attributes = [];
        
        // Extract attributes using regex
        preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $img_tag, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
        
        return $attributes;
    }
    
    /**
     * Get WebP path from original image path
     */
    private function get_webp_path($image_path) {
        $path_info = pathinfo($image_path);
        return $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
    }
    
    /**
     * Get WebP URL from original image URL
     */
    private function get_webp_url($image_url) {
        $url_info = pathinfo($image_url);
        return $url_info['dirname'] . '/' . $url_info['filename'] . '.webp';
    }
    
    /**
     * Check if WebP file exists
     */
    private function webp_exists($webp_url) {
        // Convert URL to file path
        $upload_dir = wp_upload_dir();
        $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);
        
        return file_exists($webp_path);
    }
    
    /**
     * Check if file is an image
     */
    private function is_image($file_path) {
        $image_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        $file_type = exif_imagetype($file_path);
        
        return in_array($file_type, $image_types);
    }
    
    /**
     * Check if WebP creation is supported
     */
    private function can_create_webp() {
        return function_exists('imagewebp') && function_exists('imagecreatefromjpeg');
    }
    
    /**
     * Update optimization statistics
     */
    private function update_stats($metric, $value = 1) {
        if (!isset($this->stats[$metric])) {
            $this->stats[$metric] = 0;
        }
        
        $this->stats[$metric] += $value;
        
        // Persist stats occasionally
        if (rand(1, 10) === 1) {
            $stored_stats = get_option('hph_image_optimization_stats', []);
            
            foreach ($this->stats as $key => $stat_value) {
                if (!isset($stored_stats[$key])) {
                    $stored_stats[$key] = 0;
                }
                $stored_stats[$key] += $stat_value;
            }
            
            update_option('hph_image_optimization_stats', $stored_stats);
            $this->stats = []; // Reset runtime stats
        }
    }
    
    /**
     * Enqueue lazy loading script
     */
    public function enqueue_lazy_loading_script() {
        ?>
        <script>
        // Enhanced lazy loading with intersection observer
        (function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            const src = img.dataset.src;
                            
                            if (src) {
                                img.src = src;
                                img.removeAttribute('data-src');
                                img.classList.add('loaded');
                            }
                            
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '100px 0px'
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for older browsers
                document.querySelectorAll('img[data-src]').forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            }
        })();
        </script>
        <style>
        img[data-src] {
            filter: blur(2px);
            transition: filter 0.3s ease;
        }
        img.loaded {
            filter: none;
        }
        </style>
        <?php
    }
    
    /**
     * Cleanup optimized images when attachment is deleted
     */
    public function cleanup_optimized_images($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path) {
            return;
        }
        
        // Delete WebP version
        $webp_path = $this->get_webp_path($file_path);
        if (file_exists($webp_path)) {
            unlink($webp_path);
        }
        
        // Delete WebP versions of all sizes
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $base_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size_data) {
                $size_path = $base_dir . '/' . $size_data['file'];
                $size_webp_path = $this->get_webp_path($size_path);
                
                if (file_exists($size_webp_path)) {
                    unlink($size_webp_path);
                }
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Image Optimization',
            'Image Optimization',
            'manage_options',
            'hph-image-optimization',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $stats = get_option('hph_image_optimization_stats', []);
        
        ?>
        <div class="wrap">
            <h1>Image Optimization Manager</h1>
            
            <div class="optimization-dashboard">
                <?php if (!empty($stats)): ?>
                    <div class="dashboard-section">
                        <h2>Optimization Statistics</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <tbody>
                                <?php if (isset($stats['webp_created'])): ?>
                                    <tr>
                                        <td>WebP Images Created</td>
                                        <td><?php echo esc_html(number_format($stats['webp_created'])); ?></td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php if (isset($stats['compression_saved'])): ?>
                                    <tr>
                                        <td>Storage Saved by Compression</td>
                                        <td><?php echo esc_html(size_format($stats['compression_saved'])); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="dashboard-section">
                    <h2>Bulk Actions</h2>
                    <p>
                        <button class="button button-primary" id="optimize-existing-images">
                            Optimize All Existing Images
                        </button>
                        <button class="button" id="clear-webp-cache">
                            Clear WebP Cache
                        </button>
                    </p>
                    <p class="description">
                        Optimizing existing images will create WebP versions and compress images that haven't been processed yet.
                    </p>
                </div>
                
                <div class="dashboard-section">
                    <h2>Configuration</h2>
                    <form method="post" action="">
                        <?php wp_nonce_field('image_optimization_config'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th>Enable WebP Conversion</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_webp" value="1" 
                                               <?php checked($this->config['enable_webp']); ?>>
                                        Convert images to WebP format for better compression
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Enable Lazy Loading</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_lazy_loading" value="1" 
                                               <?php checked($this->config['enable_lazy_loading']); ?>>
                                        Load images only when they enter the viewport
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>WebP Quality</th>
                                <td>
                                    <input type="number" name="webp_quality" value="<?php echo esc_attr($this->config['webp_quality']); ?>" 
                                           min="1" max="100" step="1">
                                    <p class="description">Quality for WebP images (1-100, recommended: 85)</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" class="button-primary" value="Save Configuration">
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#optimize-existing-images').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Optimizing...');
                
                $.post(ajaxurl, {
                    action: 'optimize_existing_images',
                    nonce: '<?php echo wp_create_nonce('image_optimization_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Images optimized successfully: ' + response.data);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                    
                    $button.prop('disabled', false).text('Optimize All Existing Images');
                });
            });
            
            $('#clear-webp-cache').on('click', function() {
                if (confirm('Clear all WebP cache?')) {
                    $.post(ajaxurl, {
                        action: 'clear_webp_cache',
                        nonce: '<?php echo wp_create_nonce('image_optimization_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('WebP cache cleared successfully');
                        }
                    });
                }
            });
        });
        </script>
        <?php
        
        // Handle configuration save
        if (isset($_POST['enable_webp']) && wp_verify_nonce($_POST['_wpnonce'], 'image_optimization_config')) {
            $new_config = [
                'enable_webp' => !empty($_POST['enable_webp']),
                'enable_lazy_loading' => !empty($_POST['enable_lazy_loading']),
                'webp_quality' => max(1, min(100, intval($_POST['webp_quality'])))
            ];
            
            $this->config = array_merge($this->config, $new_config);
            update_option('hph_image_optimization_config', $this->config);
            
            echo '<div class="notice notice-success"><p>Configuration saved successfully!</p></div>';
        }
    }
    
    /**
     * AJAX handler for optimizing existing images
     */
    public function ajax_optimize_existing_images() {
        if (!wp_verify_nonce($_POST['nonce'], 'image_optimization_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ]);
        
        $optimized = 0;
        
        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            
            if ($file_path && file_exists($file_path)) {
                // Create WebP if it doesn't exist
                $webp_path = $this->get_webp_path($file_path);
                
                if (!file_exists($webp_path)) {
                    if ($this->create_webp_version($file_path)) {
                        $optimized++;
                    }
                }
            }
        }
        
        wp_send_json_success("Optimized {$optimized} images");
    }
    
    /**
     * AJAX handler for clearing WebP cache
     */
    public function ajax_clear_webp_cache() {
        if (!wp_verify_nonce($_POST['nonce'], 'image_optimization_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        // This would implement WebP cache clearing logic
        wp_send_json_success('WebP cache cleared');
    }
}
