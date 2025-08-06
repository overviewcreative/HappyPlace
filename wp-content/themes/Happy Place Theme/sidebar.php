<?php
/**
 * The sidebar containing the main widget area
 *
 * @package Happy_Place_Theme
 */

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside id="secondary" class="widget-area">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside><!-- #secondary -->