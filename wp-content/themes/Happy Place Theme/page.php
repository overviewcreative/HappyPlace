<?php
/**
 * The template for displaying all pages
 *
 * @package Happy_Place_Theme
 */

get_header();
?>

<main id="primary" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
            </header>

            <div class="entry-content">
                <?php
                the_content();

                wp_link_pages([
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'happy-place'),
                    'after'  => '</div>',
                ]);
                ?>
            </div>

            <?php if (get_edit_post_link()) : ?>
                <footer class="entry-footer">
                    <?php edit_post_link(
                        sprintf(
                            wp_kses(
                                __('Edit <span class="screen-reader-text">%s</span>', 'happy-place'),
                                ['span' => ['class' => []]]
                            ),
                            wp_kses_post(get_the_title())
                        ),
                        '<span class="edit-link">',
                        '</span>'
                    ); ?>
                </footer>
            <?php endif; ?>
        </article>

        <?php
        // If comments are open or we have at least one comment, load up the comment template.
        if (comments_open() || get_comments_number()) :
            comments_template();
        endif;
    endwhile;
    ?>
</main>

<?php
get_sidebar();
get_footer();