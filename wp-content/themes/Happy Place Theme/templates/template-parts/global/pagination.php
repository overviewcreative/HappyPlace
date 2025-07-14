<?php
/**
 * Template part for displaying pagination
 */

global $wp_query;

$big = 999999999;
$args = array(
    'base'         => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
    'format'       => '?paged=%#%',
    'current'      => max(1, get_query_var('paged')),
    'total'        => $wp_query->max_num_pages,
    'show_all'     => false,
    'end_size'     => 2,
    'mid_size'     => 1,
    'prev_next'    => true,
    'prev_text'    => '<i class="fas fa-chevron-left hph-mr-1"></i>' . __('Previous', 'happy-place'),
    'next_text'    => __('Next', 'happy-place') . '<i class="fas fa-chevron-right hph-ml-1"></i>',
    'type'         => 'array',
    'add_args'     => false,
    'add_fragment' => ''
);

$pages = paginate_links($args);

if ($pages) : ?>
    <nav class="hph-pagination hph-mt-8" aria-label="<?php esc_attr_e('Pagination', 'happy-place'); ?>">
        <ul class="hph-flex hph-items-center hph-justify-center hph-space-x-1">
            <?php foreach ($pages as $page) : 
                $active = strpos($page, 'current') !== false;
                $disabled = strpos($page, 'dots') !== false;
                
                // Clean up the link text
                $page = str_replace('page-numbers', 'hph-pagination-link', $page);
                $page = str_replace('current', 'hph-pagination-link--active', $page);
                $page = str_replace('dots', 'hph-pagination-dots', $page);
                
                if ($disabled) {
                    $page = str_replace('<span', '<span class="hph-pagination-link hph-pagination-dots"', $page);
                } elseif (!$active) {
                    $page = str_replace('<a', '<a class="hph-pagination-link hover:hph-bg-gray-100"', $page);
                }
            ?>
                <li class="hph-pagination-item">
                    <?php echo $page; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>
