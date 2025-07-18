#!/bin/bash

# Happy Place Theme - Complete Listing Page Setup Script
# This script creates all the files and structure needed for the single listing page

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if we're in the right directory
if [[ ! -f "functions.php" ]] || [[ ! -f "style.css" ]]; then
    print_error "This doesn't appear to be a WordPress theme directory."
    print_error "Please run this script from your theme's root directory."
    exit 1
fi

print_step "Setting up Single Listing Page Structure..."

# Create directory structure
print_status "Creating directory structure..."
mkdir -p template-parts/{listings,components,forms,global}
mkdir -p assets/src/{scss/{pages,components,layout},js/modules/listings}

# === ROOT TEMPLATE FILES ===
print_step "Creating root template files..."

# Main single-listing.php template
cat > single-listing.php << 'EOF

# === SIDEBAR WIDGET STYLES ===
print_step "Creating sidebar widget styles..."

# Sidebar widgets SCSS
cat > assets/src/scss/components/_sidebar-widgets.scss << 'EOF'
// Sidebar Widget Styles

.sidebar-widget {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);

  .widget-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
  }
}

// Agent Card
.agent-card {
  text-align: center;

  .agent-info {
    margin-bottom: 1.5rem;
  }

  .agent-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    border: 3px solid #f8f9fa;
  }

  .agent-name {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
  }

  .agent-title {
    color: #666;
    font-size: 0.9rem;
  }

  .agent-contact {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;

    .contact-btn {
      background: #667eea;
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.3s ease;
      text-align: center;

      &:hover {
        background: #5a6fd8;
      }

      &.secondary {
        background: #28a745;

        &:hover {
          background: #218838;
        }
      }
    }
  }
}

// Mortgage Calculator
.mortgage-calculator {
  .calculator-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;

    .form-group {
      display: flex;
      flex-direction: column;

      label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
      }

      input, select {
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        transition: border-color 0.3s ease;

        &:focus {
          outline: none;
          border-color: #667eea;
        }
      }
    }

    .calculate-btn {
      background: #667eea;
      color: white;
      border: none;
      padding: 1rem;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      margin-top: 0.5rem;
      transition: background 0.3s ease;

      &:hover {
        background: #5a6fd8;
      }
    }

    .monthly-payment {
      background: #e8f5e8;
      padding: 1rem;
      border-radius: 6px;
      text-align: center;
      margin-top: 1rem;
      border: 1px solid #d4edda;

      .payment-label {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 0.5rem;
      }

      .payment-amount {
        font-size: 1.5rem;
        font-weight: 700;
        color: #28a745;
      }
    }
  }
}

// Contact Form
.contact-form-widget {
  .contact-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;

    .form-row {
      display: flex;
      gap: 1rem;

      .form-group {
        flex: 1;
      }

      @media (max-width: 480px) {
        flex-direction: column;
        gap: 1rem;
      }
    }

    .form-group {
      display: flex;
      flex-direction: column;

      label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
      }

      input, textarea {
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        transition: border-color 0.3s ease;

        &:focus {
          outline: none;
          border-color: #667eea;
        }
      }

      textarea {
        min-height: 100px;
        resize: vertical;
      }
    }

    .form-messages {
      margin-top: 1rem;

      .success {
        background: #d4edda;
        color: #155724;
        padding: 0.75rem;
        border-radius: 4px;
        border: 1px solid #c3e6cb;
      }

      .error {
        background: #f8d7da;
        color: #721c24;
        padding: 0.75rem;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
      }

      .loading {
        background: #d1ecf1;
        color: #0c5460;
        padding: 0.75rem;
        border-radius: 4px;
        border: 1px solid #bee5eb;
      }
    }
  }
}

// Quick Actions
.quick-actions {
  .action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;

    .action-btn {
      flex: 1;
      min-width: 120px;
      padding: 0.75rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      text-align: center;
      font-size: 0.9rem;

      &.btn-primary {
        background: #667eea;
        color: white;

        &:hover {
          background: #5a6fd8;
        }
      }

      &.btn-outline {
        background: transparent;
        border: 2px solid #667eea;
        color: #667eea;

        &:hover {
          background: #667eea;
          color: white;
        }
      }
    }
  }
}
EOF

