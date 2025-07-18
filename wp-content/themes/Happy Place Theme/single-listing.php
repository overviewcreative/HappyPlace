<?php
/**
 * Template for displaying single property listings
 * Enhanced with new component-based design system
 * Following listing swipe card design standards
 *
 * @package Happy_Place_Theme
 */

get_header();

// Get listing ID
$listing_id = get_the_ID();

// Load property data using enhanced bridge functions
$price = get_field('price');
$status = get_field('status');
$address = get_field('address');
$bedrooms = get_field('bedrooms');
$bathrooms = get_field('bathrooms');
$square_feet = get_field('square_feet');
$lot_size = get_field('lot_size');
$description = get_field('description') ?: get_the_content();
$virtual_tour = get_field('virtual_tour_link');
$gallery = get_field('gallery') ?: [];
$agent_name = get_field('agent_name');
$agent_email = get_field('agent_email');
$agent_phone = get_field('agent_phone');
$is_favorite = false; // Will be dynamic with user system

?>

<main class="single-listing">
    <?php while (have_posts()) : the_post(); ?>
        
        <!-- Hero Section - Following Card Design Standard -->
        <section class="listing-hero">
            <div class="listing-hero-image-container">
                <?php if (has_post_thumbnail()) : ?>
                    <img src="<?php the_post_thumbnail_url('large'); ?>" 
                         alt="<?php the_title_attribute(); ?>"
                         class="listing-hero-image"
                         loading="eager">
                <?php endif; ?>
                
                <!-- Status Badge - Following Card Pattern -->
                <div class="status-badge status-badge--<?php echo esc_attr(strtolower(str_replace(' ', '-', $status))); ?>">
                    <?php echo esc_html($status); ?>
                </div>
                
                <!-- Favorite Button - Following Card Pattern -->
                <button class="favorite-button <?php echo $is_favorite ? 'favorite-button--active' : ''; ?>" 
                        data-listing-id="<?php echo esc_attr($listing_id); ?>" 
                        data-nonce="<?php echo wp_create_nonce('hph_favorite_nonce'); ?>">
                    <?php echo $is_favorite ? '♥' : '♡'; ?>
                </button>
            </div>
            
            <div class="listing-hero-content">
                <div class="container">
                    <div class="listing-hero-info">
                        <h1 class="listing-title"><?php the_title(); ?></h1>
                        <?php if ($address) : ?>
                            <div class="listing-address">📍 <?php echo esc_html($address); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($price) : ?>
                            <div class="listing-price">$<?php echo number_format($price); ?></div>
                        <?php endif; ?>
                        
                        <!-- Property Stats in Hero -->
                        <div class="listing-features">
                            <?php if ($bedrooms) : ?>
                                <div class="feature-stat">
                                    <span class="feature-number"><?php echo esc_html($bedrooms); ?></span>
                                    <span class="feature-label">Beds</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bathrooms) : ?>
                                <div class="feature-stat">
                                    <span class="feature-number"><?php echo esc_html($bathrooms); ?></span>
                                    <span class="feature-label">Baths</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($square_feet) : ?>
                                <div class="feature-stat">
                                    <span class="feature-number"><?php echo number_format($square_feet); ?></span>
                                    <span class="feature-label">Sq Ft</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="listing-actions">
                            <a href="#contact-form" class="btn btn-primary">Contact Agent</a>
                            <a href="#mortgage-calculator" class="btn btn-secondary">Calculate Payment</a>
                            <?php if ($virtual_tour) : ?>
                                <a href="<?php echo esc_url($virtual_tour); ?>" class="btn btn-secondary" target="_blank">Virtual Tour</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Main Content Grid - Following Design System -->
        <div class="container">
            <div class="property-details-grid">
                
                <!-- Gallery Section -->
                <div class="property-gallery grid-area-gallery">
                    <?php if (!empty($gallery)) : ?>
                        <div class="gallery-container">
                            <div class="gallery-main">
                                <img src="<?php echo esc_url($gallery[0]['url']); ?>" alt="Property Image" class="gallery-main-image" data-full="<?php echo esc_url($gallery[0]['url']); ?>">
                            </div>
                            <div class="gallery-thumbnails">
                                <?php foreach (array_slice($gallery, 1, 4) as $index => $image) : ?>
                                    <img src="<?php echo esc_url($image['sizes']['thumbnail']); ?>" 
                                         alt="Property Image <?php echo $index + 2; ?>" 
                                         class="gallery-thumbnail"
                                         data-full="<?php echo esc_url($image['url']); ?>">
                                <?php endforeach; ?>
                                <?php if (count($gallery) > 5) : ?>
                                    <button class="gallery-more-btn">+<?php echo count($gallery) - 5; ?> more</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif (has_post_thumbnail()) : ?>
                        <img src="<?php the_post_thumbnail_url('large'); ?>" 
                             alt="<?php the_title_attribute(); ?>" 
                             class="listing-image">
                    <?php else : ?>
                        <div class="listing-image placeholder">
                            📷 No Image Available
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Property Details Card -->
                <div class="property-info-card grid-area-info">
                    <div class="card-header">
                        <h2>Property Details</h2>
                    </div>
                    
                    <div class="card-content">
                        <div class="property-details-list">
                            <?php if ($price) : ?>
                                <div class="property-detail">
                                    <span class="detail-label">Price</span>
                                    <span class="detail-value">$<?php echo number_format($price); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bedrooms) : ?>
                                <div class="property-detail">
                                    <span class="detail-label">Bedrooms</span>
                                    <span class="detail-value"><?php echo esc_html($bedrooms); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bathrooms) : ?>
                                <div class="property-detail">
                                    <span class="detail-label">Bathrooms</span>
                                    <span class="detail-value"><?php echo esc_html($bathrooms); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($square_feet) : ?>
                                <div class="property-detail">
                                    <span class="detail-label">Square Feet</span>
                                    <span class="detail-value"><?php echo number_format($square_feet); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($lot_size) : ?>
                                <div class="property-detail">
                                    <span class="detail-label">Lot Size</span>
                                    <span class="detail-value"><?php echo esc_html($lot_size); ?> acres</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($status) : ?>
                                <div class="property-detail">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value"><?php echo esc_html($status); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Features Card -->
                <div class="property-features-card grid-area-features">
                    <div class="card-header">
                        <h2>Property Features</h2>
                    </div>
                    
                    <div class="card-content">
                        <div class="feature-group">
                            <h3 class="feature-group-title">Interior Features</h3>
                            <div class="features-grid">
                                <div class="feature-badge"><i class="icon-check"></i> Hardwood floors</div>
                                <div class="feature-badge"><i class="icon-check"></i> Granite countertops</div>
                                <div class="feature-badge"><i class="icon-check"></i> Stainless steel appliances</div>
                                <div class="feature-badge"><i class="icon-check"></i> Walk-in closets</div>
                                <div class="feature-badge"><i class="icon-check"></i> Fireplace</div>
                                <div class="feature-badge"><i class="icon-check"></i> Central air conditioning</div>
                            </div>
                        </div>
                        
                        <div class="feature-group">
                            <h3 class="feature-group-title">Exterior Features</h3>
                            <div class="features-grid">
                                <div class="feature-badge"><i class="icon-check"></i> Covered patio</div>
                                <div class="feature-badge"><i class="icon-check"></i> Landscaped yard</div>
                                <div class="feature-badge"><i class="icon-check"></i> 2-car garage</div>
                                <div class="feature-badge"><i class="icon-check"></i> Sprinkler system</div>
                                <div class="feature-badge"><i class="icon-check"></i> Mature trees</div>
                                <div class="feature-badge"><i class="icon-check"></i> Fenced backyard</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description & Additional Info -->
                <div class="property-description-card grid-area-description">
                    <div class="card-header">
                        <h2>About This Property</h2>
                    </div>
                    
                    <div class="card-content">
                        <div class="property-description">
                            <?php if ($description) : ?>
                                <?php echo wp_kses_post($description); ?>
                            <?php else : ?>
                                <p>Welcome to this beautiful home featuring modern amenities and a great location. This property offers comfortable living spaces perfect for families. The well-maintained interior includes updated fixtures and finishes throughout.</p>
                                <p>The exterior features a landscaped yard with mature trees and a covered patio area perfect for entertaining. Located in a quiet neighborhood with easy access to schools, shopping, and dining.</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($virtual_tour) : ?>
                            <div class="virtual-tour-container">
                                <iframe src="<?php echo esc_url($virtual_tour); ?>" 
                                        class="virtual-tour-iframe" 
                                        allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                        
                        <div class="map-container" id="property-map" 
                             data-lat="40.7128" 
                             data-lng="-74.0060" 
                             data-title="<?php the_title_attribute(); ?>">
                            <div class="map-placeholder">
                                <i class="icon-map"></i>
                                <p>Interactive map will load here</p>
                            </div>
                        </div>
                        
                        <div class="nearby-buttons">
                            <button class="btn btn-secondary nearby-btn" onclick="searchNearbyPlaces('school')">Schools</button>
                            <button class="btn btn-secondary nearby-btn" onclick="searchNearbyPlaces('restaurant')">Restaurants</button>
                            <button class="btn btn-secondary nearby-btn" onclick="searchNearbyPlaces('hospital')">Healthcare</button>
                            <button class="btn btn-secondary nearby-btn" onclick="searchNearbyPlaces('shopping_mall')">Shopping</button>
                        </div>
                    </div>
                </div>
                
                <!-- Agent Card - Following Card Component Pattern -->
                <div class="agent-section grid-area-agent">
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Listed By</h3>
                        <div class="agent-info">
                            <div class="agent-avatar">👤</div>
                            <div class="agent-name"><?php echo esc_html($agent_name); ?></div>
                            <div class="agent-title">Real Estate Agent</div>
                        </div>
                        <div class="agent-contact">
                            <a href="tel:<?php echo esc_attr($agent_phone); ?>" class="btn btn-primary">📞 Call Agent</a>
                            <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="btn btn-secondary">✉️ Email Agent</a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form Card -->
                <div class="contact-form-card grid-area-contact">
                    <div class="card-header">
                        <h2>Request Information</h2>
                    </div>
                    
                    <div class="card-content">
                        <form class="contact-form" id="property-inquiry-form">
                            <div class="form-group">
                                <label for="inquiry-name">Name *</label>
                                <input type="text" id="inquiry-name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="inquiry-email">Email *</label>
                                <input type="email" id="inquiry-email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="inquiry-phone">Phone</label>
                                <input type="tel" id="inquiry-phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="inquiry-message">Message</label>
                                <textarea id="inquiry-message" name="message" placeholder="I'm interested in this property..."></textarea>
                            </div>
                            <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
                            <input type="hidden" name="listing_title" value="<?php the_title_attribute(); ?>">
                            <button type="submit" class="btn btn-primary btn-block">Send Inquiry</button>
                        </form>
                    </div>
                </div>
                
                <!-- Mortgage Calculator Card -->
                <div class="mortgage-calculator-card grid-area-calculator">
                    <div class="card-header">
                        <h2>Mortgage Calculator</h2>
                    </div>
                    
                    <div class="card-content">
                        <form id="mortgage-calculator">
                            <div class="form-group">
                                <label for="property-price">Home Price</label>
                                <input type="number" id="property-price" value="<?php echo esc_attr($price); ?>" min="0" step="1000">
                            </div>
                            <div class="form-group">
                                <label for="down-payment">Down Payment</label>
                                <input type="number" id="down-payment" value="<?php echo esc_attr($price * 0.2); ?>" min="0" step="1000">
                            </div>
                            <div class="form-group">
                                <label for="interest-rate">Interest Rate (%)</label>
                                <input type="number" id="interest-rate" value="6.5" min="0" max="30" step="0.1">
                            </div>
                            <div class="form-group">
                                <label for="loan-term">Loan Term</label>
                                <select id="loan-term">
                                    <option value="30">30 years</option>
                                    <option value="15">15 years</option>
                                    <option value="20">20 years</option>
                                    <option value="25">25 years</option>
                                </select>
                            </div>
                            
                            <div class="mortgage-results">
                                <div class="result-item">
                                    <span class="result-label">Monthly Payment:</span>
                                    <span class="result-value" id="monthly-payment">$0</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Principal & Interest:</span>
                                    <span class="result-value" id="principal-interest">$0</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Est. Taxes & Insurance:</span>
                                    <span class="result-value" id="taxes-insurance">$0</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Listings Section -->
        <section class="related-listings">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Similar Properties</h2>
                    <p class="section-subtitle">Properties you might also be interested in</p>
                </div>
                
                <div class="related-listings-grid">
                    <?php 
                    // Get related listings
                    $related_args = array(
                        'post_type' => 'listing',
                        'posts_per_page' => 3,
                        'post__not_in' => array($listing_id),
                        'meta_query' => array(
                            array(
                                'key' => 'price',
                                'value' => array($price * 0.8, $price * 1.2),
                                'type' => 'NUMERIC',
                                'compare' => 'BETWEEN'
                            )
                        )
                    );
                    
                    $related_listings = new WP_Query($related_args);
                    
                    if ($related_listings->have_posts()) : 
                        while ($related_listings->have_posts()) : $related_listings->the_post();
                            $related_price = get_field('price') ?: 350000;
                            $related_bedrooms = get_field('bedrooms') ?: 3;
                            $related_bathrooms = get_field('bathrooms') ?: 2;
                            $related_sqft = get_field('square_feet') ?: 1800;
                    ?>
                        <div class="hph-listing-card">
                            <div class="hph-listing-card-inner">
                                <div class="hph-listing-image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title_attribute(); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="hph-listing-content">
                                    <h3 class="hph-listing-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <div class="hph-listing-price">$<?php echo number_format($related_price); ?></div>
                                    <div class="hph-listing-stats">
                                        <div class="stat"><?php echo esc_html($related_bedrooms); ?> beds</div>
                                        <div class="stat"><?php echo esc_html($related_bathrooms); ?> baths</div>
                                        <div class="stat"><?php echo number_format($related_sqft); ?> sqft</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                        wp_reset_postdata();
                    else : 
                    ?>
                        <p>No similar properties found at this time.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
    <?php endwhile; ?>
