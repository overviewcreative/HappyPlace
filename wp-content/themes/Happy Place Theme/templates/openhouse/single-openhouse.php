<?php

/**
 * Single Open House Template
 *
 * @package HappyPlace
 */

get_header();
?>

<main class="hph-site-main hph-site-main--single">
    <div class="hph-container">
        <?php
        while (have_posts()) :
            the_post();

            // Get the associated listing
            $listing_id = get_post_meta(get_the_ID(), 'associated_listing', true);
        ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('hph-single-openhouse'); ?>>
                <header class="hph-entry-header">
                    <h1 class="hph-entry-title"><?php the_title(); ?></h1>

                    <div class="hph-openhouse-meta">
                        <?php
                        $date = get_post_meta(get_the_ID(), 'open_house_date', true);
                        $start_time = get_post_meta(get_the_ID(), 'start_time', true);
                        $end_time = get_post_meta(get_the_ID(), 'end_time', true);
                        $host = get_post_meta(get_the_ID(), 'host_agent', true);

                        if ($date) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-calendar hph-icon"></i>
                                <span><?php echo esc_html($date); ?></span>
                            </div>
                        <?php endif;

                        if ($start_time && $end_time) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-clock hph-icon"></i>
                                <span><?php printf(
                                            esc_html__('%s to %s', 'happy-place'),
                                            esc_html($start_time),
                                            esc_html($end_time)
                                        ); ?></span>
                            </div>
                        <?php endif;

                        if ($host) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-user hph-icon"></i>
                                <span><?php echo esc_html($host); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($listing_id) : ?>
                    <div class="hph-associated-listing">
                        <h2><?php esc_html_e('Property Details', 'happy-place'); ?></h2>
                        <?php
                        $listing = get_post($listing_id);
                        if ($listing) {
                            setup_postdata($listing);
                            get_template_part('templates/listing/content', 'listing');
                            wp_reset_postdata();
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="hph-entry-content">
                    <?php the_content(); ?>
                </div>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="hph-featured-image">
                        <?php the_post_thumbnail('full'); ?>
                    </div>
                <?php endif; ?>
            </article>

        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
