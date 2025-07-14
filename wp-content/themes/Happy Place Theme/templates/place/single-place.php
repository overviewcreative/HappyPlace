<?php

/**
 * Single Place Template
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

            // Get place details
            $address = get_post_meta(get_the_ID(), 'place_address', true);
            $website = get_post_meta(get_the_ID(), 'place_website', true);
            $phone = get_post_meta(get_the_ID(), 'place_phone', true);
            $hours = get_post_meta(get_the_ID(), 'place_hours', true);
            $rating = get_post_meta(get_the_ID(), 'place_rating', true);
        ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('hph-single-place'); ?>>
                <header class="hph-entry-header">
                    <h1 class="hph-entry-title"><?php the_title(); ?></h1>

                    <?php if (has_post_thumbnail()) : ?>
                        <div class="hph-featured-image">
                            <?php the_post_thumbnail('full'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="hph-place-meta">
                        <?php if ($address) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-map-marker-alt hph-icon"></i>
                                <span><?php echo esc_html($address); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($phone) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-phone hph-icon"></i>
                                <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if ($website) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-globe hph-icon"></i>
                                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e('Visit Website', 'happy-place'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($rating) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-star hph-icon"></i>
                                <span class="hph-rating"><?php echo esc_html($rating); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($hours) : ?>
                        <div class="hph-place-hours">
                            <h3><?php esc_html_e('Hours of Operation', 'happy-place'); ?></h3>
                            <div class="hph-hours-content">
                                <?php echo wp_kses_post($hours); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="hph-entry-content">
                    <?php the_content(); ?>
                </div>

                <?php
                // Display nearby listings
                $nearby_listings = get_posts(array(
                    'post_type' => 'listing',
                    'meta_query' => array(
                        array(
                            'key' => 'nearby_places',
                            'value' => get_the_ID(),
                            'compare' => 'LIKE',
                        ),
                    ),
                    'posts_per_page' => 6,
                ));

                if ($nearby_listings) :
                ?>
                    <div class="hph-nearby-listings">
                        <h2><?php esc_html_e('Properties Near This Location', 'happy-place'); ?></h2>
                        <div class="hph-grid hph-grid--listings">
                            <?php
                            foreach ($nearby_listings as $post) :
                                setup_postdata($post);
                                get_template_part('templates/listing/content', 'listing');
                            endforeach;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
