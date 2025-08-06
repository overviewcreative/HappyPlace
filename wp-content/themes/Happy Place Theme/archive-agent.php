<?php

/**
 * Template Name: Agents Archive
 * 
 * This is the template for displaying the agents directory.
 * 
 * @package HappyPlace
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <?php get_template_part('templates/partials/global/content-header'); ?>

        <div class="agents-grid">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'agent',
                'posts_per_page' => 12,
                'paged' => $paged,
                'orderby' => 'title',
                'order' => 'ASC'
            );

            $query = new WP_Query($args);

            if ($query->have_posts()) :
                while ($query->have_posts()) :
                    $query->the_post();
            ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('agent-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="agent-card__image">
                                <?php the_post_thumbnail('agent-thumbnail'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="agent-card__content">
                            <h2 class="agent-card__name">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <?php if ($title = get_field('title')) : ?>
                                <p class="agent-card__title"><?php echo esc_html($title); ?></p>
                            <?php endif; ?>

                            <?php if ($phone = get_field('phone')) : ?>
                                <p class="agent-card__phone">
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>">
                                        <?php echo esc_html($phone); ?>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <?php if ($email = get_field('email')) : ?>
                                <p class="agent-card__email">
                                    <a href="mailto:<?php echo esc_attr($email); ?>">
                                        <?php echo esc_html($email); ?>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <div class="agent-card__actions">
                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">View Profile</a>
                            </div>
                        </div>
                    </article>
            <?php
                endwhile;

                // Custom pagination for WP_Query
                $big = 999999999;
                echo paginate_links(array(
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $query->max_num_pages
                ));

                wp_reset_postdata();
            else :
                get_template_part('templates/partials/global/no-results');
            endif;
            ?>
        </div>
    </div>
</main>

<?php
get_footer();
