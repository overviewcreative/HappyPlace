<?php

/**
 * Archive Template for Transactions
 *
 * @package HappyPlace
 */

get_header();
?>

<main class="hph-site-main hph-site-main--archive">
    <div class="hph-container">
        <header class="hph-archive-header">
            <h1 class="hph-archive-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description('<div class="hph-archive-description">', '</div>'); ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="hph-grid hph-grid--transactions">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('templates/transaction/content', 'transaction');
                endwhile;
                ?>
            </div>
            <div class="hph-pagination">
                <?php the_posts_pagination(array(
                    'prev_text' => '&laquo; ' . __('Previous', 'happy-place'),
                    'next_text' => __('Next', 'happy-place') . ' &raquo;',
                )); ?>
            </div>
        <?php else : ?>
            <div class="hph-no-results">
                <p><?php esc_html_e('No transactions found.', 'happy-place'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
