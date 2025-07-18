<?php
/**
 * Single listing header
 */

$listing_id = get_the_ID();
$price = get_post_meta($listing_id, 'listing_price', true);
$status = get_post_meta($listing_id, 'listing_status', true);
$address = get_post_meta($listing_id, 'listing_address', true);
$mls_number = get_post_meta($listing_id, 'mls_number', true);
?>

<div class="listing-header">
    <div class="container">
        <h1 class="listing-title"><?php the_title(); ?></h1>
        
        <?php if ($address) : ?>
            <div class="listing-address">📍 <?php echo esc_html($address); ?></div>
        <?php endif; ?>
        
        <div class="listing-meta">
            <?php if ($price) : ?>
                <div class="price">$<?php echo number_format($price); ?></div>
            <?php endif; ?>
            
            <?php if ($status) : ?>
                <div class="listing-status <?php echo esc_attr(strtolower($status)); ?>">
                    <?php echo esc_html($status); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($mls_number) : ?>
                <div class="listing-id">MLS# <?php echo esc_html($mls_number); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
