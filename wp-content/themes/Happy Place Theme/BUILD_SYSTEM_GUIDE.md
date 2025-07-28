# Happy Place Theme - Build System Guide

## Quick Start

To run a build immediately:

```bash
npm run build
```

or

```bash
./build.sh
```

## Build System Overview

The Happy Place Theme uses a custom build system that compiles SCSS to CSS and manages JavaScript assets. This system was created as a reliable alternative to webpack when dependency issues arise.

## Available Build Commands

### NPM Scripts (Recommended)

```bash
# Production build (compressed CSS, no source maps)
npm run build

# Webpack build (if dependencies are working)
npm run build:webpack

# Development build with webpack
npm run dev

# Watch mode for webpack
npm run watch

# Watch mode for custom build (expanded CSS with source maps)
npm run watch:custom

# Clean build directory
npm run clean

# SCSS-only compilation
npm run build:sass

# Watch SCSS only
npm run watch:sass
```

### Direct Build Script Usage

```bash
# Single production build
./build.sh

# Watch mode (development)
./build-enhanced.sh --watch
```

## Asset Loading System

The theme's `functions.php` includes a sophisticated asset loading system:

### Key Features

1. **Webpack Manifest Support**: Automatically loads webpack-generated assets with version hashes
2. **Fallback System**: If webpack manifest fails, falls back to custom build manifest
3. **Source File Fallback**: If manifest files aren't found, loads directly from source
4. **Template-Specific Assets**: Automatically loads CSS/JS for specific page templates
5. **Cache Busting**: Uses timestamps for browser cache invalidation

### Asset Loading Priority

1. **Webpack manifest** (`webpack-assets.json`) - preferred for production
2. **Custom build manifest** (`manifest.json`) - fallback for custom builds
3. **Source files** - emergency fallback for development

### File Structure

```
assets/
├── src/
│   ├── scss/
│   │   ├── main.scss              # Main stylesheet entry point
│   │   ├── single-listing.scss    # Single listing page styles
│   │   └── ...                    # Other SCSS components
│   └── js/
│       ├── main.js                # Main JavaScript
│       ├── single-listing.js      # Single listing JavaScript
│       ├── modules/               # JavaScript modules
│       └── components/            # JavaScript components
└── dist/
    ├── css/                       # Compiled CSS files
    ├── js/                        # Processed JavaScript files
    └── manifest.json              # Asset version manifest
```

## Troubleshooting

### Common Issues

1. **"npm run build" fails**: Use `./build.sh` directly
2. **Webpack dependencies won't install**: Use the custom build system (already configured)
3. **Styles not loading**: Check the WordPress admin for any cache plugins
4. **Changes not appearing**: Run `npm run build` after making SCSS changes

### Dependency Issues

If you encounter webpack dependency problems:

1. Try `npm install` first
2. If that fails, the custom build system (`build.sh`) will work without webpack
3. The `functions.php` is designed to handle both webpack and custom builds seamlessly

### Build Verification

After running a build, check:

1. Files exist in `assets/dist/css/` and `assets/dist/js/`
2. `manifest.json` contains current timestamp versions
3. WordPress frontend loads the new assets (check browser dev tools)

## Development Workflow

### For Active Development

```bash
# Start watch mode for real-time compilation
npm run watch:custom

# Or use the enhanced build script
./build-enhanced.sh --watch
```

This will:
- Watch SCSS files for changes
- Automatically recompile when files are saved
- Generate expanded CSS with source maps for debugging
- Update timestamps for cache busting

### For Production Deployment

```bash
# Single production build
npm run build
```

This generates:
- Compressed CSS files
- Copied JavaScript files
- Updated manifest with cache-busting timestamps

## CSS Architecture

The SCSS structure follows a modular architecture:

- **Main Entry**: `main.scss` - Global styles and layout
- **Template Specific**: `single-listing.scss` - Property detail page styles
- **Components**: Organized in `/components/` folder
- **Utilities**: Mixins, variables, and functions in `/abstracts/`

## Cache Management

The build system automatically:

1. Generates timestamp-based cache busting
2. Updates the manifest.json with new versions
3. Integrates with WordPress cache (if WP-CLI available)

## Notes

- **Deprecation Warnings**: The SCSS compilation shows deprecation warnings about `@import` statements. These are normal and don't affect functionality.
- **Asset Fallbacks**: The theme is designed to work even if builds fail, by falling back to source files.
- **Cross-Platform**: Build scripts work on macOS, Linux, and Windows (with bash available).

## Quick Reference

| Task | Command |
|------|---------|
| Build for production | `npm run build` |
| Watch during development | `npm run watch:custom` |
| Clean build directory | `npm run clean` |
| Check for problems | Verify files in `assets/dist/` |

---

*Last updated: $(date +"%Y-%m-%d")*
