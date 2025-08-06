<?php
/**
 * Open House Form Template Part
 * 
 * @package HappyPlace
 */

$open_house_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$open_house = $open_house_id ? get_post($open_house_id) : null;
$open_house_data = $open_house ? get_fields($open_house_id) : [];
?>

<form id="hph-open-house-form" class="hph-form" data-open-house-id="<?php echo esc_attr($open_house_id); ?>">
    <?php wp_nonce_field('hph_open_house_form', 'hph_open_house_nonce'); ?>
    
    <div class="hph-form-grid">
        <!-- Basic Information -->
        <div class="hph-form-section">
            <h3>Open House Details</h3>
            
            <div class="hph-form-row">
                <label for="open_house_listing">Select Listing</label>
                <select name="open_house_listing" id="open_house_listing" required>
                    <option value="">Select a Listing</option>
                    <?php
                    $listings = hph_get_agent_listings(get_current_user_id(), ['active', 'coming-soon']);
                    foreach ($listings as $listing) :
                    ?>
                        <option value="<?php echo esc_attr($listing->ID); ?>" 
                            <?php selected($listing->ID, $open_house_data['listing'] ?? 0); ?>>
                            <?php echo esc_html($listing->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="hph-form-row hph-form-row--columns">
                <div>
                    <label for="open_house_date">Date</label>
                    <input type="date" name="open_house_date" id="open_house_date" 
                        value="<?php echo esc_attr($open_house_data['date'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="open_house_start_time">Start Time</label>
                    <input type="time" name="open_house_start_time" id="open_house_start_time" 
                        value="<?php echo esc_attr($open_house_data['start_time'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="open_house_end_time">End Time</label>
                    <input type="time" name="open_house_end_time" id="open_house_end_time" 
                        value="<?php echo esc_attr($open_house_data['end_time'] ?? ''); ?>" required>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="hph-form-section">
            <h3>Additional Information</h3>
            
            <div class="hph-form-row">
                <label for="open_house_notes">Notes</label>
                <textarea name="open_house_notes" id="open_house_notes" rows="4"><?php 
                    echo esc_textarea($open_house_data['notes'] ?? ''); 
                ?></textarea>
            </div>

            <div class="hph-form-row">
                <label>
                    <input type="checkbox" name="open_house_refreshments" id="open_house_refreshments" 
                        <?php checked($open_house_data['refreshments'] ?? false); ?>>
                    Refreshments will be served
                </label>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="hph-form-actions">
        <button type="submit" class="hph-button hph-button--primary">
            <?php echo $open_house_id ? 'Update Open House' : 'Schedule Open House'; ?>
        </button>
    </div>
</form>
