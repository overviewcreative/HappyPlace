# VSCode Copilot Agent Instructions: Happy Place Plugin+Theme Restructuring

## Project Overview

This is a comprehensive WordPress plugin+theme combo for real estate websites. The project has evolved significantly and now requires a top-to-bottom restructuring to eliminate redundancy, remove development artifacts, and establish clean architectural boundaries before the next development phase.

**Current Status:**
- ✅ 770+ bridge functions implemented
- ✅ Modern component architecture with Base_Component system
- ✅ Enterprise build system (Webpack 5, SCSS compilation)
- ✅ Comprehensive ACF field management
- ⚠️ Significant technical debt and redundancy requiring cleanup

---

## Critical Architecture Principles

### Plugin ↔ Theme Relationship
```
Custom Post Types (Plugin) → ACF Fields (Plugin) → Bridge Functions (Theme) → Template Classes (Theme) → Template Parts (Theme) → Full Templates (Theme)
```

**Plugin Responsibilities:**
- Custom Post Types (CPT) registration and management
- ACF field definitions and data storage
- External API integrations (Airtable, Google Maps)
- Admin interfaces and settings
- Data syncing and validation

**Theme Responsibilities:**
- Bridge functions for data access (with plugin-inactive fallbacks)
- Template classes for display logic
- UI components and template parts
- Asset management and loading
- Frontend functionality and styling

---

## Phase 1: Immediate Cleanup (Priority 1)

### Files to Remove Completely

**Plugin Debug/Test Files:**
```
wp-content/plugins/Happy Place Plugin/
├── diagnostic-sync-test.php ❌ DELETE
├── verify-acf-setup.php ❌ DELETE
├── includes/debug/ ❌ DELETE ENTIRE DIRECTORY
├── includes/admin/Architecture_Map.php ❌ DELETE
└── Any files containing 'test', 'debug', 'diagnostic' ❌ DELETE
```

**Theme Debug/Test Files:**
```
wp-content/themes/Happy Place Theme/
├── inc/debug/ ❌ DELETE ENTIRE DIRECTORY
├── inc/Helpers/class-debug.php ❌ DELETE
├── DEBUG_RESOLUTION_COMPLETE.md ❌ DELETE
└── Any debug utilities in functions.php ❌ CLEAN OUT
```

### Redundant Asset Systems to Consolidate

**Keep Only:** `inc/HappyPlace/Core/Asset_Loader.php` (most modern implementation)

**Remove/Consolidate:**
```
❌ inc/core/Assets.php - DELETE
❌ inc/core/Asset_Loader.php - DELETE  
🔄 includes/core/class-assets-manager.php - MODIFY to complement, not duplicate
```

**Action Required:** Update all references to use the single Asset_Loader system.

### Template Loading Consolidation

**Current Problem:** Multiple overlapping template loading systems
- `inc/core/Template_Loader.php`
- `inc/core/Template_Structure.php`
- Template logic mixed into `template-bridge.php`

**Solution:** 
- Keep template data access in bridge functions
- Remove template loading logic from bridge functions
- Consolidate to single template loading system in theme
- Clean separation: Bridge = Data, Templates = Display

---

## Phase 2: File Structure Reorganization

### Plugin Structure (Target)

