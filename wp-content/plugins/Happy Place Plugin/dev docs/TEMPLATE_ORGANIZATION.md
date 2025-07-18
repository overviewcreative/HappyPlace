# Phase 1.2 - Template Organization Strategy (Revised)

## Templates to Keep (Specialized/Complex Functionality)

### Cards (Specialized)
- `templates/template-parts/cards/listing-swipe-card.php` - **KEEP** - Complex interactive swipe functionality
- `templates/template-parts/cards/card-agent.php` - **KEEP** - Agent-specific card layout
- `templates/template-parts/cards/card-community.php` - **KEEP** - Community-specific card layout

### Forms (Specialized) 
- `templates/template-parts/forms/open-house-form.php` - **KEEP** - Open house specific form
- `templates/template-parts/forms/showing-request-form.php` - **KEEP** - Showing specific form  
- `templates/template-parts/forms/listing-form.php` - **KEEP** - Listing creation/edit form

### Dashboard (Specialized)
- `templates/template-parts/dashboard/` - **KEEP ALL** - Dashboard sections are specialized

### Global (Essential)
- `templates/template-parts/global/` - **KEEP ALL** - Header, footer, search, etc.

## Templates Successfully Consolidated

### ✅ Replaced by `templates/parts/listing-card.php`:
- `templates/template-parts/cards/listing-list-card.php` - **REMOVE** - Replaced by consolidated card
  - Used for: List view in archives
  - Functionality: Basic property display with stats
  - Replacement: Our new listing-card.php with `style='list'`

### ✅ Replaced by `templates/parts/contact-form.php`:
- `templates/template-parts/forms/contact-form-template.php` - **REMOVE** - Basic contact form
- `templates/template-parts/forms/submit-client.php` - **REMOVE** - Simple submission form

### ✅ Replaced by `templates/parts/dashboard-section.php`:
- Individual dashboard section files for basic sections (overview, profile, etc.)

## New Consolidated Templates Created

1. **`templates/parts/listing-card.php`** - Unified listing card with multiple styles:
   - `style='list'` - Horizontal layout for list view
   - `style='grid'` - Vertical layout for grid view  
   - `style='compact'` - Smaller version for sidebars

2. **`templates/parts/contact-form.php`** - Unified form with multiple types:
   - `form_type='contact'` - General contact
   - `form_type='showing'` - Showing requests
   - `form_type='info'` - Information requests

3. **`templates/parts/dashboard-section.php`** - Unified dashboard sections:
   - `section='overview'` - Dashboard overview
   - `section='listings'` - User's listings
   - `section='profile'` - Profile management

## Template Usage Map

### Archive Templates:
- `archive-listing.php` → Uses both `listing-card.php` (list view) and `listing-swipe-card.php` (grid view)
- `archive-agent.php` → Uses `card-agent.php`

### Dashboard Templates:
- `page-templates/agent-dashboard.php` → Uses `dashboard-section.php`

### Individual Templates:
- Contact modals → Use `contact-form.php`
- Property interactions → Use specialized forms as needed

## Benefits of This Approach

1. **Maintains Functionality** - Keeps complex, specialized templates intact
2. **Reduces Redundancy** - Consolidates simple, repetitive templates  
3. **Improves Maintainability** - Common patterns centralized
4. **Bridge Integration** - All consolidated templates use bridge functions
5. **Performance** - Fewer template files to load and maintain

## Next Steps

1. ✅ Remove only the truly redundant listing-list-card.php
2. ✅ Update any remaining references to use consolidated templates
3. ✅ Test all archive views (list, grid, map)
4. ✅ Validate dashboard functionality
5. ✅ Check form submissions work correctly
