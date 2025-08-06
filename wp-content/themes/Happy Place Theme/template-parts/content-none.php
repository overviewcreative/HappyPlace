<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @package Happy_Place_Theme
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('Nothing here', 'happy-place'); ?></h1>
    </header>

    <div class="page-content">
        <?php
        if (is_home() && current_user_can('publish_posts')) :
            printf(
                '<p>' . wp_kses(
                    __('Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'happy-place'),
                    ['a' => ['href' => []]]
                ) . '</p>',
                esc_url(admin_url('post-new.php'))
            );
        elseif (is_search()) :
            ?>
            <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'happy-place'); ?></p>
            <?php get_search_form();
        else :
            ?>
            <p><?php esc_html_e('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'happy-place'); ?></p>
            <?php get_search_form();
        endif;
        ?>
    </div>
</section>