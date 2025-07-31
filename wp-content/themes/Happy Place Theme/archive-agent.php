<?php
/**
 * Archive template for agents
 * 
 * @package Happy_Place_Theme
 * @since 1.0.0
 */

get_header();

// Get archive data
$archive_data = [
    'title' => 'Our Agents',
    'description' => 'Meet our professional real estate agents'
];

// Get agents
$agents = get_posts([
    'post_type' => 'agent',
    'posts_per_page' => get_option('posts_per_page', 12),
    'meta_key' => 'agent_status',
    'meta_value' => 'active',
    'meta_compare' => '='
]);

if (empty($agents)) {
    $agents = get_posts([
        'post_type' => 'agent',
        'posts_per_page' => get_option('posts_per_page', 12)
    ]);
}
?>

<div class="archive-agents">
    <!-- Archive Header -->
    <div class="archive-header">
        <div class="container">
            <h1 class="archive-title"><?php echo esc_html($archive_data['title']); ?></h1>
            <p class="archive-description"><?php echo esc_html($archive_data['description']); ?></p>
            
            <div class="archive-stats">
                <?php
                $total_agents = count($agents);
                if ($total_agents > 0): ?>
                    <span class="agents-count"><?php echo esc_html($total_agents); ?> professional agent<?php echo $total_agents !== 1 ? 's' : ''; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Search & Filters -->
    <div class="archive-filters">
        <div class="container">
            <form class="search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="agent">
                <input type="search" name="s" placeholder="Search agents..." value="<?php echo get_search_query(); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
    
    <!-- Agents Grid -->
    <div class="archive-content">
        <div class="container">
            <?php if (!empty($agents)): ?>
                <div class="agents-grid">
                    <?php foreach ($agents as $agent): ?>
                        <div class="agent-card">
                            <div class="agent-photo">
                                <?php if (has_post_thumbnail($agent->ID)): ?>
                                    <a href="<?php echo get_permalink($agent->ID); ?>">
                                        <?php echo get_the_post_thumbnail($agent->ID, 'medium'); ?>
                                    </a>
                                <?php else: ?>
                                    <div class="agent-photo-placeholder">
                                        <span class="agent-initials">
                                            <?php 
                                            $name = get_the_title($agent->ID);
                                            $name_parts = explode(' ', $name);
                                            echo esc_html(substr($name_parts[0], 0, 1));
                                            if (count($name_parts) > 1) {
                                                echo esc_html(substr($name_parts[1], 0, 1));
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="agent-info">
                                <h3 class="agent-name">
                                    <a href="<?php echo get_permalink($agent->ID); ?>">
                                        <?php echo get_the_title($agent->ID); ?>
                                    </a>
                                </h3>
                                
                                <?php 
                                $agent_title = get_field('agent_title', $agent->ID);
                                if ($agent_title): ?>
                                    <p class="agent-title"><?php echo esc_html($agent_title); ?></p>
                                <?php endif; ?>
                                
                                <?php 
                                $agent_phone = get_field('agent_phone', $agent->ID);
                                if ($agent_phone): ?>
                                    <p class="agent-phone">
                                        <a href="tel:<?php echo esc_attr($agent_phone); ?>">
                                            <?php echo esc_html($agent_phone); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                
                                <?php 
                                $agent_email = get_field('agent_email', $agent->ID);
                                if ($agent_email): ?>
                                    <p class="agent-email">
                                        <a href="mailto:<?php echo esc_attr($agent_email); ?>">
                                            <?php echo esc_html($agent_email); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="agent-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt($agent->ID), 20); ?>
                                </div>
                                
                                <a href="<?php echo get_permalink($agent->ID); ?>" class="agent-link">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="archive-pagination">
                    <?php
                    the_posts_pagination([
                        'prev_text' => '← Previous',
                        'next_text' => 'Next →',
                        'mid_size' => 2
                    ]);
                    ?>
                </div>
                
            <?php else: ?>
                <div class="no-agents">
                    <h2>No agents found</h2>
                    <p>There are currently no agents available. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
