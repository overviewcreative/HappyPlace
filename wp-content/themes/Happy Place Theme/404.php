<?php

/**
 * The template for displaying 404 pages (not found)
 *
 * @package HappyPlace
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title">
                    <?php esc_html_e('Oops! That page can&rsquo;t be found.', 'happy-place'); ?>
                </h1>
            </header>

            <div class="page-content">
                <p>
                    <?php esc_html_e('It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'happy-place'); ?>
                </p>

                <?php get_template_part('templates/partials/global/search-form'); ?>

                <div class="error-404__content">
                    <div class="error-404__recent">
                        <h2><?php esc_html_e('Recent Listings', 'happy-place'); ?></h2>
                        <?php
                        $recent_listings = new WP_Query(array(
                            'post_type' => 'listing',
                            'posts_per_page' => 3,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ));

                        if ($recent_listings->have_posts()) :
                            echo '<div class="listings-grid listings-grid--small">';
                            while ($recent_listings->have_posts()) :
                                $recent_listings->the_post();
                                get_template_part('templates/partials/listing/card', 'listing');
                            endwhile;
                            echo '</div>';
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>

                    <div class="error-404__explore">
                        <h2><?php esc_html_e('Explore', 'happy-place'); ?></h2>
                        <ul class="explore-links">
                            <li>
                                <a href="<?php echo esc_url(get_post_type_archive_link('listing')); ?>">
                                    <i class="fas fa-home"></i>
                                    <?php esc_html_e('All Listings', 'happy-place'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(get_post_type_archive_link('agent')); ?>">
                                    <i class="fas fa-user-tie"></i>
                                    <?php esc_html_e('Our Agents', 'happy-place'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(get_post_type_archive_link('community')); ?>">
                                    <i class="fas fa-map-marked-alt"></i>
                                    <?php esc_html_e('Communities', 'happy-place'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">
                                    <i class="fas fa-envelope"></i>
                                    <?php esc_html_e('Contact Us', 'happy-place'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
get_footer();
