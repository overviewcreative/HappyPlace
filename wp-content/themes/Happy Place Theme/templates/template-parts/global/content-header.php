<?php
/**
 * Template part for displaying the page header content
 */
?>

<header class="hph-page-header hph-py-8 hph-mb-8 hph-bg-gray-50 hph-border-b hph-border-gray-200">
    <div class="hph-container">
        <?php if (is_archive()) : ?>
            <h1 class="hph-text-4xl hph-font-bold hph-text-gray-900 hph-mb-4">
                <?php
                if (is_post_type_archive('listing')) {
                    esc_html_e('Featured Properties', 'happy-place');
                } elseif (is_post_type_archive('agent')) {
                    esc_html_e('Meet Our Agents', 'happy-place');
                } elseif (is_post_type_archive('community')) {
                    esc_html_e('Explore Communities', 'happy-place');
                } elseif (is_post_type_archive('city')) {
                    esc_html_e('Cities We Serve', 'happy-place');
                } else {
                    the_archive_title();
                }
                ?>
            </h1>
            <?php 
            $description = get_the_archive_description();
            if ($description) : ?>
                <div class="hph-text-lg hph-text-gray-600 hph-max-w-3xl">
                    <?php echo wp_kses_post($description); ?>
                </div>
            <?php endif; ?>
        <?php elseif (is_search()) : ?>
            <h1 class="hph-text-4xl hph-font-bold hph-text-gray-900 hph-mb-4">
                <?php
                printf(
                    /* translators: %s: search query */
                    esc_html__('Search Results for: %s', 'happy-place'),
                    '<span class="hph-text-primary">' . get_search_query() . '</span>'
                );
                ?>
            </h1>
            <p class="hph-text-lg hph-text-gray-600">
                <?php 
                global $wp_query;
                printf(
                    /* translators: %d: number of results */
                    _n(
                        'Found %d result matching your search.',
                        'Found %d results matching your search.',
                        $wp_query->found_posts,
                        'happy-place'
                    ),
                    $wp_query->found_posts
                ); 
                ?>
            </p>
        <?php elseif (is_404()) : ?>
            <div class="hph-text-center">
                <h1 class="hph-text-4xl hph-font-bold hph-text-gray-900 hph-mb-4">
                    <?php esc_html_e('Page Not Found', 'happy-place'); ?>
                </h1>
                <p class="hph-text-lg hph-text-gray-600">
                    <?php esc_html_e('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'happy-place'); ?>
                </p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="hph-btn hph-btn--primary hph-mt-6">
                    <?php esc_html_e('Return Home', 'happy-place'); ?>
                </a>
            </div>
        <?php else : ?>
            <h1 class="hph-text-4xl hph-font-bold hph-text-gray-900">
                <?php the_title(); ?>
            </h1>
        <?php endif; ?>
    </div>
</header>
