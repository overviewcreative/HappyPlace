<?php
/**
 * Contact Form Component
 *
 * Flexible contact form component for various use cases.
 *
 * @package HappyPlace\Components\UI
 * @since 2.0.0
 */

namespace HappyPlace\Components\UI;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Contact_Form extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'contact-form';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'form_type' => 'general', // general, listing_inquiry, agent_contact, showing_request
            'listing_id' => 0,
            'agent_id' => 0,
            'title' => '',
            'description' => '',
            'fields' => [
                'name',
                'email',
                'phone',
                'message'
            ],
            'required_fields' => [
                'name',
                'email',
                'message'
            ],
            'show_privacy_consent' => true,
            'consent_text' => '',
            'submit_button_text' => 'Send Message',
            'success_message' => 'Thank you for your message. We\'ll get back to you soon!',
            'redirect_url' => '',
            'style' => 'standard', // standard, inline, modal
            'layout' => 'vertical', // vertical, horizontal, grid
            'show_labels' => true,
            'placeholder_text' => true,
            'enable_ajax' => true,
            'show_loading' => true,
            'custom_class' => '',
            'prefill_data' => [],
            'additional_fields' => [],
            'field_order' => [],
            'honeypot_field' => true,
            'recaptcha' => false
        ];
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $form_id = 'hph-contact-form-' . uniqid();
        $form_classes = $this->get_form_classes();
        $custom_class = $this->get_prop('custom_class');
        
        ob_start();
        ?>
        <div class="hph-contact-form <?php echo esc_attr($form_classes . ' ' . $custom_class); ?>" 
             data-component="contact-form"
             data-form-type="<?php echo esc_attr($this->get_prop('form_type')); ?>"
             data-listing-id="<?php echo esc_attr($this->get_prop('listing_id')); ?>"
             data-agent-id="<?php echo esc_attr($this->get_prop('agent_id')); ?>">
            
            <?php $this->render_form_header(); ?>
            
            <form id="<?php echo esc_attr($form_id); ?>" 
                  class="hph-form hph-form--<?php echo esc_attr($this->get_prop('layout')); ?>" 
                  method="post" 
                  action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                  data-ajax="<?php echo $this->get_prop('enable_ajax') ? 'true' : 'false'; ?>">
                
                <?php $this->render_hidden_fields(); ?>
                <?php $this->render_form_fields(); ?>
                <?php $this->render_privacy_consent(); ?>
                <?php $this->render_submit_section(); ?>
                
            </form>
            
            <?php $this->render_form_messages(); ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render form header
     */
    private function render_form_header() {
        $title = $this->get_form_title();
        $description = $this->get_prop('description');
        
        if (empty($title) && empty($description)) {
            return;
        }
        
        echo '<div class="hph-form-header">';
        
        if (!empty($title)) {
            echo '<h3 class="hph-form-title">' . esc_html($title) . '</h3>';
        }
        
        if (!empty($description)) {
            echo '<p class="hph-form-description">' . esc_html($description) . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render hidden fields
     */
    private function render_hidden_fields() {
        echo '<input type="hidden" name="action" value="hph_contact_form_submit">';
        echo '<input type="hidden" name="form_type" value="' . esc_attr($this->get_prop('form_type')) . '">';
        echo '<input type="hidden" name="nonce" value="' . wp_create_nonce('hph_contact_form') . '">';
        
        if ($this->get_prop('listing_id')) {
            echo '<input type="hidden" name="listing_id" value="' . esc_attr($this->get_prop('listing_id')) . '">';
        }
        
        if ($this->get_prop('agent_id')) {
            echo '<input type="hidden" name="agent_id" value="' . esc_attr($this->get_prop('agent_id')) . '">';
        }
        
        $redirect_url = $this->get_prop('redirect_url');
        if (!empty($redirect_url)) {
            echo '<input type="hidden" name="redirect_url" value="' . esc_url($redirect_url) . '">';
        }
        
        // Honeypot field
        if ($this->get_prop('honeypot_field')) {
            echo '<input type="text" name="website" value="" style="display: none !important;" tabindex="-1" autocomplete="off">';
        }
    }
    
    /**
     * Render form fields
     */
    private function render_form_fields() {
        $fields = $this->get_ordered_fields();
        $prefill_data = $this->get_prop('prefill_data');
        
        echo '<div class="hph-form-fields">';
        
        foreach ($fields as $field_name) {
            $field_config = $this->get_field_config($field_name);
            $prefill_value = $prefill_data[$field_name] ?? '';
            
            $this->render_field($field_name, $field_config, $prefill_value);
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual field
     */
    private function render_field($field_name, $config, $prefill_value = '') {
        $field_id = 'hph-field-' . $field_name . '-' . uniqid();
        $required = in_array($field_name, $this->get_prop('required_fields'));
        $show_labels = $this->get_prop('show_labels');
        $use_placeholders = $this->get_prop('placeholder_text');
        
        $field_classes = ['hph-form-field', 'hph-field--' . $field_name];
        if ($required) {
            $field_classes[] = 'hph-field--required';
        }
        if ($config['type'] === 'textarea') {
            $field_classes[] = 'hph-field--textarea';
        }
        
        echo '<div class="' . esc_attr(implode(' ', $field_classes)) . '">';
        
        // Label
        if ($show_labels) {
            echo '<label for="' . esc_attr($field_id) . '" class="hph-field-label">';
            echo esc_html($config['label']);
            if ($required) {
                echo ' <span class="hph-required-asterisk" aria-label="' . esc_attr__('required', 'happy-place') . '">*</span>';
            }
            echo '</label>';
        }
        
        // Field wrapper
        echo '<div class="hph-field-wrapper">';
        
        // Input/textarea
        $this->render_field_input($field_id, $field_name, $config, $prefill_value, $required, $use_placeholders);
        
        // Field hint/help text
        if (!empty($config['hint'])) {
            echo '<div class="hph-field-hint">' . esc_html($config['hint']) . '</div>';
        }
        
        // Error message placeholder
        echo '<div class="hph-field-error" id="' . esc_attr($field_id) . '-error" role="alert" style="display: none;"></div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render field input element
     */
    private function render_field_input($field_id, $field_name, $config, $prefill_value, $required, $use_placeholders) {
        $attributes = [
            'id' => $field_id,
            'name' => $field_name,
            'class' => 'hph-field-input',
            'data-field' => $field_name
        ];
        
        if ($required) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        
        if ($use_placeholders && !empty($config['placeholder'])) {
            $attributes['placeholder'] = $config['placeholder'];
        }
        
        if (!empty($prefill_value)) {
            $attributes['value'] = $prefill_value;
        }
        
        // Add field-specific attributes
        switch ($config['type']) {
            case 'email':
                $attributes['type'] = 'email';
                $attributes['autocomplete'] = 'email';
                break;
            case 'tel':
                $attributes['type'] = 'tel';
                $attributes['autocomplete'] = 'tel';
                break;
            case 'text':
            default:
                $attributes['type'] = 'text';
                if ($field_name === 'name') {
                    $attributes['autocomplete'] = 'name';
                }
                break;
        }
        
        if ($config['type'] === 'textarea') {
            echo '<textarea';
            foreach ($attributes as $attr => $value) {
                if ($attr !== 'type' && $attr !== 'value') {
                    echo ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
                }
            }
            echo ' rows="4">';
            echo esc_textarea($prefill_value);
            echo '</textarea>';
        } elseif ($config['type'] === 'select') {
            echo '<select';
            foreach ($attributes as $attr => $value) {
                if ($attr !== 'type' && $attr !== 'value') {
                    echo ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
                }
            }
            echo '>';
            
            if ($use_placeholders && !empty($config['placeholder'])) {
                echo '<option value="">' . esc_html($config['placeholder']) . '</option>';
            }
            
            foreach ($config['options'] as $option_value => $option_label) {
                $selected = $prefill_value === $option_value ? 'selected' : '';
                echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input';
            foreach ($attributes as $attr => $value) {
                echo ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
            echo '>';
        }
    }
    
    /**
     * Render privacy consent checkbox
     */
    private function render_privacy_consent() {
        if (!$this->get_prop('show_privacy_consent')) {
            return;
        }
        
        $consent_text = $this->get_prop('consent_text');
        if (empty($consent_text)) {
            $consent_text = sprintf(
                __('I agree to the %s and %s', 'happy-place'),
                '<a href="' . get_privacy_policy_url() . '" target="_blank">' . __('Privacy Policy', 'happy-place') . '</a>',
                '<a href="#" target="_blank">' . __('Terms of Service', 'happy-place') . '</a>'
            );
        }
        
        $consent_id = 'hph-privacy-consent-' . uniqid();
        
        echo '<div class="hph-form-field hph-field--consent hph-field--required">';
        echo '<div class="hph-field-wrapper">';
        echo '<label class="hph-checkbox-label" for="' . esc_attr($consent_id) . '">';
        echo '<input type="checkbox" id="' . esc_attr($consent_id) . '" name="privacy_consent" value="1" required class="hph-checkbox-input" data-field="privacy_consent">';
        echo '<span class="hph-checkbox-custom"></span>';
        echo '<span class="hph-checkbox-text">' . wp_kses_post($consent_text) . ' <span class="hph-required-asterisk">*</span></span>';
        echo '</label>';
        echo '<div class="hph-field-error" id="' . esc_attr($consent_id) . '-error" role="alert" style="display: none;"></div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render submit section
     */
    private function render_submit_section() {
        $submit_text = $this->get_prop('submit_button_text');
        $enable_ajax = $this->get_prop('enable_ajax');
        $show_loading = $this->get_prop('show_loading');
        
        echo '<div class="hph-form-submit">';
        
        // reCAPTCHA
        if ($this->get_prop('recaptcha')) {
            echo '<div class="hph-recaptcha-wrapper">';
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr(get_option('hph_recaptcha_site_key')) . '"></div>';
            echo '</div>';
        }
        
        echo '<button type="submit" class="hph-button hph-button--primary hph-submit-btn"';
        if ($enable_ajax) {
            echo ' data-ajax="true"';
        }
        echo '>';
        
        if ($show_loading) {
            echo '<span class="hph-btn-text">' . esc_html($submit_text) . '</span>';
            echo '<span class="hph-btn-loading" style="display: none;">';
            echo '<span class="hph-spinner" aria-hidden="true"></span>';
            echo '<span class="hph-loading-text">' . esc_html__('Sending...', 'happy-place') . '</span>';
            echo '</span>';
        } else {
            echo esc_html($submit_text);
        }
        
        echo '</button>';
        echo '</div>';
    }
    
    /**
     * Render form messages
     */
    private function render_form_messages() {
        echo '<div class="hph-form-messages">';
        
        echo '<div class="hph-form-success" style="display: none;" role="alert">';
        echo '<div class="hph-success-icon" aria-hidden="true">✓</div>';
        echo '<div class="hph-success-content">';
        echo '<h4 class="hph-success-title">' . esc_html__('Message Sent!', 'happy-place') . '</h4>';
        echo '<p class="hph-success-text">' . esc_html($this->get_prop('success_message')) . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="hph-form-error" style="display: none;" role="alert">';
        echo '<div class="hph-error-icon" aria-hidden="true">⚠</div>';
        echo '<div class="hph-error-content">';
        echo '<h4 class="hph-error-title">' . esc_html__('Error', 'happy-place') . '</h4>';
        echo '<p class="hph-error-text"></p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Get form classes
     */
    private function get_form_classes() {
        $classes = [];
        
        $classes[] = 'hph-contact-form--' . $this->get_prop('style');
        $classes[] = 'hph-contact-form--' . $this->get_prop('layout');
        
        if ($this->get_prop('enable_ajax')) {
            $classes[] = 'hph-contact-form--ajax';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get form title based on type
     */
    private function get_form_title() {
        $title = $this->get_prop('title');
        
        if (!empty($title)) {
            return $title;
        }
        
        // Auto-generate title based on form type
        switch ($this->get_prop('form_type')) {
            case 'listing_inquiry':
                $listing_id = $this->get_prop('listing_id');
                if ($listing_id) {
                    $listing = hph_get_template_listing_data($listing_id);
                    return sprintf(__('Inquire About %s', 'happy-place'), $listing['title']);
                }
                return __('Property Inquiry', 'happy-place');
                
            case 'agent_contact':
                $agent_id = $this->get_prop('agent_id');
                if ($agent_id) {
                    $agent = hph_get_agent_data($agent_id);
                    return sprintf(__('Contact %s', 'happy-place'), $agent['display_name']);
                }
                return __('Contact Agent', 'happy-place');
                
            case 'showing_request':
                return __('Request a Showing', 'happy-place');
                
            case 'general':
            default:
                return __('Contact Us', 'happy-place');
        }
    }
    
    /**
     * Get ordered fields list
     */
    private function get_ordered_fields() {
        $fields = $this->get_prop('fields');
        $additional_fields = $this->get_prop('additional_fields');
        $field_order = $this->get_prop('field_order');
        
        // Merge standard and additional fields
        $all_fields = array_merge($fields, array_keys($additional_fields));
        
        // Apply custom ordering if specified
        if (!empty($field_order)) {
            $ordered_fields = [];
            
            // Add fields in specified order
            foreach ($field_order as $field_name) {
                if (in_array($field_name, $all_fields)) {
                    $ordered_fields[] = $field_name;
                }
            }
            
            // Add any remaining fields not in order
            $remaining_fields = array_diff($all_fields, $ordered_fields);
            $all_fields = array_merge($ordered_fields, $remaining_fields);
        }
        
        return $all_fields;
    }
    
    /**
     * Get field configuration
     */
    private function get_field_config($field_name) {
        $additional_fields = $this->get_prop('additional_fields');
        
        // Check for custom field configuration
        if (isset($additional_fields[$field_name])) {
            return $additional_fields[$field_name];
        }
        
        // Default field configurations
        $default_configs = [
            'name' => [
                'type' => 'text',
                'label' => __('Full Name', 'happy-place'),
                'placeholder' => __('Enter your full name', 'happy-place')
            ],
            'email' => [
                'type' => 'email',
                'label' => __('Email Address', 'happy-place'),
                'placeholder' => __('Enter your email address', 'happy-place')
            ],
            'phone' => [
                'type' => 'tel',
                'label' => __('Phone Number', 'happy-place'),
                'placeholder' => __('Enter your phone number', 'happy-place')
            ],
            'message' => [
                'type' => 'textarea',
                'label' => __('Message', 'happy-place'),
                'placeholder' => __('Tell us how we can help you...', 'happy-place')
            ],
            'preferred_contact_method' => [
                'type' => 'select',
                'label' => __('Preferred Contact Method', 'happy-place'),
                'placeholder' => __('Select preferred method', 'happy-place'),
                'options' => [
                    'email' => __('Email', 'happy-place'),
                    'phone' => __('Phone', 'happy-place'),
                    'text' => __('Text Message', 'happy-place')
                ]
            ],
            'best_time_to_contact' => [
                'type' => 'select',
                'label' => __('Best Time to Contact', 'happy-place'),
                'placeholder' => __('Select best time', 'happy-place'),
                'options' => [
                    'morning' => __('Morning (8am-12pm)', 'happy-place'),
                    'afternoon' => __('Afternoon (12pm-5pm)', 'happy-place'),
                    'evening' => __('Evening (5pm-8pm)', 'happy-place'),
                    'anytime' => __('Anytime', 'happy-place')
                ]
            ]
        ];
        
        return $default_configs[$field_name] ?? [
            'type' => 'text',
            'label' => ucwords(str_replace('_', ' ', $field_name)),
            'placeholder' => ''
        ];
    }
}
