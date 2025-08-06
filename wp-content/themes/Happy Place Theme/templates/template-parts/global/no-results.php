<?php

/**
 * Template part for displaying a message when posts are not found
 */
?>

<div class="hph-container hph-py-16">
    <div class="hph-no-results hph-text-center hph-space-y-6">
        <div class="hph-icon-container hph-mb-6">
            <i class="fas fa-search hph-text-6xl hph-text-gray-300"></i>
        </div>

        <h2 class="hph-text-3xl hph-font-bold hph-text-gray-900">
            <?php esc_html_e('Nothing Found', 'happy-place'); ?>
        </h2>

        <?php if (is_search()) : ?>
            <p class="hph-text-lg hph-text-gray-600">
                <?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'happy-place'); ?>
            </p>
            <div class="hph-max-w-lg hph-mx-auto hph-mt-8">
                <?php get_template_part('templates/template-parts/global/search-form'); ?>
            </div>
        <?php elseif (is_archive()) : ?>
            <p class="hph-text-lg hph-text-gray-600">
                <?php esc_html_e('Sorry, but there are no items to display at this time.', 'happy-place'); ?>
            </p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hph-button hph-button--primary">
                <?php esc_html_e('Return Home', 'happy-place'); ?>
            </a>
        <?php else : ?>
            <p class="hph-text-lg hph-text-gray-600">
                <?php esc_html_e('It seems we can\'t find what you\'re looking for. Perhaps searching can help.', 'happy-place'); ?>
            </p>
            <div class="hph-max-w-lg hph-mx-auto hph-mt-8">
                <?php get_template_part('templates/template-parts/global/search-form'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>