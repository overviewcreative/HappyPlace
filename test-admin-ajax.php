<?php
/**
 * Simple test script to verify admin AJAX functionality
 * Run this from the browser: http://your-site.local/test-admin-ajax.php
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Ensure user is logged in
if (!is_user_logged_in()) {
    die('You must be logged in to test admin AJAX');
}

// Test the dashboard quick stats endpoint
$nonce = wp_create_nonce('hph_ajax_nonce');

echo "<h1>Testing Admin AJAX Endpoints</h1>";

// Test 1: Check if action is registered
echo "<h2>Test 1: Action Registration Check</h2>";
if (has_action('wp_ajax_hph_dashboard_quick_stats')) {
    echo "✅ Action 'wp_ajax_hph_dashboard_quick_stats' is registered<br>";
} else {
    echo "❌ Action 'wp_ajax_hph_dashboard_quick_stats' is NOT registered<br>";
}

// Test 2: Check nonce
echo "<h2>Test 2: Nonce Generation</h2>";
echo "Generated nonce: " . $nonce . "<br>";

// Test 3: JavaScript configuration test
echo "<h2>Test 3: JavaScript Test</h2>";
echo '<div id="ajax-test-result">Click the button to test AJAX</div>';
echo '<button onclick="testAjax()">Test AJAX Connection</button>';

// Add JavaScript test
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testAjax() {
    $('#ajax-test-result').html('Testing...');
    
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'hph_dashboard_quick_stats',
            nonce: '<?php echo $nonce; ?>'
        },
        success: function(response) {
            console.log('Response:', response);
            $('#ajax-test-result').html('✅ AJAX Success: ' + JSON.stringify(response));
        },
        error: function(xhr, status, error) {
            console.error('Error:', error, xhr.responseText);
            $('#ajax-test-result').html('❌ AJAX Error: ' + error + '<br>' + xhr.responseText);
        }
    });
}
</script>

<?php
// Test 4: Check if Happy Place plugin is active
echo "<h2>Test 4: Plugin Status</h2>";
if (is_plugin_active('Happy Place Plugin/happy-place.php')) {
    echo "✅ Happy Place Plugin is active<br>";
} else {
    echo "❌ Happy Place Plugin is NOT active<br>";
}

// Test 5: Check if AJAX coordinator is loaded
echo "<h2>Test 5: AJAX Coordinator</h2>";
if (class_exists('HappyPlace\\Api\\Ajax\\Ajax_Coordinator')) {
    echo "✅ AJAX Coordinator class exists<br>";
} else {
    echo "❌ AJAX Coordinator class does NOT exist<br>";
}
