<?php
/**
 * Assets Configuration
 */

return [
    'css' => [
        'main' => [
            'file' => 'main.css',
            'deps' => [],
            'version' => '1.0.0',
        ],
        'admin' => [
            'file' => 'admin.css',
            'deps' => [],
            'version' => '1.0.0',
        ],
    ],
    'js' => [
        'main' => [
            'file' => 'main.js',
            'deps' => ['jquery'],
            'version' => '1.0.0',
            'in_footer' => true,
        ],
        'admin' => [
            'file' => 'admin.js',
            'deps' => ['jquery'],
            'version' => '1.0.0',
            'in_footer' => true,
        ],
    ],
];