```
wp-content/plugins/Happy Place Plugin/
├── happy-place.php                          # Main plugin file
├── includes/
│   ├── core/                                # Core functionality only
│   │   ├── class-plugin-manager.php         # Main plugin orchestrator
│   │   ├── class-post-types.php             # CPT registration
│   │   ├── class-taxonomies.php             # Taxonomy registration
│   │   └── class-assets-manager.php         # Plugin-specific assets
│   ├── admin/                               # Admin interfaces
│   │   ├── class-admin-menu.php             # Main admin menu
│   │   ├── class-settings-page.php          # Settings interface
│   │   ├── class-integrations-manager.php   # Integration management
│   │   └── class-csv-import-manager.php     # Import functionality
│   ├── fields/                              # ACF management
│   │   ├── class-acf-manager.php            # ACF orchestrator
│   │   └── acf-json/                        # Field group definitions
│   ├── integrations/                        # External API integrations
│   │   ├── class-base-integration.php       # Base integration class
│   │   ├── class-airtable-integration.php   # Airtable sync
│   │   └── class-google-api-integration.php # Google Maps/Places
│   ├── api/                                 # AJAX handlers
│   │   └── class-ajax-handler.php           # Plugin AJAX endpoints
│   └── utilities/                           # Helper classes only
│       ├── class-data-validator.php         # Data validation
│       └── class-image-processor.php        # Image processing
├── assets/                                  # Plugin-specific assets
└── templates/                               # Admin template files only
```

### Theme Structure (Target)

```
wp-content/themes/Happy Place Theme/
├── style.css                                # Main stylesheet
├── functions.php                            # Theme initialization
├── inc/
│   ├── core/                                # Theme core management
│   │   ├── class-theme-manager.php          # Main theme orchestrator
│   │   ├── class-asset-loader.php           # Single asset system
│   │   └── class-template-loader.php        # Template loading system
│   ├── bridge/                              # Data access bridge functions
│   │   ├── listing-bridge.php               # Listing data access
│   │   ├── agent-bridge.php                 # Agent data access  
│   │   ├── template-bridge.php              # Template utilities
│   │   ├── cache-bridge.php                 # Cache management
│   │   └── fallback-bridge.php              # Plugin-inactive fallbacks
│   ├── components/                          # UI component system
│   │   ├── class-base-component.php         # Single base component
│   │   ├── listing/                         # Listing-specific components
│   │   ├── agent/                           # Agent-specific components
│   │   ├── dashboard/                       # Dashboard components
│   │   └── ui/                              # Reusable UI elements
│   ├── template-classes/                    # Template display logic
│   │   ├── class-listing-template.php       # Listing template logic
│   │   ├── class-agent-template.php         # Agent template logic
│   │   ├── class-dashboard-template.php     # Dashboard template logic
│   │   └── class-archive-template.php       # Archive template logic
│   └── shortcodes/                          # Shortcode system
│       ├── class-shortcode-manager.php      # Shortcode orchestrator
│       └── components/                      # Shortcode components
├── assets/                                  # Theme assets
│   ├── src/                                 # Source files (SCSS, JS)
│   │   ├── scss/
│   │   │   ├── abstracts/                   # Variables, mixins, functions
│   │   │   ├── base/                        # Reset, typography, globals
│   │   │   ├── components/                  # Component-specific styles
│   │   │   ├── layout/                      # Grid, header, footer
│   │   │   └── pages/                       # Page-specific styles
│   │   └── js/
│   │       ├── components/                  # Component JS modules
│   │       ├── utils/                       # Utility functions
│   │       └── vendor/                      # Third-party libraries
│   └── dist/                                # Compiled assets
├── templates/                               # Full page templates
│   ├── single-listing.php                  # Single listing template
│   ├── single-agent.php                    # Single agent template
│   ├── archive-listing.php                 # Listing archive
│   └── agent-dashboard.php                 # Dashboard template
└── template-parts/                          # Modular template parts
    ├── listing/                             # Listing components
    ├── agent/                               # Agent components
    ├── dashboard/                           # Dashboard sections
    └── global/                              # Site-wide components
```

---

## Phase 3: Code Standardization

### Naming Conventions (ENFORCE CONSISTENTLY)

**Plugin:**
- Classes: `HPH_Class_Name` or `HPH\Namespace\Class_Name`
- Functions: `hph_function_name()`
- Constants: `HPH_CONSTANT_NAME`
- CSS Classes: `.hph-class-name`

**Theme:**
- Classes: `HappyPlace\Namespace\Class_Name`
- Functions: `hph_function_name()` (same as plugin for consistency)
- CSS Classes: `.hph-class-name`

