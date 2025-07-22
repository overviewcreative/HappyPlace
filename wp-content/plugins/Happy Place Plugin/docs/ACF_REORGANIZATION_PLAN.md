# ACF Field Groups Reorganization Plan

## Current Issues Identified:
1. **Too many required fields** - Making data entry cumbersome
2. **Scattered organization** - Fields spread across 50+ separate groups
3. **Poor data flow** - No logical progression for agents
4. **Duplicate/redundant groups** - Similar fields in multiple places
5. **Complex tab structure** - Hard to navigate

## New Organized Structure (4 Main Groups)

### 1. Essential Listing Information
**Purpose**: Core data needed for every listing
**Required Fields**: Only 4 essential fields
**Menu Order**: 1

#### Fields:
- **Price** (Required)
- **Status** (Required) 
- **MLS Number** (Required)
- **List Date** (Required)
- Property Type (Optional)
- Property Style (Optional)
- Square Footage (Optional)
- Bedrooms (Optional)
- Bathrooms (Optional)
- Half Bathrooms (Optional)
- Year Built (Optional)
- Lot Size (Optional)

### 2. Property Details & Features
**Purpose**: Physical property characteristics and amenities
**Required Fields**: None (all optional for flexibility)
**Menu Order**: 2

#### Tabs:
- **📍 Location & Address**
  - Street Number, Street Name, Unit
  - City, State, ZIP Code, County
  - Latitude, Longitude (Auto-populated)
  - Full Address (Auto-calculated)

- **🏠 Property Features**
  - Interior Features (checkboxes)
  - Exterior Features (checkboxes)
  - Utility Features (checkboxes)
  - Custom Features (repeater)

- **💰 Financial Information**
  - Property Tax (Annual)
  - HOA Fees (Monthly)
  - Estimated Monthly Payment

### 3. Location Intelligence (Auto-Populated)
**Purpose**: External API data - mostly read-only
**Required Fields**: None
**Menu Order**: 3

#### Tabs:
- **🎓 Schools & Education**
  - Elementary School (Auto-populated)
  - Middle School (Auto-populated)
  - High School (Auto-populated)
  - School District (Auto-populated)

- **🚶 Walkability & Transit**
  - Walk Score (Auto-populated)
  - Transit Score (Auto-populated)
  - Bike Score (Auto-populated)

- **📍 Nearby Amenities**
  - Nearby Places (Auto-populated repeater)
  - Manual refresh buttons

### 4. Advanced Analytics & Relationships
**Purpose**: Calculated data, relationships, and advanced features
**Required Fields**: None
**Menu Order**: 4

#### Tabs:
- **📊 Calculated Fields**
  - Price per Sq Ft (Auto-calculated)
  - Price per Living Sq Ft (Auto-calculated)
  - Property Tax Rate (Auto-calculated)

- **🏦 Mortgage Calculator**
  - Down Payment %
  - Monthly Payment (P&I)
  - Monthly Taxes
  - Monthly Insurance
  - PITI Payment
  - Total Monthly Cost

- **👥 Relationships**
  - Listing Agent
  - Co-Listing Agent
  - Buyer Agent
  - Related Community

- **📅 Timeline**
  - Contract Date
  - Close Date
  - Days on Market (Auto-calculated)

## Benefits of New Structure:

1. **Simplified Data Entry**: Only 4 required fields vs current 20+
2. **Logical Flow**: Essential → Details → Intelligence → Advanced
3. **Better UX**: Related fields grouped together in tabs
4. **Flexibility**: Most fields optional, letting agents add detail as needed
5. **Auto-Population**: External data clearly separated and auto-filled
6. **Progressive Disclosure**: Basic info first, advanced features later

## Implementation Strategy:

1. **Backup current structure**
2. **Create new consolidated groups**
3. **Migrate field data**
4. **Update auto-population hooks**
5. **Test data flow**
6. **Remove old groups**
