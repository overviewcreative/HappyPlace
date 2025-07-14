<?php

/**
 * Archive Template for Open Houses
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="archive-open-houses">
    <div class="hph-container">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
        </header>

        <?php if (have_posts()) : ?>
            <div class="open-houses-grid">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('templates/open-house/content', 'open-house');
                endwhile;
                ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No open houses found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
