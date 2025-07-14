<?php

/**
 * The template for displaying community archives
 *
 * @package Happy_Place_Theme
 */

get_header();
?>

<main id="primary" class="site-main communities-archive">
    <div class="container">
        <?php if (have_posts()) : ?>
            <header class="page-header">
                <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
                <?php the_archive_description(); ?>
            </header>

            <div class="communities-grid">
                <?php while (have_posts()) : ?>
                    <?php the_post(); ?>
                    <?php get_template_part('templates/community/content', 'community'); ?>
                <?php endwhile; ?>
            </div>

            <?php the_posts_navigation(); ?>

        <?php else : ?>
            <p><?php esc_html_e('No communities found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
