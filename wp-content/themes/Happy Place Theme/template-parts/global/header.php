<?php

/**
 * Template Part: Header
 * 
 * This template part displays the site header with navigation and search.
 * 
 * @package HappyPlace
 */
?>

<header id="masthead" class="site-header">
    <div class="header-top">
        <div class="container">
            <?php if ($phone = get_theme_mod('contact_phone')) : ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>" class="header-contact">
                    <i class="fas fa-phone"></i>
                    <?php echo esc_html($phone); ?>
                </a>
            <?php endif; ?>

            <?php if ($email = get_theme_mod('contact_email')) : ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="header-contact">
                    <i class="fas fa-envelope"></i>
                    <?php echo esc_html($email); ?>
                </a>
            <?php endif; ?>

            <?php
            wp_nav_menu(array(
                'theme_location' => 'top-menu',
                'container' => false,
                'menu_class' => 'top-menu',
                'fallback_cb' => false
            ));
            ?>
        </div>
    </div>

    <div class="header-main">
        <div class="container">
            <div class="site-branding">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            <?php echo esc_html(get_bloginfo('name')); ?>
                        </a>
                    </h1>
                <?php endif; ?>
            </div>

            <nav id="site-navigation" class="main-navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text">Menu</span>
                    <span class="menu-icon"></span>
                </button>

                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id' => 'primary-menu',
                    'container' => false,
                    'menu_class' => 'primary-menu'
                ));
                ?>
            </nav>

            <?php get_template_part('templates/template-parts/global/search-form'); ?>
        </div>
    </div>
</header>