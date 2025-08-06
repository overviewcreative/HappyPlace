<?php
/**
 * Template Name: Agents Archive
 * 
 * This is the template for displaying the agents directory.
 * Updated to match the listing archive design patterns and functionality.
 * 
 * @package HappyPlace
 */

get_header();

// Get archive description
$archive_description = get_the_archive_description();
if (!$archive_description) {
    $archive_description = 'Discover our team of experienced real estate professionals. Browse through our agent profiles to find the perfect agent for your real estate needs.';
}

// Current view mode
$view_mode = $_GET['view'] ?? 'grid';
$sort_by = $_GET['sort'] ?? 'name';

// Total agent count
$total_agents = wp_count_posts('agent')->publish ?? 0;
?>

<main class="hph-site-main hph-agents-archive" role="main">
    <!-- Hero Section - Following listing archive pattern -->
    <section class="hph-archive-hero">
        <div class="hph-container">
            <div class="hph-hero-content">
                <h1 class="hph-hero-title"><?php post_type_archive_title(); ?></h1>
                <p class="hph-hero-subtitle"><?php echo esc_html($archive_description); ?></p>
                
                <!-- Quick Search - Following listing pattern -->
                <div class="hph-quick-search">
                    <form class="hph-search-form" method="GET" action="<?php echo esc_url(get_post_type_archive_link('agent')); ?>">
                        <div class="hph-search-input-group">
                            <input 
                                type="text" 
                                name="search" 
                                id="agent-search" 
                                class="hph-search-input" 
                                placeholder="Search agents by name, location, or specialty..."
                                value="<?php echo esc_attr($_GET['search'] ?? ''); ?>"
                                autocomplete="off"
                            >
                            <button type="submit" class="hph-search-btn">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <span>Search</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="hph-archive-content">
        <div class="hph-container">
            <div class="hph-split-view">
                <!-- Sidebar Filters - Following listing pattern -->
                <aside class="hph-sidebar" role="complementary">
                    <div class="hph-sidebar-header">
                        <h2 class="hph-sidebar-title">Filter Agents</h2>
                    </div>
                    <div class="hph-sidebar-content">
                        <form id="agent-filter-form" class="hph-filters-form">
                            <!-- Location Filter -->
                            <div class="hph-filter-section">
                                <h3 class="hph-filter-title">Location</h3>
                                <div class="hph-form-group">
                                    <input 
                                        type="text" 
                                        id="agent-location" 
                                        name="location" 
                                        class="hph-form-input hph-filter-control" 
                                        data-filter="location"
                                        placeholder="City or ZIP code"
                                        value="<?php echo esc_attr($_GET['location'] ?? ''); ?>"
                                    >
                                </div>
                            </div>

                            <!-- Specialization Filter -->
                            <div class="hph-filter-section">
                                <h3 class="hph-filter-title">Specialization</h3>
                                <div class="hph-filter-chips">
                                    <?php
                                    $specializations = [
                                        '' => 'Any',
                                        'residential' => 'Residential',
                                        'commercial' => 'Commercial', 
                                        'luxury' => 'Luxury',
                                        'investment' => 'Investment',
                                        'first-time-buyers' => 'First-Time Buyers',
                                        'relocation' => 'Relocation'
                                    ];
                                    $selected_spec = $_GET['specialization'] ?? '';
                                    
                                    foreach ($specializations as $value => $label) {
                                        $active_class = ($selected_spec === $value) ? ' active' : '';
                                        echo sprintf(
                                            '<button type="button" class="hph-filter-chip%s" data-filter="specialization" data-value="%s">%s</button>',
                                            $active_class,
                                            esc_attr($value),
                                            esc_html($label)
                                        );
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Language Filter -->
                            <div class="hph-filter-section">
                                <h3 class="hph-filter-title">Languages</h3>
                                <div class="hph-form-group">
                                    <select class="hph-form-select hph-filter-control" data-filter="language" name="language">
                                        <option value="">Any Language</option>
                                        <option value="spanish" <?php selected($_GET['language'] ?? '', 'spanish'); ?>>Spanish</option>
                                        <option value="french" <?php selected($_GET['language'] ?? '', 'french'); ?>>French</option>
                                        <option value="chinese" <?php selected($_GET['language'] ?? '', 'chinese'); ?>>Chinese</option>
                                        <option value="german" <?php selected($_GET['language'] ?? '', 'german'); ?>>German</option>
                                        <option value="italian" <?php selected($_GET['language'] ?? '', 'italian'); ?>>Italian</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Experience Filter -->
                            <div class="hph-filter-section">
                                <h3 class="hph-filter-title">Experience</h3>
                                <div class="hph-filter-chips">
                                    <?php
                                    $experience_levels = [
                                        '' => 'Any',
                                        '1' => '1+ Years',
                                        '5' => '5+ Years',
                                        '10' => '10+ Years',
                                        '15' => '15+ Years'
                                    ];
                                    $selected_exp = $_GET['experience'] ?? '';
                                    
                                    foreach ($experience_levels as $value => $label) {
                                        $active_class = ($selected_exp === $value) ? ' active' : '';
                                        echo sprintf(
                                            '<button type="button" class="hph-filter-chip%s" data-filter="experience" data-value="%s">%s</button>',
                                            $active_class,
                                            esc_attr($value),
                                            esc_html($label)
                                        );
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Rating Filter -->
                            <div class="hph-filter-section">
                                <h3 class="hph-filter-title">Rating</h3>
                                <div class="hph-filter-chips">
                                    <?php
                                    $rating_levels = [
                                        '' => 'Any Rating',
                                        '3' => '3+ Stars',
                                        '4' => '4+ Stars',
                                        '5' => '5 Stars'
                                    ];
                                    $selected_rating = $_GET['rating'] ?? '';
                                    
                                    foreach ($rating_levels as $value => $label) {
                                        $active_class = ($selected_rating === $value) ? ' active' : '';
                                        echo sprintf(
                                            '<button type="button" class="hph-filter-chip%s" data-filter="rating" data-value="%s">%s</button>',
                                            $active_class,
                                            esc_attr($value),
                                            esc_html($label)
                                        );
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Clear Filters -->
                            <div class="hph-filter-section">
                                <button type="button" class="hph-btn hph-btn-outline hph-btn-block hph-clear-filters">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                    Clear All Filters
                                </button>
                            </div>

                            <!-- Hidden inputs for form state -->
                            <input type="hidden" name="view" value="<?php echo esc_attr($view_mode); ?>">
                            <input type="hidden" name="sort" value="<?php echo esc_attr($sort_by); ?>">
                        </form>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="hph-main-content">
                    <!-- Results Header - Following listing pattern -->
                    <div class="hph-results-header">
                        <div class="hph-results-count">
                            <span class="hph-results-count-text"><?php echo esc_html($total_agents); ?> agent<?php echo $total_agents !== 1 ? 's' : ''; ?> found</span>
                        </div>
                        
                        <div class="hph-results-controls">
                            <!-- View Toggle -->
                            <div class="hph-view-toggle">
                                <button 
                                    type="button" 
                                    class="hph-view-btn <?php echo $view_mode === 'grid' ? 'active' : ''; ?>" 
                                    data-view="grid"
                                    aria-label="Grid view"
                                >
                                    <i class="fas fa-th-large" aria-hidden="true"></i>
                                </button>
                                <button 
                                    type="button" 
                                    class="hph-view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>" 
                                    data-view="list"
                                    aria-label="List view"
                                >
                                    <i class="fas fa-list" aria-hidden="true"></i>
                                </button>
                            </div>

                            <!-- Sort Controls -->
                            <div class="hph-sort-controls">
                                <label for="agent-sort" class="hph-sr-only">Sort agents by</label>
                                <select id="agent-sort" class="hph-sort-select">
                                    <option value="name" <?php selected($sort_by, 'name'); ?>>Name (A-Z)</option>
                                    <option value="name_desc" <?php selected($sort_by, 'name_desc'); ?>>Name (Z-A)</option>
                                    <option value="experience" <?php selected($sort_by, 'experience'); ?>>Most Experience</option>
                                    <option value="rating" <?php selected($sort_by, 'rating'); ?>>Highest Rated</option>
                                    <option value="listings" <?php selected($sort_by, 'listings'); ?>>Most Listings</option>
                                    <option value="recent" <?php selected($sort_by, 'recent'); ?>>Recently Joined</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filter Chips -->
                    <div class="hph-filter-chips-container">
                        <!-- Filter chips will be populated by JavaScript -->
                    </div>

                    <!-- Results Content -->
                    <div class="hph-results-content view-<?php echo esc_attr($view_mode); ?>">
                        <?php if (have_posts()) : ?>
                            <!-- Grid View -->
                            <div class="hph-agents-grid" style="<?php echo $view_mode !== 'grid' ? 'display: none;' : ''; ?>">
                                <?php while (have_posts()) : the_post(); ?>
                                    <article id="post-<?php the_ID(); ?>" <?php post_class('hph-agent-card'); ?>>
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="hph-agent-image">
                                                <?php the_post_thumbnail('agent-thumbnail', ['alt' => get_the_title() . ' - Real Estate Agent']); ?>
                                                
                                                <!-- Agent Status Badge -->
                                                <?php 
                                                $agent_status = get_field('agent_status') ?: 'online';
                                                $status_class = $agent_status === 'online' ? 'hph-agent-status' : 'hph-agent-status hph-agent-status--offline';
                                                $status_text = $agent_status === 'online' ? 'Available' : 'Offline';
                                                ?>
                                                <div class="<?php echo esc_attr($status_class); ?>">
                                                    <?php echo esc_html($status_text); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="hph-agent-content">
                                            <h2 class="hph-agent-name">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h2>

                                            <?php if ($title = get_field('agent_title')) : ?>
                                                <p class="hph-agent-title"><?php echo esc_html($title); ?></p>
                                            <?php endif; ?>

                                            <!-- Agent Specialties -->
                                            <?php 
                                            $specialties = get_field('agent_specialties');
                                            if ($specialties && is_array($specialties)) : ?>
                                                <div class="hph-agent-specialties">
                                                    <?php foreach (array_slice($specialties, 0, 3) as $specialty) : ?>
                                                        <span class="hph-specialty-tag"><?php echo esc_html($specialty); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Agent Stats -->
                                            <div class="hph-agent-stats">
                                                <div class="hph-stat-item">
                                                    <span class="hph-stat-number"><?php echo esc_html(get_field('years_experience') ?: '0'); ?></span>
                                                    <span class="hph-stat-label">Years</span>
                                                </div>
                                                <div class="hph-stat-item">
                                                    <?php
                                                    // Get agent's listing count
                                                    $listing_count = get_posts([
                                                        'post_type' => 'listing',
                                                        'meta_query' => [
                                                            ['key' => 'listing_agent', 'value' => get_the_ID(), 'compare' => '=']
                                                        ],
                                                        'post_status' => 'publish',
                                                        'numberposts' => -1
                                                    ]);
                                                    $count = count($listing_count);
                                                    ?>
                                                    <span class="hph-stat-number"><?php echo esc_html($count); ?></span>
                                                    <span class="hph-stat-label">Listings</span>
                                                </div>
                                            </div>

                                            <!-- Contact Info -->
                                            <div class="hph-agent-contact">
                                                <?php if ($phone = get_field('agent_phone')) : ?>
                                                    <div class="hph-agent-phone">
                                                        <i class="fas fa-phone" aria-hidden="true"></i>
                                                        <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($email = get_field('agent_email')) : ?>
                                                    <div class="hph-agent-email">
                                                        <i class="fas fa-envelope" aria-hidden="true"></i>
                                                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="hph-agent-actions">
                                                <a href="<?php the_permalink(); ?>" class="hph-btn hph-btn-primary hph-btn-sm">
                                                    View Profile
                                                </a>
                                                <button type="button" class="hph-btn hph-btn-outline hph-btn-sm hph-contact-agent-modal" data-agent-id="<?php echo esc_attr(get_the_ID()); ?>" data-agent-name="<?php echo esc_attr(get_the_title()); ?>">
                                                    Contact
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                <?php endwhile; ?>
                            </div>

                            <!-- List View -->
                            <div class="hph-agents-list" style="<?php echo $view_mode !== 'list' ? 'display: none;' : ''; ?>">
                                <?php 
                                // Reset the loop for list view
                                rewind_posts();
                                while (have_posts()) : the_post(); ?>
                                    <article class="hph-agent-list-item">
                                        <div class="hph-agent-list-image">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php the_post_thumbnail('thumbnail', ['alt' => get_the_title() . ' - Real Estate Agent']); ?>
                                            <?php else : ?>
                                                <div class="hph-agent-placeholder">
                                                    <i class="fas fa-user" aria-hidden="true"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="hph-agent-list-content">
                                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                            <p>
                                                <?php 
                                                $title = get_field('title');
                                                $office_location = get_field('office_location');
                                                $parts = array_filter([$title, $office_location]);
                                                echo esc_html(implode(' • ', $parts));
                                                ?>
                                            </p>
                                        </div>

                                        <div class="hph-agent-list-actions">
                                            <a href="<?php the_permalink(); ?>" class="hph-btn hph-btn-primary hph-btn-sm">
                                                View Profile
                                            </a>
                                        </div>
                                    </article>
                                <?php endwhile; ?>
                            </div>

                            <!-- Pagination -->
                            <div class="hph-pagination-wrapper">
                                <?php
                                echo paginate_links([
                                    'prev_text' => '<i class="fas fa-chevron-left"></i> Previous',
                                    'next_text' => 'Next <i class="fas fa-chevron-right"></i>',
                                    'class' => 'hph-pagination'
                                ]);
                                ?>
                            </div>

                        <?php else : ?>
                            <!-- No Results -->
                            <div class="hph-no-results">
                                <i class="fas fa-user-slash" aria-hidden="true"></i>
                                <h3>No Agents Found</h3>
                                <p>We couldn't find any agents matching your criteria. Try adjusting your filters or search terms.</p>
                                <button type="button" class="hph-btn hph-btn-primary hph-clear-filters">
                                    Clear All Filters
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Loading Spinner -->
                    <div class="hph-loading-spinner">
                        <div class="hph-spinner"></div>
                        <p>Loading agents...</p>
                    </div>
                </main>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();