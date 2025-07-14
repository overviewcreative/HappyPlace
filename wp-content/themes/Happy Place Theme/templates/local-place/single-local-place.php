<?php

/**
 * Single Place Template
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="single-place">
    <div class="hph-container">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('templates/local-place/content', 'local-place');

            // If comments are open or we have at least one comment, load up the comment template.
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;

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
                <div class="nearby-listings">
                    <h2><?php esc_html_e('Listings Near This Place', 'happy-place'); ?></h2>
                    <div class="listings-grid">
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

        <?php endwhile; ?>
    </div>
</div>

<?php
get_footer();