# === FUNCTIONS.PHP ADDITIONS ===
print_step "Creating functions.php additions..."

# Functions to add to functions.php
cat > functions-listing-additions.php << 'EOF'
<?php
/**
 * Add these functions to your functions.php file
 */

// Enqueue listing-specific assets
function enqueue_listing_assets() {
    if (is_singular('listing')) {
        wp_enqueue_script(
            'single-listing',
            get_template_directory_uri() . '/assets/dist/js/single-listing.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('single-listing', 'wpAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('listing_ajax_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_listing_assets');

// AJAX handler for contact form
function handle_listing_contact_form() {
    check_ajax_referer('listing_contact_form', 'listing_contact_nonce');
    
    $listing_id = intval($_POST['listing_id']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_textarea_field($_POST['message']);
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        wp_send_json_error('Please fill in all required fields.');
    }
    
    // Send email to agent
    $listing_title = get_the_title($listing_id);
    $agent_id = get_post_meta($listing_id, 'listing_agent', true);
    $agent_email = get_post_meta($agent_id, 'agent_email', true);
    
    if (!$agent_email) {
        $agent_email = get_option('admin_email');
    }
    
    $subject = "Listing Inquiry: {$listing_title}";
    $email_message = "
        New inquiry for listing: {$listing_title}
        
        Name: {$first_name} {$last_name}
        Email: {$email}
        Phone: {$phone}
        
        Message:
        {$message}
        
        Listing URL: " . get_permalink($listing_id);
    
    $sent = wp_mail($agent_email, $subject, $email_message);
    
    if ($sent) {
        wp_send_json_success('Message sent successfully!');
    } else {
        wp_send_json_error('Failed to send message. Please try again.');
    }
}
add_action('wp_ajax_listing_contact_form', 'handle_listing_contact_form');
add_action('wp_ajax_nopriv_listing_contact_form', 'handle_listing_contact_form');

// AJAX handler for saving favorites
function handle_save_favorite() {
    check_ajax_referer('listing_ajax_nonce', 'nonce');
    
    $listing_id = intval($_POST['listing_id']);
    $is_favorite = $_POST['is_favorite'] === 'true';
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in to save favorites.');
    }
    
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'favorite_listings', true);
    
    if (!is_array($favorites)) {
        $favorites = [];
    }
    
    if ($is_favorite) {
        if (!in_array($listing_id, $favorites)) {
            $favorites[] = $listing_id;
        }
    } else {
        $favorites = array_filter($favorites, function($id) use ($listing_id) {
            return $id != $listing_id;
        });
    }
    
    update_user_meta($user_id, 'favorite_listings', $favorites);
    wp_send_json_success();
}
add_action('wp_ajax_save_favorite', 'handle_save_favorite');

// AJAX handler for saving listings
function handle_save_listing() {
    check_ajax_referer('listing_ajax_nonce', 'nonce');
    
    $listing_id = intval($_POST['listing_id']);
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in to save listings.');
    }
    
    $user_id = get_current_user_id();
    $saved_listings = get_user_meta($user_id, 'saved_listings', true);
    
    if (!is_array($saved_listings)) {
        $saved_listings = [];
    }
    
    if (!in_array($listing_id, $saved_listings)) {
        $saved_listings[] = $listing_id;
        update_user_meta($user_id, 'saved_listings', $saved_listings);
    }
    
    wp_send_json_success('Listing saved successfully!');
}
add_action('wp_ajax_save_listing', 'handle_save_listing');

