<?php
/**
 * Front Page Template
 * 
 * @package HappyPlace
 */

get_header(); ?>

<main class="site-main">
    <div class="container">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                    </header>
                    
                    <div class="entry-content">
                        <?php
                        the_content();
                        
                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . esc_html__('Pages:', 'happy-place'),
                            'after'  => '</div>',
                        ));
                        ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php esc_html_e('No content found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
