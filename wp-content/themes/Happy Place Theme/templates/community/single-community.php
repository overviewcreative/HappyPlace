<?php

/**
 * Single Community Template
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="single-community">
    <div class="hph-container">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('templates/community/content', 'community');

            // Display related listings
            $related_listings = get_posts(array(
                'post_type' => 'listing',
                'meta_query' => array(
                    array(
                        'key' => 'community',
                        'value' => get_the_ID(),
                    ),
                ),
                'posts_per_page' => 6,
            ));

            if ($related_listings) :
        ?>
                <div class="related-listings">
                    <h2><?php esc_html_e('Listings in this Community', 'happy-place'); ?></h2>
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
        <?php
            endif;
        endwhile;
        ?>
    </div>
</div>

<?php
get_footer();
