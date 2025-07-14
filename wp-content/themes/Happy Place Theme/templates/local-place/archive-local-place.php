<?php

/**
 * Archive Template for Places
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="archive-places">
    <div class="hph-container">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>

        <div class="places-filters">
            <?php
            // Add filters for place categories, ratings, etc.
            $categories = get_terms(array(
                'taxonomy' => 'place_category',
                'hide_empty' => true,
            ));

            if ($categories) :
            ?>
                <div class="filter-group">
                    <label for="place-category"><?php esc_html_e('Category:', 'happy-place'); ?></label>
                    <select id="place-category" class="place-filter">
                        <option value=""><?php esc_html_e('All Categories', 'happy-place'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <?php if (have_posts()) : ?>
            <div class="places-grid">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('templates/local-place/content', 'local-place');
                endwhile;
                ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No places found.', 'happy-place'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
