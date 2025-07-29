<?php
/**
 * Forms Localization
 * 
 * @package HappyPlace
 */

/**
 * Enqueue form scripts and localize strings
 */
function hph_enqueue_form_scripts() {
    // Open House Form
    if (hph_is_form_active('open-house')) {
        wp_enqueue_script(
            'hph-open-house-form',
            get_template_directory_uri() . '/assets/js/forms/open-house-form.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-open-house-form', 'hphLocalized', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_form_nonce'),
            'strings' => [
                'endTimeError' => __('End time must be after start time', 'happy-place'),
                'pastDateError' => __('Please select a future date', 'happy-place'),
                'invalidMaxVisitors' => __('Maximum visitors must be at least 1', 'happy-place'),
                'formError' => __('Please fix the errors and try again', 'happy-place'),
                'requiredField' => __('This field is required', 'happy-place')
            ]
        ]);
    }

    // Contact Form
    if (hph_is_form_active('contact')) {
        wp_enqueue_script(
            'hph-contact-form',
            get_template_directory_uri() . '/assets/js/forms/contact-form.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-contact-form', 'hphContactForm', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_contact_nonce'),
            'strings' => [
                'messageTooShort' => __('Please enter a message of at least 10 characters', 'happy-place'),
                'formError' => __('Please fix the errors and try again', 'happy-place'),
                'invalidEmail' => __('Please enter a valid email address', 'happy-place')
            ]
        ]);
    }

    // Showing Request Form
    if (hph_is_form_active('showing-request')) {
        wp_enqueue_script(
            'hph-showing-form',
            get_template_directory_uri() . '/assets/js/forms/showing-request-form.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-showing-form', 'hphShowingForm', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_showing_nonce'),
            'strings' => [
                'pastDateError' => __('Please select a future date', 'happy-place'),
                'noTimeSelected' => __('Please select your preferred showing date and time', 'happy-place'),
                'formError' => __('Please fix the errors and try again', 'happy-place')
            ]
        ]);
    }
}
// Form scripts are now handled by the central Asset_Loader system
// add_action('wp_enqueue_scripts', 'hph_enqueue_form_scripts');

/**
 * Check if a form is active on the current page
 */
function hph_is_form_active($form_type) {
    global $post;

    if (!$post) return false;

    // Check for shortcode
    if (has_shortcode($post->post_content, "hph_{$form_type}_form")) {
        return true;
    }

    // Check template parts
    $form_templates = [
        'open-house' => 'template-parts/forms/open-house-form.php',
        'contact' => 'template-parts/forms/contact-form-template.php',
        'showing-request' => 'template-parts/forms/showing-request-form.php'
    ];

    if (isset($form_templates[$form_type])) {
        $template = get_template_directory() . '/' . $form_templates[$form_type];
        return file_exists($template);
    }

    return false;
}
