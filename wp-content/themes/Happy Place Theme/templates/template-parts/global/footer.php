<?php
/**
 * Template Part: Footer
 * 
 * This template part displays the site footer with widgets, navigation, and copyright.
 * 
 * @package HappyPlace
 */
?>

<footer id="colophon" class="site-footer">
    <div class="footer-widgets">
        <div class="container">
            <div class="footer-widgets__grid">
                <div class="footer-widget">
                    <?php if (is_active_sidebar('footer-1')) : ?>
                        <?php dynamic_sidebar('footer-1'); ?>
                    <?php endif; ?>
                </div>

                <div class="footer-widget">
                    <?php if (is_active_sidebar('footer-2')) : ?>
                        <?php dynamic_sidebar('footer-2'); ?>
                    <?php endif; ?>
                </div>

                <div class="footer-widget">
                    <?php if (is_active_sidebar('footer-3')) : ?>
                        <?php dynamic_sidebar('footer-3'); ?>
                    <?php endif; ?>
                </div>

                <div class="footer-widget">
                    <?php if (is_active_sidebar('footer-4')) : ?>
                        <?php dynamic_sidebar('footer-4'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom__content">
                <div class="footer-copyright">
                    <?php
                    printf(
                        /* translators: %1$s: Current year, %2$s: Blog name */
                        esc_html__('© %1$s %2$s. All rights reserved.', 'happy-place'),
                        date_i18n('Y'),
                        get_bloginfo('name')
                    );
                    ?>
                </div>

                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container' => false,
                    'menu_class' => 'footer-menu',
                    'depth' => 1,
                    'fallback_cb' => false
                ));
                ?>
            </div>
        </div>
    </div>
</footer>
