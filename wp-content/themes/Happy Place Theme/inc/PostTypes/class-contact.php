<?php
/**
 * Register Contact Post Type
 *
 * @package HappyPlace
 */

function hph_register_contact_post_type() {
    $labels = array(
        'name'                  => _x('Contact Messages', 'Post type general name', 'happy-place'),
        'singular_name'         => _x('Contact Message', 'Post type singular name', 'happy-place'),
        'menu_name'            => _x('Contact Messages', 'Admin Menu text', 'happy-place'),
        'name_admin_bar'        => _x('Contact Message', 'Add New on Toolbar', 'happy-place'),
        'add_new'              => __('Add New', 'happy-place'),
        'add_new_item'         => __('Add New Message', 'happy-place'),
        'new_item'             => __('New Message', 'happy-place'),
        'edit_item'            => __('Edit Message', 'happy-place'),
        'view_item'            => __('View Message', 'happy-place'),
        'all_items'            => __('All Messages', 'happy-place'),
        'search_items'         => __('Search Messages', 'happy-place'),
        'not_found'            => __('No messages found.', 'happy-place'),
        'not_found_in_trash'   => __('No messages found in Trash.', 'happy-place'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'           => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-email-alt',
        'supports'           => array('title', 'editor'),
    );

    register_post_type('hph_contact', $args);
}
add_action('init', 'hph_register_contact_post_type');

/**
 * Add custom columns to contact messages list
 *
 * @param array $columns Existing columns
 * @return array Modified columns
 */
function hph_contact_columns($columns) {
    $date = $columns['date'];
    unset($columns['date']);

    $columns['contact_name'] = __('Name', 'happy-place');
    $columns['contact_email'] = __('Email', 'happy-place');
    $columns['contact_phone'] = __('Phone', 'happy-place');
    $columns['contact_interest'] = __('Interest', 'happy-place');
    $columns['date'] = $date;

    return $columns;
}
add_filter('manage_hph_contact_posts_columns', 'hph_contact_columns');

/**
 * Display custom column content
 *
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function hph_contact_column_content($column, $post_id) {
    switch ($column) {
        case 'contact_name':
            echo esc_html(get_post_meta($post_id, '_contact_name', true));
            break;
        case 'contact_email':
            $email = get_post_meta($post_id, '_contact_email', true);
            echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            break;
        case 'contact_phone':
            $phone = get_post_meta($post_id, '_contact_phone', true);
            if ($phone) {
                echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
            }
            break;
        case 'contact_interest':
            echo esc_html(get_post_meta($post_id, '_contact_interest', true));
            break;
    }
}
add_action('manage_hph_contact_posts_custom_column', 'hph_contact_column_content', 10, 2);

/**
 * Add meta box for contact details
 */
function hph_add_contact_meta_box() {
    add_meta_box(
        'hph_contact_details',
        __('Contact Details', 'happy-place'),
        'hph_contact_meta_box_callback',
        'hph_contact',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'hph_add_contact_meta_box');

/**
 * Display contact meta box content
 *
 * @param WP_Post $post Post object
 */
function hph_contact_meta_box_callback($post) {
    $meta = array(
        '_contact_name' => get_post_meta($post->ID, '_contact_name', true),
        '_contact_email' => get_post_meta($post->ID, '_contact_email', true),
        '_contact_phone' => get_post_meta($post->ID, '_contact_phone', true),
        '_contact_subject' => get_post_meta($post->ID, '_contact_subject', true),
        '_contact_interest' => get_post_meta($post->ID, '_contact_interest', true),
    );

    ?>
    <table class="form-table">
        <tr>
            <th><label for="contact_name"><?php _e('Name', 'happy-place'); ?></label></th>
            <td>
                <input type="text" id="contact_name" name="contact_name" value="<?php echo esc_attr($meta['_contact_name']); ?>" class="regular-text" readonly>
            </td>
        </tr>
        <tr>
            <th><label for="contact_email"><?php _e('Email', 'happy-place'); ?></label></th>
            <td>
                <input type="email" id="contact_email" name="contact_email" value="<?php echo esc_attr($meta['_contact_email']); ?>" class="regular-text" readonly>
                <p class="description">
                    <a href="mailto:<?php echo esc_attr($meta['_contact_email']); ?>" class="button">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('Send Email', 'happy-place'); ?>
                    </a>
                </p>
            </td>
        </tr>
        <tr>
            <th><label for="contact_phone"><?php _e('Phone', 'happy-place'); ?></label></th>
            <td>
                <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo esc_attr($meta['_contact_phone']); ?>" class="regular-text" readonly>
                <?php if ($meta['_contact_phone']) : ?>
                    <p class="description">
                        <a href="tel:<?php echo esc_attr($meta['_contact_phone']); ?>" class="button">
                            <span class="dashicons dashicons-phone"></span>
                            <?php _e('Call', 'happy-place'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="contact_subject"><?php _e('Subject', 'happy-place'); ?></label></th>
            <td>
                <input type="text" id="contact_subject" name="contact_subject" value="<?php echo esc_attr($meta['_contact_subject']); ?>" class="regular-text" readonly>
            </td>
        </tr>
        <tr>
            <th><label for="contact_interest"><?php _e('Interest', 'happy-place'); ?></label></th>
            <td>
                <input type="text" id="contact_interest" name="contact_interest" value="<?php echo esc_attr($meta['_contact_interest']); ?>" class="regular-text" readonly>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Add sortable columns
 *
 * @param array $columns Sortable columns
 * @return array Modified sortable columns
 */
function hph_contact_sortable_columns($columns) {
    $columns['contact_name'] = 'contact_name';
    $columns['contact_email'] = 'contact_email';
    $columns['contact_interest'] = 'contact_interest';
    return $columns;
}
add_filter('manage_edit-hph_contact_sortable_columns', 'hph_contact_sortable_columns');

/**
 * Handle custom column sorting
 *
 * @param WP_Query $query Query object
 */
function hph_contact_sort_columns($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') !== 'hph_contact') {
        return;
    }

    $orderby = $query->get('orderby');

    switch ($orderby) {
        case 'contact_name':
            $query->set('meta_key', '_contact_name');
            $query->set('orderby', 'meta_value');
            break;
        case 'contact_email':
            $query->set('meta_key', '_contact_email');
            $query->set('orderby', 'meta_value');
            break;
        case 'contact_interest':
            $query->set('meta_key', '_contact_interest');
            $query->set('orderby', 'meta_value');
            break;
    }
}
add_action('pre_get_posts', 'hph_contact_sort_columns');