### Bridge Function Patterns (STANDARDIZE)

**Data Access Bridge Functions:**
```php
/**
 * Get listing data with fallback
 * @param int $listing_id
 * @return array
 */
function hph_get_listing_data($listing_id) {
    // Check if plugin is active
    if (!function_exists('plugin_specific_function')) {
        return hph_fallback_listing_data($listing_id);
    }
    
    // Use plugin data with caching
    $cache_key = "hph_listing_{$listing_id}";
    $data = wp_cache_get($cache_key, 'hph_listings');
    
    if (false === $data) {
        $data = get_field('listing_details', $listing_id);
        wp_cache_set($cache_key, $data, 'hph_listings', 3600);
    }
    
    return $data;
}
```

**Template Bridge Functions:**
```php
/**
 * Get formatted template data (display-ready)
 * @param int $listing_id
 * @return array
 */
function hph_get_template_listing_data($listing_id) {
    $raw_data = hph_get_listing_data($listing_id);
    
    return [
        'title' => esc_html($raw_data['title']),
        'price' => hph_format_price($raw_data['price']),
        'features' => hph_format_features($raw_data['features']),
        'url' => get_permalink($listing_id)
    ];
}
```

---

## Phase 4: Component Architecture Consolidation

### Base Component Pattern (STANDARDIZE)

```php
<?php
namespace HappyPlace\Components;

abstract class Base_Component {
    protected $props = [];
    protected $defaults = [];
    
    public function __construct($props = []) {
        $this->props = wp_parse_args($props, $this->get_defaults());
        $this->validate_props();
        $this->init();
    }
    
    abstract protected function get_defaults();
    abstract protected function render();
    abstract protected function get_component_name();
    
    protected function init() {
        // Override in child classes
    }
    
    protected function validate_props() {
        // Implement validation logic
    }
    
    public function display($echo = true) {
        $output = $this->render();
        
        if ($echo) {
            echo $output;
        }
        
        return $output;
    }
}
```

### Component Usage Pattern

```php
// In template files
$listing_card = new HappyPlace\Components\Listing_Card([
    'listing_id' => get_the_ID(),
    'variant' => 'featured',
    'context' => 'archive'
]);

$listing_card->display();
```

---

## Phase 5: Asset Management Consolidation

### Single Asset Loading System

**File:** `inc/HappyPlace/Core/Asset_Loader.php`

**Pattern:**
```php
class Asset_Loader {
    public function enqueue_core_assets() {
        // Core CSS and JS for all pages
    }
    
    public function enqueue_template_assets(string $template_type) {
        // Template-specific assets
        // Only load what's needed for current page
    }
    
    public function enqueue_component_assets(string $component_name) {
        // Component-specific assets
        // Load on-demand when component is used
    }
}
```

**Remove All Other Asset Loading:**
- Delete duplicate asset loading functions
- Consolidate all CSS/JS enqueuing through single system
- Implement conditional loading based on page type

---

## Phase 6: Database Query Optimization

### Bridge Function Caching Pattern

```php
function hph_get_cached_data($key, $callback, $group = 'hph_general', $expiry = 3600) {
    $data = wp_cache_get($key, $group);
    
    if (false === $data) {
        $data = $callback();
        wp_cache_set($key, $data, $group, $expiry);
    }
    
    return $data;
}

// Usage
function hph_get_agent_listings($agent_id) {
    return hph_get_cached_data(
        "agent_listings_{$agent_id}",
        function() use ($agent_id) {
            return get_posts([
                'post_type' => 'listing',
                'meta_key' => 'listing_agent',
                'meta_value' => $agent_id,
                'posts_per_page' => -1
            ]);
        },
        'hph_agents',
        1800
    );
}
```

### Bulk Data Loading Strategy

