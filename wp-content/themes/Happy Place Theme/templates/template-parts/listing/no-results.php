<?php
/**
 * Template part for displaying no listing results
 *
 * @package HappyPlace
 */
?>

<div class="hph-listings-no-results">
    <div class="hph-listings-no-results__icon">
        <i class="fas fa-search" aria-hidden="true"></i>
    </div>
    
    <h2 class="hph-listings-no-results__title">
        <?php esc_html_e('No Properties Found', 'happy-place'); ?>
    </h2>
    
    <div class="hph-listings-no-results__content">
        <p><?php esc_html_e('We couldn\'t find any properties matching your search criteria.', 'happy-place'); ?></p>
        
        <div class="hph-listings-no-results__suggestions">
            <h3><?php esc_html_e('Suggestions:', 'happy-place'); ?></h3>
            <ul>
                <li><?php esc_html_e('Try expanding your price range', 'happy-place'); ?></li>
                <li><?php esc_html_e('Consider different locations', 'happy-place'); ?></li>
                <li><?php esc_html_e('Adjust your search filters', 'happy-place'); ?></li>
            </ul>
        </div>
        
        <div class="hph-listings-no-results__actions">
            <button type="button" class="hph-button hph-button--secondary js-clear-filters">
                <?php esc_html_e('Clear All Filters', 'happy-place'); ?>
            </button>
            
            <a href="<?php echo esc_url(get_post_type_archive_link('listing')); ?>" class="hph-button">
                <?php esc_html_e('View All Properties', 'happy-place'); ?>
            </a>
        </div>
        
        <div class="hph-listings-no-results__help">
            <p>
                <?php esc_html_e('Need assistance? Contact our team:', 'happy-place'); ?>
                <a href="<?php echo esc_url(get_theme_mod('contact_page_url', '#')); ?>" class="hph-link">
                    <?php esc_html_e('Get in Touch', 'happy-place'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
