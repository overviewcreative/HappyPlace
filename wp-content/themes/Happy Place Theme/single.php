<?php
/**
 * The template for displaying all single posts
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
                <?php
                the_title('<h1 class="entry-title">', '</h1>');

                if ('post' === get_post_type()) :
                    ?>
                    <div class="entry-meta">
                        <?php
                        echo '<span class="posted-on">' . get_the_date() . '</span>';
                        echo '<span class="byline"> by ' . get_the_author() . '</span>';
                        ?>
                    </div>
                    <?php
                endif;
                ?>
            </header>

            <?php if (has_post_thumbnail()) : ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>

            <div class="entry-content">
                <?php
                the_content(sprintf(
                    wp_kses(
                        __('Continue reading<span class="screen-reader-text"> "%s"</span>', 'happy-place'),
                        ['span' => ['class' => []]]
                    ),
                    wp_kses_post(get_the_title())
                ));

                wp_link_pages([
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'happy-place'),
                    'after'  => '</div>',
                ]);
                ?>
            </div>

            <footer class="entry-footer">
                <?php
                $categories_list = get_the_category_list(esc_html__(', ', 'happy-place'));
                if ($categories_list) {
                    printf('<span class="cat-links">' . esc_html__('Posted in %1$s', 'happy-place') . '</span>', $categories_list);
                }

                $tags_list = get_the_tag_list('', esc_html_x(', ', 'list item separator', 'happy-place'));
                if ($tags_list) {
                    printf('<span class="tags-links">' . esc_html__('Tagged %1$s', 'happy-place') . '</span>', $tags_list);
                }

                if (get_edit_post_link()) {
                    edit_post_link(
                        sprintf(
                            wp_kses(
                                __('Edit <span class="screen-reader-text">%s</span>', 'happy-place'),
                                ['span' => ['class' => []]]
                            ),
                            wp_kses_post(get_the_title())
                        ),
                        '<span class="edit-link">',
                        '</span>'
                    );
                }
                ?>
            </footer>
        </article>

        <?php
        the_post_navigation([
            'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous:', 'happy-place') . '</span> <span class="nav-title">%title</span>',
            'next_text' => '<span class="nav-subtitle">' . esc_html__('Next:', 'happy-place') . '</span> <span class="nav-title">%title</span>',
        ]);

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