<?php

/**
 * Archive Template for Places
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

        <!-- Place Filters -->
        <div class="hph-filters">
            <form class="hph-filters-form" method="get">
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'place_category',
                    'hide_empty' => true,
                ));

                if ($categories) :
                ?>
                    <div class="hph-filter-group">
                        <label for="category" class="hph-filter-label">
                            <?php esc_html_e('Category:', 'happy-place'); ?>
                        </label>
                        <select name="category" id="category" class="hph-select">
                            <option value=""><?php esc_html_e('All Categories', 'happy-place'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected(get_query_var('category'), $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" class="hph-btn hph-btn--primary">
                    <?php esc_html_e('Apply Filters', 'happy-place'); ?>
                </button>
            </form>
        </div>

        <?php if (have_posts()) : ?>
            <div class="hph-grid hph-grid--places">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('templates/place/content', 'place');
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
                <p><?php esc_html_e('No places found.', 'happy-place'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
