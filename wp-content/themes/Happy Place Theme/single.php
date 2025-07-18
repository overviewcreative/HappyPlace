<?php
/**
 * Template for displaying single posts
 * 
 * @package HappyPlace
 */

get_header(); ?>

<main class="site-main">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    
                    <?php if ('post' === get_post_type()) : ?>
                        <div class="entry-meta">
                            <span class="posted-on">
                                <i class="fas fa-calendar-alt"></i>
                                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                    <?php echo esc_html(get_the_date()); ?>
                                </time>
                            </span>
                            
                            <span class="byline">
                                <i class="fas fa-user"></i>
                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                    <?php echo esc_html(get_the_author()); ?>
                                </a>
                            </span>

                            <?php if (has_category()) : ?>
                                <span class="categories">
                                    <i class="fas fa-folder"></i>
                                    <?php the_category(', '); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="entry-thumbnail">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'happy-place'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <footer class="entry-footer">
                    <?php if (has_tag()) : ?>
                        <div class="tags">
                            <i class="fas fa-tags"></i>
                            <?php the_tags('', ', ', ''); ?>
                        </div>
                    <?php endif; ?>
                </footer>

                <?php if (comments_open() || get_comments_number()) : ?>
                    <div class="comments-section">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
