# Google API Integration for Cities

## Overview

The Happy Place Plugin now includes comprehensive Google API integration for cities, consolidating geocoding, Google Maps, and Google Places API functionality for both backend data management and frontend display.

## Features

### Backend API Integration

1. **Automatic Geocoding**
   - Auto-geocodes city locations from city names
   - Updates Google Map field with coordinates
   - Caches results for performance

2. **Google Places Auto-Population**
   - Automatically populates nearby places when API mode is selected
   - Categorizes places (Restaurants, Attractions, Parks, etc.)
   - Includes ratings, addresses, and icons
   - Updates on save or manual refresh

3. **Admin Interface Enhancements**
   - Geocoding buttons in city edit screens
   - Places refresh controls
   - Real-time status updates
   - Auto-save functionality

### Frontend Display

1. **City Places Display**
   - Organized by category with place counts
   - Ratings display with star icons
   - Place icons and addresses
   - Responsive grid layout

2. **Interactive Google Maps**
   - City center marker
   - Category-colored place markers
   - Info windows with place details
   - Category filtering
   - Map controls (reset view, show all)

## Setup & Configuration

### 1. Google API Configuration

Configure your Google APIs in **WordPress Admin > Happy Place > External APIs**:

- **Google Maps API Key**: Required for all map and geocoding functionality
- **Enable Google Places API**: Check to enable places auto-population

### Required Google APIs

Your Google Cloud Console project needs these APIs enabled:

- **Maps JavaScript API**: For frontend map display
- **Geocoding API**: For address-to-coordinates conversion  
- **Places API**: For nearby places auto-population

### 2. ACF Field Group Setup

The city field group (`group_city_core.json`) includes:

**Basic City Info:**
- City Tagline
- Introduction Text
- Featured Gallery
- Google Map Location

**City Statistics:**
- Population
- Median Home Price
- School District
- City Vibe

**Places Integration:**
- Places Source (Manual vs API)
- Manual Places (relationship field)
- API Places (auto-populated repeater)

**API Integration Fields:**
- Last API Update timestamp
- API Status indicator
- Google Place ID

### 3. File Structure

```
wp-content/plugins/Happy Place Plugin/
├── includes/services/
│   └── class-city-api-integration.php     # Main API service
├── assets/js/
│   └── city-admin.js                      # Admin interface JS

wp-content/themes/Happy Place Theme/
├── inc/
│   └── template-bridge.php                # City bridge functions
├── template-parts/city/
│   └── city-places.php                    # Places display template
└── assets/js/
    └── city-places-map.js                 # Frontend map JS
```

## API Service Functions

### Backend Functions (`class-city-api-integration.php`)

- `auto_populate_city_data()`: Triggered on city save
- `auto_geocode_city()`: Geocodes city from name
- `populate_city_places_from_api()`: Fetches places from Google API
- `find_nearby_places()`: Core Google Places API call
- `ajax_refresh_city_places()`: Manual refresh endpoint
- `ajax_geocode_city()`: Manual geocoding endpoint

### Bridge Functions (`template-bridge.php`)

- `hph_bridge_get_city_data()`: Get complete city data
- `hph_bridge_get_city_places()`: Get places (manual or API)
- `hph_bridge_get_city_places_by_category()`: Categorized places
- `hph_bridge_get_city_coordinates()`: Map coordinates
- `hph_bridge_get_city_api_status()`: API status info

## Usage Examples

### In City Templates

```php
<?php
// Get city data
$city_data = hph_bridge_get_city_data(get_the_ID());
$places_by_category = hph_bridge_get_city_places_by_category(get_the_ID());
$coordinates = hph_bridge_get_city_coordinates(get_the_ID());

// Display places
get_template_part('template-parts/city/city-places');
?>
```

### Display City Map

```php
<?php if ($coordinates): ?>
    <div id="hph-city-places-map" 
         data-lat="<?php echo $coordinates['lat']; ?>"
         data-lng="<?php echo $coordinates['lng']; ?>"
         data-zoom="<?php echo $coordinates['zoom']; ?>"
         data-places="<?php echo esc_attr(json_encode($places_by_category)); ?>">
    </div>
<?php endif; ?>
```

### Admin Interface

The admin interface automatically adds:
- **Geocode City Location** button to map fields
- **Refresh Places from Google API** button to places fields
- Auto-save on key field changes
- Status notifications for API operations

## Places Data Structure

### API Places (from Google Places API)

```json
{
  "place_id": "ChIJ...",
  "place_name": "Restaurant Name",
  "place_category": "Restaurants",
  "place_type": "restaurant",
  "place_rating": 4.2,
  "place_address": "123 Main St",
  "place_icon": "https://maps.googleapis.com/..."
}
```

### Manual Places (from Local Places CPT)

```json
{
  "ID": 123,
  "post_title": "Local Business",
  "post_type": "local-place",
  "place_category": "Shopping"
}
```

## Styling

The city places template includes comprehensive CSS for:
- Responsive grid layout
- Category organization
- Rating display with stars
- Map integration
- Mobile optimization

Key CSS classes:
- `.hph-city-places-section`: Main container
- `.hph-places-grid`: Category grid
- `.hph-place-item`: Individual place
- `.hph-google-map`: Map container

## Performance Considerations

1. **Caching**: API responses cached for 24 hours
2. **Lazy Loading**: Place images load on demand
3. **Debounced Updates**: Admin auto-save prevents excessive API calls
4. **Error Handling**: Graceful fallbacks for API failures

## Troubleshooting

### Common Issues

1. **No Places Showing**
   - Check Google Places API is enabled
   - Verify API key has Places API permissions
   - Ensure city has coordinates set

2. **Geocoding Fails**
   - Verify Geocoding API is enabled
   - Check API key permissions
   - Ensure city name is valid

3. **Map Not Loading**
   - Check Maps JavaScript API is enabled
   - Verify API key in browser console
   - Check for JavaScript errors

### Debug Information

Enable WordPress debug logging to see:
- API response details
- Geocoding success/failure
- Places population results
- Error messages

## Integration with Existing System

This city API integration is designed to work alongside the existing listing API system:

- **Shared API Key**: Uses same Google Maps API key as listings
- **Compatible Caching**: Uses same caching patterns
- **Consistent Bridge Pattern**: Follows same theme/plugin bridge architecture
- **Admin Integration**: Uses same settings page structure

The system gracefully handles scenarios where:
- Plugin is deactivated (bridge functions provide fallbacks)
- API key is missing (displays appropriate messages)
- Network errors occur (cached data used when available)