```php
/**
 * Load multiple listings data in single query
 * Reduces N+1 query problems
 */
function hph_preload_listings_data($listing_ids) {
    if (empty($listing_ids)) return [];
    
    // Single query for all meta data
    global $wpdb;
    $ids_string = implode(',', array_map('intval', $listing_ids));
    
    $meta_data = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ($ids_string)
        AND meta_key LIKE 'field_%'
    ");
    
    // Group by post ID for easy access
    $grouped_data = [];
    foreach ($meta_data as $meta) {
        $grouped_data[$meta->post_id][$meta->meta_key] = $meta->meta_value;
    }
    
    // Cache the results
    foreach ($grouped_data as $post_id => $data) {
        wp_cache_set("hph_listing_meta_{$post_id}", $data, 'hph_listings', 1800);
    }
    
    return $grouped_data;
}
```

---

## Phase 7: Dependency Management & Data Flow

### Plugin-Theme Communication Protocol

```php
/**
 * Plugin provides data contract interface
 * Theme uses interface, never direct plugin methods
 */
interface HPH_Data_Contract {
    public function get_listing_data($listing_id);
    public function get_agent_data($agent_id);
    public function get_dashboard_data($user_id);
    public function search_listings($criteria);
}

// Plugin implements the contract
class HPH_Plugin_Data_Provider implements HPH_Data_Contract {
    // Implementation details
}

// Theme registers data provider
function hph_register_data_provider($provider) {
    if (!$provider instanceof HPH_Data_Contract) {
        throw new InvalidArgumentException('Provider must implement HPH_Data_Contract');
    }
    
    $GLOBALS['hph_data_provider'] = $provider;
}

// Bridge functions use the contract
function hph_get_listing_data($listing_id) {
    $provider = $GLOBALS['hph_data_provider'] ?? new HPH_Fallback_Data_Provider();
    return $provider->get_listing_data($listing_id);
}
```

### Fallback Data Provider

```php
/**
 * Provides basic functionality when plugin is inactive
 */
class HPH_Fallback_Data_Provider implements HPH_Data_Contract {
    public function get_listing_data($listing_id) {
        return [
            'title' => get_the_title($listing_id),
            'content' => get_the_content(null, false, $listing_id),
            'thumbnail' => get_the_post_thumbnail_url($listing_id),
            'price' => 'Contact for Price',
            'status' => 'Available'
        ];
    }
    
    public function get_agent_data($agent_id) {
        return [
            'name' => get_the_title($agent_id),
            'bio' => get_the_content(null, false, $agent_id),
            'avatar' => get_the_post_thumbnail_url($agent_id),
            'contact' => 'Contact for Information'
        ];
    }
    
    // Additional fallback methods...
}
```

---

## Specific Refactoring Tasks

### 1. Template Bridge Cleanup

**Current Issue:** `template-bridge.php` contains 3800+ lines mixing data access with template logic

**Action Required:**
- Split into separate bridge files by functionality
- Remove template loading logic
- Keep only data access functions
- Add proper caching to expensive operations

### 2. Component Consolidation

**Current Issue:** Base components in multiple locations with different patterns

**Action Required:**
- Use single `Base_Component` class in `inc/HappyPlace/Components/`
- Remove duplicate base component implementations
- Standardize all components to extend single base class
- Remove component-related code from other locations

### 3. Asset System Unification

**Current Issue:** 4 different asset loading approaches

**Action Required:**
- Keep only `inc/HappyPlace/Core/Asset_Loader.php`
- Remove all other asset loading classes
- Update all references to use single system
- Implement conditional loading for better performance

### 4. ACF Field Management

**Current Issue:** ACF fields scattered between plugin and theme

**Action Required:**
- Consolidate all ACF field definitions in plugin
- Theme should only access fields through bridge functions
- Remove ACF-specific code from theme files
- Standardize field access patterns

---

## Migration Strategy & Backwards Compatibility

### Function Deprecation Pattern

