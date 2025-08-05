<?php
/**
 * Form Renderer - Generates HTML forms from ACF field definitions
 * 
 * Provides consistent form rendering across all CPT forms with
 * ACF integration and responsive design.
 * 
 * @package HappyPlace
 * @subpackage Forms
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Form_Renderer {
    
    /**
     * Form templates directory
     *
     * @var string
     */
    private $templates_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->templates_dir = plugin_dir_path(__FILE__) . 'templates/';
        
        // Create templates directory if it doesn't exist
        if (!file_exists($this->templates_dir)) {
            wp_mkdir_p($this->templates_dir);
        }
    }
    
    /**
     * Render a complete form
     *
     * @param string $form_type Form type identifier
     * @param array $args Form arguments
     * @return string|WP_Error Form HTML or error
     */
    public function render($form_type, $args = []) {
        $defaults = [
            'action' => '',
            'method' => 'post',
            'class' => 'hph-form',
            'id' => "hph-form-{$form_type}",
            'ajax' => true,
            'show_title' => true,
            'show_description' => true,
            'submit_text' => __('Submit', 'happy-place'),
            'post_id' => 0,
            'field_groups' => [],
            'fields' => [],
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Get field groups for form type
        if (empty($args['field_groups'])) {
            $args['field_groups'] = $this->get_form_field_groups($form_type);
        }
        
        // Check if we have a custom template
        $template_file = $this->get_template_file($form_type);
        if ($template_file) {
            return $this->render_from_template($template_file, $args);
        }
        
        // Generate form HTML
        return $this->generate_form_html($form_type, $args);
    }
    
    /**
     * Generate form HTML
     *
     * @param string $form_type Form type
     * @param array $args Form arguments
     * @return string Form HTML
     */
    private function generate_form_html($form_type, $args) {
        ob_start();
        
        $form_class = $args['class'] . ' hph-form-' . $form_type;
        if ($args['ajax']) {
            $form_class .= ' hph-ajax-form';
        }
        
        ?>
        <form 
            id="<?php echo esc_attr($args['id']); ?>" 
            class="<?php echo esc_attr($form_class); ?>"
            method="<?php echo esc_attr($args['method']); ?>"
            <?php if (!$args['ajax'] && $args['action']): ?>
            action="<?php echo esc_url($args['action']); ?>"
            <?php endif; ?>
            data-form-type="<?php echo esc_attr($form_type); ?>"
        >
            
            <?php wp_nonce_field('hph_form_submit', 'hph_form_nonce'); ?>
            <input type="hidden" name="form_type" value="<?php echo esc_attr($form_type); ?>">
            <?php if ($args['post_id']): ?>
                <input type="hidden" name="post_id" value="<?php echo esc_attr($args['post_id']); ?>">
            <?php endif; ?>
            
            <?php if ($args['show_title']): ?>
                <div class="hph-form-header">
                    <h3 class="hph-form-title"><?php echo esc_html($this->get_form_title($form_type)); ?></h3>
                    <?php if ($args['show_description']): ?>
                        <p class="hph-form-description"><?php echo esc_html($this->get_form_description($form_type)); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="hph-form-messages"></div>
            
            <div class="hph-form-fields">
                <?php $this->render_form_fields($form_type, $args); ?>
            </div>
            
            <div class="hph-form-actions">
                <?php $this->render_form_actions($form_type, $args); ?>
            </div>
            
        </form>
        
        <?php if ($args['ajax']): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof HPH_Forms !== 'undefined') {
                    HPH_Forms.initForm('<?php echo esc_js($args['id']); ?>');
                }
            });
            </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render form fields
     *
     * @param string $form_type Form type
     * @param array $args Form arguments
     */
    private function render_form_fields($form_type, $args) {
        // If specific fields are provided, render only those
        if (!empty($args['fields'])) {
            foreach ($args['fields'] as $field_name => $field_config) {
                $this->render_custom_field($field_name, $field_config, $args['post_id']);
            }
            return;
        }
        
        // Render ACF field groups
        if (!empty($args['field_groups'])) {
            foreach ($args['field_groups'] as $field_group) {
                $this->render_field_group($field_group, $args['post_id']);
            }
            return;
        }
        
        // Fallback: render basic fields for form type
        $this->render_basic_fields($form_type, $args['post_id']);
    }
    
    /**
     * Render ACF field group
     *
     * @param string $field_group_key Field group key
     * @param int $post_id Post ID for field values
     */
    private function render_field_group($field_group_key, $post_id = 0) {
        if (!function_exists('acf_get_fields')) {
            return;
        }
        
        $fields = acf_get_fields($field_group_key);
        if (!$fields) {
            return;
        }
        
        echo '<div class="hph-field-group" data-field-group="' . esc_attr($field_group_key) . '">';
        
        foreach ($fields as $field) {
            $this->render_acf_field($field, $post_id);
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual ACF field
     *
     * @param array $field ACF field array
     * @param int $post_id Post ID for field values
     */
    private function render_acf_field($field, $post_id = 0) {
        // Skip fields that shouldn't be in frontend forms
        if (isset($field['readonly']) && $field['readonly']) {
            return;
        }
        
        // Get field value
        $value = $post_id ? get_field($field['name'], $post_id) : '';
        
        // Field wrapper
        $wrapper_class = 'hph-field-wrapper hph-field-' . $field['type'];
        if (isset($field['wrapper']['class'])) {
            $wrapper_class .= ' ' . $field['wrapper']['class'];
        }
        
        $wrapper_width = isset($field['wrapper']['width']) ? $field['wrapper']['width'] : '100';
        
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>" style="width: <?php echo esc_attr($wrapper_width); ?>%;">
            
            <?php if ($field['label']): ?>
                <label for="<?php echo esc_attr($field['name']); ?>" class="hph-field-label">
                    <?php echo esc_html($field['label']); ?>
                    <?php if (isset($field['required']) && $field['required']): ?>
                        <span class="hph-required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <?php if ($field['instructions']): ?>
                <p class="hph-field-instructions"><?php echo esc_html($field['instructions']); ?></p>
            <?php endif; ?>
            
            <div class="hph-field-input">
                <?php $this->render_field_input($field, $value); ?>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Render field input based on type
     *
     * @param array $field ACF field array
     * @param mixed $value Field value
     */
    private function render_field_input($field, $value) {
        $field_name = $field['name'];
        $field_id = $field['name'];
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        
        switch ($field['type']) {
            case 'text':
                ?>
                <input 
                    type="text" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                    maxlength="<?php echo esc_attr($field['maxlength'] ?? ''); ?>"
                    class="hph-field-input-text"
                    <?php echo $required; ?>
                >
                <?php
                break;
                
            case 'email':
                ?>
                <input 
                    type="email" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                    class="hph-field-input-email"
                    <?php echo $required; ?>
                >
                <?php
                break;
                
            case 'number':
                ?>
                <input 
                    type="number" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                    min="<?php echo esc_attr($field['min'] ?? ''); ?>"
                    max="<?php echo esc_attr($field['max'] ?? ''); ?>"
                    step="<?php echo esc_attr($field['step'] ?? ''); ?>"
                    class="hph-field-input-number"
                    <?php echo $required; ?>
                >
                <?php
                break;
                
            case 'textarea':
                ?>
                <textarea 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                    rows="<?php echo esc_attr($field['rows'] ?? '4'); ?>"
                    maxlength="<?php echo esc_attr($field['maxlength'] ?? ''); ?>"
                    class="hph-field-input-textarea"
                    <?php echo $required; ?>
                ><?php echo esc_textarea($value); ?></textarea>
                <?php
                break;
                
            case 'select':
                ?>
                <select 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name) . (isset($field['multiple']) && $field['multiple'] ? '[]' : ''); ?>" 
                    class="hph-field-input-select"
                    <?php echo isset($field['multiple']) && $field['multiple'] ? 'multiple' : ''; ?>
                    <?php echo $required; ?>
                >
                    <?php if (isset($field['allow_null']) && $field['allow_null']): ?>
                        <option value=""><?php _e('Select...', 'happy-place'); ?></option>
                    <?php endif; ?>
                    
                    <?php if (isset($field['choices']) && is_array($field['choices'])): ?>
                        <?php foreach ($field['choices'] as $choice_value => $choice_label): ?>
                            <option 
                                value="<?php echo esc_attr($choice_value); ?>"
                                <?php selected($value, $choice_value); ?>
                            >
                                <?php echo esc_html($choice_label); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php
                break;
                
            case 'checkbox':
                if (isset($field['choices']) && is_array($field['choices'])) {
                    // Multiple checkboxes
                    foreach ($field['choices'] as $choice_value => $choice_label) {
                        $checked = is_array($value) && in_array($choice_value, $value) ? 'checked' : '';
                        ?>
                        <label class="hph-checkbox-option">
                            <input 
                                type="checkbox" 
                                name="<?php echo esc_attr($field_name); ?>[]" 
                                value="<?php echo esc_attr($choice_value); ?>"
                                <?php echo $checked; ?>
                                class="hph-field-input-checkbox"
                            >
                            <span><?php echo esc_html($choice_label); ?></span>
                        </label>
                        <?php
                    }
                } else {
                    // Single checkbox
                    ?>
                    <label class="hph-checkbox-single">
                        <input 
                            type="checkbox" 
                            id="<?php echo esc_attr($field_id); ?>" 
                            name="<?php echo esc_attr($field_name); ?>" 
                            value="1"
                            <?php checked($value, 1); ?>
                            class="hph-field-input-checkbox"
                            <?php echo $required; ?>
                        >
                        <span><?php echo esc_html($field['message'] ?? ''); ?></span>
                    </label>
                    <?php
                }
                break;
                
            case 'radio':
                if (isset($field['choices']) && is_array($field['choices'])) {
                    foreach ($field['choices'] as $choice_value => $choice_label) {
                        ?>
                        <label class="hph-radio-option">
                            <input 
                                type="radio" 
                                name="<?php echo esc_attr($field_name); ?>" 
                                value="<?php echo esc_attr($choice_value); ?>"
                                <?php checked($value, $choice_value); ?>
                                class="hph-field-input-radio"
                                <?php echo $required; ?>
                            >
                            <span><?php echo esc_html($choice_label); ?></span>
                        </label>
                        <?php
                    }
                }
                break;
                
            case 'file':
                ?>
                <input 
                    type="file" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    class="hph-field-input-file"
                    accept="<?php echo esc_attr($field['mime_types'] ?? ''); ?>"
                    <?php echo $required; ?>
                >
                <?php if ($value): ?>
                    <div class="hph-current-file">
                        <span><?php _e('Current:', 'happy-place'); ?></span>
                        <a href="<?php echo esc_url(wp_get_attachment_url($value)); ?>" target="_blank">
                            <?php echo esc_html(get_the_title($value)); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php
                break;
                
            default:
                // For unsupported field types, render as text input
                ?>
                <input 
                    type="text" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    class="hph-field-input-text hph-field-unsupported"
                    <?php echo $required; ?>
                >
                <small class="hph-field-notice">
                    <?php printf(__('Field type "%s" not fully supported in frontend forms', 'happy-place'), esc_html($field['type'])); ?>
                </small>
                <?php
        }
    }
    
    /**
     * Render form actions (submit button, etc.)
     *
     * @param string $form_type Form type
     * @param array $args Form arguments
     */
    private function render_form_actions($form_type, $args) {
        ?>
        <div class="hph-form-submit-wrapper">
            <button type="submit" class="hph-form-submit hph-btn hph-btn-primary">
                <span class="hph-submit-text"><?php echo esc_html($args['submit_text']); ?></span>
                <span class="hph-submit-spinner" style="display: none;">
                    <?php _e('Processing...', 'happy-place'); ?>
                </span>
            </button>
            
            <?php if ($args['post_id']): ?>
                <a href="<?php echo esc_url(get_permalink($args['post_id'])); ?>" class="hph-form-cancel hph-btn hph-btn-secondary">
                    <?php _e('Cancel', 'happy-place'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render basic fields for form type
     *
     * @param string $form_type Form type
     * @param int $post_id Post ID
     */
    private function render_basic_fields($form_type, $post_id = 0) {
        // Basic fallback fields
        $basic_fields = [
            'title' => [
                'label' => __('Title', 'happy-place'),
                'type' => 'text',
                'required' => true
            ],
            'description' => [
                'label' => __('Description', 'happy-place'),
                'type' => 'textarea',
                'required' => false
            ]
        ];
        
        foreach ($basic_fields as $field_name => $field_config) {
            $this->render_custom_field($field_name, $field_config, $post_id);
        }
    }
    
    /**
     * Render custom field
     *
     * @param string $field_name Field name
     * @param array $field_config Field configuration
     * @param int $post_id Post ID
     */
    private function render_custom_field($field_name, $field_config, $post_id = 0) {
        $value = $post_id ? get_post_meta($post_id, $field_name, true) : '';
        
        ?>
        <div class="hph-field-wrapper hph-field-<?php echo esc_attr($field_config['type']); ?>">
            
            <label for="<?php echo esc_attr($field_name); ?>" class="hph-field-label">
                <?php echo esc_html($field_config['label']); ?>
                <?php if (isset($field_config['required']) && $field_config['required']): ?>
                    <span class="hph-required">*</span>
                <?php endif; ?>
            </label>
            
            <div class="hph-field-input">
                <?php
                $mock_field = [
                    'name' => $field_name,
                    'type' => $field_config['type'],
                    'required' => $field_config['required'] ?? false,
                    'placeholder' => $field_config['placeholder'] ?? '',
                    'choices' => $field_config['choices'] ?? []
                ];
                
                $this->render_field_input($mock_field, $value);
                ?>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Get form field groups
     *
     * @param string $form_type Form type
     * @return array Field group keys
     */
    private function get_form_field_groups($form_type) {
        // Map form types to post types
        $post_type_map = [
            'listing' => 'listing',
            'agent' => 'agent',
            'community' => 'community',
            'office' => 'office'
        ];
        
        $post_type = $post_type_map[$form_type] ?? $form_type;
        
        if (!function_exists('acf_get_field_groups')) {
            return [];
        }
        
        $field_groups = acf_get_field_groups(['post_type' => $post_type]);
        return array_column($field_groups, 'key');
    }
    
    /**
     * Get form title
     *
     * @param string $form_type Form type
     * @return string Form title
     */
    private function get_form_title($form_type) {
        $titles = [
            'listing' => __('Listing Information', 'happy-place'),
            'agent' => __('Agent Profile', 'happy-place'),
            'community' => __('Community Information', 'happy-place'),
            'office' => __('Office Information', 'happy-place'),
            'contact' => __('Contact Us', 'happy-place')
        ];
        
        return $titles[$form_type] ?? ucfirst($form_type) . ' ' . __('Form', 'happy-place');
    }
    
    /**
     * Get form description
     *
     * @param string $form_type Form type
     * @return string Form description
     */
    private function get_form_description($form_type) {
        $descriptions = [
            'listing' => __('Please fill out the form below to create or update a listing.', 'happy-place'),
            'agent' => __('Update your agent profile information.', 'happy-place'),
            'community' => __('Manage community information and settings.', 'happy-place'),
            'office' => __('Update office information and details.', 'happy-place'),
            'contact' => __('Get in touch with us using the form below.', 'happy-place')
        ];
        
        return $descriptions[$form_type] ?? '';
    }
    
    /**
     * Get template file path
     *
     * @param string $form_type Form type
     * @return string|false Template file path or false if not found
     */
    private function get_template_file($form_type) {
        $template_file = $this->templates_dir . "form-{$form_type}.php";
        
        if (file_exists($template_file)) {
            return $template_file;
        }
        
        return false;
    }
    
    /**
     * Render from template file
     *
     * @param string $template_file Template file path
     * @param array $args Template arguments
     * @return string Rendered template
     */
    private function render_from_template($template_file, $args) {
        ob_start();
        
        // Extract args for use in template
        extract($args);
        
        include $template_file;
        
        return ob_get_clean();
    }
}