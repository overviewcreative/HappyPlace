<?php
/**
 * Quick actions component
 */

$listing_id = get_the_ID();
?>

<div class="sidebar-widget quick-actions">
    <h3 class="widget-title">Quick Actions</h3>
    
    <div class="action-buttons">
        <button class="action-btn btn-primary save-listing" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>">
            💾 Save Listing
        </button>
        
        <button class="action-btn btn-outline share-listing" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>">
            📤 Share
        </button>
        
        <button class="action-btn btn-outline price-history" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>">
            📊 Price History
        </button>
        
        <button class="action-btn btn-outline print-listing" 
                onclick="window.print()">
            📋 Print Details
        </button>
    </div>
</div>
