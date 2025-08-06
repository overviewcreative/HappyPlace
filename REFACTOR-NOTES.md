# Happy Place Platform - Refactored Structure

## Overview
This project has been comprehensively refactored to provide a clean, maintainable foundation for future development.

## Project Structure

```
wp-content/
├── themes/
│   └── Happy Place Theme/
│       ├── src/                  # Source files (development)
│       │   ├── js/               # JavaScript source
│       │   │   ├── components/   # Reusable JS components
│       │   │   ├── pages/        # Page-specific JS
│       │   │   ├── admin/        # Admin JS
│       │   │   └── utilities/    # Utility functions
│       │   ├── scss/             # SCSS source
│       │   │   ├── components/   # Component styles
│       │   │   ├── pages/        # Page-specific styles
│       │   │   ├── admin/        # Admin styles
│       │   │   ├── base/         # Base styles
│       │   │   ├── layout/       # Layout styles
│       │   │   └── utilities/    # Utility classes
│       │   ├── images/           # Source images
│       │   └── fonts/            # Custom fonts
│       ├── assets/               # Compiled assets
│       │   ├── dist/             # Production-ready files
│       │   └── vendor/           # Third-party assets
│       ├── includes/             # PHP includes
│       │   ├── acf/              # ACF configurations
│       │   ├── api/              # API integrations
│       │   ├── admin/            # Admin functionality
│       │   ├── integrations/     # External integrations
│       │   ├── utilities/        # Helper functions
│       │   ├── post-types/       # CPT definitions
│       │   ├── taxonomies/       # Taxonomy definitions
│       │   ├── shortcodes/       # Shortcode handlers
│       │   └── blocks/           # Block definitions
│       ├── templates/            # Template files
│       │   ├── layouts/          # Layout templates
│       │   ├── components/       # Component templates
│       │   ├── partials/         # Partial templates
│       │   └── blocks/           # Block templates
│       └── build/                # Build artifacts
│
└── plugins/
    └── Happy Place Plugin/
        ├── src/                  # Source files
        │   ├── js/               # JavaScript source
        │   ├── scss/             # SCSS source
        │   └── images/           # Source images
        ├── dist/                 # Compiled assets
        ├── includes/             # PHP includes
        │   ├── blocks/           # Gutenberg blocks
        │   ├── widgets/          # WordPress widgets
        │   ├── cron/             # Cron jobs
        │   └── cli/              # WP-CLI commands
        └── tests/                # Test suites
            ├── unit/             # Unit tests
            ├── integration/      # Integration tests
            └── e2e/              # End-to-end tests
```

## Key Changes

### Completed Refactoring Tasks

1. **Removed Test Files**
   - Deleted all test files (test-*.php, system-test.html)
   - Cleaned up temporary files

2. **Organized Directory Structure**
   - Created logical separation between source and compiled assets
   - Established clear directory structure for future development
   - Organized templates into logical categories

3. **Consolidated Assets**
   - Moved JavaScript files into organized src/js structure
   - Moved CSS files into organized src/scss structure
   - Created separate directories for components, pages, and utilities

4. **Updated Build Configuration**
   - Created modern webpack.config.js for both theme and plugin
   - Updated package.json with latest dependencies
   - Added linting configurations (ESLint, Stylelint)
   - Added PostCSS for autoprefixing

5. **Removed Redundancies**
   - Deleted duplicate openhouse/open-house directories
   - Cleaned up empty directories
   - Removed obsolete configuration files

## Build Commands

### Theme
```bash
cd wp-content/themes/Happy Place Theme
npm install          # Install dependencies
npm run dev          # Development mode with watch
npm run build        # Production build
npm run lint         # Run linters
```

### Plugin
```bash
cd wp-content/plugins/Happy Place Plugin
npm install          # Install dependencies
npm run dev          # Development mode with watch
npm run build        # Production build
npm run lint         # Run linters
```

## Important Notes

### Preserved Functionality
- ✅ ACF field groups remain intact (JSON format in plugin)
- ✅ Custom Post Types registration preserved
- ✅ Taxonomies preserved
- ✅ Bridge functions system maintained
- ✅ Dashboard functionality preserved

### Next Steps
1. Run `npm install` in both theme and plugin directories
2. Build assets using `npm run build`
3. Test all functionality to ensure nothing is broken
4. Update any hardcoded asset paths in PHP files
5. Implement asset versioning for cache busting

### Development Guidelines
- Place new JavaScript in `src/js/` directories
- Place new styles in `src/scss/` directories
- Use the established directory structure for organization
- Follow the linting rules configured in .eslintrc.json and .stylelintrc.json
- Always build assets before deployment

## Migration Notes
- JavaScript files have been reorganized but not modified
- CSS files have been moved but content preserved
- All PHP functionality remains unchanged
- Database structure and ACF fields are untouched

## Version
Refactored to v2.0.0 - Clean foundation for future development