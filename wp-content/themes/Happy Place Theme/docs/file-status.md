# Happy Place Theme - File Status

## Template Organization Principles
1. Root Template Files (`/`)
- All main template files should remain in the theme root for WordPress standard structure
- This includes `single-*.php`, `archive-*.php`, `page-*.php`, etc.
- Core WordPress templates like `index.php`, `404.php`, etc.

2. Template Parts (`/template-parts/`)
- Reusable components and partial templates
- Organized by feature/functionality (cards, forms, listing components, etc.)

## Redundant Files
1. Template Location Duplicates
- `/templates/archive-listing.php` - **Redundant**: Should be moved to root
- `/templates/single-*.php` files - **Redundant**: Should be moved to root
- `/templates/content-*.php` files - **Redundant**: Should be moved to root or template-parts

2. Duplicate Components
- `/templates/partials/card-listing.php` - **Redundant**: Use `/template-parts/cards/listing-list-card.php` instead
- `/templates/partials/no-results.php` - **Redundant**: Use `/template-parts/listing/no-results.php` instead

## Unused or Legacy Files

1. Preview Directory
- `/preview/*` - **Legacy**: Old static preview templates, can be removed

2. Old Dashboard Components
- `/templates/partials/dashboard/*` - **Legacy**: Old dashboard templates
  - `favorites.php`
  - `profile.php`
  - `overview.php`
  - `saved-searches.php`

## Files to Relocate

1. Move to Root (/)
- From `/templates/`:
  - `archive-listing.php`
  - `single-listing.php`
  - `single-agent.php`
  - `single-community.php`
  - `single-city.php`
  - `single-place.php`
  - Other main template files

2. Move to /template-parts/
- From `/templates/partials/`:
  - `header.php` → `/template-parts/header/header.php`
  - `footer.php` → `/template-parts/footer/footer.php`
  - `search-form.php` → `/template-parts/forms/search-form.php`
  - `filter-chips.php` → `/template-parts/filters/filter-chips.php`
  - `property-filters.php` → `/template-parts/filters/property-filters.php`
  - `map-view.php` → `/template-parts/listing/map-view.php`

## Currently Active Structure

1. Root Templates (/)
- `functions.php`
- `header.php`
- `footer.php`
- `index.php`
- `single-listing.php`
- `archive-listing.php`
- Other main templates

2. Template Parts (/template-parts/)
- `/cards/`
  - `listing-list-card.php`
  - `listing-swipe-card.php`
- `/listing/`
  - `card.php`
  - `map-markers.php`
  - `no-results.php`
- `/forms/`
  - `submit-*.php`
- `/filters/`
  - `filter-sidebar.php`
- `/calculators/`
  - `mortgage-calculator.php`

## Recommendations

1. Template Cleanup
- Move all main templates to root directory
- Remove `/templates/` directory after migration
- Remove `/preview/` directory

2. Component Organization
- Keep all reusable parts in `/template-parts/`
- Organize by feature type (cards, forms, filters, etc.)
- Update template part calls to use new structure

3. Documentation Updates
- Update inline documentation to reflect new structure
- Add template hierarchy documentation
- Document template part locations in `functions.php`

## Note on Migration
- When moving files, update all `get_template_part()` calls
- Test thoroughly after moving each template
- Keep clear separation between main templates and components
- Maintain WordPress template hierarchy standards

# Template and Asset Dependencies

## Template Part Usage

1. Root Templates
- `archive-listing.php` uses:
  - `template-parts/cards/listing-list-card.php`
  - `template-parts/cards/listing-swipe-card.php`
  - `template-parts/filters/filter-sidebar.php`
  - `template-parts/listing/map-markers.php`

- `single-listing.php` uses:
  - `template-parts/listing/gallery.php`
  - `template-parts/listing/map-view.php`
  - `template-parts/calculators/mortgage-calculator.php`

2. AJAX Handler
- `class-ajax-handler.php` uses:
  - `template-parts/listing/card.php`
  - `template-parts/listing/no-results.php`

