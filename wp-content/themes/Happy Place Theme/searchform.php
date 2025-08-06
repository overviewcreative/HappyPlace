<?php
/**
 * Template for displaying search forms
 *
 * @package Happy_Place_Theme
 */

$unique_id = wp_unique_id('search-form-');
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label for="<?php echo esc_attr($unique_id); ?>">
        <span class="screen-reader-text"><?php echo _x('Search for:', 'label', 'happy-place'); ?></span>
    </label>
    <input type="search" id="<?php echo esc_attr($unique_id); ?>" class="search-field" placeholder="<?php echo esc_attr_x('Search &hellip;', 'placeholder', 'happy-place'); ?>" value="<?php echo get_search_query(); ?>" name="s" />
    <button type="submit" class="search-submit">
        <span class="screen-reader-text"><?php echo _x('Search', 'submit button', 'happy-place'); ?></span>
        <svg class="icon icon-search" aria-hidden="true" role="img">
            <use href="#icon-search"></use>
        </svg>
    </button>
</form>