</main>

<script>
// Enhanced JavaScript for single listing functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initializeFavoriteButton();
    initializeContactForm();
    initializeMortgageCalculator();
    initializeGallery();
    initializeMap();
    initializeNearbyPlaces();
    
    // Favorite button functionality
    function initializeFavoriteButton() {
        const favoriteBtn = document.querySelector('.favorite-button');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const listingId = this.dataset.listingId;
                const nonce = this.dataset.nonce;
                const isActive = this.classList.contains('favorite-button--active');
                
                // Optimistic UI update
                this.classList.toggle('favorite-button--active');
                this.textContent = isActive ? '♡' : '♥';
                
                // Send AJAX request (placeholder)
                console.log('Favorite toggled for listing:', listingId);
            });
        }
    }
    
    // Contact form functionality
    function initializeContactForm() {
        const contactForm = document.getElementById('property-inquiry-form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const name = formData.get('name');
                const email = formData.get('email');
                
                if (!name || !email) {
                    showAlert('Please fill in all required fields.', 'error');
                    return;
                }
                
                // Simulate successful submission
                showAlert('Thank you for your inquiry! We will contact you soon.', 'success');
                this.reset();
            });
        }
    }
    
    // Mortgage calculator functionality
    function initializeMortgageCalculator() {
        const calculator = document.getElementById('mortgage-calculator');
        if (calculator) {
            const inputs = calculator.querySelectorAll('input, select');
            
            inputs.forEach(input => {
                input.addEventListener('input', calculateMortgage);
            });
            
            // Initial calculation
            calculateMortgage();
        }
        
        function calculateMortgage() {
            const price = parseFloat(document.getElementById('property-price').value) || 0;
            const downPayment = parseFloat(document.getElementById('down-payment').value) || 0;
            const interestRate = parseFloat(document.getElementById('interest-rate').value) || 0;
            const loanTerm = parseInt(document.getElementById('loan-term').value) || 30;
            
            const loanAmount = price - downPayment;
            const monthlyRate = interestRate / 100 / 12;
            const numberOfPayments = loanTerm * 12;
            
            let monthlyPayment = 0;
            if (monthlyRate > 0) {
                monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numberOfPayments)) / (Math.pow(1 + monthlyRate, numberOfPayments) - 1);
            } else {
                monthlyPayment = loanAmount / numberOfPayments;
            }
            
            const estimatedTaxesInsurance = price * 0.015 / 12; // Estimate 1.5% annually
            const totalMonthlyPayment = monthlyPayment + estimatedTaxesInsurance;
            
            // Update display
            document.getElementById('monthly-payment').textContent = 
                '$' + totalMonthlyPayment.toLocaleString('en-US', { maximumFractionDigits: 0 });
            document.getElementById('principal-interest').textContent = 
                '$' + monthlyPayment.toLocaleString('en-US', { maximumFractionDigits: 0 });
            document.getElementById('taxes-insurance').textContent = 
                '$' + estimatedTaxesInsurance.toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
    }
    
    // Gallery functionality
    function initializeGallery() {
        const galleryImages = document.querySelectorAll('.gallery-thumbnail, .gallery-main-image');
        const moreBtn = document.querySelector('.gallery-more-btn');
        
        galleryImages.forEach(img => {
            img.addEventListener('click', function() {
                openLightbox(this.dataset.full, this.alt);
            });
        });
        
        if (moreBtn) {
            moreBtn.addEventListener('click', function() {
                openFullGallery();
            });
        }
    }
    
    // Map functionality
    function initializeMap() {
        const mapContainer = document.getElementById('property-map');
        if (mapContainer && typeof google !== 'undefined') {
            const lat = parseFloat(mapContainer.dataset.lat);
            const lng = parseFloat(mapContainer.dataset.lng);
            const title = mapContainer.dataset.title;
            
            const map = new google.maps.Map(mapContainer, {
                center: { lat: lat, lng: lng },
                zoom: 15,
                mapTypeId: 'roadmap'
            });
            
            const marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: title
            });
        }
    }
    
    // Nearby places functionality
    function initializeNearbyPlaces() {
        const nearbyBtns = document.querySelectorAll('.nearby-btn');
        
        nearbyBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Reset all buttons
                nearbyBtns.forEach(b => b.classList.remove('btn-primary'));
                nearbyBtns.forEach(b => b.classList.add('btn-secondary'));
                
                // Activate clicked button
                this.classList.remove('btn-secondary');
                this.classList.add('btn-primary');
            });
        });
    }
    
    // Utility functions
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert--${type}`;
        alertDiv.textContent = message;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            z-index: 9999;
            color: white;
            background: ${type === 'success' ? '#059669' : '#dc2626'};
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    function openLightbox(imageSrc, altText) {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        lightbox.innerHTML = `
            <div class="lightbox-content" style="position: relative; max-width: 90%; max-height: 90%;">
                <img src="${imageSrc}" alt="${altText}" style="max-width: 100%; max-height: 100%; border-radius: 8px;">
                <button class="lightbox-close" style="position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 2rem; cursor: pointer;">&times;</button>
            </div>
        `;
        
        document.body.appendChild(lightbox);
        
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox || e.target.classList.contains('lightbox-close')) {
                lightbox.remove();
            }
        });
    }
    
    function openFullGallery() {
        console.log('Opening full gallery...');
    }
    
    function searchNearbyPlaces(type) {
        console.log('Searching for nearby:', type);
    }
});
</script>

<?php get_footer(); ?>