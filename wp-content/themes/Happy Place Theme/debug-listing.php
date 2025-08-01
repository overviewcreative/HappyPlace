<?php
/**
 * Debug listing issue - create this in theme root and visit it
 */

// Load WordPress
require_once('../../../wp-config.php');
require_once('../../../wp-load.php');

echo "<h1>Listing Debug Information</h1>";

// Check if listing post type exists
$post_types = get_post_types(['public' => true], 'objects');
echo "<h2>Registered Post Types:</h2>";
foreach ($post_types as $post_type) {
    echo "<li>{$post_type->name} - {$post_type->label}</li>";
}

// Check for listing posts
echo "<h2>Listing Posts:</h2>";
$listings = get_posts([
    'post_type' => 'listing',
    'posts_per_page' => 5,
    'post_status' => 'any'
]);

if (empty($listings)) {
    echo "<p>No listing posts found. Checking if post type is registered...</p>";
    if (post_type_exists('listing')) {
        echo "<p>✅ Listing post type IS registered</p>";
    } else {
        echo "<p>❌ Listing post type is NOT registered</p>";
    }
} else {
    foreach ($listings as $listing) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>ID:</strong> {$listing->ID}<br>";
        echo "<strong>Title:</strong> {$listing->post_title}<br>";
        echo "<strong>Status:</strong> {$listing->post_status}<br>";
        echo "<strong>URL:</strong> <a href='" . get_permalink($listing->ID) . "'>" . get_permalink($listing->ID) . "</a><br>";
        echo "</div>";
    }
}

// Check template hierarchy
echo "<h2>Template Hierarchy Check:</h2>";
$template_files = [
    'single-listing.php',
    'templates/listing/single-listing.php',
    'templates/single-listing.php'
];

foreach ($template_files as $template_file) {
    $path = get_template_directory() . '/' . $template_file;
    if (file_exists($path)) {
        echo "<p>✅ Found: {$template_file}</p>";
    } else {
        echo "<p>❌ Missing: {$template_file}</p>";
    }
}

// Check if Template_Engine is initialized
echo "<h2>Template Engine Status:</h2>";
if (class_exists('HappyPlace\\Core\\Template_Engine')) {
    echo "<p>✅ Template_Engine class exists</p>";
    try {
        $engine = HappyPlace\Core\Template_Engine::instance();
        echo "<p>✅ Template_Engine instance created</p>";
    } catch (Exception $e) {
        echo "<p>❌ Template_Engine initialization failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Template_Engine class not found</p>";
}
?>
