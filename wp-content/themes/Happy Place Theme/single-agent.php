<?php
/**
 * Single Agent Template
 * 
 * Displays individual agent profiles with complete component integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="single-agent-wrapper">
    <?php while (have_posts()) : the_post(); ?>
        
        <article id="agent-<?php the_ID(); ?>" <?php post_class('single-agent'); ?>>
            
            <!-- Agent Hero Section -->
            <div class="agent-hero-section">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4">
                            <div class="agent-photo">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('large', ['class' => 'agent-photo-img']); ?>
                                <?php else : ?>
                                    <div class="agent-photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="agent-intro">
                                <h1 class="agent-name"><?php the_title(); ?></h1>
                                
                                <?php
                                $title = get_post_meta(get_the_ID(), '_agent_title', true);
                                if ($title) : ?>
                                    <p class="agent-title"><?php echo esc_html($title); ?></p>
                                <?php endif; ?>
                                
                                <div class="agent-contact-info">
                                    <?php
                                    $phone = get_post_meta(get_the_ID(), '_agent_phone', true);
                                    $email = get_post_meta(get_the_ID(), '_agent_email', true);
                                    $license = get_post_meta(get_the_ID(), '_agent_license_number', true);
                                    ?>
                                    
                                    <?php if ($phone) : ?>
                                        <div class="contact-item">
                                            <i class="fas fa-phone"></i>
                                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($email) : ?>
                                        <div class="contact-item">
                                            <i class="fas fa-envelope"></i>
                                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($license) : ?>
                                        <div class="contact-item">
                                            <i class="fas fa-certificate"></i>
                                            <span>License #<?php echo esc_html($license); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="agent-actions">
                                    <button class="btn btn-primary contact-agent-btn" data-agent-id="<?php the_ID(); ?>">
                                        <i class="fas fa-envelope"></i> Contact Agent
                                    </button>
                                    <button class="btn btn-outline schedule-meeting-btn" data-agent-id="<?php the_ID(); ?>">
                                        <i class="fas fa-calendar"></i> Schedule Meeting
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="agent-content-wrapper">
                <div class="container">
                    <div class="row">
                        
                        <!-- Main Content Column -->
                        <div class="col-lg-8">
                            
                            <!-- Agent Bio -->
                            <div class="agent-bio-section">
                                <h2>About <?php echo esc_html(get_the_title()); ?></h2>
                                <div class="bio-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>

                            <?php
                            // Agent Statistics Component
                            if (class_exists('HPH_Agent_Stats_Component')) {
                                $stats_component = new HPH_Agent_Stats_Component();
                                $stats_data = function_exists('hph_bridge_get_agent_stats')
                                    ? hph_bridge_get_agent_stats(get_the_ID())
                                    : hph_fallback_get_agent_stats(get_the_ID());
                                echo $stats_component->render($stats_data);
                            } else {
                                // Fallback stats display
                                $listings_count = get_posts([
                                    'post_type' => 'listing',
                                    'meta_query' => [
                                        [
                                            'key' => '_listing_agent',
                                            'value' => get_the_ID(),
                                            'compare' => '='
                                        ]
                                    ],
                                    'numberposts' => -1
                                ]);
                                ?>
                                <div class="agent-stats-fallback">
                                    <h3>Agent Performance</h3>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <span class="stat-number"><?php echo count($listings_count); ?></span>
                                            <span class="stat-label">Active Listings</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number"><?php echo get_post_meta(get_the_ID(), '_agent_sales_count', true) ?: '0'; ?></span>
                                            <span class="stat-label">Sales This Year</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number"><?php echo get_post_meta(get_the_ID(), '_agent_experience_years', true) ?: '0'; ?></span>
                                            <span class="stat-label">Years Experience</span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>

                            <!-- Agent Specialties -->
                            <?php
                            $specialties = get_post_meta(get_the_ID(), '_agent_specialties', true);
                            if (!empty($specialties) && is_array($specialties)) : ?>
                                <div class="agent-specialties-section">
                                    <h3>Specialties</h3>
                                    <div class="specialties-list">
                                        <?php foreach ($specialties as $specialty) : ?>
                                            <span class="specialty-tag"><?php echo esc_html($specialty); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Agent Certifications -->
                            <?php
                            $certifications = get_post_meta(get_the_ID(), '_agent_certifications', true);
                            if (!empty($certifications) && is_array($certifications)) : ?>
                                <div class="agent-certifications-section">
                                    <h3>Certifications & Awards</h3>
                                    <ul class="certifications-list">
                                        <?php foreach ($certifications as $certification) : ?>
                                            <li><?php echo esc_html($certification); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Sidebar Column -->
                        <div class="col-lg-4">
                            <div class="agent-sidebar">

                                <?php
                                // Contact Form Component
                                if (class_exists('HPH_Contact_Form_Component')) {
                                    $contact_component = new HPH_Contact_Form_Component();
                                    $contact_data = [
                                        'agent_id' => get_the_ID(),
                                        'agent_name' => get_the_title(),
                                        'form_title' => 'Contact ' . get_the_title(),
                                        'form_type' => 'agent_contact'
                                    ];
                                    echo $contact_component->render($contact_data);
                                } else {
                                    // Fallback contact form
                                    ?>
                                    <div class="contact-form-widget">
                                        <h3>Contact <?php echo esc_html(get_the_title()); ?></h3>
                                        <form class="agent-contact-form" method="post">
                                            <input type="hidden" name="agent_id" value="<?php the_ID(); ?>">
                                            <div class="form-group">
                                                <input type="text" name="contact_name" placeholder="Your Name" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="email" name="contact_email" placeholder="Your Email" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="tel" name="contact_phone" placeholder="Your Phone">
                                            </div>
                                            <div class="form-group">
                                                <textarea name="contact_message" placeholder="Your Message" rows="4" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Send Message</button>
                                        </form>
                                    </div>
                                    <?php
                                }
                                ?>

                                <!-- Agent Schedule Widget -->
                                <div class="agent-schedule-widget">
                                    <h3>Schedule a Meeting</h3>
                                    <p>Available for consultations and property viewings.</p>
                                    <button class="btn btn-outline full-width schedule-btn" data-agent-id="<?php the_ID(); ?>">
                                        <i class="fas fa-calendar-alt"></i> View Available Times
                                    </button>
                                </div>

                                <!-- Social Media Links -->
                                <?php
                                $social_links = [
                                    'facebook' => get_post_meta(get_the_ID(), '_agent_facebook', true),
                                    'linkedin' => get_post_meta(get_the_ID(), '_agent_linkedin', true),
                                    'twitter' => get_post_meta(get_the_ID(), '_agent_twitter', true),
                                    'instagram' => get_post_meta(get_the_ID(), '_agent_instagram', true)
                                ];
                                
                                $has_social = array_filter($social_links);
                                if (!empty($has_social)) : ?>
                                    <div class="agent-social-widget">
                                        <h3>Connect with <?php echo esc_html(get_the_title()); ?></h3>
                                        <div class="social-links">
                                            <?php foreach ($social_links as $platform => $url) : 
                                                if (!empty($url)) : ?>
                                                    <a href="<?php echo esc_url($url); ?>" class="social-link <?php echo esc_attr($platform); ?>" target="_blank" rel="noopener">
                                                        <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                                                    </a>
                                                <?php endif;
                                            endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Dynamic sidebar for additional widgets
                                if (is_active_sidebar('agent-sidebar')) {
                                    dynamic_sidebar('agent-sidebar');
                                }
                                ?>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <?php
            // Agent Listings Component
            if (class_exists('HPH_Agent_Listings_Component')) {
                $listings_component = new HPH_Agent_Listings_Component();
                $listings_data = [
                    'agent_id' => get_the_ID(),
                    'limit' => 6,
                    'title' => 'Listings by ' . get_the_title()
                ];
                echo $listings_component->render($listings_data);
            } else {
                // Fallback agent listings display
                $agent_listings = get_posts([
                    'post_type' => 'listing',
                    'meta_query' => [
                        [
                            'key' => '_listing_agent',
                            'value' => get_the_ID(),
                            'compare' => '='
                        ]
                    ],
                    'numberposts' => 6,
                    'post_status' => 'publish'
                ]);
                
                if (!empty($agent_listings)) : ?>
                    <div class="agent-listings-section">
                        <div class="container">
                            <h2>Listings by <?php echo esc_html(get_the_title()); ?></h2>
                            <div class="listings-grid">
                                <?php foreach ($agent_listings as $listing) : ?>
                                    <div class="listing-card">
                                        <a href="<?php echo get_permalink($listing->ID); ?>">
                                            <?php if (has_post_thumbnail($listing->ID)) : ?>
                                                <?php echo get_the_post_thumbnail($listing->ID, 'medium'); ?>
                                            <?php endif; ?>
                                            <h3><?php echo get_the_title($listing->ID); ?></h3>
                                            <p class="listing-price">
                                                <?php
                                                $price = get_post_meta($listing->ID, '_listing_price', true);
                                                if ($price) {
                                                    echo '$' . number_format($price);
                                                } else {
                                                    echo 'Contact for Price';
                                                }
                                                ?>
                                            </p>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="view-all-listings">
                                <a href="<?php echo esc_url(add_query_arg('agent', get_the_ID(), get_post_type_archive_link('listing'))); ?>" class="btn btn-outline">
                                    View All Listings by <?php echo esc_html(get_the_title()); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif;
            }
            ?>

        </article>

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