// Add custom meta boxes for listing fields
function add_listing_meta_boxes() {
    add_meta_box(
        'listing_details',
        'Listing Details',
        'listing_details_callback',
        'listing',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_listing_meta_boxes');

function listing_details_callback($post) {
    wp_nonce_field('listing_details_nonce', 'listing_details_nonce');
    
    $fields = [
        'listing_price' => 'Price',
        'listing_status' => 'Status',
        'listing_address' => 'Address',
        'mls_number' => 'MLS Number',
        'bedrooms' => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'square_feet' => 'Square Feet',
        'lot_size' => 'Lot Size',
        'year_built' => 'Year Built',
        'listing_agent' => 'Agent ID',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'walk_score' => 'Walk Score',
        'school_rating' => 'School Rating',
    ];
    
    echo '<table class="form-table">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th><label for="' . $key . '">' . $label . '</label></th>';
        echo '<td><input type="text" id="' . $key . '" name="' . $key . '" value="' . esc_attr($value) . '" /></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Save listing meta
function save_listing_meta($post_id) {
    if (!isset($_POST['listing_details_nonce']) || !wp_verify_nonce($_POST['listing_details_nonce'], 'listing_details_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $fields = [
        'listing_price', 'listing_status', 'listing_address', 'mls_number',
        'bedrooms', 'bathrooms', 'square_feet', 'lot_size', 'year_built',
        'listing_agent', 'latitude', 'longitude', 'walk_score', 'school_rating'
    ];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'save_listing_meta');
EOF

# === UPDATE MAIN SCSS ===
print_step "Updating main SCSS imports..."

# Add imports to main.scss
cat >> assets/src/scss/main.scss << 'EOF'

// Listing-specific styles
@import "pages/single-listing";
@import "components/gallery";
@import "components/property-details";
@import "components/sidebar-widgets";
EOF

# === UPDATE MAIN JS ===
print_step "Updating main JavaScript..."

# Add import to main.js
cat >> assets/src/js/main.js << 'EOF'

// Import listing functionality
import './modules/listings/SingleListing';
EOF

# === WEBPACK ENTRY POINT ===
print_step "Creating webpack entry for listings..."

# Create listing-specific JS entry
cat > assets/src/js/single-listing.js << 'EOF'
// Single Listing Entry Point
import './modules/listings/SingleListing';
EOF

# === FINAL STEPS ===
print_step "Final setup steps..."

# Create security index files
find template-parts/ -type d -exec touch {}/index.php \; 2>/dev/null || true

# Set permissions
chmod -R 755 template-parts/
chmod -R 755 assets/src/
chmod 644 single-listing.php

print_status "✅ Single listing page structure created successfully!"
print_status ""
print_status "📋 Next steps:"
echo "1. Add the contents of 'functions-listing-additions.php' to your functions.php"
echo "2. Run 'npm run build' to compile assets"
echo "3. Create a test listing post to see the page in action"
echo "4. Customize the styling and functionality as needed"
echo ""
print_status "📁 Files created:"
echo "├── single-listing.php (main template)"
echo "├── template-parts/listings/ (listing components)"
echo "├── template-parts/components/ (reusable widgets)"
echo "├── template-parts/forms/ (contact form)"
echo "├── assets/src/scss/ (styling)"
echo "├── assets/src/js/ (JavaScript functionality)"
echo "└── functions-listing-additions.php (WordPress functions)"
echo ""
print_status "🎉 Your single listing page is ready to use!"'
<?php
/**
 * Template for displaying single listing posts
 */

get_header(); ?>

<main class="listing-single">
    <?php while (have_posts()) : the_post(); ?>
        
        <?php get_template_part('template-parts/listings/single-header'); ?>
        
        <div class="container">
            <div class="listing-content">
                <div class="main-content">
                    <?php get_template_part('template-parts/listings/single-gallery'); ?>
                    <?php get_template_part('template-parts/listings/single-details'); ?>
                    <?php get_template_part('template-parts/listings/single-features'); ?>
                    <?php get_template_part('template-parts/listings/single-map'); ?>
                </div>
                
                <div class="sidebar">
                    <?php get_template_part('template-parts/components/agent-card'); ?>
                    <?php get_template_part('template-parts/components/mortgage-calculator'); ?>
                    <?php get_template_part('template-parts/forms/contact-form'); ?>
                    <?php get_template_part('template-parts/components/quick-actions'); ?>
                </div>
            </div>
        </div>
        
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
EOF

# === TEMPLATE PARTS ===
print_step "Creating template parts..."

# Listing Header
cat > template-parts/listings/single-header.php << 'EOF'
<?php
/**
 * Single listing header
 */

$listing_id = get_the_ID();
$price = get_post_meta($listing_id, 'listing_price', true);
$status = get_post_meta($listing_id, 'listing_status', true);
$address = get_post_meta($listing_id, 'listing_address', true);
$mls_number = get_post_meta($listing_id, 'mls_number', true);
?>

<div class="listing-header">
    <div class="container">
        <h1 class="listing-title"><?php the_title(); ?></h1>
        
        <?php if ($address) : ?>
            <div class="listing-address">📍 <?php echo esc_html($address); ?></div>
        <?php endif; ?>
        
        <div class="listing-meta">
            <?php if ($price) : ?>
                <div class="price">$<?php echo number_format($price); ?></div>
            <?php endif; ?>
            
            <?php if ($status) : ?>
                <div class="listing-status <?php echo esc_attr(strtolower($status)); ?>">
                    <?php echo esc_html($status); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($mls_number) : ?>
                <div class="listing-id">MLS# <?php echo esc_html($mls_number); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
EOF

# Listing Gallery
cat > template-parts/listings/single-gallery.php << 'EOF'
<?php
/**
 * Single listing gallery
 */

$listing_id = get_the_ID();
$gallery_images = get_post_meta($listing_id, 'listing_gallery', true);
$featured_image = get_the_post_thumbnail_url($listing_id, 'full');

// If no gallery, use featured image
if (empty($gallery_images) && $featured_image) {
    $gallery_images = [$featured_image];
}
?>

<div class="listing-gallery" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <?php if (!empty($gallery_images)) : ?>
        <button class="favorite-btn" data-listing-id="<?php echo esc_attr($listing_id); ?>">♡</button>
        
        <div class="gallery-main-container">
            <img src="<?php echo esc_url($gallery_images[0]); ?>" 
                 alt="<?php echo esc_attr(get_the_title()); ?>" 
                 class="gallery-main">
        </div>
        
        <div class="gallery-counter">
            <span class="current">1</span> / <span class="total"><?php echo count($gallery_images); ?></span>
        </div>
        
        <?php if (count($gallery_images) > 1) : ?>
            <div class="gallery-nav">
                <?php foreach ($gallery_images as $index => $image) : ?>
                    <div class="gallery-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                         data-index="<?php echo $index; ?>"
                         data-image="<?php echo esc_url($image); ?>"></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="gallery-data" style="display: none;">
            <?php echo wp_json_encode($gallery_images); ?>
        </div>
    <?php else : ?>
        <div class="no-gallery">
            <div class="placeholder-image">📷 No images available</div>
        </div>
    <?php endif; ?>
</div>
EOF

# Listing Details
cat > template-parts/listings/single-details.php << 'EOF'
<?php
/**
 * Single listing details
 */

$listing_id = get_the_ID();
$bedrooms = get_post_meta($listing_id, 'bedrooms', true);
$bathrooms = get_post_meta($listing_id, 'bathrooms', true);
$square_feet = get_post_meta($listing_id, 'square_feet', true);
$lot_size = get_post_meta($listing_id, 'lot_size', true);
$year_built = get_post_meta($listing_id, 'year_built', true);
?>

<div class="property-details">
    <!-- Property Stats -->
    <div class="property-stats">
        <?php if ($bedrooms) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($bedrooms); ?></div>
                <div class="stat-label">Bedrooms</div>
            </div>
        <?php endif; ?>
        
        <?php if ($bathrooms) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($bathrooms); ?></div>
                <div class="stat-label">Bathrooms</div>
            </div>
        <?php endif; ?>
        
        <?php if ($square_feet) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($square_feet); ?></div>
                <div class="stat-label">Sq Ft</div>
            </div>
        <?php endif; ?>
        
        <?php if ($lot_size) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($lot_size); ?></div>
                <div class="stat-label">Acres</div>
            </div>
        <?php endif; ?>
        
        <?php if ($year_built) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($year_built); ?></div>
                <div class="stat-label">Year Built</div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Description -->
    <div class="details-section">
        <h3 class="section-title">Property Description</h3>
        <div class="property-description">
            <?php the_content(); ?>
        </div>
    </div>
</div>
EOF

# Listing Features
cat > template-parts/listings/single-features.php << 'EOF'
<?php
/**
 * Single listing features
 */

$listing_id = get_the_ID();
$interior_features = get_post_meta($listing_id, 'interior_features', true);
$exterior_features = get_post_meta($listing_id, 'exterior_features', true);
?>

<div class="details-section">
    <h3 class="section-title">Property Features</h3>
    <div class="features-grid">
        
        <?php if (!empty($interior_features)) : ?>
            <div class="feature-category">
                <h4>Interior Features</h4>
                <ul class="feature-list">
                    <?php foreach ($interior_features as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($exterior_features)) : ?>
            <div class="feature-category">
                <h4>Exterior Features</h4>
                <ul class="feature-list">
                    <?php foreach ($exterior_features as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($interior_features) && empty($exterior_features)) : ?>
            <div class="feature-category">
                <h4>Interior Features</h4>
                <ul class="feature-list">
                    <li>Hardwood floors</li>
                    <li>Granite countertops</li>
                    <li>Stainless steel appliances</li>
                    <li>Walk-in closets</li>
                </ul>
            </div>
            <div class="feature-category">
                <h4>Exterior Features</h4>
                <ul class="feature-list">
                    <li>Covered patio</li>
                    <li>Landscaped yard</li>
                    <li>2-car garage</li>
                    <li>Sprinkler system</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
EOF

# Listing Map
cat > template-parts/listings/single-map.php << 'EOF'
<?php
/**
 * Single listing map
 */

$listing_id = get_the_ID();
$latitude = get_post_meta($listing_id, 'latitude', true);
$longitude = get_post_meta($listing_id, 'longitude', true);
$walk_score = get_post_meta($listing_id, 'walk_score', true);
$school_rating = get_post_meta($listing_id, 'school_rating', true);
?>

<div class="details-section">
    <h3 class="section-title">Location & Neighborhood</h3>
    
    <div class="map-container" 
         data-lat="<?php echo esc_attr($latitude); ?>" 
         data-lng="<?php echo esc_attr($longitude); ?>"
         data-listing-id="<?php echo esc_attr($listing_id); ?>">
        <div class="map-placeholder">📍 Loading Map...</div>
    </div>
    
    <div class="neighborhood-info">
        <?php if ($walk_score) : ?>
            <div class="info-item">
                <span class="label">Walk Score:</span>
                <span class="value"><?php echo esc_html($walk_score); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($school_rating) : ?>
            <div class="info-item">
                <span class="label">School Rating:</span>
                <span class="value"><?php echo esc_html($school_rating); ?>/10</span>
            </div>
        <?php endif; ?>
        
        <div class="info-item">
            <span class="label">Crime Rate:</span>
            <span class="value">Low</span>
        </div>
    </div>
</div>
EOF

# === COMPONENTS ===
print_step "Creating component files..."

# Agent Card
cat > template-parts/components/agent-card.php << 'EOF'
<?php
/**
 * Agent card component
 */

$listing_id = get_the_ID();
$agent_id = get_post_meta($listing_id, 'listing_agent', true);

if ($agent_id) {
    $agent_name = get_the_title($agent_id);
    $agent_title = get_post_meta($agent_id, 'agent_title', true);
    $agent_phone = get_post_meta($agent_id, 'agent_phone', true);
    $agent_email = get_post_meta($agent_id, 'agent_email', true);
    $agent_photo = get_the_post_thumbnail_url($agent_id, 'thumbnail');
} else {
    // Default agent info
    $agent_name = 'Sarah Johnson';
    $agent_title = 'Senior Real Estate Agent';
    $agent_phone = '(555) 123-4567';
    $agent_email = 'sarah@happyplace.com';
    $agent_photo = get_template_directory_uri() . '/assets/images/default-agent.jpg';
}
?>

<div class="sidebar-widget agent-card">
    <h3 class="widget-title">Listed By</h3>
    
    <div class="agent-info">
        <?php if ($agent_photo) : ?>
            <img src="<?php echo esc_url($agent_photo); ?>" 
                 alt="<?php echo esc_attr($agent_name); ?>" 
                 class="agent-photo">
        <?php endif; ?>
        
        <div class="agent-name"><?php echo esc_html($agent_name); ?></div>
        
        <?php if ($agent_title) : ?>
            <div class="agent-title"><?php echo esc_html($agent_title); ?></div>
        <?php endif; ?>
    </div>
    
    <div class="agent-contact">
        <?php if ($agent_phone) : ?>
            <a href="tel:<?php echo esc_attr($agent_phone); ?>" class="contact-btn">
                📞 Call Agent
            </a>
        <?php endif; ?>
        
        <?php if ($agent_email) : ?>
            <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="contact-btn secondary">
                ✉️ Email Agent
            </a>
        <?php endif; ?>
    </div>
</div>
EOF

# Mortgage Calculator
cat > template-parts/components/mortgage-calculator.php << 'EOF'
<?php
/**
 * Mortgage calculator component
 */

$listing_id = get_the_ID();
$price = get_post_meta($listing_id, 'listing_price', true);
$down_payment = $price ? ($price * 0.2) : 0;
?>

<div class="sidebar-widget mortgage-calculator">
    <h3 class="widget-title">Mortgage Calculator</h3>
    
    <form class="calculator-form" id="mortgage-calculator">
        <div class="form-group">
            <label for="home-price">Home Price</label>
            <input type="text" 
                   id="home-price" 
                   name="home_price" 
                   value="<?php echo $price ? '$' . number_format($price) : ''; ?>" 
                   readonly>
        </div>
        
        <div class="form-group">
            <label for="down-payment">Down Payment</label>
            <input type="text" 
                   id="down-payment" 
                   name="down_payment" 
                   value="<?php echo $down_payment ? '$' . number_format($down_payment) : ''; ?>" 
                   placeholder="20%">
        </div>
        
        <div class="form-group">
            <label for="interest-rate">Interest Rate</label>
            <input type="text" 
                   id="interest-rate" 
                   name="interest_rate" 
                   value="6.5%" 
                   placeholder="6.5%">
        </div>
        
        <div class="form-group">
            <label for="loan-term">Loan Term</label>
            <select id="loan-term" name="loan_term">
                <option value="30">30 years</option>
                <option value="15">15 years</option>
            </select>
        </div>
        
        <button type="button" class="calculate-btn" id="calculate-payment">
            Calculate Payment
        </button>
        
        <div class="monthly-payment" id="payment-result" style="display: none;">
            <div class="payment-label">Estimated Monthly Payment</div>
            <div class="payment-amount" id="payment-amount">$0</div>
        </div>
    </form>
</div>
EOF

# Quick Actions
cat > template-parts/components/quick-actions.php << 'EOF'
<?php
/**
 * Quick actions component
 */

$listing_id = get_the_ID();
?>

<div class="sidebar-widget quick-actions">
    <h3 class="widget-title">Quick Actions</h3>
    
    <div class="action-buttons">
        <button class="action-btn btn-primary save-listing" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>">
            💾 Save Listing
        </button>
        
        <button class="action-btn btn-outline share-listing" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>">
            📤 Share
        </button>
        
        <button class="action-btn btn-outline price-history" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>">
            📊 Price History
        </button>
        
        <button class="action-btn btn-outline print-listing" 
                onclick="window.print()">
            📋 Print Details
        </button>
    </div>
</div>
EOF

# === FORMS ===
print_step "Creating form files..."

# Contact Form
cat > template-parts/forms/contact-form.php << 'EOF'
<?php
/**
 * Contact form component
 */

$listing_id = get_the_ID();
$listing_title = get_the_title();
?>

<div class="sidebar-widget contact-form-widget">
    <h3 class="widget-title">Request Information</h3>
    
    <form class="contact-form" id="listing-contact-form" method="post">
        <?php wp_nonce_field('listing_contact_form', 'listing_contact_nonce'); ?>
        <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
        <input type="hidden" name="action" value="listing_contact_form">
        
        <div class="form-row">
            <div class="form-group">
                <label for="first-name">First Name *</label>
                <input type="text" id="first-name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last-name">Last Name *</label>
                <input type="text" id="last-name" name="last_name" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone">
        </div>
        
        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" 
                      name="message" 
                      rows="4" 
                      placeholder="I'm interested in <?php echo esc_attr($listing_title); ?>..."></textarea>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="action-btn btn-primary">
                Send Message
            </button>
            <button type="button" class="action-btn btn-outline schedule-tour" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                Schedule Tour
            </button>
        </div>
        
        <div class="form-messages" id="form-messages"></div>
    </form>
</div>
EOF

# === SCSS FILES ===
print_step "Creating SCSS files..."

# Main single listing SCSS
cat > assets/src/scss/pages/_single-listing.scss << 'EOF'
// Single Listing Page Styles

.listing-single {
  background: #f8f9fa;
  min-height: 100vh;
}

.listing-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 2rem;

  .listing-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .listing-address {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 1rem;
  }

  .listing-meta {
    display: flex;
    gap: 2rem;
    align-items: center;
    flex-wrap: wrap;

    .price {
      font-size: 2rem;
      font-weight: 700;
      color: #ffd700;
    }

    .listing-status {
      background: #28a745;
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;

      &.sold {
        background: #dc3545;
      }

      &.pending {
        background: #ffc107;
        color: #333;
      }
    }

    .listing-id {
      opacity: 0.8;
    }
  }
}

.listing-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.main-content {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
EOF

# Gallery component SCSS
cat > assets/src/scss/components/_gallery.scss << 'EOF'
// Gallery Component Styles

.listing-gallery {
  position: relative;
  height: 400px;
  overflow: hidden;

  .gallery-main-container {
    width: 100%;
    height: 100%;
  }

  .gallery-main {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .gallery-nav {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;

    .gallery-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: rgba(255,255,255,0.5);
      cursor: pointer;
      transition: all 0.3s ease;

      &.active {
        background: white;
        transform: scale(1.2);
      }
    }
  }

  .gallery-counter {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
  }

  .favorite-btn {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(255,255,255,0.9);
    border: none;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    z-index: 10;

    &:hover {
      background: white;
      transform: scale(1.1);
    }

    &.active {
      color: #dc3545;
    }
  }

  .no-gallery {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: #e9ecef;
    color: #666;
    font-size: 1.2rem;
  }
}
EOF

# Property details SCSS
cat > assets/src/scss/components/_property-details.scss << 'EOF'
// Property Details Component Styles

.property-details {
  padding: 2rem;
}

.property-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;

  .stat-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;

    .stat-number {
      font-size: 1.5rem;
      font-weight: 700;
      color: #667eea;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #666;
      margin-top: 0.25rem;
    }
  }
}

.details-section {
  margin-bottom: 2rem;

  .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 2px solid #667eea;
    padding-bottom: 0.5rem;
  }
}

.property-description {
  font-size: 1.1rem;
  line-height: 1.8;
  color: #555;
  margin-bottom: 1.5rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;

  .feature-category {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #667eea;

    h4 {
      color: #333;
      margin-bottom: 0.75rem;
      font-size: 1.1rem;
    }

    .feature-list {
      list-style: none;
      padding: 0;

      li {
        padding: 0.25rem 0;
        color: #666;

        &::before {
          content: "✓";
          color: #28a745;
          font-weight: bold;
          margin-right: 0.5rem;
        }
      }
    }
  }
}

.map-container {
  height: 300px;
  background: #e9ecef;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  margin-bottom: 1rem;

  .map-placeholder {
    font-size: 1.2rem;
  }
}

.neighborhood-info {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  font-size: 0.9rem;
  color: #666;

  .info-item {
    .label {
      font-weight: 600;
    }
  }
}
EOF

# === JAVASCRIPT FILES ===
print_step "Creating JavaScript files..."

# Main listing JavaScript
cat > assets/src/js/modules/listings/SingleListing.js << 'EOF'
/**
 * Single Listing Page JavaScript
 */

class SingleListing {
    constructor() {
        this.init();
    }

    init() {
        this.initGallery();
        this.initFavorites();
        this.initMortgageCalculator();
        this.initContactForm();
        this.initQuickActions();
    }

    initGallery() {
        const gallery = document.querySelector('.listing-gallery');
        if (!gallery) return;

        const dots = gallery.querySelectorAll('.gallery-dot');
        const mainImage = gallery.querySelector('.gallery-main');
        const counter = gallery.querySelector('.gallery-counter');

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                // Remove active class from all dots
                dots.forEach(d => d.classList.remove('active'));
                
                // Add active class to clicked dot
                dot.classList.add('active');
                
                // Update main image
                const imageUrl = dot.dataset.image;
                if (imageUrl && mainImage) {
                    mainImage.src = imageUrl;
                }
                
                // Update counter
                if (counter) {
                    counter.querySelector('.current').textContent = index + 1;
                }
            });
        });
    }

    initFavorites() {
        const favoriteBtn = document.querySelector('.favorite-btn');
        if (!favoriteBtn) return;

        favoriteBtn.addEventListener('click', () => {
            const listingId = favoriteBtn.dataset.listingId;
            const isActive = favoriteBtn.classList.contains('active');
            
            // Toggle visual state
            favoriteBtn.classList.toggle('active');
            favoriteBtn.textContent = isActive ? '♡' : '♥';
            
            // Send AJAX request to save favorite
            this.saveFavorite(listingId, !isActive);
        });
    }

    initMortgageCalculator() {
        const calcBtn = document.getElementById('calculate-payment');
        if (!calcBtn) return;

        calcBtn.addEventListener('click', () => {
            const homePrice = this.parsePrice(document.getElementById('home-price').value);
            const downPayment = this.parsePrice(document.getElementById('down-payment').value);
            const interestRate = parseFloat(document.getElementById('interest-rate').value);
            const loanTerm = parseInt(document.getElementById('loan-term').value);

            if (homePrice && downPayment && interestRate && loanTerm) {
                const monthlyPayment = this.calculateMonthlyPayment(
                    homePrice - downPayment,
                    interestRate / 100,
                    loanTerm
                );

                document.getElementById('payment-amount').textContent = 
                    ' + monthlyPayment.toLocaleString();
                document.getElementById('payment-result').style.display = 'block';
            }
        });
    }

    initContactForm() {
        const form = document.getElementById('listing-contact-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const messageDiv = document.getElementById('form-messages');
            
            // Show loading state
            messageDiv.innerHTML = '<div class="loading">Sending message...</div>';
            
            // Send AJAX request
            fetch(wpAjax.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success">Message sent successfully!</div>';
                    form.reset();
                } else {
                    messageDiv.innerHTML = '<div class="error">Error: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error">Error sending message.</div>';
            });
        });
    }

    initQuickActions() {
        // Save listing
        const saveBtn = document.querySelector('.save-listing');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const listingId = saveBtn.dataset.listingId;
                this.saveListing(listingId);
            });
        }

        // Share listing
        const shareBtn = document.querySelector('.share-listing');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        url: window.location.href
                    });
                } else {
                    // Fallback - copy to clipboard
                    navigator.clipboard.writeText(window.location.href);
                    alert('Link copied to clipboard!');
                }
            });
        }

        // Schedule tour
        const tourBtn = document.querySelector('.schedule-tour');
        if (tourBtn) {
            tourBtn.addEventListener('click', () => {
                const listingId = tourBtn.dataset.listingId;
                this.scheduleTour(listingId);
            });
        }
    }

    // Helper methods
    parsePrice(priceString) {
        return parseInt(priceString.replace(/[^0-9]/g, ''));
    }

    calculateMonthlyPayment(principal, annualRate, years) {
        const monthlyRate = annualRate / 12;
        const numPayments = years * 12;
        
        return (principal * monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
               (Math.pow(1 + monthlyRate, numPayments) - 1);
    }

    saveFavorite(listingId, isFavorite) {
        // AJAX call to save favorite
        fetch(wpAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_favorite&listing_id=${listingId}&is_favorite=${isFavorite}&nonce=${wpAjax.nonce}`
        });
    }

    saveListing(listingId) {
        // AJAX call to save listing
        fetch(wpAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_listing&listing_id=${listingId}&nonce=${wpAjax.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Listing saved successfully!');
            }
        });
    }

    scheduleTour(listingId) {
        // Open tour scheduling modal or redirect
        const tourUrl = `/schedule-tour/?listing=${listingId}`;
        window.open(tourUrl, '_blank');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SingleListing();
});

export default SingleListing;