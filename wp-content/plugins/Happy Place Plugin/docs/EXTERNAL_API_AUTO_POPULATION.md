# External API Auto-Population Documentation

## Overview

The External API Auto-Population system automatically populates location intelligence data for property listings using various external APIs. This includes school information, walkability scores, nearby amenities, and property tax estimates.

## Features

### Auto-Populated Fields

**School Information:**
- School District
- Elementary School
- Middle School
- High School

**Walkability & Transit Data:**
- Walk Score (0-100)
- Transit Score (0-100)  
- Bike Score (0-100)

**Nearby Amenities:**
- Grocery Stores
- Restaurants
- Gas Stations
- Banks
- Pharmacies
- Hospitals
- Parks
- Gyms
- Shopping Centers
- Movie Theaters
- Libraries
- Post Offices

**Property Tax Data:**
- Property Tax Rate (%)
- Annual Property Tax Estimate

## API Configuration

### Required APIs

**Google Maps API** (Required)
- **Purpose:** Geocoding, Places data, nearby amenities
- **Setup:** Get API key from [Google Cloud Console](https://console.cloud.google.com/)
- **Required APIs:** Maps JavaScript API, Geocoding API, Places API
- **Setting:** WordPress Admin > Happy Place > External APIs

**Walk Score API** (Optional)
- **Purpose:** Accurate walkability, transit, and bike scores
- **Setup:** Get API key from [Walk Score Professional](https://www.walkscore.com/professional/api.php)
- **Fallback:** If not configured, scores are estimated based on nearby amenities

### Configuration Steps

1. **Navigate to External API Settings**
   - WordPress Admin > Happy Place > External APIs

2. **Configure Google Maps API**
   - Enter your Google Maps API key
   - Enable Google Places API integration
   - Test the connection

3. **Configure Walk Score API** (Optional)
   - Enter your Walk Score API key
   - Test the connection

4. **Configure Auto-Population Settings**
   - Enable "Auto-populate on Save" to automatically refresh data when listings are saved
   - Set cache duration (recommended: 24 hours)

## How It Works

### Automatic Population

1. **Trigger:** When a listing is saved with valid address data
2. **Auto-Geocoding:** If latitude/longitude are missing, they are automatically calculated from the address fields (street address, city, state, zip)
3. **Background Processing:** Location intelligence data is populated in the background using the coordinates
4. **Caching:** API responses are cached to reduce API calls and improve performance

### Address Requirements for Auto-Geocoding

The system will attempt to geocode from these address fields (in order of preference):
- **Street Address:** `street_address` or `address`
- **City:** `city`
- **State:** `state` or `region` 
- **ZIP Code:** `zip_code`, `zip`, or `postal_code`

At minimum, city and state are recommended for successful geocoding.

### Manual Refresh

**From Listing Edit Screen:**
- Use the "Location Intelligence Controls" meta box
- **"Get Coordinates from Address"** button to manually geocode the listing's address
- Individual refresh buttons for different data types
- "Refresh All Data" button for complete update

**From ACF Fields:**
- Small refresh buttons next to readonly fields
- Click to refresh specific field data

### Data Sources

**School Information:**
- Google Places API for nearby schools
- Hardcoded Delaware school district mappings
- City-based district estimation for Delaware properties

**Walkability Scores:**
- Walk Score API (if configured)
- Estimated scores based on nearby amenity density (fallback)

**Nearby Amenities:**
- Google Places API within 2-mile radius
- Up to 15 amenities, sorted by distance
- Includes rating and distance information

**Property Tax Data:**
- Delaware county-based tax rates
- Calculated as percentage of listing price
- Supports New Castle, Kent, and Sussex counties

## Field Mapping

### ACF Field Structure

**School Data Group (`field_school_data`)**
- `field_school_district` → School District
- `field_elementary_school` → Elementary School
- `field_middle_school` → Middle School  
- `field_high_school` → High School

**Walkability Data Group (`field_walkability_data`)**
- `field_walk_score` → Walk Score (0-100)
- `field_transit_score` → Transit Score (0-100)
- `field_bike_score` → Bike Score (0-100)

**Nearby Amenities (`field_nearby_amenities`)**
- Repeater field with sub-fields:
  - `amenity_name` → Name of amenity
  - `amenity_type` → Category (Grocery Store, Restaurant, etc.)
  - `amenity_distance` → Distance in miles
  - `amenity_rating` → Google rating (1-5)

**Property Tax Fields**
- `property_tax_rate` → Tax rate as percentage
- `annual_property_taxes` → Estimated annual tax amount

## Delaware-Specific Features

### School Districts
Pre-configured mappings for Delaware school districts:
- **New Castle County:** Red Clay, Christina, Appoquinimink, Brandywine
- **Kent County:** Capital, Smyrna, Milford
- **Sussex County:** Indian River, Cape Henlopen, Seaford, Laurel, Delmar

### Property Tax Rates
County-based tax rates for Delaware:
- **New Castle County:** 0.54%
- **Kent County:** 0.51%
- **Sussex County:** 0.43%

## Performance & Caching

### Caching Strategy
- **Cache Duration:** 24 hours (configurable)
- **Cache Keys:** Based on post ID and coordinates
- **Cache Storage:** WordPress object cache
- **Cache Invalidation:** Manual refresh clears cache for that listing

### API Rate Limiting
- **Background Processing:** Prevents timeout during listing saves
- **Batch Processing:** Multiple API calls handled efficiently
- **Error Handling:** Graceful fallbacks for API failures

## Troubleshooting

### Common Issues

**Data Not Populating:**
1. Check if coordinates (latitude/longitude) are present
2. Verify Google Maps API key is valid and has required permissions
3. Check WordPress error logs for API errors
4. Ensure listing has valid address data

**Incomplete Data:**
1. Some APIs may not have data for all locations
2. Rural areas may have limited nearby amenities
3. School data depends on Google Places coverage

**API Errors:**
1. Check API key validity and permissions
2. Verify API quotas and billing
3. Test API connections in External API Settings

### Manual Debugging

**Enable Debug Mode:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

**Check Error Logs:**
- Look for "HPH Location Intelligence Error" messages
- API response errors logged with details

**Test Individual APIs:**
- Use "Test Connection" buttons in External API Settings
- Manually trigger refresh from listing edit screen

## Development

### Extending the System

**Adding New Data Sources:**
1. Extend the `External_API_Auto_Population` class
2. Add new API integration methods
3. Create corresponding ACF fields
4. Update field mapping in auto-population logic

**Custom Field Integration:**
```php
// Hook into the location intelligence processing
add_action('hph_process_location_intelligence', 'custom_populate_data', 10, 3);

function custom_populate_data($post_id, $lat, $lng) {
    // Your custom API integration
    $custom_data = call_custom_api($lat, $lng);
    update_field('custom_field', $custom_data, $post_id);
}
```

### API Integration Examples

**Adding New Amenity Types:**
```php
// Filter the amenity types
add_filter('hph_amenity_types', 'add_custom_amenity_types');

function add_custom_amenity_types($types) {
    $types['veterinary_care'] = 'Veterinary';
    $types['car_wash'] = 'Car Wash';
    return $types;
}
```

**Custom School District Logic:**
```php
// Filter school district detection
add_filter('hph_school_district_mapping', 'custom_school_districts');

function custom_school_districts($districts) {
    $districts['Custom City'] = 'Custom School District';
    return $districts;
}
```

## Support

For technical support or customization requests:
- Check WordPress error logs first
- Test API connections in admin settings
- Review this documentation for configuration issues
- Contact development team with specific error messages and steps to reproduce
