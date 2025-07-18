# Field Mapping Update - Testing Checklist

## Overview
Field mappings have been updated across all templates to use bridge functions. Test each section to ensure data displays correctly.

## Testing Priority

### High Priority (Core Functionality)
- [ ] **Listing Cards** - Verify price, beds, baths, sqft, address display correctly
- [ ] **Single Listing Pages** - Check all property details, agent info, gallery
- [ ] **Dashboard Listings** - Verify agent dashboard shows listing data
- [ ] **Agent Profiles** - Check agent contact information displays

### Medium Priority (Extended Functionality)  
- [ ] **Dashboard Performance** - Verify listing data in performance metrics
- [ ] **Dashboard Overview** - Check recent listings display correctly
- [ ] **Community Pages** - Verify community information displays
- [ ] **Place Pages** - Check place/business information

### Low Priority (Specialized Features)
- [ ] **Open House Management** - Verify listing data in open house section
- [ ] **Listing Flyers** - Check PDF generation with correct data
- [ ] **Profile Forms** - Verify agent profile editing works

## Specific Test Cases

### Listing Data Tests
```
Test URL: /listing/[any-listing]/
Expected: Property price, beds, baths, sqft, address, description, features
Bridge Functions: hph_get_listing_price(), hph_get_listing_bedrooms(), etc.
```

### Agent Data Tests  
```
Test URL: /agents/ and individual agent pages
Expected: Agent name, phone, email, license, photo
Bridge Functions: hph_get_agent_profile_data()
```

### Dashboard Tests
```
Test URL: /agent-dashboard/?section=listings
Expected: Agent's listings with all property details
Bridge Functions: Multiple listing bridge functions
```

### Community/Place Tests
```
Test URLs: /communities/[slug]/ and /places/[slug]/
Expected: Location, population, amenities (communities) / address, phone, rating (places)
Bridge Functions: hph_get_community_data(), hph_get_place_data()
```

## Fallback Testing

### With Plugin Active
- [ ] Verify data loads from plugin services
- [ ] Check console for no bridge function errors

### With Plugin Inactive  
- [ ] Verify data falls back to ACF fields
- [ ] Confirm no fatal errors occur
- [ ] Check that basic functionality remains

## Error Monitoring
Monitor for these potential issues:
- Missing bridge functions (check error logs)
- Undefined array indices (data structure mismatches)  
- Agent data not loading (user meta vs post meta confusion)
- Gallery images not displaying (array structure differences)

## Performance Checks
- [ ] Dashboard loads in reasonable time (<3 seconds)
- [ ] No excessive database queries on listing pages
- [ ] Gallery images load efficiently

## Browser Console Checks
- [ ] No JavaScript errors on listing pages
- [ ] Ajax requests work properly (forms, filters)
- [ ] Map functionality still works (coordinates bridge function)

## Success Criteria
✅ All template data displays correctly
✅ No PHP fatal errors
✅ Dashboard functionality preserved  
✅ Plugin/ACF fallback system works
✅ Performance remains acceptable

## Troubleshooting

### If Data Missing
1. Check if bridge function exists in template-bridge.php
2. Verify ACF field names match fallback calls
3. Check plugin data structure compatibility

### If Dashboard Broken
1. Verify agent data bridge functions work
2. Check listing query compatibility
3. Ensure user permissions maintained

### If Performance Issues
1. Review bridge function efficiency
2. Check for N+1 query problems
3. Consider caching for heavy operations
