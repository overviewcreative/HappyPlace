<?php
/**
 * Template part for displaying user's saved searches
 */

$current_user = wp_get_current_user();
$saved_searches = get_user_meta($current_user->ID, 'saved_searches', true);
?>

<div class="hph-saved-searches">
    <h1><?php esc_html_e('Saved Searches', 'happy-place'); ?></h1>

    <?php if (!empty($saved_searches) && is_array($saved_searches)) : ?>
        <div class="saved-searches-list">
            <?php foreach ($saved_searches as $search) : ?>
                <div class="search-card">
                    <h3><?php echo esc_html($search['name']); ?></h3>
                    <div class="search-criteria">
                        <?php foreach ($search['criteria'] as $key => $value) : ?>
                            <span class="search-tag">
                                <?php 
                                printf(
                                    '%s: %s',
                                    esc_html(str_replace('_', ' ', ucfirst($key))),
                                    esc_html($value)
                                );
                                ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="search-actions">
                        <a href="<?php echo esc_url($search['url']); ?>" class="hph-btn hph-btn-secondary">
                            <?php esc_html_e('View Results', 'happy-place'); ?>
                        </a>
                        <form method="post" class="delete-search-form">
                            <?php wp_nonce_field('delete_search_' . $search['id']); ?>
                            <input type="hidden" name="search_id" value="<?php echo esc_attr($search['id']); ?>">
                            <button type="submit" name="delete_search" class="hph-btn hph-btn-text hph-btn-danger">
                                <?php esc_html_e('Delete', 'happy-place'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="hph-no-searches">
            <p><?php esc_html_e('You haven\'t saved any searches yet.', 'happy-place'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('search'))); ?>" class="hph-btn hph-btn-primary">
                <?php esc_html_e('Search Properties', 'happy-place'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
