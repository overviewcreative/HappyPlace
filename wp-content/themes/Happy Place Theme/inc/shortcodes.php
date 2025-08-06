<?php
/**
 * Form shortcodes for Happy Place Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register form shortcodes
 */
function hph_register_form_shortcodes() {
    $forms = [
        'agent',
        'community',
        'city',
        'open-house',
        'transaction',
        'client',
        'listing'
    ];

    foreach ($forms as $form) {
        add_shortcode("hph_{$form}_form", "hph_render_{$form}_form");
    }
}
add_action('init', 'hph_register_form_shortcodes');

/**
 * Render agent form
 */
function hph_render_agent_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'agent');
    return ob_get_clean();
}

/**
 * Render community form
 */
function hph_render_community_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'community');
    return ob_get_clean();
}

/**
 * Render city form
 */
function hph_render_city_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'city');
    return ob_get_clean();
}

/**
 * Render open house form
 */
function hph_render_open_house_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'open-house');
    return ob_get_clean();
}

/**
 * Render transaction form
 */
function hph_render_transaction_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'transaction');
    return ob_get_clean();
}

/**
 * Render client form
 */
function hph_render_client_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'client');
    return ob_get_clean();
}

/**
 * Render listing form
 */
function hph_render_listing_form() {
    ob_start();
    get_template_part('template-parts/forms/submit', 'listing');
    return ob_get_clean();
}
