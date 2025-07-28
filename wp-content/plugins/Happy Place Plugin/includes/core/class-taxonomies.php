<?php
namespace HappyPlace\Core;

// Class for managing custom taxonomies

class Taxonomies {
    private static ?self $instance = null;

    // Define taxonomy configurations
    private $taxonomies = [
        'property_feature' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'feature'],
            ],
            'labels' => [
                'name' => 'Property Features',
                'singular_name' => 'Property Feature',
                'menu_name' => 'Features',
                'search_items' => 'Search Features',
                'all_items' => 'All Features',
                'parent_item' => 'Parent Feature',
                'parent_item_colon' => 'Parent Feature:',
                'edit_item' => 'Edit Feature',
                'update_item' => 'Update Feature',
                'add_new_item' => 'Add New Feature',
                'new_item_name' => 'New Feature Name',
            ]
        ],
        'property_type' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'type'],
            ],
            'labels' => [
                'name' => 'Property Types',
                'singular_name' => 'Property Type',
                'menu_name' => 'Property Types',
                'search_items' => 'Search Property Types',
                'all_items' => 'All Property Types',
                'parent_item' => 'Parent Type',
                'parent_item_colon' => 'Parent Type:',
                'edit_item' => 'Edit Property Type',
                'update_item' => 'Update Property Type',
                'add_new_item' => 'Add New Property Type',
                'new_item_name' => 'New Property Type Name',
            ]
        ],
        'property_status' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'status'],
            ],
            'labels' => [
                'name' => 'Property Status',
                'singular_name' => 'Status',
                'menu_name' => 'Status',
                'search_items' => 'Search Statuses',
                'all_items' => 'All Statuses',
                'edit_item' => 'Edit Status',
                'update_item' => 'Update Status',
                'add_new_item' => 'Add New Status',
                'new_item_name' => 'New Status Name',
            ]
        ],
        'agent_specialty' => [
            'post_types' => ['agent'],
            'args' => [
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'specialty'],
            ],
            'labels' => [
                'name' => 'Agent Specialties',
                'singular_name' => 'Agent Specialty',
                'menu_name' => 'Specialties',
                'search_items' => 'Search Specialties',
                'all_items' => 'All Specialties',
                'edit_item' => 'Edit Specialty',
                'update_item' => 'Update Specialty',
                'add_new_item' => 'Add New Specialty',
                'new_item_name' => 'New Specialty Name',
            ]
        ],
        'community_amenity' => [
            'post_types' => ['community'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'amenity'],
            ],
            'labels' => [
                'name' => 'Community Amenities',
                'singular_name' => 'Amenity',
                'menu_name' => 'Amenities',
                'search_items' => 'Search Amenities',
                'all_items' => 'All Amenities',
                'parent_item' => 'Parent Amenity',
                'parent_item_colon' => 'Parent Amenity:',
                'edit_item' => 'Edit Amenity',
                'update_item' => 'Update Amenity',
                'add_new_item' => 'Add New Amenity',
                'new_item_name' => 'New Amenity Name',
            ]
        ],
        'place_category' => [
            'post_types' => ['local-place'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'place-category'],
            ],
            'labels' => [
                'name' => 'Place Categories',
                'singular_name' => 'Category',
                'menu_name' => 'Categories',
                'search_items' => 'Search Categories',
                'all_items' => 'All Categories',
                'parent_item' => 'Parent Category',
                'parent_item_colon' => 'Parent Category:',
                'edit_item' => 'Edit Category',
                'update_item' => 'Update Category',
                'add_new_item' => 'Add New Category',
                'new_item_name' => 'New Category Name',
            ]
        ],
        'listing_location' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'location'],
            ],
            'labels' => [
                'name' => 'Listing Locations',
                'singular_name' => 'Location',
                'menu_name' => 'Locations',
                'search_items' => 'Search Locations',
                'all_items' => 'All Locations',
                'parent_item' => 'Parent Location',
                'parent_item_colon' => 'Parent Location:',
                'edit_item' => 'Edit Location',
                'update_item' => 'Update Location',
                'add_new_item' => 'Add New Location',
                'new_item_name' => 'New Location Name',
            ]
        ],
        'service_area' => [
            'post_types' => ['agent'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'service-area'],
            ],
            'labels' => [
                'name' => 'Service Areas',
                'singular_name' => 'Service Area',
                'menu_name' => 'Service Areas',
                'search_items' => 'Search Service Areas',
                'all_items' => 'All Service Areas',
                'parent_item' => 'Parent Service Area',
                'parent_item_colon' => 'Parent Service Area:',
                'edit_item' => 'Edit Service Area',
                'update_item' => 'Update Service Area',
                'add_new_item' => 'Add New Service Area',
                'new_item_name' => 'New Service Area Name',
            ]
        ],
        'listing_feature' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => 'feature'],
            ],
            'labels' => [
                'name' => 'Listing Features',
                'singular_name' => 'Feature',
                'menu_name' => 'Features',
                'search_items' => 'Search Features',
                'all_items' => 'All Features',
                'edit_item' => 'Edit Feature',
                'update_item' => 'Update Feature',
                'add_new_item' => 'Add New Feature',
                'new_item_name' => 'New Feature Name',
            ]
        ]
    ];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register taxonomies early on init
        add_action('init', [$this, 'register_taxonomies'], 0);
        
        // Log registration for debugging
        add_action('init', [$this, 'log_registered_taxonomies'], 1);
        
        // Initialize additional functionality
        add_action('init', [$this, 'add_taxonomy_filters'], 20);
        add_action('init', [$this, 'create_default_terms'], 25);
        add_action('init', [$this, 'add_taxonomy_meta_fields'], 30);
    }

    /**
     * Register all taxonomies
     */
    public function register_taxonomies(): void {
        foreach ($this->taxonomies as $taxonomy => $config) {
            $args = array_merge([
                'labels' => $config['labels'],
                'public' => true,
                'show_ui' => true,
                'show_in_nav_menus' => true,
                'show_tagcloud' => true,
                'show_in_quick_edit' => true,
                'show_admin_column' => true,
                'query_var' => true,
            ], $config['args']);

            $result = register_taxonomy($taxonomy, $config['post_types'], $args);
            
            if (is_wp_error($result)) {
                error_log('HPH: Error registering taxonomy ' . $taxonomy . ': ' . $result->get_error_message());
            } else {
                error_log('HPH: Successfully registered taxonomy: ' . $taxonomy);
            }
        }
    }

    /**
     * Log all registered taxonomies for debugging
     */
    public function log_registered_taxonomies(): void {
        $taxonomies = get_taxonomies(['_builtin' => false], 'names');
        error_log('HPH: All registered custom taxonomies: ' . implode(', ', $taxonomies));
    }

    /**
     * Add taxonomy filters to admin screens
     */
    public function add_taxonomy_filters(): void {
        $taxonomy_filters = [
            'listing' => ['property_type', 'property_status', 'listing_location', 'property_feature', 'listing_feature'],
            'agent' => ['agent_specialty', 'service_area'],
            'community' => ['community_amenity'],
        ];

        foreach ($taxonomy_filters as $post_type => $taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                add_filter("manage_edit-{$post_type}_columns", function($columns) use ($taxonomy) {
                    if (taxonomy_exists($taxonomy)) {
                        $tax_obj = get_taxonomy($taxonomy);
                        $columns[$taxonomy] = $tax_obj->labels->singular_name;
                    }
                    return $columns;
                });

                add_filter("manage_{$post_type}_posts_custom_column", function($column_name, $post_id) use ($taxonomy) {
                    if ($column_name === $taxonomy) {
                        $terms = get_the_terms($post_id, $taxonomy);
                        if ($terms && !is_wp_error($terms)) {
                            $term_names = array_map(function($term) {
                                return $term->name;
                            }, $terms);
                            echo implode(', ', $term_names);
                        } else {
                            echo '—';
                        }
                    }
                }, 10, 2);
            }
        }
    }

    /**
     * Create default terms for important taxonomies
     */
    public function create_default_terms(): void {
        $default_terms = [
            'property_type' => [
                'Single Family Home',
                'Townhouse',
                'Condo',
                'Multi-Family',
                'Vacant Land'
            ],
            'property_status' => [
                'Active',
                'Pending',
                'Contingent',
                'Sold',
                'Expired'
            ],
            'agent_specialty' => [
                'Residential',
                'Commercial',
                'Luxury',
                'First-Time Buyers',
                'Waterfront Properties'
            ]
        ];

        foreach ($default_terms as $taxonomy => $terms) {
            if (taxonomy_exists($taxonomy)) {
                foreach ($terms as $term) {
                    if (!term_exists($term, $taxonomy)) {
                        wp_insert_term($term, $taxonomy);
                    }
                }
            }
        }
    }

    /**
     * Add taxonomy meta fields
     */
    public function add_taxonomy_meta_fields(): void {
        // Add meta fields to property type taxonomy
        $taxonomy = 'property_type';
        add_action("{$taxonomy}_add_form_fields", [$this, 'add_property_type_meta_fields']);
        add_action("{$taxonomy}_edit_form_fields", [$this, 'edit_property_type_meta_fields'], 10, 2);
        add_action("edited_{$taxonomy}", [$this, 'save_property_type_meta_fields'], 10, 2);
        add_action("create_{$taxonomy}", [$this, 'save_property_type_meta_fields'], 10, 2);
    }

    /**
     * Add meta fields when creating a new term
     */
    public function add_property_type_meta_fields($taxonomy): void {
        wp_nonce_field('property_type_meta', 'property_type_meta_nonce');
        ?>
        <div class="form-field">
            <label for="property_type_icon">Icon URL</label>
            <input type="text" name="property_type_icon" id="property_type_icon" />
            <p class="description">Enter an icon URL for this property type</p>
        </div>
        <?php
    }

    /**
     * Edit meta fields for existing terms
     */
    public function edit_property_type_meta_fields($term, $taxonomy): void {
        wp_nonce_field('property_type_meta', 'property_type_meta_nonce');
        $icon = get_term_meta($term->term_id, 'property_type_icon', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="property_type_icon">Icon URL</label></th>
            <td>
                <input type="text" name="property_type_icon" id="property_type_icon" value="<?php echo esc_attr($icon); ?>" />
                <p class="description">Enter an icon URL for this property type</p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save taxonomy meta fields
     */
    public function save_property_type_meta_fields($term_id, $taxonomy): void {
        // Verify nonce
        if (!isset($_POST['property_type_meta_nonce']) || 
            !wp_verify_nonce($_POST['property_type_meta_nonce'], 'property_type_meta')) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_categories')) {
            return;
        }

        if (isset($_POST['property_type_icon'])) {
            $icon = sanitize_text_field($_POST['property_type_icon']);
            if (filter_var($icon, FILTER_VALIDATE_URL)) {
                update_term_meta($term_id, 'property_type_icon', $icon);
            }
        }
    }
}

// Initialize the Taxonomies
Taxonomies::get_instance();