<?php
$file = '/Users/patrickgallagher/Local Sites/tpgv12/app/public/wp-content/plugins/Happy Place Plugin/includes/graphics/class-flyer-generator-clean.php';
$content = file_get_contents($file);

// WordPress functions that need global namespace prefix
$wp_functions = [
    'add_action',
    'wp_enqueue_script',
    'wp_enqueue_style', 
    'wp_localize_script',
    'admin_url',
    'plugin_dir_url',
    'wp_create_nonce'
];

foreach ($wp_functions as $func) {
    // Only replace if not already prefixed with backslash
    $content = preg_replace('/(?<!\\\\)' . preg_quote($func) . '\\s*\\(/', '\\' . $func . '(', $content);
}

file_put_contents($file, $content);
echo "Fixed WordPress function namespace issues in flyer generator\n";