```php
/**
 * Deprecated function wrapper
 * Provides backwards compatibility during transition
 */
function old_function_name($args) {
    // Log deprecation notice
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('DEPRECATED: old_function_name() is deprecated. Use new_function_name() instead.');
    }
    
    // Call new function
    return new_function_name($args);
}

/**
 * Mark function for removal
 */
function hph_deprecated_function($function_name, $version, $replacement = '') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $message = sprintf(
            'Function %s is deprecated since version %s',
            $function_name,
            $version
        );
        
        if ($replacement) {
            $message .= sprintf(' Use %s instead.', $replacement);
        }
        
        error_log('HPH DEPRECATED: ' . $message);
    }
}
```

### File Migration Checklist

```php
/**
 * Pre-migration file verification
 */
function hph_verify_file_dependencies($file_path) {
    $dependencies = [];
    
    // Scan for function calls
    $content = file_get_contents($file_path);
    
    // Find function dependencies
    preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
    $function_calls = array_unique($matches[1]);
    
    // Check if functions exist
    foreach ($function_calls as $function) {
        if (strpos($function, 'hph_') === 0 && !function_exists($function)) {
            $dependencies['missing_functions'][] = $function;
        }
    }
    
    // Find class dependencies
    preg_match_all('/new\s+([A-Za-z_][A-Za-z0-9_\\\\]*)/i', $content, $matches);
    $class_calls = array_unique($matches[1]);
    
    foreach ($class_calls as $class) {
        if (!class_exists($class)) {
            $dependencies['missing_classes'][] = $class;
        }
    }
    
    return $dependencies;
}
```

### Safe File Replacement Strategy

```php
/**
 * Safe file replacement with rollback capability
 */
class HPH_File_Migrator {
    private $backup_dir;
    private $migrated_files = [];
    
    public function __construct() {
        $this->backup_dir = WP_CONTENT_DIR . '/hph-migration-backup-' . date('Y-m-d-H-i-s');
        wp_mkdir_p($this->backup_dir);
    }
    
    public function backup_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $relative_path = str_replace(ABSPATH, '', $file_path);
        $backup_path = $this->backup_dir . '/' . $relative_path;
        
        wp_mkdir_p(dirname($backup_path));
        
        if (copy($file_path, $backup_path)) {
            $this->migrated_files[] = $file_path;
            return $backup_path;
        }
        
        return false;
    }
    
    public function rollback_all() {
        foreach ($this->migrated_files as $file_path) {
            $relative_path = str_replace(ABSPATH, '', $file_path);
            $backup_path = $this->backup_dir . '/' . $relative_path;
            
            if (file_exists($backup_path)) {
                copy($backup_path, $file_path);
            }
        }
    }
    
    public function cleanup_backups() {
        // Remove backup directory after successful migration
        $this->delete_directory($this->backup_dir);
    }
    
    private function delete_directory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
```

---

## Testing Requirements

### Before Restructuring
1. **Create Full Backup** of both plugin and theme
2. **Document Current Functionality** - test all features work
3. **List All Active Shortcodes** and their usage
4. **Note All Template Dependencies** and their relationships

### During Restructuring
1. **Test Each Phase Individually** - don't move to next phase until current is working
2. **Verify Bridge Functions** still work after each change
3. **Check Template Rendering** after each consolidation
4. **Test Plugin Deactivation** - theme should gracefully degrade

### After Restructuring
1. **Full Functionality Test** - all features should work as before
2. **Performance Testing** - should be faster, not slower
3. **Code Quality Check** - no duplicate functionality remaining
4. **Security Audit** - ensure no vulnerabilities introduced

### Automated Testing Integration

