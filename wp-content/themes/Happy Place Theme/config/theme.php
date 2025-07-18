<?php
/**
 * Theme Configuration
 */

return [
    'name' => 'Happy Place',
    'version' => '1.0.0',
    'text_domain' => 'happy-place',
    'supports' => [
        'post-thumbnails',
        'custom-logo',
        'custom-header',
        'custom-background',
        'title-tag',
        'html5',
        'customize-selective-refresh-widgets',
    ],
    'image_sizes' => [
        'listing-thumbnail' => [300, 200, true],
        'listing-medium' => [600, 400, true],
        'listing-large' => [1200, 800, true],
        'agent-profile' => [300, 300, true],
    ],
    'menus' => [
        'primary' => 'Primary Menu',
        'footer' => 'Footer Menu',
        'mobile' => 'Mobile Menu',
    ],
];
