<?php

/**
 * Custom Meta Boxes
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Meta Boxes Class
 */
class Meta_Boxes
{
    /**
     * Initialize the meta boxes
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
    }

    /**
     * Register meta boxes
     */
    public function register_meta_boxes()
    {
        // Listing Details
        add_meta_box(
            'listing_details',
            'Listing Details',
            array($this, 'render_listing_details'),
            'listing',
            'normal',
            'high'
        );

        // Open House Schedule
        add_meta_box(
            'open_house_schedule',
            'Open House Schedule',
            array($this, 'render_open_house_schedule'),
            'listing',
            'normal',
            'default'
        );

        // Lead Information
        add_meta_box(
            'lead_information',
            'Lead Information',
            array($this, 'render_lead_information'),
            'lead',
            'normal',
            'high'
        );

        // Transaction Details
        add_meta_box(
            'transaction_details',
            'Transaction Details',
            array($this, 'render_transaction_details'),
            'transaction',
            'normal',
            'high'
        );
    }

    /**
     * Render listing details meta box
     *
     * @param \WP_Post $post Post object.
     */
    public function render_listing_details($post)
    {
        // Add nonce for security
        wp_nonce_field('happy_place_listing_details', 'listing_details_nonce');

        // Get current values
        $price = get_post_meta($post->ID, 'listing_price', true);
        $beds = get_post_meta($post->ID, 'listing_bedrooms', true);
        $baths = get_post_meta($post->ID, 'listing_bathrooms', true);
        $sqft = get_post_meta($post->ID, 'listing_square_feet', true);
        $garage = get_post_meta($post->ID, 'listing_garage', true);
        $lot_size = get_post_meta($post->ID, 'listing_lot_size', true);
        $year_built = get_post_meta($post->ID, 'listing_year_built', true);

        // Address fields
        $address = get_post_meta($post->ID, 'listing_address', true);
        $unit = get_post_meta($post->ID, 'listing_unit', true);
        $city = get_post_meta($post->ID, 'listing_city', true);
        $state = get_post_meta($post->ID, 'listing_state', true);
        $zip = get_post_meta($post->ID, 'listing_zip', true);

        // Render form fields
?>
        <div class="happy-place-meta-box">
            <div class="meta-row">
                <div class="meta-cell">
                    <label for="listing_price">Price:</label>
                    <input type="number" id="listing_price" name="listing_price" value="<?php echo esc_attr($price); ?>" step="1000">
                </div>
                <div class="meta-cell">
                    <label for="listing_bedrooms">Bedrooms:</label>
                    <input type="number" id="listing_bedrooms" name="listing_bedrooms" value="<?php echo esc_attr($beds); ?>" step="1">
                </div>
                <div class="meta-cell">
                    <label for="listing_bathrooms">Bathrooms:</label>
                    <input type="number" id="listing_bathrooms" name="listing_bathrooms" value="<?php echo esc_attr($baths); ?>" step="0.5">
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="listing_square_feet">Square Feet:</label>
                    <input type="number" id="listing_square_feet" name="listing_square_feet" value="<?php echo esc_attr($sqft); ?>" step="1">
                </div>
                <div class="meta-cell">
                    <label for="listing_garage">Garage Spaces:</label>
                    <input type="number" id="listing_garage" name="listing_garage" value="<?php echo esc_attr($garage); ?>" step="1">
                </div>
                <div class="meta-cell">
                    <label for="listing_lot_size">Lot Size (acres):</label>
                    <input type="number" id="listing_lot_size" name="listing_lot_size" value="<?php echo esc_attr($lot_size); ?>" step="0.01">
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="listing_year_built">Year Built:</label>
                    <input type="number" id="listing_year_built" name="listing_year_built" value="<?php echo esc_attr($year_built); ?>" step="1">
                </div>
            </div>

            <h4>Address Information</h4>
            <div class="meta-row">
                <div class="meta-cell full-width">
                    <label for="listing_address">Street Address:</label>
                    <input type="text" id="listing_address" name="listing_address" value="<?php echo esc_attr($address); ?>">
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="listing_unit">Unit/Apt #:</label>
                    <input type="text" id="listing_unit" name="listing_unit" value="<?php echo esc_attr($unit); ?>">
                </div>
                <div class="meta-cell">
                    <label for="listing_city">City:</label>
                    <input type="text" id="listing_city" name="listing_city" value="<?php echo esc_attr($city); ?>">
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="listing_state">State:</label>
                    <input type="text" id="listing_state" name="listing_state" value="<?php echo esc_attr($state); ?>">
                </div>
                <div class="meta-cell">
                    <label for="listing_zip">ZIP Code:</label>
                    <input type="text" id="listing_zip" name="listing_zip" value="<?php echo esc_attr($zip); ?>">
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render open house schedule meta box
     *
     * @param \WP_Post $post Post object.
     */
    public function render_open_house_schedule($post)
    {
        wp_nonce_field('happy_place_open_house', 'open_house_nonce');

        $schedules = get_post_meta($post->ID, 'open_house_schedules', true);
        if (! is_array($schedules)) {
            $schedules = array();
        }
    ?>
        <div class="open-house-schedules">
            <div id="open-house-list">
                <?php foreach ($schedules as $index => $schedule) : ?>
                    <div class="schedule-row">
                        <input type="date" name="open_house_date[]" value="<?php echo esc_attr($schedule['date']); ?>">
                        <input type="time" name="open_house_start_time[]" value="<?php echo esc_attr($schedule['start_time']); ?>">
                        <input type="time" name="open_house_end_time[]" value="<?php echo esc_attr($schedule['end_time']); ?>">
                        <button type="button" class="remove-schedule">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-open-house">Add Open House</button>
        </div>
    <?php
    }

    /**
     * Render lead information meta box
     *
     * @param \WP_Post $post Post object.
     */
    public function render_lead_information($post)
    {
        wp_nonce_field('happy_place_lead_info', 'lead_info_nonce');

        $name = get_post_meta($post->ID, 'lead_name', true);
        $email = get_post_meta($post->ID, 'lead_email', true);
        $phone = get_post_meta($post->ID, 'lead_phone', true);
        $status = get_post_meta($post->ID, 'lead_status', true);
        $source = get_post_meta($post->ID, 'lead_source', true);
        $notes = get_post_meta($post->ID, 'lead_notes', true);

    ?>
        <div class="lead-information">
            <div class="meta-row">
                <div class="meta-cell">
                    <label for="lead_name">Name:</label>
                    <input type="text" id="lead_name" name="lead_name" value="<?php echo esc_attr($name); ?>">
                </div>
                <div class="meta-cell">
                    <label for="lead_email">Email:</label>
                    <input type="email" id="lead_email" name="lead_email" value="<?php echo esc_attr($email); ?>">
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="lead_phone">Phone:</label>
                    <input type="tel" id="lead_phone" name="lead_phone" value="<?php echo esc_attr($phone); ?>">
                </div>
                <div class="meta-cell">
                    <label for="lead_status">Status:</label>
                    <select id="lead_status" name="lead_status">
                        <option value="new" <?php selected($status, 'new'); ?>>New</option>
                        <option value="contacted" <?php selected($status, 'contacted'); ?>>Contacted</option>
                        <option value="qualified" <?php selected($status, 'qualified'); ?>>Qualified</option>
                        <option value="unqualified" <?php selected($status, 'unqualified'); ?>>Unqualified</option>
                        <option value="converted" <?php selected($status, 'converted'); ?>>Converted</option>
                    </select>
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell full-width">
                    <label for="lead_source">Source:</label>
                    <select id="lead_source" name="lead_source">
                        <option value="website" <?php selected($source, 'website'); ?>>Website</option>
                        <option value="listing_inquiry" <?php selected($source, 'listing_inquiry'); ?>>Listing Inquiry</option>
                        <option value="open_house" <?php selected($source, 'open_house'); ?>>Open House</option>
                        <option value="referral" <?php selected($source, 'referral'); ?>>Referral</option>
                        <option value="other" <?php selected($source, 'other'); ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell full-width">
                    <label for="lead_notes">Notes:</label>
                    <textarea id="lead_notes" name="lead_notes" rows="5"><?php echo esc_textarea($notes); ?></textarea>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render transaction details meta box
     *
     * @param \WP_Post $post Post object.
     */
    public function render_transaction_details($post)
    {
        wp_nonce_field('happy_place_transaction', 'transaction_nonce');

        $status = get_post_meta($post->ID, 'transaction_status', true);
        $type = get_post_meta($post->ID, 'transaction_type', true);
        $price = get_post_meta($post->ID, 'transaction_price', true);
        $commission = get_post_meta($post->ID, 'transaction_commission', true);
        $closing_date = get_post_meta($post->ID, 'transaction_closing_date', true);

    ?>
        <div class="transaction-details">
            <div class="meta-row">
                <div class="meta-cell">
                    <label for="transaction_status">Status:</label>
                    <select id="transaction_status" name="transaction_status">
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                        <option value="active" <?php selected($status, 'active'); ?>>Active</option>
                        <option value="closed" <?php selected($status, 'closed'); ?>>Closed</option>
                        <option value="cancelled" <?php selected($status, 'cancelled'); ?>>Cancelled</option>
                    </select>
                </div>
                <div class="meta-cell">
                    <label for="transaction_type">Type:</label>
                    <select id="transaction_type" name="transaction_type">
                        <option value="sale" <?php selected($type, 'sale'); ?>>Sale</option>
                        <option value="purchase" <?php selected($type, 'purchase'); ?>>Purchase</option>
                        <option value="lease" <?php selected($type, 'lease'); ?>>Lease</option>
                    </select>
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="transaction_price">Price:</label>
                    <input type="number" id="transaction_price" name="transaction_price" value="<?php echo esc_attr($price); ?>" step="1000">
                </div>
                <div class="meta-cell">
                    <label for="transaction_commission">Commission (%):</label>
                    <input type="number" id="transaction_commission" name="transaction_commission" value="<?php echo esc_attr($commission); ?>" step="0.1">
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <label for="transaction_closing_date">Closing Date:</label>
                    <input type="date" id="transaction_closing_date" name="transaction_closing_date" value="<?php echo esc_attr($closing_date); ?>">
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Save meta box data
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function save_meta_boxes($post_id, $post)
    {
        // Check if our nonce is set for each meta box
        $nonces = array(
            'listing_details' => 'listing_details_nonce',
            'open_house'      => 'open_house_nonce',
            'lead_info'       => 'lead_info_nonce',
            'transaction'     => 'transaction_nonce',
        );

        foreach ($nonces as $action => $nonce) {
            if (! isset($_POST[$nonce])) {
                continue;
            }

            if (! wp_verify_nonce($_POST[$nonce], 'happy_place_' . $action)) {
                continue;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                continue;
            }

            if (! current_user_can('edit_post', $post_id)) {
                continue;
            }

            $method = 'save_' . str_replace('_', '-', $action);
            if (method_exists($this, $method)) {
                $this->$method($post_id);
            }
        }
    }

    /**
     * Save listing details
     *
     * @param int $post_id Post ID.
     */
    private function save_listing_details($post_id)
    {
        $fields = array(
            'listing_price',
            'listing_bedrooms',
            'listing_bathrooms',
            'listing_square_feet',
            'listing_garage',
            'listing_lot_size',
            'listing_year_built',
            'listing_address',
            'listing_unit',
            'listing_city',
            'listing_state',
            'listing_zip',
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * Save open house schedule
     *
     * @param int $post_id Post ID.
     */
    private function save_open_house($post_id)
    {
        if (! isset($_POST['open_house_date'])) {
            return;
        }

        $schedules = array();
        $dates = $_POST['open_house_date'];
        $start_times = $_POST['open_house_start_time'];
        $end_times = $_POST['open_house_end_time'];

        for ($i = 0; $i < count($dates); $i++) {
            if (empty($dates[$i])) {
                continue;
            }

            $schedules[] = array(
                'date'       => sanitize_text_field($dates[$i]),
                'start_time' => sanitize_text_field($start_times[$i]),
                'end_time'   => sanitize_text_field($end_times[$i]),
            );
        }

        update_post_meta($post_id, 'open_house_schedules', $schedules);
    }

    /**
     * Save lead information
     *
     * @param int $post_id Post ID.
     */
    private function save_lead_info($post_id)
    {
        $fields = array(
            'lead_name',
            'lead_email',
            'lead_phone',
            'lead_status',
            'lead_source',
            'lead_notes',
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'lead_notes') {
                    update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
                } else {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }

    /**
     * Save transaction details
     *
     * @param int $post_id Post ID.
     */
    private function save_transaction($post_id)
    {
        $fields = array(
            'transaction_status',
            'transaction_type',
            'transaction_price',
            'transaction_commission',
            'transaction_closing_date',
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}
