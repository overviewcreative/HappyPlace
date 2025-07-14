<?php

/**
 * Archive Template for Communities
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="archive-communities">
    <div class="hph-container">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="communities-grid">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('templates/community/content', 'community');
                endwhile;
                ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No communities found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
