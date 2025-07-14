<?php

/**
 * The template for displaying listing archives
 *
 * @package Happy_Place_Theme
 */

get_header();
?>

<main class="hph-site-main hph-site-main--archive">
    <div class="hph-container">
        <?php if (have_posts()) : ?>
            <header class="hph-archive-header">
                <h1 class="hph-archive-title"><?php post_type_archive_title(); ?></h1>
                <?php the_archive_description(); ?>
            </header>

            <div class="hph-listings-grid">
                <?php while (have_posts()) : ?>
                    <?php the_post(); ?>
                    <?php get_template_part('templates/content', 'listing'); ?>
                <?php endwhile; ?>
            </div>

            <?php the_posts_navigation(); ?>

        <?php else : ?>
            <p><?php esc_html_e('No listings found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
