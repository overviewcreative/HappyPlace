# Happy Place Theme Architecture

## 1. Theme Structure Overview

### 1.1 Core Organization
```
happy-place-theme/
├── assets/           # Theme assets (JS, CSS, images)
├── inc/             # Theme functionality classes
├── template-parts/   # Reusable template components
├── page-templates/   # Page-specific templates
└── functions.php    # Theme setup and hooks
```

### 1.2 Active Templates
1. Root Templates
- `index.php` - Default template fallback
- `archive-listing.php` - Property listings archive
- `single-listing.php` - Single property view
- `header.php`, `footer.php` - Theme framework
- Other WordPress standard templates

2. Component Structure
- Cards: Listing display components
- Forms: Submission and search forms
- Filters: Search and filtering components
- Maps: Property location components

## 2. Component Architecture

### 2.1 Listing Components
1. Archive Views
```php
archive-listing.php
├── filters/filter-sidebar.php
├── cards/listing-list-card.php
└── cards/listing-swipe-card.php
```

2. Single Property View
```php
single-listing.php
├── listing/gallery.php
├── listing/map-view.php
└── calculators/mortgage-calculator.php
```

### 2.2 Card System
1. List Card (`listing-list-card.php`)
- Purpose: Detailed property information
- Use: Archive and search results
- Dependencies: Property data, optional agent info

2. Swipe Card (`listing-swipe-card.php`)
- Purpose: Image-focused presentation
- Use: Gallery views, featured listings
- Dependencies: Images, basic property data

### 2.3 Map Integration
1. Components
- Map View Template
- Marker Generation
- Clustering System
- Info Windows

2. Data Flow
```
archive-listing.php
└── map-view.php
    ├── markers (from listing data)
    └── clustering (from MarkerClusterer)
```

## 3. Data Architecture

### 3.1 Field Groups

#### Property Fields

1. Basic Details
- `property_price` (number)
  - Required
  - Validation: Positive number, max 999999999
  - Format: Stored raw, displayed with currency formatter
  - Used in: List cards, filters, sorting

- `property_status` (select)
  - Required
  - Options: For Sale, For Rent, Under Contract, Sold
  - Affects: Visibility, search filters, display badges
  - Dependencies: Controls price display format

- `property_type` (select)
  - Required
  - Options: Single Family, Condo, Townhouse, Multi-Family, Land
  - Used in: Search filters, property cards
  - Related: Affects available amenities fields

2. Physical Characteristics
- `square_footage` (number)
  - Optional
  - Validation: Positive integer
  - Format: Stored in sq ft, convertible to meters
  - Dependencies: Used in price/sqft calculations

- `bedrooms` (number)
  - Required
  - Validation: Integer 0-99
  - Used in: Search filters, sort options
  - Display: "Studio" if 0

- `bathrooms` (number)
  - Required
  - Validation: Decimal (0.5 increments)
  - Format: Stored decimal, displayed as fractions
  - Example: 2.5 displays as "2½"

3. Location Data
- `property_address` (group)
  - Required fields:
    - `street_address` (text)
    - `city` (text)
    - `state` (select)
    - `zip` (text)
  - Validation: Address verification API
  - Dependencies: Used for geocoding

- `location` (map point)
  - Required
  - Format: lat/lng object
  - Generated: From address or manual pin
  - Used in: Map markers, proximity search

4. Media Assets
- `gallery_images` (repeater)
  - Minimum: 1 image
  - Maximum: 50 images
  - Validation: min 1200x800px
  - Types: jpg, png, webp
  - First image used as featured

- `virtual_tour` (url)
  - Optional
  - Validation: Valid URL
  - Supported: Matterport, YouTube, custom

#### Agent Fields

1. Contact Information
- `agent_email` (email)
  - Required
  - Validation: Valid email format
  - Usage: Contact forms, notifications
  - Privacy: Optionally masked

- `agent_phone` (text)
  - Required
  - Format: Standardized +1 format
  - Types: Office, Mobile, Fax
  - Display: Click-to-call enabled

2. Profile Data
- `agent_bio` (wysiwyg)
  - Optional
  - Max length: 2000 chars
  - Formatting: Limited HTML allowed
  - SEO: Used in agent schema

- `agent_photo` (image)
  - Required
  - Size: min 400x400px
  - Format: jpg, png
  - Usage: Cards, profiles, schema

3. Listing Relationships
- `agent_listings` (relationship)
  - Auto-populated
  - Direction: Bi-directional
  - Updates: On listing save
  - Display: Filtered by status

#### Search and Filter Fields

1. Search Parameters
- `search_radius` (number)
  - Optional
  - Range: 1-100 miles
  - Default: 20 miles
  - Used in: Map view, proximity

- `price_range` (range)
  - Optional
  - Format: min/max values
  - Validation: Min < Max
  - Dynamic: Based on inventory

2. Filter Options
- `amenities` (checkbox)
  - Multi-select enabled
  - Grouped by category
  - Dynamic count display
  - Affects: Query parameters

### 3.2 Field Validation and Processing

1. Input Validation
- Server-side validation
  - Type checking
  - Range validation
  - Required fields
  - Format verification

2. Data Sanitization
- Text fields: WordPress sanitize_text_field()
- HTML fields: wp_kses_post()
- URLs: esc_url_raw()
- Email: sanitize_email()

3. Error Handling
- Validation errors
  - User feedback
  - Form persistence
  - Field highlighting
- Processing errors
  - Logged to debug.log
  - Admin notifications
  - Graceful fallbacks

4. Performance Considerations
- Field caching
  - Transient API usage
  - Cache invalidation
  - Group caching
- Query optimization
  - Field selection
  - Join reduction
  - Index usage
````
