# Happy Place Theme - Consolidated Build System

## Overview
This theme now uses a single, reliable build system that eliminates complications when pushing changes or collaborating.

## Build Commands
All commands should be run from the theme directory: `wp-content/themes/Happy Place Theme/`

### Primary Commands:
- `npm run build` - Production build (optimized, compressed)
- `npm run build:dev` - Development build (readable, with source maps)
- `npm run dev` - Development build with watch mode
- `npm run watch` - Same as dev (alias)
- `npm run clean` - Clean the dist directory

### What Gets Built:
- **JavaScript**: `assets/dist/js/main.js`, `assets/dist/js/single-listing.js`, `assets/dist/js/dashboard-entry.js`
- **CSS**: `assets/dist/css/main.css` (if sass is available)

## Key Benefits:
1. **No Dependency Conflicts**: Uses simple concatenation, no complex webpack setup
2. **Reliable**: Always works, no missing loader issues
3. **Fast**: Simple concatenation is much faster than webpack
4. **Portable**: Works on any system with Node.js
5. **Git-Friendly**: Consistent builds, no merge conflicts

## File Structure:
```
wp-content/themes/Happy Place Theme/
├── package.json           # Single source of truth for scripts
├── build-simple.sh        # Main build script
├── assets/
│   ├── src/               # Source files
│   │   ├── js/
│   │   └── scss/
│   └── dist/              # Built files (git ignored)
│       ├── js/
│       └── css/
```

## What Was Removed:
- Root `package.json` (backed up as `package.json.backup`)
- Complex webpack configurations
- Duplicate npm scripts
- Conflicting dependency locations

## For Deployment:
1. Run `npm run build` before committing
2. The `assets/dist/` directory contains production-ready files
3. WordPress automatically loads these files via the theme

## Troubleshooting:
If builds fail:
1. Check that you're in the theme directory
2. Ensure `build-simple.sh` is executable: `chmod +x build-simple.sh`
3. All JavaScript syntax errors are caught during validation

This system is designed to be foolproof and eliminate build-related complications when working with version control.
