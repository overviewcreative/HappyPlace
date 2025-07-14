<?php
/**
 * Template Name: Single Agent
 * 
 * This is the template for displaying a single agent profile.
 * Updated to match the single listing design patterns and functionality.
 * 
 * @package HappyPlace
 */

get_header();
?>

<main class="hph-site-main hph-site-main--single" role="main">
    <?php while (have_posts()) : the_post(); ?>
        
        <!-- Agent Hero Section - Following single listing pattern -->
        <section class="hph-agent-hero">
            <div class="hph-agent-hero-background"></div>
            <div class="hph-agent-hero-overlay">
                <div class="hph-container">
                    <div class="hph-agent-hero-content">
                        <!-- Agent Photo -->
                        <div class="hph-agent-hero-photo">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('agent-large', ['alt' => get_the_title() . ' - Real Estate Agent']); ?>
                            <?php else : ?>
                                <div class="hph-agent-placeholder">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Indicator -->
                            <?php 
                            $agent_status = get_field('agent_status') ?: 'online';
                            $status_class = $agent_status === 'online' ? 'hph-agent-status-indicator' : 'hph-agent-status-indicator hph-agent-status-indicator--offline';
                            ?>
                            <div class="<?php echo esc_attr($status_class); ?>" title="<?php echo $agent_status === 'online' ? 'Available' : 'Offline'; ?>"></div>
                        </div>

                        <!-- Agent Info -->
                        <div class="hph-agent-hero-info">
                            <h1 class="hph-agent-hero-name"><?php the_title(); ?></h1>

                            <?php if ($title = get_field('title')) : ?>
                                <p class="hph-agent-hero-title"><?php echo esc_html($title); ?></p>
                            <?php endif; ?>

                            <!-- Agent Badges -->
                            <div class="hph-agent-hero-badges">
                                <?php
                                // License state badge
                                $license_state = get_field('license_state');
                                if ($license_state) {
                                    $state_names = ['de' => 'Delaware Licensed', 'md' => 'Maryland Licensed'];
                                    echo '<span class="hph-agent-badge">' . esc_html($state_names[$license_state] ?? $license_state) . '</span>';
                                }

                                // Certifications
                                $certifications = get_field('certifications');
                                if ($certifications && is_array($certifications)) {
                                    foreach (array_slice($certifications, 0, 2) as $cert) {
                                        echo '<span class="hph-agent-badge">' . esc_html($cert['name']) . '</span>';
                                    }
                                }

                                // Service areas count
                                $service_areas = get_field('service_areas');
                                if ($service_areas && is_array($service_areas) && count($service_areas) > 1) {
                                    echo '<span class="hph-agent-badge">Serves ' . count($service_areas) . ' Counties</span>';
                                }
                                ?>
                            </div>

                            <!-- Contact Information -->
                            <div class="hph-agent-hero-contact">
                                <?php if ($phone = get_field('phone')) : ?>
                                    <a href="tel:<?php echo esc_attr($phone); ?>" class="hph-agent-hero-phone">
                                        <i class="fas fa-phone" aria-hidden="true"></i>
                                        <span><?php echo esc_html($phone); ?></span>
                                    </a>
                                <?php endif; ?>

                                <?php if ($email = get_field('email')) : ?>
                                    <a href="mailto:<?php echo esc_attr($email); ?>" class="hph-agent-hero-email">
                                        <i class="fas fa-envelope" aria-hidden="true"></i>
                                        <span><?php echo esc_html($email); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Social Links -->
                            <?php
                            $social_links = get_field('social_links');
                            if ($social_links && is_array($social_links)) : ?>
                                <div class="hph-agent-hero-social">
                                    <?php foreach ($social_links as $social) :
                                        if (!empty($social['url']) && !empty($social['platform'])) : ?>
                                            <a href="<?php echo esc_url($social['url']); ?>" class="hph-social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(ucfirst($social['platform'])); ?>">
                                                <i class="fab fa-<?php echo esc_attr($social['platform']); ?>" aria-hidden="true"></i>
                                            </a>
                                    <?php endif;
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Actions -->
                        <div class="hph-agent-hero-actions">
                            <button type="button" class="hph-hero-action-btn hph-hero-action-btn--primary hph-contact-agent-modal" data-agent-id="<?php echo esc_attr(get_the_ID()); ?>" data-agent-name="<?php echo esc_attr(get_the_title()); ?>">
                                <i class="fas fa-comment" aria-hidden="true"></i>
                                Contact Agent
                            </button>
                            
                            <?php if ($schedule_link = get_field('schedule_link')) : ?>
                                <a href="<?php echo esc_url($schedule_link); ?>" class="hph-hero-action-btn hph-hero-action-btn--outline" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-calendar" aria-hidden="true"></i>
                                    Schedule Meeting
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($chat_link = get_field('chat_link')) : ?>
                                <a href="<?php echo esc_url($chat_link); ?>" class="hph-hero-action-btn hph-hero-action-btn--outline" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-comments" aria-hidden="true"></i>
                                    Quick Chat
                                </a>
                            <?php else : ?>
                                <button type="button" class="hph-hero-action-btn hph-hero-action-btn--outline hph-property-inquiry-modal" data-agent-id="<?php echo esc_attr(get_the_ID()); ?>">
                                    <i class="fas fa-search" aria-hidden="true"></i>
                                    Property Search
                                </button>
                            <?php endif; ?> endif; ?>
                        </div>

                        <!-- Quick Actions -->
                        <div class="hph-agent-hero-actions">
                            <button type="button" class="hph-hero-action-btn hph-hero-action-btn--primary hph-contact-agent-modal" data-agent-id="<?php echo esc_attr(get_the_ID()); ?>" data-agent-name="<?php echo esc_attr(get_the_title()); ?>">
                                <i class="fas fa-comment" aria-hidden="true"></i>
                                Contact Agent
                            </button>
                            <a href="#agent-listings" class="hph-hero-action-btn hph-hero-action-btn--outline">
                                <i class="fas fa-home" aria-hidden="true"></i>
                                View Listings
                            </a>
                            <button type="button" class="hph-hero-action-btn hph-hero-action-btn--outline hph-property-inquiry-modal" data-agent-id="<?php echo esc_attr(get_the_ID()); ?>">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                Property Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="hph-container">
            <div class="hph-agent-content-wrapper">
                <!-- Main Content Column -->
                <div class="hph-agent-main">
                    
                    <!-- Biography Section -->
                    <?php 
                    // Use actual ACF field names from the JSON
                    $bio_content = get_field('bio') ?: get_the_content();
                    if ($bio_content) : ?>
                        <section class="hph-agent-section hph-agent-bio">
                            <h2 class="hph-agent-section-title">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                About <?php echo esc_html(get_the_title()); ?>
                            </h2>
                            <div class="hph-agent-bio-content">
                                <?php echo wp_kses_post($bio_content); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Specialties Section -->
                    <?php 
                    // Get specialties from taxonomy
                    $specialties = get_the_terms(get_the_ID(), 'agent_specialty');
                    if ($specialties && !is_wp_error($specialties)) : ?>
                        <section class="hph-agent-section hph-agent-specialties">
                            <h2 class="hph-agent-section-title">
                                <i class="fas fa-star" aria-hidden="true"></i>
                                Specialties & Expertise
                            </h2>
                            <div class="hph-agent-specialties-grid">
                                <?php 
                                $specialty_icons = [
                                    'residential' => 'fa-home',
                                    'commercial' => 'fa-building',
                                    'luxury' => 'fa-gem',
                                    'investment' => 'fa-chart-line',
                                    'first-time-buyers' => 'fa-key',
                                    'relocation' => 'fa-truck-moving',
                                    'new-construction' => 'fa-hammer',
                                    'foreclosures' => 'fa-gavel'
                                ];
                                
                                foreach ($specialties as $specialty) : 
                                    $icon = $specialty_icons[$specialty->slug] ?? 'fa-check-circle';
                                ?>
                                    <div class="hph-specialty-item">
                                        <div class="hph-specialty-icon">
                                            <i class="fas <?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                                        </div>
                                        <div class="hph-specialty-content">
                                            <h4><?php echo esc_html($specialty->name); ?></h4>
                                            <?php if ($specialty->description) : ?>
                                                <p><?php echo esc_html($specialty->description); ?></p>
                                            <?php else : ?>
                                                <p>Expert guidance and specialized knowledge in <?php echo esc_html(strtolower($specialty->name)); ?>.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Service Areas Section -->
                    <?php 
                    $service_areas = get_field('service_areas');
                    if ($service_areas && is_array($service_areas)) : ?>
                        <section class="hph-agent-section">
                            <h2 class="hph-agent-section-title">
                                <i class="fas fa-map" aria-hidden="true"></i>
                                Service Areas
                            </h2>
                            <div class="hph-service-areas-grid">
                                <?php 
                                $area_labels = [
                                    'new_castle' => 'New Castle County, DE',
                                    'kent' => 'Kent County, DE',
                                    'sussex' => 'Sussex County, DE',
                                    'cecil' => 'Cecil County, MD',
                                    'harford' => 'Harford County, MD',
                                    'baltimore_county' => 'Baltimore County, MD',
                                    'anne_arundel' => 'Anne Arundel County, MD'
                                ];
                                
                                foreach ($service_areas as $area) : 
                                    $area_name = $area_labels[$area] ?? $area;
                                ?>
                                    <div class="hph-service-area-item">
                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                        <span><?php echo esc_html($area_name); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Professional Certifications -->
                    <?php 
                    $certifications = get_field('certifications');
                    if ($certifications && is_array($certifications)) : ?>
                        <section class="hph-agent-section">
                            <h2 class="hph-agent-section-title">
                                <i class="fas fa-award" aria-hidden="true"></i>
                                Professional Certifications
                            </h2>
                            <div class="hph-certifications-grid">
                                <?php foreach ($certifications as $cert) : ?>
                                    <div class="hph-certification-item">
                                        <div class="hph-certification-icon">
                                            <i class="fas fa-certificate" aria-hidden="true"></i>
                                        </div>
                                        <div class="hph-certification-content">
                                            <h4><?php echo esc_html($cert['name']); ?></h4>
                                            <?php if (!empty($cert['year'])) : ?>
                                                <p>Obtained: <?php echo esc_html($cert['year']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Professional Information -->
                    <section class="hph-agent-section">
                        <h2 class="hph-agent-section-title">
                            <i class="fas fa-briefcase" aria-hidden="true"></i>
                            Professional Information
                        </h2>
                        <div class="hph-professional-info">
                            <?php if ($license_number = get_field('license_number')) : ?>
                                <div class="hph-info-item">
                                    <strong>License Number:</strong> <?php echo esc_html($license_number); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($license_state = get_field('license_state')) : ?>
                                <div class="hph-info-item">
                                    <strong>Licensed in:</strong> 
                                    <?php 
                                    $state_names = ['de' => 'Delaware', 'md' => 'Maryland'];
                                    echo esc_html($state_names[$license_state] ?? $license_state); 
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($office_location = get_field('office_location')) : ?>
                                <div class="hph-info-item">
                                    <strong>Office Location:</strong> <?php echo esc_html($office_location); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Agent Listings Section -->
                    <?php
                    // Get agent's current listings
                    $listings_args = [
                        'post_type' => 'listing',
                        'posts_per_page' => 6,
                        'post_status' => 'publish',
                        'meta_query' => [
                            [
                                'key' => 'listing_agent',
                                'value' => get_the_ID(),
                                'compare' => '='
                            ]
                        ]
                    ];
                    $listings_query = new WP_Query($listings_args);

                    if ($listings_query->have_posts()) : ?>
                        <section id="agent-listings" class="hph-agent-section">
                            <h2 class="hph-agent-section-title">
                                <i class="fas fa-home" aria-hidden="true"></i>
                                Current Listings
                            </h2>
                            <div class="hph-agent-listings-grid">
                                <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                                    <?php get_template_part('template-parts/cards/listing-swipe-card', null, ['size' => 'medium']); ?>
                                <?php endwhile; ?>
                            </div>
                            
                            <?php if ($listings_query->found_posts > 6) : ?>
                                <div class="hph-view-all-listings">
                                    <a href="<?php echo esc_url(add_query_arg('agent', get_the_ID(), get_post_type_archive_link('listing'))); ?>" class="hph-btn hph-btn-primary">
                                        View All <?php echo esc_html($listings_query->found_posts); ?> Listings
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </section>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>

                </div>

                <!-- Sidebar -->
                <aside class="hph-agent-sidebar" role="complementary">
                    
                    <!-- Contact Card -->
                    <div class="hph-agent-contact-card">
                        <h3 class="hph-contact-card-title">Get In Touch</h3>
                        
                        <div class="hph-contact-info">
                            <?php if ($phone = get_field('phone')) : ?>
                                <div class="hph-contact-item">
                                    <div class="hph-contact-icon">
                                        <i class="fas fa-phone" aria-hidden="true"></i>
                                    </div>
                                    <div class="hph-contact-text">
                                        <div class="hph-contact-label">Phone</div>
                                        <div class="hph-contact-value">
                                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($email = get_field('email')) : ?>
                                <div class="hph-contact-item">
                                    <div class="hph-contact-icon">
                                        <i class="fas fa-envelope" aria-hidden="true"></i>
                                    </div>
                                    <div class="hph-contact-text">
                                        <div class="hph-contact-label">Email</div>
                                        <div class="hph-contact-value">
                                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($office_address = get_field('office_address')) : ?>
                                <div class="hph-contact-item">
                                    <div class="hph-contact-icon">
                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                    </div>
                                    <div class="hph-contact-text">
                                        <div class="hph-contact-label">Office</div>
                                        <div class="hph-contact-value"><?php echo esc_html($office_address); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($office_phone = get_field('office_phone')) : ?>
                                <div class="hph-contact-item">
                                    <div class="hph-contact-icon">
                                        <i class="fas fa-building" aria-hidden="true"></i>
                                    </div>
                                    <div class="hph-contact-text">
                                        <div class="hph-contact-label">Office Phone</div>
                                        <div class="hph-contact-value">
                                            <a href="tel:<?php echo esc_attr($office_phone); ?>"><?php echo esc_html($office_phone); ?></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($schedule_link = get_field('schedule_link')) : ?>
                                <div class="hph-contact-item">
                                    <div class="hph-contact-icon">
                                        <i class="fas fa-calendar" aria-hidden="true"></i>
                                    </div>
                                    <div class="hph-contact-text">
                                        <div class="hph-contact-label">Schedule Meeting</div>
                                        <div class="hph-contact-value">
                                            <a href="<?php echo esc_url($schedule_link); ?>" target="_blank" rel="noopener noreferrer">Book Appointment</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Contact Form -->
                        <form class="hph-quick-contact-form" id="agent-contact-form">
                            <input type="hidden" name="agent_id" value="<?php echo esc_attr(get_the_ID()); ?>">
                            
                            <div class="hph-form-group">
                                <label for="contact_name" class="hph-form-label">Your Name *</label>
                                <input type="text" id="contact_name" name="contact_name" class="hph-form-input" required>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="contact_email" class="hph-form-label">Your Email *</label>
                                <input type="email" id="contact_email" name="contact_email" class="hph-form-input" required>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="contact_phone" class="hph-form-label">Your Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone" class="hph-form-input">
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="contact_message" class="hph-form-label">Message *</label>
                                <textarea id="contact_message" name="contact_message" class="hph-form-textarea" rows="4" placeholder="How can I help you with your real estate needs?" required></textarea>
                            </div>
                            
                            <button type="submit" class="hph-btn hph-btn-primary hph-btn-block">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Agent Stats -->
                    <div class="hph-agent-stats-card">
                        <h3 class="hph-contact-card-title">Agent Statistics</h3>
                        <div class="hph-stats-grid">
                            <div class="hph-stat-item">
                                <span class="hph-stat-number"><?php echo esc_html(get_field('years_experience') ?: '0'); ?></span>
                                <span class="hph-stat-label">Years Experience</span>
                            </div>
                            <div class="hph-stat-item">
                                <?php
                                $active_listings = get_posts([
                                    'post_type' => 'listing',
                                    'meta_query' => [
                                        ['key' => 'listing_agent', 'value' => get_the_ID(), 'compare' => '='],
                                        ['key' => 'listing_status', 'value' => 'active', 'compare' => '=']
                                    ],
                                    'post_status' => 'publish',
                                    'numberposts' => -1
                                ]);
                                ?>
                                <span class="hph-stat-number"><?php echo count($active_listings); ?></span>
                                <span class="hph-stat-label">Active Listings</span>
                            </div>
                            <div class="hph-stat-item">
                                <span class="hph-stat-number"><?php echo esc_html(get_field('homes_sold_ytd') ?: '0'); ?></span>
                                <span class="hph-stat-label">Homes Sold YTD</span>
                            </div>
                            <div class="hph-stat-item">
                                <?php 
                                $rating = get_field('agent_rating') ?: 5.0;
                                $rating_display = number_format($rating, 1);
                                ?>
                                <span class="hph-stat-number"><?php echo esc_html($rating_display); ?></span>
                                <span class="hph-stat-label">Average Rating</span>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews/Testimonials -->
                    <?php 
                    $testimonials = get_field('client_testimonials');
                    if ($testimonials && is_array($testimonials)) : ?>
                        <div class="hph-agent-reviews-card">
                            <h3 class="hph-contact-card-title">Client Reviews</h3>
                            <?php foreach (array_slice($testimonials, 0, 3) as $testimonial) : ?>
                                <div class="hph-review-item">
                                    <div class="hph-review-stars">
                                        <?php 
                                        $stars = $testimonial['rating'] ?? 5;
                                        for ($i = 1; $i <= 5; $i++) {
                                            $star_class = $i <= $stars ? 'fas' : 'far';
                                            echo '<i class="' . $star_class . ' fa-star hph-review-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div class="hph-review-text">
                                        "<?php echo esc_html($testimonial['review'] ?? 'Great service!'); ?>"
                                    </div>
                                    <div class="hph-review-author">
                                        - <?php echo esc_html($testimonial['client_name'] ?? 'Anonymous'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Additional Info Cards -->
                    <?php if ($languages = get_field('languages_spoken')) : ?>
                        <div class="hph-agent-section">
                            <h3 class="hph-agent-section-title">
                                <i class="fas fa-language" aria-hidden="true"></i>
                                Languages
                            </h3>
                            <div class="hph-languages-list">
                                <?php foreach ($languages as $language) : ?>
                                    <span class="hph-specialty-tag"><?php echo esc_html($language); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($service_areas = get_field('service_areas')) : ?>
                        <div class="hph-agent-section">
                            <h3 class="hph-agent-section-title">
                                <i class="fas fa-map" aria-hidden="true"></i>
                                Service Areas
                            </h3>
                            <div class="hph-service-areas">
                                <?php foreach ($service_areas as $area) : ?>
                                    <span class="hph-specialty-tag"><?php echo esc_html($area); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </aside>
            </div>
        </div>

    <?php endwhile; ?>
</main>

<?php 
get_footer();