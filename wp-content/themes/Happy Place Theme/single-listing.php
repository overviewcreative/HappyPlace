<?php
/**
 * Single Listing Template - Root Level Fallback
 * This file ensures single listings display correctly
 * 
 * @package HappyPlace
 */

get_header(); ?>

<main class="main-content">
    <div class="container">
        <?php
        while (have_posts()) : the_post();
            echo '<div class="debug-info" style="background: #f0f0f0; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">';
            echo '<h3>Debug Information</h3>';
            echo '<p><strong>Post ID:</strong> ' . get_the_ID() . '</p>';
            echo '<p><strong>Post Type:</strong> ' . get_post_type() . '</p>';
            echo '<p><strong>Post Title:</strong> ' . get_the_title() . '</p>';
            echo '<p><strong>Post Status:</strong> ' . get_post_status() . '</p>';
            echo '<p><strong>Template File:</strong> single-listing.php (root level)</p>';
            echo '</div>';
            
            // Try to include the actual template
            $template_path = get_template_directory() . '/templates/listing/single-listing.php';
            if (file_exists($template_path)) {
                echo '<div class="listing-content">';
                include $template_path;
                echo '</div>';
            } else {
                // Fallback content
                echo '<article class="single-listing-fallback">';
                echo '<header class="entry-header">';
                echo '<h1 class="entry-title">' . get_the_title() . '</h1>';
                echo '</header>';
                
                echo '<div class="entry-content">';
                the_content();
                
                // Show custom fields if any
                $custom_fields = get_post_meta(get_the_ID());
                if (!empty($custom_fields)) {
                    echo '<h3>Property Details</h3>';
                    echo '<ul>';
                    foreach ($custom_fields as $key => $value) {
                        if (!str_starts_with($key, '_')) { // Skip WordPress internal fields
                            echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html(is_array($value) ? implode(', ', $value) : $value[0]) . '</li>';
                        }
                    }
                    echo '</ul>';
                }
                
                echo '</div>';
                echo '</article>';
            }
        endwhile;
        ?>
    </div>
</main>

<?php get_footer(); ?>
