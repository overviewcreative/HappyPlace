<?php
if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group([
        'key' => 'group_company_settings',
        'title' => 'Company Settings',
        'fields' => [
            [
                'key' => 'field_company_logo',
                'label' => 'Company Logo',
                'name' => 'company_logo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'instructions' => 'Upload your company logo (recommended size: 200x200px)',
                'required' => 0,
            ],
            [
                'key' => 'field_company_name',
                'label' => 'Company Name',
                'name' => 'company_name',
                'type' => 'text',
                'instructions' => 'Enter your company\'s official name',
                'required' => 1,
                'default_value' => 'the parker group',
            ],
            [
                'key' => 'field_company_tagline',
                'label' => 'Company Tagline',
                'name' => 'company_tagline',
                'type' => 'text',
                'instructions' => 'Enter your company\'s tagline or slogan',
                'required' => 0,
                'default_value' => 'find your happy place',
            ],
            [
                'key' => 'field_office_contact_info',
                'label' => 'Office Contact Information',
                'name' => 'office_contact_info',
                'type' => 'group',
                'layout' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_office_email',
                        'label' => 'Office Email',
                        'name' => 'office_email',
                        'type' => 'email',
                        'instructions' => 'Primary contact email for the office',
                        'required' => 1,
                        'default_value' => 'cheers@theparkergroup.com',
                    ],
                    [
                        'key' => 'field_office_phone',
                        'label' => 'Office Phone',
                        'name' => 'office_phone',
                        'type' => 'text',
                        'instructions' => 'Main office phone number',
                        'required' => 1,
                        'default_value' => '302-217-6692',
                    ],
                    [
                        'key' => 'field_office_address',
                        'label' => 'Office Address',
                        'name' => 'office_address',
                        'type' => 'text',
                        'instructions' => 'Physical office address',
                        'required' => 1,
                        'default_value' => '673 N Bedford St. Georgetown, DE',
                    ],
                ],
            ],
            [
                'key' => 'field_social_media',
                'label' => 'Social Media',
                'name' => 'social_media',
                'type' => 'group',
                'layout' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_facebook_url',
                        'label' => 'Facebook URL',
                        'name' => 'facebook_url',
                        'type' => 'url',
                        'required' => 0,
                    ],
                    [
                        'key' => 'field_instagram_url',
                        'label' => 'Instagram URL',
                        'name' => 'instagram_url',
                        'type' => 'url',
                        'required' => 0,
                    ],
                    [
                        'key' => 'field_linkedin_url',
                        'label' => 'LinkedIn URL',
                        'name' => 'linkedin_url',
                        'type' => 'url',
                        'required' => 0,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'company-settings',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => 'Global company settings and contact information',
    ]);

endif;
