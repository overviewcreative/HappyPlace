# Happy Place Theme - Asset Structure

## Overview
This theme uses a consolidated, efficient CSS/SCSS structure that eliminates redundancies and ensures consistent styling across all pages.

## Asset Architecture

### Consolidated Structure
```
assets/
├── src/
│   ├── scss/
│   │   ├── _consolidated-main.scss   # Main consolidated stylesheet
│   │   ├── main.scss                 # General site styles
│   │   ├── single-listing.scss       # Listing-specific styles
│   │   ├── abstracts/                # Mixins, functions
│   │   ├── base/                     # Typography, reset
│   │   └── components/               # Reusable components
│   └── js/
│       ├── main.js                   # General site scripts
│       └── single-listing.js         # Listing-specific scripts
└── dist/                             # Compiled assets (auto-generated)
    ├── css/
    ├── js/
    └── manifest.json                 # Asset mapping for WordPress
```

### CSS Variable System
All CSS variables are defined once in `_consolidated-main.scss` and used consistently across:
- `style.css` (main WordPress stylesheet)
- Compiled SCSS files
- Component stylesheets

**Key Variables:**
- `--hph-color-*` - Color palette
- `--hph-spacing-*` - Spacing scale
- `--hph-font-*` - Typography
- `--hph-radius-*` - Border radius
- `--hph-shadow-*` - Box shadows

### Grid Layout System
The grid layout is defined in the main stylesheet and works consistently across:
- Desktop: `grid-template-columns: 1fr 320px`
- Tablet: `grid-template-columns: 1fr 280px`
- Mobile: `grid-template-columns: 1fr` (stacked)

## Build Process

### Development
```bash
npm run dev        # Single build for development
npm run watch      # Watch for changes and rebuild
npm run watch:scss # SCSS-only watch mode
```

### Production
```bash
npm run build      # Production build with optimization
npm run clean      # Clean dist folder
```

### SCSS Only (Fallback)
```bash
npm run build:scss # Direct SCSS compilation
npm run watch:scss # SCSS watch without webpack
```

## Asset Loading Strategy

### WordPress Integration
1. **Main Stylesheet** (`style.css`) loads first with CSS variables
2. **Compiled Assets** load via manifest system with proper dependencies
3. **Fallback Mode** works even without compiled assets

### Loading Priority
```php
1. style.css (WordPress theme stylesheet)
2. single-listing.css (compiled from SCSS)
3. single-listing.js (page functionality)
4. vendors.js (third-party libraries)
```

### Cache Busting
- Production builds use contenthash for cache busting
- Development builds use simple names for easier debugging
- WordPress `filemtime()` provides additional cache control

## File Organization

### What to Edit
- **CSS Variables**: Edit `assets/src/scss/_consolidated-main.scss`
- **General Styles**: Edit `assets/src/scss/main.scss`
- **Listing Styles**: Edit `assets/src/scss/single-listing.scss`
- **Components**: Edit files in `assets/src/scss/components/`

### What NOT to Edit
- `assets/dist/*` (auto-generated)
- `manifest.json` (auto-generated)
- Compiled CSS files

## Troubleshooting

### Styles Not Loading
1. Check if `npm run build` was run
2. Verify `manifest.json` exists in `assets/dist/`
3. Check WordPress error logs for file permissions
4. Ensure `style.css` contains CSS variables

### Grid Layout Issues
The grid layout is protected by:
1. CSS variables in `style.css`
2. Consolidated definitions in SCSS
3. Inline CSS fallbacks in PHP template

### Development Mode
If no compiled assets exist:
- Main styles still work via `style.css`
- JavaScript may need manual loading
- Run `npm run watch` to enable live compilation

## Performance Notes

### Optimizations
- **CSS**: Autoprefixer, CSSNano compression
- **JavaScript**: Code splitting, vendor separation
- **Caching**: Content hashes, WordPress cache headers
- **Dependencies**: Proper load order prevents blocking

### File Sizes
- Main CSS: ~50KB compressed
- Single-listing CSS: ~15KB compressed
- JavaScript: ~10KB compressed

## Migration Notes

### From Old Structure
The new structure consolidates:
- Multiple `:root` definitions → Single source
- Redundant SCSS files → Modular imports
- Complex asset loading → Simplified dependencies
- Grid conflicts → Protected layout system

### Backwards Compatibility
- Existing CSS classes remain unchanged
- WordPress hooks still function
- Template structure is preserved
- Asset URLs maintain consistency
