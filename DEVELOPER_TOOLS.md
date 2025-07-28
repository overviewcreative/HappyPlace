# Happy Place Developer Tools

## Overview
Development tools and utilities for the Happy Place WordPress theme and plugin. These tools help with building assets, managing cache, and other development tasks.

## Access Methods

### 1. WordPress Admin Menu
- Navigate to **Happy Place > Developer Tools** in WordPress admin
- Only available to administrators
- Provides web interface for common tasks

### 2. Direct Script Access
Access development tools directly via URL:
```
/wp-content/plugins/Happy Place Plugin/dev-tools.php?action={action}&key=dev123
```

Available actions:
- `build_sass` - Build theme Sass files
- `build_webpack` - Build theme with Webpack
- `build_plugin` - Build plugin assets
- `flush_cache` - Clear WordPress cache
- `flush_rewrite` - Flush rewrite rules
- `env_info` - Show environment information
- `watch_sass` - Start Sass watch mode

### 3. Command Line Helper
Use the shell script for quick command line access:
```bash
./hph-dev.sh {command}
```

## Available Tools

### Cache Management
- **Flush WordPress Cache**: Clears object cache and transients
- **Flush Rewrite Rules**: Regenerates permalink structure
- **Clear Transients**: Removes expired transient cache entries

### Build Tools
- **Build Sass**: Compile SCSS to CSS (`npm run build:sass`)
- **Build Webpack**: Full webpack build (`npm run build`)
- **Build Plugin**: Compile plugin assets
- **Watch Sass**: Auto-compile SCSS on changes (`npm run watch:sass`)

### Database Tools
- **Optimize Database**: Optimize all database tables
- **Clear Transients**: Remove expired transient data

### Development Utilities
- **Environment Info**: Display PHP, WordPress, and system information
- **Debug Status**: Check current debug mode status

## Quick Commands

### Build Assets
```bash
# Build everything
./hph-dev.sh build-all

# Just Sass
./hph-dev.sh build-sass

# Start watch mode
./hph-dev.sh watch-sass
```

### Cache Management
```bash
# Flush cache
./hph-dev.sh flush-cache

# Flush permalinks
./hph-dev.sh flush-rewrite
```

### Development Setup
```bash
# Install dependencies
./hph-dev.sh install

# Clean build files
./hph-dev.sh clean

# Lint SCSS
./hph-dev.sh lint
```

## File Structure

### Theme Assets
- **Source**: `wp-content/themes/Happy Place Theme/assets/src/`
- **Compiled**: `wp-content/themes/Happy Place Theme/assets/dist/`
- **Config**: `package.json`, `webpack.config.js`

### Plugin Assets
- **Source**: `wp-content/plugins/Happy Place Plugin/assets/src/`
- **Compiled**: `wp-content/plugins/Happy Place Plugin/assets/dist/`

## Development Workflow

1. **Start watch mode** for real-time compilation:
   ```bash
   ./hph-dev.sh watch-sass
   ```

2. **Make changes** to SCSS files in `assets/src/scss/`

3. **Check compilation** in `assets/dist/css/`

4. **Clear cache** when testing:
   ```bash
   ./hph-dev.sh flush-cache
   ```

5. **Build production assets** when ready:
   ```bash
   ./hph-dev.sh build-all
   ```

## Security Notes

- Admin menu tools require `manage_options` capability
- Direct script access requires simple key authentication
- Shell script should only be used in development environments

## Troubleshooting

### Common Issues

1. **Build fails**: Check if npm dependencies are installed
   ```bash
   ./hph-dev.sh install
   ```

2. **Styles not updating**: Clear cache and check if Sass compiled
   ```bash
   ./hph-dev.sh flush-cache
   ./hph-dev.sh build-sass
   ```

3. **Permission denied**: Make shell script executable
   ```bash
   chmod +x hph-dev.sh
   ```

### Debug Information
Check environment details:
```bash
./hph-dev.sh env-info
```

## Integration with Existing Tools

- Compatible with existing `npm run` commands
- Works alongside theme's `serve.sh` script
- Integrates with WordPress caching plugins (W3TC, WP Rocket, LiteSpeed)

---

*Happy Place Developer Tools v1.0*
