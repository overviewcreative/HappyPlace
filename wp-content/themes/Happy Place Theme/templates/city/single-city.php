<?php

/**
 * Single City Template
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="single-city">
    <div class="hph-container">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('templates/city/content', 'city');

            // Display neighborhoods/communities in this city
            $communities = get_posts(array(
                'post_type' => 'community',
                'meta_query' => array(
                    array(
                        'key' => 'city',
                        'value' => get_the_ID(),
                    ),
                ),
                'posts_per_page' => -1,
            ));

            if ($communities) :
        ?>
                <div class="city-communities">
                    <h2><?php esc_html_e('Communities in this City', 'happy-place'); ?></h2>
                    <div class="communities-grid">
                        <?php
                        foreach ($communities as $post) :
                            setup_postdata($post);
                            get_template_part('templates/community/content', 'community');
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Display related listings
            $related_listings = get_posts(array(
                'post_type' => 'listing',
                'meta_query' => array(
                    array(
                        'key' => 'city',
                        'value' => get_the_ID(),
                    ),
                ),
                'posts_per_page' => 6,
            ));

            if ($related_listings) :
            ?>
                <div class="related-listings">
                    <h2><?php esc_html_e('Featured Listings in this City', 'happy-place'); ?></h2>
                    <div class="listings-grid">
                        <?php
                        foreach ($related_listings as $post) :
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