```php
/**
 * Bridge function test suite
 */
class HPH_Bridge_Function_Tests {
    public function test_listing_data_access() {
        $listing_id = $this->create_test_listing();
        
        // Test with plugin active
        $data_with_plugin = hph_get_listing_data($listing_id);
        $this->assertArrayHasKey('price', $data_with_plugin);
        
        // Test fallback behavior
        $this->deactivate_plugin();
        $data_fallback = hph_get_listing_data($listing_id);
        $this->assertArrayHasKey('title', $data_fallback);
        
        $this->activate_plugin();
    }
    
    public function test_performance_benchmarks() {
        $start_time = microtime(true);
        
        // Load 50 listings
        $listings = get_posts(['post_type' => 'listing', 'numberposts' => 50]);
        foreach ($listings as $listing) {
            hph_get_listing_data($listing->ID);
        }
        
        $execution_time = microtime(true) - $start_time;
        
        // Should load 50 listings in under 2 seconds
        $this->assertLessThan(2.0, $execution_time);
    }
}
```

### Migration Validation Script

```php
/**
 * Post-migration validation
 */
function hph_validate_migration() {
    $results = [
        'errors' => [],
        'warnings' => [],
        'success' => []
    ];
    
    // Check critical functions exist
    $critical_functions = [
        'hph_get_listing_data',
        'hph_get_agent_data',
        'hph_render_component'
    ];
    
    foreach ($critical_functions as $function) {
        if (!function_exists($function)) {
            $results['errors'][] = "Critical function missing: {$function}";
        } else {
            $results['success'][] = "Function exists: {$function}";
        }
    }
    
    // Check asset loading
    if (!class_exists('HappyPlace\\Core\\Asset_Loader')) {
        $results['errors'][] = 'Asset_Loader class missing';
    }
    
    // Check for orphaned files
    $orphaned_files = hph_find_orphaned_files();
    if (!empty($orphaned_files)) {
        $results['warnings'] = array_merge($results['warnings'], $orphaned_files);
    }
    
    // Performance check
    $performance_score = hph_run_performance_test();
    if ($performance_score < 80) {
        $results['warnings'][] = "Performance score below target: {$performance_score}%";
    }
    
    return $results;
}

/**
 * Find files that may have been orphaned during migration
 */
function hph_find_orphaned_files() {
    $potential_orphans = [];
    
    // Look for backup files
    $backup_patterns = ['*.bak', '*.backup', '*.old', '*-backup.*'];
    foreach ($backup_patterns as $pattern) {
        $files = glob(get_template_directory() . '/**/' . $pattern, GLOB_BRACE);
        $potential_orphans = array_merge($potential_orphans, $files);
    }
    
    // Look for duplicate class definitions
    $class_files = glob(get_template_directory() . '/**/class-*.php', GLOB_BRACE);
    $class_names = [];
    
    foreach ($class_files as $file) {
        $content = file_get_contents($file);
        preg_match('/class\s+([A-Za-z_][A-Za-z0-9_]*)/i', $content, $matches);
        
        if (isset($matches[1])) {
            $class_name = $matches[1];
            if (isset($class_names[$class_name])) {
                $potential_orphans[] = "Duplicate class {$class_name} in {$file}";
            }
            $class_names[$class_name] = $file;
        }
    }
    
    return $potential_orphans;
}
```

---

## Success Metrics

### Code Quality Goals
- [ ] Reduce total lines of code by 20-30%
- [ ] Eliminate all duplicate functionality
- [ ] Remove all debug/test code from production
- [ ] Achieve consistent naming conventions
- [ ] Establish clear plugin-theme boundaries

### Performance Goals
- [ ] Reduce asset loading time by 40%
- [ ] Improve database query efficiency through caching
- [ ] Optimize memory usage
- [ ] Faster page load times

### Architecture Goals
- [ ] Single asset loading system
- [ ] Unified component architecture
- [ ] Clear separation of concerns
- [ ] Consistent error handling
- [ ] Proper fallback mechanisms

---

## Risk Mitigation

### Low Risk Changes (Start Here)
- Removing debug/test files
- Consolidating duplicate classes
- Standardizing naming conventions

### Medium Risk Changes (Careful Testing)
- Reorganizing file structure
- Changing asset loading system
- Consolidating components

