<?php
/**
 * Template part for displaying posts
 *
 * @package Happy_Place_Theme
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="entry-title">', '</h1>');
        else :
            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;

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

    <?php if (has_post_thumbnail() && !is_singular()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if (is_singular()) {
            the_content();
        } else {
            the_excerpt();
        }

        wp_link_pages([
            'before' => '<div class="page-links">' . esc_html__('Pages:', 'happy-place'),
            'after'  => '</div>',
        ]);
        ?>
    </div>

    <?php if (!is_singular()) : ?>
        <footer class="entry-footer">
            <a href="<?php the_permalink(); ?>" class="read-more">
                <?php esc_html_e('Read More', 'happy-place'); ?>
            </a>
        </footer>
    <?php endif; ?>
</article>