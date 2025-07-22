<?php
/**
 * Living Experience Template Part
 * 
 * Displays location intelligence, walkability, schools, and nearby amenities
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$listing_data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? 0;

// Get location intelligence data
$location_data = $listing_data['location_intelligence'] ?? [];
$walk_score = $location_data['walk_score'] ?? 0;
$transit_score = $location_data['transit_score'] ?? 0;
$bike_score = $location_data['bike_score'] ?? 0;
$nearby_amenities = $location_data['nearby_amenities'] ?? [];
$schools = [
    'elementary' => $location_data['elementary_school'] ?? '',
    'middle' => $location_data['middle_school'] ?? '',
    'high' => $location_data['high_school'] ?? '',
    'district' => $location_data['school_district'] ?? ''
];

// Get community data
$community = $listing_data['relationships']['community'] ?? null;
?>

<section class="living-experience">
    <div class="container">
        <header class="section-header">
            <h2 class="section-title">Living Experience</h2>
            <p class="section-subtitle">Discover what makes this location special</p>
        </header>

        <div class="experience-grid">
            
            <?php if ($walk_score || $transit_score || $bike_score): ?>
            <!-- Walkability Scores -->
            <div class="experience-card walkability-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="icon">🚶</span>
                        Walkability & Transit
                    </h3>
                </div>
                <div class="card-content">
                    <div class="score-grid">
                        <?php if ($walk_score): ?>
                        <div class="score-item">
                            <div class="score-circle" data-score="<?php echo esc_attr($walk_score); ?>">
                                <span class="score-number"><?php echo esc_html($walk_score); ?></span>
                            </div>
                            <span class="score-label">Walk Score</span>
                            <span class="score-description"><?php echo hph_get_walk_score_description($walk_score); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($transit_score): ?>
                        <div class="score-item">
                            <div class="score-circle" data-score="<?php echo esc_attr($transit_score); ?>">
                                <span class="score-number"><?php echo esc_html($transit_score); ?></span>
                            </div>
                            <span class="score-label">Transit Score</span>
                            <span class="score-description"><?php echo hph_get_transit_score_description($transit_score); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($bike_score): ?>
                        <div class="score-item">
                            <div class="score-circle" data-score="<?php echo esc_attr($bike_score); ?>">
                                <span class="score-number"><?php echo esc_html($bike_score); ?></span>
                            </div>
                            <span class="score-label">Bike Score</span>
                            <span class="score-description"><?php echo hph_get_bike_score_description($bike_score); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($schools['district']) || !empty($schools['elementary'])): ?>
            <!-- Schools & Education -->
            <div class="experience-card schools-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="icon">🎓</span>
                        Schools & Education
                    </h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($schools['district'])): ?>
                    <div class="school-district">
                        <strong><?php echo esc_html($schools['district']); ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <div class="schools-list">
                        <?php foreach (['elementary', 'middle', 'high'] as $school_type): ?>
                            <?php if (!empty($schools[$school_type])): ?>
                            <div class="school-item">
                                <span class="school-type"><?php echo esc_html(ucfirst($school_type)); ?>:</span>
                                <span class="school-name"><?php echo esc_html($schools[$school_type]); ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($nearby_amenities)): ?>
            <!-- Nearby Places -->
            <div class="experience-card amenities-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="icon">📍</span>
                        Nearby Places
                    </h3>
                </div>
                <div class="card-content">
                    <div class="amenities-list">
                        <?php foreach (array_slice($nearby_amenities, 0, 8) as $amenity): ?>
                        <div class="amenity-item">
                            <div class="amenity-info">
                                <span class="amenity-name"><?php echo esc_html($amenity['amenity_name'] ?? ''); ?></span>
                                <span class="amenity-type"><?php echo esc_html($amenity['amenity_type'] ?? ''); ?></span>
                            </div>
                            <div class="amenity-meta">
                                <?php if (!empty($amenity['amenity_distance'])): ?>
                                <span class="distance"><?php echo esc_html($amenity['amenity_distance']); ?> mi</span>
                                <?php endif; ?>
                                <?php if (!empty($amenity['amenity_rating'])): ?>
                                <span class="rating">⭐ <?php echo esc_html($amenity['amenity_rating']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($nearby_amenities) > 8): ?>
                    <button class="btn btn-outline show-more-amenities">
                        Show All <?php echo count($nearby_amenities); ?> Places
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($community): ?>
            <!-- Community Information -->
            <div class="experience-card community-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="icon">🏘️</span>
                        Community
                    </h3>
                </div>
                <div class="card-content">
                    <h4 class="community-name"><?php echo esc_html($community['title'] ?? ''); ?></h4>
                    
                    <?php if (!empty($community['amenities'])): ?>
                    <div class="community-amenities">
                        <?php foreach ($community['amenities'] as $amenity): ?>
                        <span class="amenity-badge"><?php echo esc_html(hph_format_amenity_name($amenity)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($community['description'])): ?>
                    <p class="community-description">
                        <?php echo wp_trim_words($community['description'], 25); ?>
                    </p>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(get_permalink($community['ID'] ?? 0)); ?>" 
                       class="btn btn-outline btn-sm">
                        Learn More About <?php echo esc_html($community['title'] ?? 'Community'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<style>
.living-experience {
    padding: 3rem 0;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--text-muted);
    margin: 0;
}

.experience-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.experience-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.experience-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-title .icon {
    font-size: 1.5rem;
}

.card-content {
    padding: 1.5rem;
}

/* Walkability Scores */
.score-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1.5rem;
}