### High Risk Changes (Extensive Testing Required)
- Modifying bridge functions
- Changing template loading logic
- Altering plugin-theme communication

---

## Implementation Order (CRITICAL)

1. **Week 1:** Remove debug/test files and obvious redundancies
2. **Week 2:** Consolidate asset systems and template loading
3. **Week 3:** Reorganize file structure and standardize naming
4. **Week 4:** Optimize performance and conduct thorough testing

**IMPORTANT:** Each phase must be fully tested and working before proceeding to the next phase. This is a refactoring project - functionality should remain identical while code becomes cleaner and more maintainable.

---

## VSCode Copilot Specific Instructions

When helping with this restructuring:

1. **Always Preserve Functionality** - this is refactoring, not feature development
2. **Follow the Architecture Principles** - maintain clear plugin-theme boundaries  
3. **Use Consistent Naming** - enforce the naming conventions specified
4. **Implement Proper Caching** - add caching to bridge functions accessing database
5. **Add Fallback Logic** - bridge functions must work when plugin is inactive
6. **Remove, Don't Comment** - delete redundant code rather than commenting it out
7. **Test Suggestions** - always suggest testing steps for changes
8. **Document Changes** - explain why each change improves the architecture

Remember: The goal is a cleaner, more maintainable codebase that performs better and is easier to develop with, while maintaining all current functionality.

---

## Post-Restructuring Monitoring & Maintenance

### Performance Monitoring

```php
/**
 * Performance monitoring dashboard
 */
class HPH_Performance_Monitor {
    public function log_page_load_time($page_type) {
        if (!defined('HPH_MONITOR_PERFORMANCE') || !HPH_MONITOR_PERFORMANCE) {
            return;
        }
        
        $load_time = timer_stop(0, 3);
        $memory_usage = memory_get_peak_usage(true);
        $query_count = get_num_queries();
        
        update_option("hph_perf_{$page_type}", [
            'load_time' => $load_time,
            'memory' => $memory_usage,
            'queries' => $query_count,
            'timestamp' => time()
        ]);
    }
    
    public function get_performance_report() {
        $pages = ['listing', 'agent', 'archive', 'dashboard'];
        $report = [];
        
        foreach ($pages as $page) {
            $data = get_option("hph_perf_{$page}", []);
            if (!empty($data)) {
                $report[$page] = $data;
            }
        }
        
        return $report;
    }
}
```

### Code Quality Maintenance

```php
/**
 * Ongoing code quality checks
 */
function hph_run_quality_checks() {
    $checks = [
        'unused_functions' => hph_find_unused_functions(),
        'duplicate_code' => hph_find_duplicate_code_blocks(),
        'naming_violations' => hph_check_naming_conventions(),
        'performance_issues' => hph_scan_performance_issues()
    ];
    
    // Log issues for review
    foreach ($checks as $check_type => $issues) {
        if (!empty($issues)) {
            error_log("HPH Quality Check - {$check_type}: " . json_encode($issues));
        }
    }
    
    return $checks;
}

/**
 * Find functions that are defined but never called
 */
function hph_find_unused_functions() {
    $all_functions = get_defined_functions()['user'];
    $hph_functions = array_filter($all_functions, function($func) {
        return strpos($func, 'hph_') === 0;
    });
    
    $unused = [];
    foreach ($hph_functions as $function) {
        if (!hph_function_is_used($function)) {
            $unused[] = $function;
        }
    }
    
    return $unused;
}

/**
 * Check if a function is used anywhere in the codebase
 */
function hph_function_is_used($function_name) {
    $search_paths = [
        get_template_directory(),
        WP_PLUGIN_DIR . '/Happy Place Plugin'
    ];
    
    foreach ($search_paths as $path) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                if (strpos($content, $function_name) !== false) {
                    return true;
                }
            }
        }
    }
    
    return false;
}
```

### Maintenance Schedule

