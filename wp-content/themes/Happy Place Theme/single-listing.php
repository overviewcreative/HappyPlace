<?php
/**
 * Simple Test Template for Listing Debug
 * 
 * This is a minimal working version to test listing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="listing-test">
    <div class="container">
        <h1>🏠 Listing Template Test</h1>
        
        <div class="listing-debug-info">
            <h2>Basic Information</h2>
            <ul>
                <li><strong>Post ID:</strong> <?php echo get_the_ID(); ?></li>
                <li><strong>Post Type:</strong> <?php echo get_post_type(); ?></li>
                <li><strong>Post Title:</strong> <?php echo get_the_title(); ?></li>
                <li><strong>Post Status:</strong> <?php echo get_post_status(); ?></li>
                <li><strong>Permalink:</strong> <?php echo get_permalink(); ?></li>
            </ul>
        </div>

        <div class="listing-content">
            <h2>Listing Content</h2>
            <?php if (have_posts()): ?>
                <?php while (have_posts()): the_post(); ?>
                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>
                    
                    <div class="post-meta">
                        <p><strong>Published:</strong> <?php echo get_the_date(); ?></p>
                        <p><strong>Modified:</strong> <?php echo get_the_modified_date(); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No content found.</p>
            <?php endif; ?>
        </div>

        <div class="template-info">
            <h2>Template Information</h2>
            <ul>
                <li><strong>Current Template File:</strong> single-listing.php (simple test version)</li>
                <li><strong>Template Directory:</strong> <?php echo get_template_directory(); ?></li>
                <li><strong>Theme Name:</strong> <?php echo wp_get_theme()->get('Name'); ?></li>
            </ul>
        </div>

        <div class="next-steps">
            <h2>✅ Success!</h2>
            <p>If you can see this page, the <code>single-listing.php</code> template is working!</p>
            <p>Next step: Enable the comprehensive template in <code>templates/listing/single-listing.php</code></p>
        </div>
    </div>
</div>

<style>
.listing-test {
    padding: 2rem 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}
.listing-debug-info,
.listing-content,
.template-info,
.next-steps {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.next-steps {
    background: #d1f2eb;
    border-color: #7dcea0;
}
ul {
    list-style: none;
    padding: 0;
}
li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}
li:last-child {
    border-bottom: none;
}
code {
    background: #f1f3f4;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}
</style>

<?php
get_footer();
?>
