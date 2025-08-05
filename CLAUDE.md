# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress-based real estate platform with custom theme and plugin, featuring advanced property listings with 55+ fields and agent dashboard functionality.

### Core Components

**Happy Place Theme** (`wp-content/themes/Happy Place Theme/`)
- Agent dashboard system with Bridge pattern for data access
- 770+ bridge functions in `inc/bridge/` for centralized data management
- Component-based architecture in `inc/components/`
- Modern build system with Webpack

**Happy Place Plugin** (`wp-content/plugins/Happy Place Plugin/`)
- MLS compliance features
- Property management system
- External API integrations (Walk Score, Google Maps)

## Key Development Commands

### Theme Development
```bash
cd "wp-content/themes/Happy Place Theme"
npm install          # Install dependencies
npm run build        # Production build
npm run dev          # Development mode with watch
npm run lint:js      # Lint JavaScript
npm run lint:scss    # Lint SCSS
npm run test         # Run Jest tests
```

### Plugin Development
```bash
cd "wp-content/plugins/Happy Place Plugin"
npm install          # Install dependencies
npm run build        # Production build
npm run dev          # Development mode
npm run lint         # Run ESLint
```

### Global Linting
```bash
# From root directory
npm run lint         # Run all linters
composer run phpcs   # PHP CodeSniffer
```

## Architecture

### Bridge Functions System
The theme uses a Bridge pattern with 770+ functions in `inc/bridge/` providing centralized data access:
- Property data retrieval and formatting
- ACF field management
- Cross-component data sharing
- Consistent API for all components

### Component Structure
Theme components in `inc/components/`:
- `dashboard/` - Agent dashboard modules
- `ui/` - Reusable UI components
- `property/` - Property-specific components
- `analytics/` - Tracking and analytics
- `search/` - Search functionality

### Field System
55+ ACF fields organized as:
- 28 manually populated fields
- 15 calculated fields (taxes, HOA fees, etc.)
- 12+ auto-populated fields (Walk Score, school data, transit info)

## Database Structure

### Custom Post Types
- `property` - Main property listings
- `agent` - Agent profiles
- `office` - Office locations

### Key Meta Fields
- `property_price`, `property_status`, `property_type`
- `property_bedrooms`, `property_bathrooms`, `property_square_feet`
- `property_mls_number`, `property_listing_date`
- `agent_license_number`, `agent_phone`, `agent_email`

## External Integrations

### API Services
- **Walk Score API**: Walkability, transit, and bike scores
- **Google Maps**: Geocoding and location services
- **Great Schools API**: School district information
- **MLS Integration**: Property data synchronization

### Integration Pattern
```php
// Smart fallback pattern used throughout:
$data = get_transient($cache_key);
if (!$data) {
    $data = fetch_from_api();
    if ($data) {
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    } else {
        $data = get_fallback_data();
    }
}
```

## Development Guidelines

### Working with Bridge Functions
Always use bridge functions for data access instead of direct queries:
```php
// Good
$property_data = hpt_bridge_get_property_data($property_id);

// Avoid
$property_data = get_post_meta($property_id, 'property_data', true);
```

### Component Development
New components should follow the established pattern:
1. Create class in appropriate `inc/components/` subdirectory
2. Implement required interfaces
3. Register with Component Manager
4. Use bridge functions for data access

### Asset Management
- JavaScript/CSS builds output to `assets/dist/`
- Source files in `assets/src/`
- Use Webpack entry points for new features

## Common Tasks

### Adding a New Property Field
1. Add ACF field definition
2. Create/update bridge function in `inc/bridge/`
3. Update relevant components to display field
4. Add field to property schema if needed

### Creating a Dashboard Module
1. Create class in `inc/components/dashboard/`
2. Extend base dashboard class
3. Register with Dashboard Manager
4. Add required assets and templates

### Debugging API Integrations
- Check transient cache: `get_transient('api_cache_key')`
- Review error logs in `wp-content/debug.log`
- Test API endpoints directly
- Verify API keys in wp-config.php

## Important Notes

- Always check for existing bridge functions before creating new data access methods
- Use WordPress coding standards for PHP
- Follow ESLint/Stylelint rules for JS/CSS
- Test with various property types and edge cases
- Consider MLS compliance requirements for any property data changes
- Cache external API calls appropriately to avoid rate limits