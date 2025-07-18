<?php
namespace HappyPlace\Theme\Forms;

/**
 * Form Handler Class
 * Handles form submission, validation, and processing
 */
class Form_Handler {
    private static array $messages = [];
    private static array $errors = [];
    private static array $fields = [];

    /**
     * Initialize form handling
     */
    public static function init(): void {
        add_action('wp_ajax_happy_place_form_submit', [self::class, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_happy_place_form_submit', [self::class, 'handle_ajax_submission']);
        add_action('init', [self::class, 'register_validation_rules']);
    }

    /**
     * Register default validation rules
     */
    public static function register_validation_rules(): void {
        self::add_validation_rule('required', function($value) {
            return !empty($value);
        }, __('This field is required.', 'happy-place-theme'));

        self::add_validation_rule('email', function($value) {
            return empty($value) || is_email($value);
        }, __('Please enter a valid email address.', 'happy-place-theme'));

        self::add_validation_rule('phone', function($value) {
            return empty($value) || preg_match('/^[\d\s-\(\)]+$/', $value);
        }, __('Please enter a valid phone number.', 'happy-place-theme'));

        self::add_validation_rule('numeric', function($value) {
            return empty($value) || is_numeric($value);
        }, __('Please enter a valid number.', 'happy-place-theme'));
    }

    /**
     * Add a custom validation rule
     */
    public static function add_validation_rule(string $name, callable $callback, string $message): void {
        self::$fields[$name] = [
            'callback' => $callback,
            'message' => $message
        ];
    }

    /**
     * Handle AJAX form submission
     */
    public static function handle_ajax_submission(): void {
        check_ajax_referer('happy_place_form_nonce', 'nonce');

        $form_data = $_POST['form_data'] ?? [];
        $form_type = $_POST['form_type'] ?? '';

        if (empty($form_type)) {
            wp_send_json_error(__('Invalid form submission.', 'happy-place-theme'));
        }

        // Validate form data
        $validation = self::validate_form($form_data, $form_type);
        if (!empty($validation['errors'])) {
            wp_send_json_error([
                'message' => __('Please check the form for errors.', 'happy-place-theme'),
                'errors' => $validation['errors']
            ]);
        }

        // Process form submission
        $result = self::process_form($form_data, $form_type);
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message']
            ]);
        }
    }

    /**
     * Validate form data
     */
    private static function validate_form(array $data, string $form_type): array {
        $errors = [];
        $fields = self::get_form_fields($form_type);

        foreach ($fields as $field => $rules) {
            $value = $data[$field] ?? '';
            foreach ($rules as $rule => $message) {
                if (!self::$fields[$rule]['callback']($value)) {
                    $errors[$field] = $message;
                    break;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Process form submission
     */
    private static function process_form(array $data, string $form_type): array {
        switch ($form_type) {
            case 'contact':
                return self::process_contact_form($data);
            case 'listing_inquiry':
                return self::process_listing_inquiry($data);
            case 'agent_contact':
                return self::process_agent_contact($data);
            default:
                return [
                    'success' => false,
                    'message' => __('Invalid form type.', 'happy-place-theme')
                ];
        }
    }

    /**
     * Get form field validation rules
     */
    private static function get_form_fields(string $form_type): array {
        $fields = [];

        switch ($form_type) {
            case 'contact':
                $fields = [
                    'name' => ['required' => __('Please enter your name.', 'happy-place-theme')],
                    'email' => [
                        'required' => __('Please enter your email address.', 'happy-place-theme'),
                        'email' => __('Please enter a valid email address.', 'happy-place-theme')
                    ],
                    'message' => ['required' => __('Please enter your message.', 'happy-place-theme')]
                ];
                break;

            case 'listing_inquiry':
                $fields = [
                    'name' => ['required' => __('Please enter your name.', 'happy-place-theme')],
                    'email' => [
                        'required' => __('Please enter your email address.', 'happy-place-theme'),
                        'email' => __('Please enter a valid email address.', 'happy-place-theme')
                    ],
                    'phone' => ['phone' => __('Please enter a valid phone number.', 'happy-place-theme')],
                    'message' => ['required' => __('Please enter your message.', 'happy-place-theme')],
                    'listing_id' => ['required' => __('Invalid listing selection.', 'happy-place-theme')]
                ];
                break;

            case 'agent_contact':
                $fields = [
                    'name' => ['required' => __('Please enter your name.', 'happy-place-theme')],
                    'email' => [
                        'required' => __('Please enter your email address.', 'happy-place-theme'),
                        'email' => __('Please enter a valid email address.', 'happy-place-theme')
                    ],
                    'phone' => ['phone' => __('Please enter a valid phone number.', 'happy-place-theme')],
                    'message' => ['required' => __('Please enter your message.', 'happy-place-theme')],
                    'agent_id' => ['required' => __('Invalid agent selection.', 'happy-place-theme')]
                ];
                break;
        }

        return $fields;
    }

    /**
     * Process contact form submission
     */
    private static function process_contact_form(array $data): array {
        $to = get_option('admin_email');
        $subject = sprintf(
            __('[%s] New Contact Form Submission', 'happy-place-theme'),
            get_bloginfo('name')
        );
        
        $message = sprintf(
            "Name: %s\nEmail: %s\nMessage: %s",
            sanitize_text_field($data['name']),
            sanitize_email($data['email']),
            wp_kses_post($data['message'])
        );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', sanitize_text_field($data['name']), sanitize_email($data['email']))
        ];

        $sent = wp_mail($to, $subject, $message, $headers);

        return [
            'success' => $sent,
            'message' => $sent 
                ? __('Thank you for your message. We will get back to you soon.', 'happy-place-theme')
                : __('Sorry, there was a problem sending your message. Please try again later.', 'happy-place-theme')
        ];
    }

    /**
     * Process listing inquiry form submission
     */
    private static function process_listing_inquiry(array $data): array {
        $listing_id = absint($data['listing_id']);
        $listing = get_post($listing_id);

        if (!$listing || $listing->post_type !== 'listing') {
            return [
                'success' => false,
                'message' => __('Invalid listing selection.', 'happy-place-theme')
            ];
        }

        // Get agent email
        $agent_id = get_field('listing_agent', $listing_id);
        $agent_email = get_field('email', $agent_id);

        if (!$agent_email) {
            return [
                'success' => false,
                'message' => __('Unable to contact the listing agent. Please try again later.', 'happy-place-theme')
            ];
        }

        $subject = sprintf(
            __('[%s] New Listing Inquiry - %s', 'happy-place-theme'),
            get_bloginfo('name'),
            get_the_title($listing_id)
        );

        $message = sprintf(
            "Listing: %s\nName: %s\nEmail: %s\nPhone: %s\nMessage: %s",
            get_the_title($listing_id),
            sanitize_text_field($data['name']),
            sanitize_email($data['email']),
            sanitize_text_field($data['phone']),
            wp_kses_post($data['message'])
        );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', sanitize_text_field($data['name']), sanitize_email($data['email']))
        ];

        $sent = wp_mail($agent_email, $subject, $message, $headers);

        return [
            'success' => $sent,
            'message' => $sent 
                ? __('Thank you for your inquiry. The agent will contact you soon.', 'happy-place-theme')
                : __('Sorry, there was a problem sending your inquiry. Please try again later.', 'happy-place-theme')
        ];
    }

    /**
     * Process agent contact form submission
     */
    private static function process_agent_contact(array $data): array {
        $agent_id = absint($data['agent_id']);
        $agent = get_post($agent_id);

        if (!$agent || $agent->post_type !== 'agent') {
            return [
                'success' => false,
                'message' => __('Invalid agent selection.', 'happy-place-theme')
            ];
        }

        $agent_email = get_field('email', $agent_id);

        if (!$agent_email) {
            return [
                'success' => false,
                'message' => __('Unable to contact the agent. Please try again later.', 'happy-place-theme')
            ];
        }

        $subject = sprintf(
            __('[%s] New Contact Request from %s', 'happy-place-theme'),
            get_bloginfo('name'),
            sanitize_text_field($data['name'])
        );

        $message = sprintf(
            "Name: %s\nEmail: %s\nPhone: %s\nMessage: %s",
            sanitize_text_field($data['name']),
            sanitize_email($data['email']),
            sanitize_text_field($data['phone']),
            wp_kses_post($data['message'])
        );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', sanitize_text_field($data['name']), sanitize_email($data['email']))
        ];

        $sent = wp_mail($agent_email, $subject, $message, $headers);

        return [
            'success' => $sent,
            'message' => $sent 
                ? __('Thank you for your message. The agent will contact you soon.', 'happy-place-theme')
                : __('Sorry, there was a problem sending your message. Please try again later.', 'happy-place-theme')
        ];
    }
}
