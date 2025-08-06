<?php
/**
 * The footer for our theme
 *
 * @package HappyPlace
 */
?>
        </main>

        <footer class="hph-footer" itemscope itemtype="https://schema.org/WPFooter">
            <div class="hph-footer-container">
                <div class="hph-footer-column" itemscope itemtype="https://schema.org/RealEstateAgent">
                    <div class="hph-footer-brand">
                        <?php 
                        // Logo with fallback to site title
                        $logo = get_theme_mod('custom_logo');
                        if ($logo) {
                            $logo_image = wp_get_attachment_image_src($logo, 'full');
                            echo '<img itemprop="logo" src="' . esc_url($logo_image[0]) . '" alt="' . get_bloginfo('name') . '">';
                        } else {
                            echo '<h2 itemprop="name">' . get_bloginfo('name') . '</h2>';
                        }
                        ?>
                        <p itemprop="description"><?php bloginfo('description'); ?></p>
                    </div>
                    <div class="hph-footer-contact">
                        <?php 
                        $phone = get_theme_mod('contact_phone', '');
                        $email = get_theme_mod('contact_email', '');
                        
                        if ($phone) {
                            echo '<p>Call us: <a href="tel:' . esc_attr(preg_replace('/[^0-9]/', '', $phone)) . '" itemprop="telephone">' . esc_html($phone) . '</a></p>';
                        }
                        
                        if ($email) {
                            echo '<p>Email: <a href="mailto:' . esc_attr($email) . '" itemprop="email">' . esc_html($email) . '</a></p>';
                        }

                        // Get business address from theme customizer
                        $address = get_theme_mod('business_address', '');
                        if ($address) {
                            echo '<address itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                            echo esc_html($address);
                            echo '</address>';
                        }
                        ?>
                    </div>
                </div>

                <div class="hph-footer-column">
                    <h3 class="hph-footer-heading"><?php esc_html_e('Quick Links', 'happy-place'); ?></h3>
                    <?php 
                    wp_nav_menu([
                        'theme_location' => 'footer-links-1',
                        'container' => false,
                        'menu_class' => 'hph-footer-links',
                        'fallback_cb' => false
                    ]); 
                    ?>
                </div>

                <div class="hph-footer-column">
                    <h3 class="hph-footer-heading"><?php esc_html_e('Properties', 'happy-place'); ?></h3>
                    <?php 
                    wp_nav_menu([
                        'theme_location' => 'footer-links-2',
                        'container' => false,
                        'menu_class' => 'hph-footer-links',
                        'fallback_cb' => false
                    ]); 
                    ?>
                </div>

                <div class="hph-footer-column">
                    <h3 class="hph-footer-heading"><?php esc_html_e('Communities', 'happy-place'); ?></h3>
                    <?php 
                    wp_nav_menu([
                        'theme_location' => 'footer-links-3',
                        'container' => false,
                        'menu_class' => 'hph-footer-links',
                        'fallback_cb' => false
                    ]); 
                    ?>
                </div>

                <div class="hph-footer-column">
                    <h3 class="hph-footer-heading"><?php esc_html_e('Connect', 'happy-place'); ?></h3>
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
                                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" class="hph-social-link hph-social-' . esc_attr($platform) . '">';
                                echo '<i class="fab fa-' . esc_attr($platform) . '"></i>';
                                echo '<span class="screen-reader-text">' . esc_html(ucfirst($platform)) . '</span>';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                    <div class="hph-newsletter-signup">
                        <h4><?php esc_html_e('Stay Updated', 'happy-place'); ?></h4>
                        <?php 
                        if (function_exists('mc4wp_show_form')) {
                            mc4wp_show_form();
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="hph-footer-bottom">
                <div class="hph-footer-container">
                    <div class="hph-footer-copyright">
                        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'happy-place'); ?></p>
                    </div>
                    
                    <div class="hph-footer-legal">
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'footer-legal',
                            'container' => false,
                            'menu_class' => 'hph-legal-links',
                            'fallback_cb' => false,
                            'depth' => 1
                        ]);
                        ?>
                    </div>

                    <?php if (get_theme_mod('show_gdpr_notice', true)) : ?>
                    <div class="hph-cookie-notice" id="cookie-notice">
                        <p><?php esc_html_e('We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.', 'happy-place'); ?></p>
                        <button class="hph-accept-cookies" id="accept-cookies"><?php esc_html_e('Accept', 'happy-place'); ?></button>
                        <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" class="hph-cookie-learn-more"><?php esc_html_e('Learn more', 'happy-place'); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
    </div><!-- .hph-site-wrapper -->
    <?php wp_footer(); ?>
</body>
</html>
