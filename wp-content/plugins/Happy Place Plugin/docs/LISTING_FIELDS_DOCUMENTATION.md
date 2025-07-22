# Listing Fields Documentation

This document provides a comprehensive overview of all ACF (Advanced Custom Fields) fields used in the Happy Place Plugin listing system, including their types, calculation status, and data sources.

## Overview

The listing system uses **55+ fields** organized into logical groups:
- **28 Manual Entry Fields**: Core listing data that requires agent input
- **15 Calculated Fields**: Auto-generated based on other field values
- **12+ Auto-Populated Fields**: Automatically filled from external APIs

---

## Field Categories

### 1. Core Listing Information
*Manual entry required by listing agents*

| Field Name | Field Type | ACF Type | Required | Description |
|------------|------------|----------|----------|-------------|
| `mls_number` | Text | Text | Yes | MLS listing number |
| `status` | Select | Select | Yes | Active, Under Contract, Sold, Withdrawn, etc. |
| `price` | Currency | Number | Yes | Current listing price |
| `bedrooms` | Number | Number | No | Number of bedrooms |
| `bathrooms` | Number | Number | No | Number of full bathrooms |
| `half_baths` | Number | Number | No | Number of half bathrooms |
| `square_footage` | Number | Number | No | Total square footage |
| `living_area` | Number | Number | No | Living area square footage |
| `lot_size` | Number | Number | No | Lot size in square feet |
| `year_built` | Number | Number | No | Year property was constructed |
| `property_type` | Select | Select | No | Single Family, Condo, Townhome, Multi-Family, etc. |
| `property_style` | Select | Select | No | Ranch, Colonial, Contemporary, Cape Cod, etc. |

### 2. Address Components
*Manual entry for complete address information*

| Field Name | Field Type | ACF Type | Required | Description |
|------------|------------|----------|----------|-------------|
| `street_number` | Text | Text | No | Street number (e.g., "123") |
| `street_name` | Text | Text | Yes | Street name (e.g., "Main Street") |
| `unit_number` | Text | Text | No | Unit/apartment number |
| `city` | Text | Text | Yes | City name |
| `state` | Text | Text | Yes | State abbreviation |
| `zip_code` | Text | Text | Yes | ZIP code |
| `county` | Text | Text | No | County name |

### 3. Calculated Fields
*Auto-generated based on other field values - Read Only*

| Field Name | Field Type | Calculation Source | Description |
|------------|------------|-------------------|-------------|
| `price_per_sqft` | Number | `price ÷ square_footage` | Price per square foot |
| `full_address` | Text | Concatenated address components | Complete formatted address |
| `latitude` | Number | Google Geocoding API | GPS latitude coordinate |
| `longitude` | Number | Google Geocoding API | GPS longitude coordinate |

### 4. Location Intelligence Fields
*Auto-populated from external APIs - Read Only*

#### School Information
| Field Name | Field Type | Data Source | Description |
|------------|------------|-------------|-------------|
| `elementary_school` | Text | Google Places API | Nearest elementary school |
| `middle_school` | Text | Google Places API | Nearest middle school |
| `high_school` | Text | Google Places API | Nearest high school |
| `school_district` | Text | Local database + API | School district name |

#### Walkability Scores
| Field Name | Field Type | Data Source | Range | Description |
|------------|------------|-------------|-------|-------------|
| `walk_score` | Number | Walk Score API / Estimated | 0-100 | Walkability rating |
| `transit_score` | Number | Walk Score API / Estimated | 0-100 | Public transit accessibility |
| `bike_score` | Number | Walk Score API / Estimated | 0-100 | Bike-friendliness rating |

#### Nearby Amenities
| Field Name | Field Type | Data Source | Description |
|------------|------------|-------------|-------------|
| `nearby_amenities` | Repeater | Google Places API | Array of nearby places |
| └ `amenity_name` | Text | Google Places | Name of the amenity |
| └ `amenity_type` | Text | Google Places | Category (Restaurant, Grocery, etc.) |
| └ `amenity_distance` | Number | Calculated | Distance in miles |
| └ `amenity_rating` | Number | Google Places | Google rating (0-5) |

### 5. Enhanced Financial Calculations
*Calculated fields for mortgage and investment analysis*

#### Mortgage Calculations
| Field Name | Field Type | Calculation Source | Description |
|------------|------------|-------------------|-------------|
| `estimated_down_payment` | Number | User input/Default 20% | Down payment percentage |
| `estimated_down_payment_amount` | Number | `price × down_payment%` | Down payment dollar amount |
| `estimated_monthly_payment` | Number | Mortgage calculation | Principal & Interest payment |
| `estimated_monthly_taxes` | Number | `annual_tax ÷ 12` | Monthly property taxes |
| `estimated_monthly_insurance` | Number | Estimated based on price | Monthly insurance estimate |
| `estimated_pmi` | Number | PMI calculation | Private mortgage insurance |
| `piti_payment` | Number | P+I+T+I calculation | Total PITI payment |
| `total_monthly_cost` | Number | All monthly costs | Complete monthly housing cost |

#### Property Analysis
| Field Name | Field Type | Calculation Source | Description |
|------------|------------|-------------------|-------------|
| `price_per_living_sqft` | Number | `price ÷ living_area` | Price per living sq ft |
| `investment_analysis` | Group | Various calculations | ROI and investment metrics |

### 6. Property Features
*Manual selection of property amenities and features*

