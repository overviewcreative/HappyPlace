<?php
/**
 * Open House ACF Fields Configuration
 * Adds hosting agent functionality to listings
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register open house fields when ACF is ready
add_action('acf/init', 'hph_register_open_house_fields');

function hph_register_open_house_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group(array(
        'key' => 'group_open_house_details',
        'title' => 'Open House Details',
        'fields' => array(
            array(
                'key' => 'field_open_house_date',
                'label' => 'Open House Date',
                'name' => 'open_house_date',
                'type' => 'date_picker',
                'instructions' => 'Select the date for the open house',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'F j, Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
            ),
            array(
                'key' => 'field_open_house_start_time',
                'label' => 'Start Time',
                'name' => 'open_house_start_time',
                'type' => 'time_picker',
                'instructions' => 'Open house start time',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'g:i a',
                'return_format' => 'H:i:s',
            ),
            array(
                'key' => 'field_open_house_end_time',
                'label' => 'End Time',
                'name' => 'open_house_end_time',
                'type' => 'time_picker',
                'instructions' => 'Open house end time',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'g:i a',
                'return_format' => 'H:i:s',
            ),
            array(
                'key' => 'field_hosting_agent',
                'label' => 'Hosting Agent',
                'name' => 'hosting_agent',
                'type' => 'user',
                'instructions' => 'Select the agent who will be hosting this open house (can be different from listing agent)',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'role' => array(
                    0 => 'estate_agent',
                    1 => 'listing_agent',
                    2 => 'administrator',
                ),
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'id',
            ),
            array(
                'key' => 'field_open_house_notes',
                'label' => 'Open House Notes',
                'name' => 'open_house_notes',
                'type' => 'textarea',
                'instructions' => 'Additional notes or instructions for the open house',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => 'Special instructions, parking info, etc.',
                'maxlength' => 500,
                'rows' => 3,
                'new_lines' => 'wpautop',
            ),
            array(
                'key' => 'field_open_house_status',
                'label' => 'Open House Status',
                'name' => 'open_house_status',
                'type' => 'select',
                'instructions' => 'Current status of the open house',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'scheduled' => 'Scheduled',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ),
                'default_value' => 'scheduled',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'placeholder' => '',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'listing',
                ),
            ),
        ),
        'menu_order' => 25,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => 'Open house scheduling and hosting agent information',
    ));
}