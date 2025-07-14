<?php

/**
 * Profile Section
 *
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Profile Section Class
 */
class Profile_Section
{
    /**
     * Initialize the section
     */
    public function __construct()
    {
        add_action('wp_ajax_happy_place_update_profile', array($this, 'update_profile'));
        add_action('wp_ajax_happy_place_get_profile_data', array($this, 'get_profile_data'));
    }

    /**
     * Update profile
     */
    public function update_profile()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('edit_user')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = get_current_user_id();

        // Basic user data
        $userdata = array(
            'ID'            => $user_id,
            'display_name'  => sanitize_text_field($_POST['display_name']),
            'user_email'    => sanitize_email($_POST['email']),
            'user_url'      => esc_url_raw($_POST['website']),
        );

        // Update user
        $user_id = wp_update_user($userdata);

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Update professional info
        update_user_meta($user_id, 'agent_title', sanitize_text_field($_POST['title']));
        update_user_meta($user_id, 'agent_phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'agent_mobile', sanitize_text_field($_POST['mobile']));
        update_user_meta($user_id, 'agent_office', sanitize_text_field($_POST['office']));
        update_user_meta($user_id, 'agent_license', sanitize_text_field($_POST['license']));

        // Update social media links
        update_user_meta($user_id, 'agent_facebook', esc_url_raw($_POST['facebook']));
        update_user_meta($user_id, 'agent_twitter', esc_url_raw($_POST['twitter']));
        update_user_meta($user_id, 'agent_linkedin', esc_url_raw($_POST['linkedin']));
        update_user_meta($user_id, 'agent_instagram', esc_url_raw($_POST['instagram']));

        // Update biography
        update_user_meta($user_id, 'description', wp_kses_post($_POST['biography']));

        // Handle profile image upload
        if (! empty($_FILES['profile_image'])) {
            if (! function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $upload_overrides = array('test_form' => false);
            $uploaded_file = wp_handle_upload($_FILES['profile_image'], $upload_overrides);

            if (! isset($uploaded_file['error'])) {
                $attachment_id = $this->create_profile_image_attachment($uploaded_file);
                if ($attachment_id) {
                    update_user_meta($user_id, 'agent_profile_image', $attachment_id);
                }
            }
        }

        // Get updated profile data
        $profile_data = $this->get_profile_data(true);

        wp_send_json_success(array(
            'message' => 'Profile updated successfully',
            'profile' => $profile_data,
        ));
    }

    /**
     * Get profile data
     *
     * @param bool $return Whether to return the data instead of sending JSON response.
     * @return array|void
     */
    public function get_profile_data($return = false)
    {
        if (! $return) {
            // Verify nonce
            check_ajax_referer('happy_place_dashboard', 'nonce');
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (! $user) {
            if ($return) {
                return array();
            }
            wp_send_json_error('User not found');
        }

        $data = array(
            'basic' => array(
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'website'      => $user->user_url,
            ),
            'professional' => array(
                'title'        => get_user_meta($user_id, 'agent_title', true),
                'phone'        => get_user_meta($user_id, 'agent_phone', true),
                'mobile'       => get_user_meta($user_id, 'agent_mobile', true),
                'office'       => get_user_meta($user_id, 'agent_office', true),
                'license'      => get_user_meta($user_id, 'agent_license', true),
            ),
            'social' => array(
                'facebook'     => get_user_meta($user_id, 'agent_facebook', true),
                'twitter'      => get_user_meta($user_id, 'agent_twitter', true),
                'linkedin'     => get_user_meta($user_id, 'agent_linkedin', true),
                'instagram'    => get_user_meta($user_id, 'agent_instagram', true),
            ),
            'content' => array(
                'biography'    => get_user_meta($user_id, 'description', true),
            ),
            'media' => array(
                'profile_image' => $this->get_profile_image_data($user_id),
            ),
            'statistics' => array(
                'active_listings' => $this->get_active_listings_count($user_id),
                'total_sales'     => $this->get_total_sales($user_id),
                'avg_days_market' => $this->get_average_days_on_market($user_id),
                'client_reviews'  => $this->get_client_reviews_count($user_id),
            ),
        );

        if ($return) {
            return $data;
        }

        wp_send_json_success($data);
    }

    /**
     * Create profile image attachment
     *
     * @param array $upload_data Upload data from wp_handle_upload.
     * @return int|false
     */
    private function create_profile_image_attachment($upload_data)
    {
        $filename = $upload_data['file'];
        $filetype = wp_check_filetype(basename($filename), null);

        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $filename);

        if (! is_wp_error($attach_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);
            return $attach_id;
        }

        return false;
    }

    /**
     * Get profile image data
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_profile_image_data($user_id)
    {
        $image_id = get_user_meta($user_id, 'agent_profile_image', true);

        if (! $image_id) {
            return array(
                'url'    => get_avatar_url($user_id),
                'id'     => 0,
                'width'  => 96,
                'height' => 96,
            );
        }

        $image = wp_get_attachment_image_src($image_id, 'full');

        return array(
            'url'    => $image[0],
            'id'     => $image_id,
            'width'  => $image[1],
            'height' => $image[2],
        );
    }

    /**
     * Get active listings count
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function get_active_listings_count($user_id)
    {
        return count_user_posts($user_id, 'listing', true);
    }

    /**
     * Get total sales
     *
     * @param int $user_id User ID.
     * @return float
     */
    private function get_total_sales($user_id)
    {
        global $wpdb;

        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(meta_value)
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_author = %d
            AND p.post_type = 'listing'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'listing_price'",
            $user_id
        ));
    }

    /**
     * Get average days on market
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function get_average_days_on_market($user_id)
    {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(DATEDIFF(meta_sold.meta_value, p.post_date))
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} meta_sold ON p.ID = meta_sold.post_id
            WHERE p.post_author = %d
            AND p.post_type = 'listing'
            AND p.post_status = 'publish'
            AND meta_sold.meta_key = 'listing_sold_date'",
            $user_id
        ));

        return $result ? round($result) : 0;
    }

    /**
     * Get client reviews count
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function get_client_reviews_count($user_id)
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$wpdb->comments} c
            JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            WHERE p.post_author = %d
            AND p.post_type = 'listing'
            AND c.comment_approved = '1'
            AND c.comment_type = 'review'",
            $user_id
        ));
    }
}
