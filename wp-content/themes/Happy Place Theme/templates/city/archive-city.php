<?php

/**
 * Archive Template for Cities
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="archive-cities">
    <div class="hph-container">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="cities-grid">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('templates/city/content', 'city');
                endwhile;
                ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No cities found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