3. Shortcode Templates
- `inc/shortcodes.php` uses:
  - `template-parts/forms/submit-agent.php`
  - `template-parts/forms/submit-community.php`
  - `template-parts/forms/submit-city.php`
  - `template-parts/forms/submit-listing.php`
  - `template-parts/forms/submit-open-house.php`
  - `template-parts/forms/submit-transaction.php`
  - `template-parts/forms/submit-client.php`

## Asset Dependencies

1. Core Assets
- `style.css` depends on:
  - Font Awesome

- `core.js` depends on:
  - jQuery

- `theme.js` depends on:
  - jQuery
  - core.js

2. Listing Assets
- `archive-listing.css` depends on:
  - happyplace-main

- `listing-swipe-card.css` depends on:
  - happyplace-main

- `listing-list-card.css` depends on:
  - happyplace-main

- `listing-map.css` depends on:
  - happyplace-main

- `maps.css` depends on:
  - happyplace-main

- `single-listing.css` depends on:
  - happyplace-main

3. Map Components
- `map-info-window.css` depends on:
  - listing-map.css

- `map-clusters.css` depends on:
  - map-info-window.css

4. JavaScript Dependencies
- `listing-swipe-card.js` depends on:
  - jQuery

- `archive-listing.js` depends on:
  - jQuery

- `filter-sidebar.js` depends on:
  - jQuery

5. Third-party Libraries
- Slick Slider:
  - slick.css
  - slick-theme.css
  - slick.js (depends on jQuery)

## Loading Order

1. Base Assets
- Font Awesome
- Main theme styles (style.css)
- Core JavaScript (core.js)
- Theme JavaScript (theme.js)

2. Template-specific Assets
- Loaded conditionally based on current template:
  - Archive listing assets for listing archives
  - Single listing assets for single listings
  - Map assets when maps are displayed

3. Component Assets
- Loaded based on template part usage:
  - Card styles with card templates
  - Form styles with form templates
  - Calculator styles with calculator templates

## Notes
- All JavaScript files are enqueued in the footer (true in wp_enqueue_script)
- CSS files maintain proper dependency chain for overlapping styles
- Maps and related components have specific loading order requirements
- Template parts are loaded on-demand, ensuring optimal performance
- AJAX handlers use specific template parts for dynamic content

# Field Usage and Dependencies

## Listing Fields

### Property Details
1. Basic Information
- `price` (number)
  - Used in: single-listing.php, archive-listing.php, template-parts/cards/listing-list-card.php
  - Used for: Display price, mortgage calculator
- `status` (select)
  - Used in: single-listing.php, archive-listing.php, class-listing-helper.php
  - Options: Active, Pending, Sold, Coming Soon
- `property_type` (taxonomy)
  - Used in: single-listing.php, class-listing-helper.php
  - Used for: Filtering, similar properties

