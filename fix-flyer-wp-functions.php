<?php
$file = '/Users/patrickgallagher/Local Sites/tpgv12/app/public/wp-content/plugins/Happy Place Plugin/includes/graphics/class-flyer-generator-clean.php';
$content = file_get_contents($file);

// WordPress functions that need global namespace prefix
$wp_functions = [
    'wp_send_json_error',
    'wp_send_json_success',
    'get_post',
    'get_post_meta',
    'wp_get_attachment_url',
    'check_ajax_referer',
    'wp_die',
    'esc_html',
    'esc_attr',
    'esc_url'
];

foreach ($wp_functions as $func) {
    // Only replace if not already prefixed with backslash
    $content = preg_replace('/(?<!\\\\)' . preg_quote($func) . '\\s*\\(/', '\\' . $func . '(', $content);
}

file_put_contents($file, $content);
echo "Fixed WordPress function namespace issues in flyer generator\n";
