<?php
/**
 * Template part for displaying posts
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('hph-post-article'); ?> itemscope itemtype="https://schema.org/Article">
    <header class="hph-post-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="hph-post-title" itemprop="headline">', '</h1>');
        else :
            the_title('<h2 class="hph-post-title" itemprop="headline"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;

        if ('post' === get_post_type()) :
            ?>
            <div class="hph-post-meta">
                <span class="hph-posted-on">
                    <i class="fas fa-calendar-alt"></i>
                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>" itemprop="datePublished">
                        <?php echo esc_html(get_the_date()); ?>
                    </time>
                </span>
                
                <span class="hph-byline">
                    <i class="fas fa-user"></i>
                    <span class="author vcard" itemprop="author" itemscope itemtype="https://schema.org/Person">
                        <a class="url fn n" href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" itemprop="url">
                            <span itemprop="name"><?php echo esc_html(get_the_author()); ?></span>
                        </a>
                    </span>
                </span>

                <?php if (has_category()) : ?>
                    <span class="hph-categories">
                        <i class="fas fa-folder"></i>
                        <?php the_category(', '); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php
        endif;
        ?>
    </header>

    <?php if (has_post_thumbnail() && !is_singular()) : ?>
        <div class="hph-post-thumbnail">
            <a href="<?php echo esc_url(get_permalink()); ?>">
                <?php the_post_thumbnail('medium_large', ['itemprop' => 'image']); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="hph-post-content" itemprop="articleBody">
        <?php
        if (is_singular() || is_front_page()) {
            the_content();
            
            wp_link_pages([
                'before' => '<div class="hph-page-links">' . esc_html__('Pages:', 'happy-place'),
                'after'  => '</div>',
            ]);
        } else {
            the_excerpt();
        }
        ?>
    </div>

    <?php if (is_singular() && get_the_tags()) : ?>
        <footer class="hph-post-footer">
            <div class="hph-tags">
                <i class="fas fa-tags"></i>
                <?php the_tags('', ', ', ''); ?>
            </div>
        </footer>
    <?php endif; ?>
</article>