2. Physical Characteristics
- `bedrooms` (number)
  - Used in: single-listing.php, archive-listing.php, template-parts/cards/*
- `bathrooms` (number)
  - Used in: single-listing.php, archive-listing.php, template-parts/cards/*
- `square_footage` (number)
  - Used in: single-listing.php, archive-listing.php, class-listing-helper.php
- `lot_size` (number)
  - Used in: single-listing.php, class-listing-helper.php
  - Unit: Acres
- `year_built` (number)
  - Used in: single-listing.php, class-listing-helper.php

3. Location Fields
- `street_address` (text)
  - Used in: single-listing.php, archive-listing.php, template-parts/cards/*
- `city` (text)
  - Used in: single-listing.php, archive-listing.php
- `region` (text)
  - Used in: single-listing.php, archive-listing.php
- `zip_code` (text)
  - Used in: single-listing.php, archive-listing.php
- `latitude` (number)
  - Used in: single-listing.php, archive-listing.php, class-listing-helper.php
- `longitude` (number)
  - Used in: single-listing.php, archive-listing.php, class-listing-helper.php
- `full_address` (text)
  - Used in: single-listing.php, archive-listing.php, class-listing-helper.php
  - Automatically generated from address components

4. Features and Amenities
- `features` (repeater)
  - Used in: single-listing.php, class-listing-helper.php
  - Sub-fields: name, icon, description
- `exterior_features` (repeater)
  - Used in: single-listing.php
- `utility_features` (repeater)
  - Used in: single-listing.php

5. Media
- `main_photo` (image)
  - Used in: single-listing.php, archive-listing.php, class-listing-helper.php
  - Size requirements: min 1200x800px
- `photo_gallery` (gallery)
  - Used in: single-listing.php, archive-listing.php, template-parts/cards/*
- `virtual_tour_link` (url)
  - Used in: single-listing.php, class-listing-helper.php

6. Additional Details
- `price_per_sqft` (number)
  - Used in: single-listing.php, class-listing-helper.php
  - Calculated field
- `mls_number` (text)
  - Used in: single-listing.php, class-listing-helper.php
- `short_description` (textarea)
  - Used in: single-listing.php, class-listing-helper.php
- `highlight_badges` (repeater)
  - Used in: template-parts/cards/*, class-listing-helper.php
  - Sub-fields: badge_text, badge_color

## Agent Fields

1. Contact Information
- `email` (email)
  - Used in: single-agent.php, template-parts/cards/agent-card.php
- `phone` (text)
  - Used in: single-agent.php, template-parts/cards/agent-card.php
- `title` (text)
  - Used in: single-agent.php
- `license_number` (text)
  - Used in: single-agent.php
- `license_state` (text)
  - Used in: single-agent.php

2. Profile
- `profile_photo` (image)
  - Used in: single-agent.php, template-parts/cards/*
  - Size requirements: min 400x400px
- `biography` (wysiwyg)
  - Used in: single-agent.php
- `specialties` (repeater)
  - Used in: single-agent.php
  - Sub-fields: specialty_name, years_experience
- `certifications` (repeater)
  - Used in: single-agent.php
  - Sub-fields: name, year_obtained, issuing_body

3. Social Media
- `social_links` (repeater)
  - Used in: single-agent.php
  - Sub-fields: platform, url, icon

4. Preferences
- `contact_preferences` (checkbox)
  - Used in: single-agent.php
  - Options: phone_ok, email_ok, text_ok
- `schedule_link` (url)
  - Used in: single-agent.php
- `chat_link` (url)
  - Used in: single-agent.php

## Field Dependencies
1. Listing Display Dependencies
- Card display requires: main_photo, price, status, bedrooms, bathrooms, square_footage
- Map display requires: latitude, longitude, status
- Gallery requires: photo_gallery with at least one image

2. Calculated Fields
- full_address: Generated from street_address, city, region, zip_code
- price_per_sqft: Calculated from price and square_footage
- total_bathrooms: Sum of full_bathrooms and partial_bathrooms

3. Required Fields
- Listings: price, status, street_address, city, region, main_photo
- Agents: name, email, phone, license_number, profile_photo

4. Conditional Fields
- virtual_tour_link: Optional, enables virtual tour section when present
- highlight_badges: Optional, displayed on cards when present
- lot_size: Required for land, optional for other property types

# Listing Template Field Handling

## Template Field Flow

1. Archive Template (`archive-listing.php`)
- **Primary Data Collection**:
  ```php
  // Core fields for listing cards
  $price = get_field('price', $listing_id);
  $status = get_field('status', $listing_id);
  $bedrooms = get_field('bedrooms', $listing_id);
  $bathrooms = get_field('bathrooms', $listing_id);
  $square_footage = get_field('square_footage', $listing_id);
  
  // Location data for map view
  $street = get_field('street_address', $listing_id);
  $city = get_field('city', $listing_id);
  $region = get_field('region', $listing_id);
  $zip = get_field('zip_code', $listing_id);
  $latitude = get_field('latitude', $listing_id);
  $longitude = get_field('longitude', $listing_id);
  ```
- **Data Passing to Templates**:
  ```php
  get_template_part('template-parts/cards/listing-list-card', null, [
      'post_id' => get_the_ID(),
      'size' => 'default',
      'show_agent' => true
  ]);
  ```

2. Card Templates
- **List Card** (`template-parts/cards/listing-list-card.php`):
  - Receives: post_id, size, show_agent
  - Retrieves own data if not passed
  - Required fields: price, status, bedrooms, bathrooms, square_footage
  - Optional fields: agent_id, gallery, full_address

- **Swipe Card** (`template-parts/cards/listing-swipe-card.php`):
  - Receives: post_id, size
  - Focuses on image presentation
  - Required fields: main_photo, price, status
  - Optional fields: highlight_badges, features

3. Single Template (`single-listing.php`)
- **Data Organization**:
  ```php
  // Basic Info
  $price = get_field('price');
  $status = get_field('status') ?: 'Active';
  $bedrooms = get_field('bedrooms');
  $bathrooms = get_field('bathrooms');
  $square_feet = get_field('square_footage');
  
  // Location
  $street_address = get_field('street_address');
  $city = get_field('city');
  $state = get_field('region');
  $zip = get_field('zip_code');
  
  // Additional Details
  $lot_size = get_field('lot_size');
  $year_built = get_field('year_built');
  $description = get_field('short_description');
  $virtual_tour = get_field('virtual_tour_link');
  ```

## Field Dependencies by Template

1. Archive View Requirements
- **Map View**:
  - Required: latitude, longitude, status
  - Optional: property_type, features
  - Filter Fields: price_min, price_max, bedrooms, bathrooms

- **List/Grid View**:
  - Required: main_photo, price, status
  - Optional: badges, features
  - Sort Fields: price, date, featured status

2. Single View Requirements
- **Hero Section**:
  - Required: main_photo OR gallery_images
  - Required: price, status, full_address
  - Optional: bedrooms, bathrooms, square_feet

- **Details Section**:
  - Required: property_type, year_built, square_footage
  - Optional: lot_size, garage, mls_number

- **Features Section**:
  - Optional: features (array)
  - Optional: exterior_features (array)
  - Optional: utility_features (array)

3. Card Components Requirements
- **List Card**:
  ```php
  // Required Fields
  'price'          => number
  'bedrooms'       => number
  'bathrooms'      => number
  'square_footage' => number
  'status'         => string
  'street_address' => string
  
  // Optional Fields
  'property_type'  => taxonomy
  'full_address'   => string
  'photo_gallery'  => array
  'agent'          => post_id
  ```

- **Swipe Card**:
  ```php
  // Required Fields
  'main_photo'     => image
  'price'          => number
  'status'         => string
  
  // Optional Fields
  'highlight_badges' => array
  'features'        => array
  'neighborhood'    => string
  'openhouse'       => array
  ```

## Field Data Flow

1. Template Hierarchy:
```
archive-listing.php
├── listing-list-card.php
│   └── agent data (if show_agent=true)
└── listing-swipe-card.php
    └── highlight_badges (if present)
```

2. Data Passing Methods:
- Direct field retrieval in main templates
- Template part arguments for reusable components
- Cached data for repeated use (markers, filters)

3. Field Value Processing:
```php
// Price formatting
number_format($price)

// Status class generation
sanitize_html_class(strtolower($status))

// Address composition
$full_address = implode(', ', array_filter([
    $street_address,
    $city,
    $state,
    $zip
]));
```

## Performance Considerations

1. Field Caching
- Archive template caches marker data
- List/Grid views cache filter options
- Card templates accept pre-fetched data

2. Conditional Loading
- Gallery images load only when viewed
- Map data loads only in map view
- Agent data loads only when show_agent=true

3. Query Optimization
- Features taxonomy loaded once for filters
- Location data queried in bulk for maps
- Property types cached for filter options

## Field Validation

1. Required Fields
```php
// Basic validation
$status = get_field('status') ?: 'Active';
$full_address = get_field('full_address') ?: "{$street_address}, {$city}";
```

2. Image Fallbacks
```php
if ($main_photo) {
    $hero_image = $main_photo;
} elseif (!empty($gallery_images)) {
    $hero_image = $gallery_images[0]['url'];
} else {
    $hero_image = get_theme_file_uri('assets/images/property-placeholder.jpg');
}
```

3. Data Type Enforcement
```php
'price' => floatval(get_field('price')),
'bedrooms' => intval(get_field('bedrooms')),
'latitude' => floatval(get_field('latitude'))
```