| Field Name | Field Type | ACF Type | Description |
|------------|------------|----------|-------------|
| `interior_features` | Array | Checkbox | Granite counters, hardwood floors, fireplace, etc. |
| `exterior_features` | Array | Checkbox | Deck, pool, garage, fenced yard, etc. |
| `utility_features` | Array | Checkbox | Central air, solar panels, security system, etc. |
| `custom_features` | Repeater | Repeater | Additional unique features |
| └ `feature_name` | Text | Text | Custom feature name |
| └ `feature_category` | Select | Select | Interior, Exterior, Location, etc. |
| └ `is_highlight` | Boolean | True/False | Feature highlight flag |

### 7. Financial Information
*Manual entry for property-specific financial data*

| Field Name | Field Type | ACF Type | Description |
|------------|------------|----------|-------------|
| `property_tax` | Number | Number | Annual property tax amount |
| `hoa_fees` | Number | Number | Monthly HOA fees |
| `estimated_payment` | Number | Number | Agent's payment estimate |

### 8. Important Dates
*Timeline tracking for listing lifecycle*

| Field Name | Field Type | ACF Type | Required | Description |
|------------|------------|----------|----------|-------------|
| `list_date` | Date | Date Picker | Yes | Date property was listed |
| `contract_date` | Date | Date Picker | No | Date went under contract |
| `close_date` | Date | Date Picker | No | Date property closed |

### 9. Relationship Fields
*Connections to other post types*

| Field Name | Field Type | ACF Type | Target Post Type | Description |
|------------|------------|----------|------------------|-------------|
| `listing_agent` | Object | Post Object | Agent | Primary listing agent |
| `co_listing_agent` | Object | Post Object | Agent | Co-listing agent |
| `buyer_agent` | Object | Post Object | Agent | Buyer's agent |
| `related_community` | Object | Post Object | Community | Associated subdivision |

---

## Auto-Population System

### Data Sources

1. **Google Places API**
   - Coordinates (latitude/longitude)
   - Nearby amenities and schools
   - Place details and ratings

2. **Walk Score API**
   - Walk Score (0-100)
   - Transit Score (0-100)
   - Bike Score (0-100)

3. **Local Tax Database**
   - Delaware property tax rates by county
   - Annual tax calculations

4. **Internal Calculations**
   - Mortgage payment calculations
   - Price per square foot
   - Investment analysis metrics

### Caching Strategy

- **Cache Duration**: 24 hours (86,400 seconds)
- **Cache Keys**: Based on post_id and coordinates
- **Cache Groups**: `hph_location_intelligence`
- **Manual Refresh**: Available via admin interface

### Background Processing

Auto-population runs in background to prevent timeouts:
```php
wp_schedule_single_event(time() + 10, 'hph_process_location_intelligence', [$post_id, $lat, $lng]);
```

---

## Field Usage Guidelines

### For Listing Agents
1. **Required Fields**: MLS number, status, price, address components, list date
2. **Recommended Fields**: Bedrooms, bathrooms, square footage, property type
3. **Auto-Populated**: Location intelligence data refreshes automatically
4. **Manual Refresh**: Use admin buttons to update external API data

### For Developers
1. **Field Naming**: Use descriptive names with underscores
2. **Validation**: Required fields have built-in ACF validation
3. **Calculations**: Triggered on `acf/save_post` hook
4. **API Limits**: External API calls are cached and rate-limited

### Performance Considerations
1. **Caching**: All API data cached for 24 hours
2. **Background Processing**: Location intelligence processes asynchronously
3. **Timeouts**: API calls have 10-15 second timeouts
4. **Error Handling**: Graceful fallbacks for API failures

---

## Field Groups in ACF

| Group Name | File | Fields Count | Purpose |
|------------|------|--------------|---------|
| `group_listing_details` | `group_listing_details.json` | 25+ | Core listing information |
| `group_calculated_fields` | `group_calculated_fields.json` | 4 | Auto-calculated values |
| `group_location_intelligence` | `group_location_intelligence.json` | 12+ | External API data |
| `group_enhanced_calculations` | `group_enhanced_calculations.json` | 15+ | Financial analytics |
| `group_property_features` | `group_property_features.json` | 3 | Property amenities |
| `group_listing_dates` | `group_listing_dates.json` | 3 | Timeline tracking |
| `group_custom_features` | `group_custom_features.json` | 1 | Additional features |

---

## API Configuration

### Required API Keys
Set these in WordPress admin under **Happy Place > External APIs**:

- `hph_google_maps_api_key`: Google Maps/Places API
- `hph_walkscore_api_key`: Walk Score API (optional)

### API Endpoints Used

1. **Google Places Nearby Search**
   ```
   https://maps.googleapis.com/maps/api/place/nearbysearch/json
   ```

2. **Walk Score API**
   ```
   https://api.walkscore.com/score
   ```

---

## Troubleshooting

### Common Issues

1. **Missing Coordinates**: Ensure address is complete and valid
2. **No Auto-Population**: Check API keys in settings
3. **Stale Data**: Use manual refresh buttons in admin
4. **Calculation Errors**: Verify required fields are populated

### Debug Information

- **Cache Status**: Check `wp_cache_get()` returns
- **API Responses**: Monitor error logs for API failures
- **Field Updates**: Verify `update_field()` calls succeed
- **Background Jobs**: Check scheduled events with WP-Cron

---

*Last Updated: July 21, 2025*
*Version: 1.0*
