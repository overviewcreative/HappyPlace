# Field Mapping Update Summary

## Overview
Updated all template and template parts to use bridge functions instead of direct ACF field access. This ensures compatibility with the plugin data structure while maintaining fallbacks for ACF fields.

## Key Bridge Functions Added

### New Community & Place Functions
- `hph_get_community_data($community_id)` - Gets community information
- `hph_get_place_data($place_id)` - Gets place/business information

### Existing Bridge Functions Used
- `hph_get_listing_data($listing_id)` - Complete listing data
- `hph_get_listing_price($listing_id, $formatted = true)` - Property price
- `hph_get_listing_address($listing_id, $format = 'full')` - Address data
- `hph_get_listing_bedrooms($listing_id)` - Bedroom count
- `hph_get_listing_bathrooms($listing_id)` - Bathroom count
- `hph_get_listing_sqft($listing_id)` - Square footage
- `hph_get_listing_photo($listing_id, $size = 'medium')` - Featured photo
- `hph_get_listing_gallery($listing_id)` - Photo gallery
- `hph_get_listing_status($listing_id)` - Listing status
- `hph_get_listing_description($listing_id)` - Property description
- `hph_get_listing_features($listing_id, $type = 'all')` - Property features
- `hph_get_listing_coordinates($listing_id)` - Lat/lng coordinates
- `hph_get_agent_profile_data($agent_id)` - Agent profile information

## Files Updated

### Dashboard Templates
- `templates/template-parts/dashboard/listings.php`
  - Replaced `get_field('listing_price')` → `hph_get_listing_price()`
  - Replaced `get_field('listing_address')` → `hph_get_listing_address()`
  - Replaced `get_field('listing_images')` → `hph_get_listing_gallery()`
  - Replaced `get_field('listing_status')` → `hph_get_listing_status()`
  - Replaced `get_field('bedrooms')` → `hph_get_listing_bedrooms()`
  - Replaced `get_field('bathrooms')` → `hph_get_listing_bathrooms()`
  - Replaced `get_field('square_footage')` → `hph_get_listing_sqft()`
  - Replaced `get_field('listing_city')` → `hph_get_listing_address($id, 'city')`

- `templates/template-parts/dashboard/performance.php`
  - Updated listing price, address, and images to use bridge functions

- `templates/template-parts/dashboard/open-houses.php`
  - Updated listing price, address, and images to use bridge functions

- `templates/template-parts/dashboard/overview.php`
  - Updated all listing fields to use bridge functions

### Agent Templates
- `archive-agent.php`
  - Replaced direct ACF agent fields with `hph_get_agent_profile_data()`
  - Fields: title, phone, email

- `templates/template-parts/cards/card-agent.php`
  - Replaced `get_post_meta()` agent fields with `hph_get_agent_profile_data()`
  - Fields: phone, email, license_number

### Community Templates
- `templates/community/content-community.php`
  - Replaced `get_post_meta()` with `hph_get_community_data()`
  - Fields: community_location, community_population, community_amenities

- `templates/template-parts/cards/card-community.php`
  - Updated to use `hph_get_community_data()`
  - Added fallback for community stats functions

### Place Templates
- `templates/place/content-place.php`
  - Replaced `get_post_meta()` with `hph_get_place_data()`
  - Fields: place_address, place_phone, place_rating

- `templates/place/single-place.php`
  - Updated to use `hph_get_place_data()`
  - Fields: place_address, place_website, place_phone, place_hours, place_rating

### Listing Templates
- `templates/listing/single-listing.php`
  - Enhanced to use listing data for lot_size and property_type
  - Updated agent-related fields to use `hph_get_agent_profile_data()`
  - Fields: lot_size, property_type, schedule_link, chat_link

- `templates/template-parts/graphics/listing-flyer.php`
  - Updated agent information to use bridge functions
  - Updated address components to use bridge functions
  - Fields: agent data, lot_size, city/state/zip

### Form Templates
- `templates/template-parts/forms/profile-form.php`
  - Replaced `get_field('agent_details')` with `hph_get_agent_profile_data()`

## Fallback Strategy
All bridge functions include fallback mechanisms:
1. **Primary**: Try plugin data via `hph_get_listing_data()` or service classes
2. **Secondary**: Fallback to ACF fields using `get_field()`
3. **Tertiary**: Fallback to WordPress post meta using `get_post_meta()`

## Plugin Data Structure Mapping

### Listing Fields
```php
[
    'price' => 'listing_price',
    'bedrooms' => 'listing_bedrooms', 
    'bathrooms' => 'listing_bathrooms',
    'square_feet' => 'square_footage',
    'address' => ['street', 'city', 'state', 'zip'],
    'gallery' => 'listing_gallery',
    'status' => 'listing_status',
    'description' => 'description',
    'features' => ['interior', 'exterior', 'utility'],
    'coordinates' => ['lat', 'lng']
]
```

### Agent Fields
```php
[
    'profile_photo' => 'agent_photo',
    'phone' => 'agent_phone',
    'email' => 'agent_email', 
    'license_number' => 'license_number',
    'title' => 'agent_title',
    'display_name' => 'agent_name'
]
```

### Community Fields
```php
[
    'location' => 'community_location',
    'population' => 'community_population',
    'amenities' => 'community_amenities',
    'average_price' => 'community_average_price',
    'total_homes' => 'community_total_homes'
]
```

### Place Fields
```php
[
    'address' => 'place_address',
    'phone' => 'place_phone',
    'rating' => 'place_rating',
    'website' => 'place_website',
    'hours' => 'place_hours',
    'category' => 'place_category'
]
```

## Benefits
1. **Plugin Compatibility**: Templates work with or without the plugin
2. **Data Consistency**: Unified data access across all templates
3. **Future-Proof**: Easy to update data sources without touching templates
4. **Performance**: Bridge functions can optimize data retrieval
5. **Fallback Safety**: Multiple fallback levels ensure data availability

## Testing Requirements
1. Test with plugin active - should use plugin data
2. Test with plugin inactive - should use ACF fallbacks
3. Verify all dashboard sections display correctly
4. Verify listing cards and single listing pages work
5. Verify agent profiles and community pages work
6. Test listing flyer generation

## Notes
- Some functions like `count_posts_in_community()` and `get_community_stats()` are expected to be implemented
- Dashboard functions like `hph_get_agent_inquiries()` may need to be added to bridge
- Option fields in flyer template (company info) still use direct `get_field('field', 'option')` calls as intended
- Open house fields in swipe card template were not updated - may need review if open house integration is needed