.score-item {
    text-align: center;
}

.score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    position: relative;
    background: conic-gradient(var(--primary-color) calc(var(--score) * 3.6deg), #e5e7eb calc(var(--score) * 3.6deg));
}

.score-circle::before {
    content: '';
    position: absolute;
    inset: 8px;
    border-radius: 50%;
    background: white;
}

.score-number {
    position: relative;
    z-index: 1;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-dark);
}

.score-label {
    display: block;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.score-description {
    display: block;
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* Schools */
.school-district {
    font-size: 1.1rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.schools-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.school-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.school-type {
    font-weight: 500;
    color: var(--text-muted);
    min-width: 90px;
}

.school-name {
    color: var(--text-dark);
    text-align: right;
    flex: 1;
}

/* Amenities */
.amenities-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.amenity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f5f5f5;
}

.amenity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.amenity-info {
    flex: 1;
}

.amenity-name {
    display: block;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.amenity-type {
    display: block;
    font-size: 0.875rem;
    color: var(--text-muted);
    text-transform: capitalize;
}

.amenity-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.875rem;
}

.distance {
    color: var(--primary-color);
    font-weight: 500;
}

.rating {
    color: var(--text-muted);
}

.show-more-amenities {
    margin-top: 1rem;
    width: 100%;
}

/* Community */
.community-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.community-amenities {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.amenity-badge {
    background: var(--primary-light);
    color: var(--primary-color);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.community-description {
    color: var(--text-muted);
    line-height: 1.6;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .experience-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .score-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 1rem;
    }
    
    .score-circle {
        width: 70px;
        height: 70px;
    }
    
    .score-number {
        font-size: 1.25rem;
    }
    
    .amenity-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .amenity-meta {
        align-self: flex-end;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate score circles
    const scoreCircles = document.querySelectorAll('.score-circle');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const circle = entry.target;
                const score = circle.dataset.score;
                circle.style.setProperty('--score', score);
            }
        });
    }, { threshold: 0.5 });
    
    scoreCircles.forEach(circle => observer.observe(circle));
    
    // Handle show more amenities
    const showMoreBtn = document.querySelector('.show-more-amenities');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            // This would typically expand to show all amenities
            // Implementation depends on your full amenities data structure
            console.log('Show more amenities clicked');
        });
    }
});
</script>