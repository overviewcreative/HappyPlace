<?php
/**
 * The header for our theme
 *
 * @package HappyPlace
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
    <meta property="og:title" content="<?php wp_title('|', true, 'right') . bloginfo('name'); ?>">
    <meta property="og:description" content="<?php echo esc_attr(get_bloginfo('description')); ?>">
    <?php if (has_post_thumbnail()) : ?>
        <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo esc_url(get_permalink()); ?>">
    <meta property="twitter:title" content="<?php wp_title('|', true, 'right') . bloginfo('name'); ?>">
    <meta property="twitter:description" content="<?php echo esc_attr(get_bloginfo('description')); ?>">
    <?php if (has_post_thumbnail()) : ?>
        <meta property="twitter:image" content="<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>">
    <?php endif; ?>
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?> itemscope itemtype="https://schema.org/WebPage">
    <?php wp_body_open(); ?>
    
    <div class="hph-site-wrapper">
        <header class="hph-header" itemscope itemtype="https://schema.org/WPHeader">
            <div class="hph-header-top">
                <div class="hph-header-container">
                    <div class="hph-contact-info">
                        <?php
                        $phone = get_theme_mod('contact_phone', '');
                        $email = get_theme_mod('contact_email', '');
                        
                        if ($phone) {
                            echo '<span class="hph-phone">';
                            echo '<i class="fas fa-phone"></i> ';
                            echo '<a href="tel:' . esc_attr(preg_replace('/[^0-9]/', '', $phone)) . '">';
                            echo esc_html($phone);
                            echo '</a>';
                            echo '</span>';
                        }
                        
                        if ($email) {
                            echo '<span class="hph-email">';
                            echo '<i class="fas fa-envelope"></i> ';
                            echo '<a href="mailto:' . esc_attr($email) . '">';
                            echo esc_html($email);
                            echo '</a>';
                            echo '</span>';
                        }
                        ?>
                    </div>
                    <div class="hph-social-links">
                        <?php
                        $social_links = [
                            'facebook' => get_theme_mod('social_facebook', ''),
                            'instagram' => get_theme_mod('social_instagram', ''),
                            'twitter' => get_theme_mod('social_twitter', ''),
                            'linkedin' => get_theme_mod('social_linkedin', '')
                        ];

                        foreach ($social_links as $platform => $url) {
                            if ($url) {
                                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">';
                                echo '<i class="fab fa-' . esc_attr($platform) . '"></i>';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="hph-header-main">
                <div class="hph-header-container">
                    <div class="hph-site-branding">
                        <?php
                        if (has_custom_logo()) {
                            the_custom_logo();
                        } else {
                            echo '<h1 class="hph-site-title">';
                            echo '<a href="' . esc_url(home_url('/')) . '">';
                            echo get_bloginfo('name');
                            echo '</a>';
                            echo '</h1>';
                            
                            $description = get_bloginfo('description');
                            if ($description) {
                                echo '<p class="hph-site-description">' . esc_html($description) . '</p>';
                            }
                        }
                        ?>
                    </div>

                    <nav id="hph-primary-navigation" class="hph-main-navigation">
                        <button class="hph-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                            <span class="screen-reader-text"><?php esc_html_e('Menu', 'happy-place'); ?></span>
                            <span class="hph-menu-icon"></span>
                        </button>
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'primary',
                            'menu_id' => 'primary-menu',
                            'menu_class' => 'hph-menu',
                            'container' => false,
                            'fallback_cb' => false
                        ]);
                        ?>
                    </nav>

                    <div class="hph-header-actions">
                        <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>" class="hph-account-link">
                                <i class="fas fa-user"></i>
                                <span class="screen-reader-text"><?php esc_html_e('My Account', 'happy-place'); ?></span>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url(wp_login_url()); ?>" class="hph-login-link">
                                <i class="fas fa-sign-in-alt"></i>
                                <span><?php esc_html_e('Login', 'happy-place'); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="hph-site-main">