```php
/**
 * Scheduled maintenance tasks
 */
function hph_schedule_maintenance_tasks() {
    // Daily cache cleanup
    if (!wp_next_scheduled('hph_daily_cache_cleanup')) {
        wp_schedule_event(time(), 'daily', 'hph_daily_cache_cleanup');
    }
    
    // Weekly performance report
    if (!wp_next_scheduled('hph_weekly_performance_report')) {
        wp_schedule_event(time(), 'weekly', 'hph_weekly_performance_report');
    }
    
    // Monthly code quality check
    if (!wp_next_scheduled('hph_monthly_quality_check')) {
        wp_schedule_event(time(), 'monthly', 'hph_monthly_quality_check');
    }
}

add_action('hph_daily_cache_cleanup', function() {
    wp_cache_flush_group('hph_listings');
    wp_cache_flush_group('hph_agents');
    clean_post_cache();
});

add_action('hph_weekly_performance_report', function() {
    $monitor = new HPH_Performance_Monitor();
    $report = $monitor->get_performance_report();
    
    // Send report to admin email if performance degrades
    foreach ($report as $page_type => $data) {
        if ($data['load_time'] > 3.0) { // Alert if load time > 3 seconds
            wp_mail(
                get_option('admin_email'),
                "Performance Alert: {$page_type} page slow",
                "Load time: {$data['load_time']}s, Memory: {$data['memory']} bytes"
            );
        }
    }
});

add_action('hph_monthly_quality_check', function() {
    $issues = hph_run_quality_checks();
    
    if (!empty($issues['unused_functions']) || !empty($issues['duplicate_code'])) {
        wp_mail(
            get_option('admin_email'),
            'Monthly Code Quality Report',
            'Code quality issues detected. Please review error logs.'
        );
    }
});
```

### Documentation Maintenance

```markdown
## Documentation Standards

### Function Documentation
```php
/**
 * Bridge function description
 * 
 * @since 2.0.0
 * @param int    $listing_id The listing post ID
 * @param string $format     Output format (raw|formatted)
 * @return array|string      Listing data or formatted output
 * 
 * @example
 * // Get formatted price
 * $price = hph_get_listing_price(123, 'formatted');
 * 
 * // Get raw price data
 * $price_data = hph_get_listing_price(123, 'raw');
 */
function hph_get_listing_price($listing_id, $format = 'formatted') {
    // Implementation
}
```

### Component Documentation
```php
/**
 * Component usage and props documentation
 * 
 * @since 2.0.0
 * 
 * Available Props:
 * - listing_id (int): Required. The listing post ID
 * - variant (string): Optional. Display variant (card|featured|minimal)
 * - show_agent (bool): Optional. Whether to display agent info
 * - lazy_load (bool): Optional. Enable lazy loading for images
 * 
 * @example
 * $component = new Listing_Card([
 *     'listing_id' => 123,
 *     'variant' => 'featured',
 *     'show_agent' => true
 * ]);
 * $component->display();
 */
```

### Change Log Maintenance
Keep detailed changelog of all architectural changes:

```markdown
## Changelog

### [2.0.0] - 2025-07-28 - Major Restructuring
#### Added
- Single Asset_Loader system for optimal performance
- Contract-based plugin-theme communication
- Comprehensive fallback system for plugin-inactive scenarios
- Performance monitoring and quality checks

#### Changed
- Consolidated 4 asset loading systems into 1
- Moved all template loading logic to theme
- Standardized naming conventions across all files
- Optimized bridge functions with proper caching

#### Removed
- All debug and test files from production
- Duplicate dashboard function implementations
- Redundant component base classes
- Unused template loading mechanisms

#### Performance
- 40% reduction in asset loading time
- 30% reduction in total codebase size
- Eliminated N+1 query problems in listing displays
- Improved memory usage through better caching
```
```

This enhanced documentation ensures long-term maintainability and provides clear guidelines for future development while preserving the architectural improvements achieved through the restructuring process.