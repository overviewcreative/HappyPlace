<?php
/**
 * Template Debug Helper
 * Add this to test template loading
 */

// Debug: Check if template loader is working
add_action('wp_head', function() {
    if (!is_admin() && is_singular('listing') && current_user_can('manage_options')) {
        echo '<!-- HPH Debug: Template Loader Status -->' . PHP_EOL;
        echo '<!-- Template Loader Class Exists: ' . (class_exists('\HappyPlace\Core\Template_Loader') ? 'Yes' : 'No') . ' -->' . PHP_EOL;
        echo '<!-- Bridge Functions Available: ' . (function_exists('hph_get_all_listing_data') ? 'Yes' : 'No') . ' -->' . PHP_EOL;
        echo '<!-- Current Template: ' . get_page_template() . ' -->' . PHP_EOL;
        echo '<!-- Post Type: ' . get_post_type() . ' -->' . PHP_EOL;
        echo '<!-- Post ID: ' . get_the_ID() . ' -->' . PHP_EOL;
        
        if (class_exists('\HappyPlace\Core\Template_Loader')) {
            $loader = \HappyPlace\Core\Template_Loader::get_instance();
            $context = $loader->get_template_context();
            echo '<!-- Template Context: ' . json_encode($context) . ' -->' . PHP_EOL;
        }
        echo '<!-- End HPH Debug -->' . PHP_EOL;
    }
}, 1);
?>
