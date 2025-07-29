<?php
/**
 * API Settings Page Template
 * Phase 4 Day 4-7: API Integration Management
 * 
 * Admin interface for configuring API integrations and performance settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$google_api_key = get_option('hph_google_maps_api_key', '');
$mls_api_url = get_option('hph_mls_api_url', '');
$mls_api_key = get_option('hph_mls_api_key', '');
$mls_username = get_option('hph_mls_username', '');
$mls_password = get_option('hph_mls_password', '');
$property_data_api_url = get_option('hph_property_data_api_url', '');
$property_data_api_key = get_option('hph_property_data_api_key', '');
$market_analytics_api_url = get_option('hph_market_analytics_api_url', '');
$market_analytics_api_key = get_option('hph_market_analytics_api_key', '');

?>

<div class="wrap">
    <h1>🔗 Happy Place API Settings</h1>
    <p>Configure external API integrations for enhanced functionality.</p>

    <?php settings_errors('hph_api_settings'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('hph_api_settings', 'hph_api_nonce'); ?>
        
        <div class="hph-api-settings" style="max-width: 1000px;">
            
            <!-- Google Maps API Section -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 style="margin-top: 0;">🗺️ Google Maps API</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hph_google_maps_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hph_google_maps_api_key" 
                                   name="hph_google_maps_api_key" 
                                   value="<?php echo esc_attr($google_api_key); ?>" 
                                   class="regular-text" 
                                   placeholder="AIzaSyB...">
                            <p class="description">
                                Google Maps API key for geocoding, places, and mapping features.
                                <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Get API Key</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- MLS Integration Section -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 style="margin-top: 0;">🏠 MLS Integration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hph_mls_api_url">MLS API URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="hph_mls_api_url" 
                                   name="hph_mls_api_url" 
                                   value="<?php echo esc_attr($mls_api_url); ?>" 
                                   class="regular-text" 
                                   placeholder="https://api.mls-provider.com">
                            <p class="description">Base URL for your MLS data provider.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hph_mls_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hph_mls_api_key" 
                                   name="hph_mls_api_key" 
                                   value="<?php echo esc_attr($mls_api_key); ?>" 
                                   class="regular-text" 
                                   placeholder="Your MLS API key">
                            <p class="description">API key provided by your MLS service.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hph_mls_username">Username</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hph_mls_username" 
                                   name="hph_mls_username" 
                                   value="<?php echo esc_attr($mls_username); ?>" 
                                   class="regular-text" 
                                   placeholder="MLS username">
                            <p class="description">Username for MLS authentication (if required).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hph_mls_password">Password</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="hph_mls_password" 
                                   name="hph_mls_password" 
                                   value="<?php echo esc_attr($mls_password); ?>" 
                                   class="regular-text" 
                                   placeholder="MLS password">
                            <p class="description">Password for MLS authentication (if required).</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Property Data API Section -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 style="margin-top: 0;">📊 Property Data API</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hph_property_data_api_url">API URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="hph_property_data_api_url" 
                                   name="hph_property_data_api_url" 
                                   value="<?php echo esc_attr($property_data_api_url); ?>" 
                                   class="regular-text" 
                                   placeholder="https://api.property-data.com">
                            <p class="description">URL for property valuation and market data API.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hph_property_data_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hph_property_data_api_key" 
                                   name="hph_property_data_api_key" 
                                   value="<?php echo esc_attr($property_data_api_key); ?>" 
                                   class="regular-text" 
                                   placeholder="Property data API key">
                            <p class="description">API key for property data service.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Market Analytics API Section -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 style="margin-top: 0;">📈 Market Analytics API</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hph_market_analytics_api_url">API URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="hph_market_analytics_api_url" 
                                   name="hph_market_analytics_api_url" 
                                   value="<?php echo esc_attr($market_analytics_api_url); ?>" 
                                   class="regular-text" 
                                   placeholder="https://api.market-analytics.com">
                            <p class="description">URL for market trends and analytics API.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hph_market_analytics_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hph_market_analytics_api_key" 
                                   name="hph_market_analytics_api_key" 
                                   value="<?php echo esc_attr($market_analytics_api_key); ?>" 
                                   class="regular-text" 
                                   placeholder="Market analytics API key">
                            <p class="description">API key for market analytics service.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- API Status & Testing -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 style="margin-top: 0;">🧪 API Status & Testing</h2>
                
                <div class="api-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                    
                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0;">Google Maps</h4>
                        <span class="status-indicator" id="google-status">
                            <?php echo !empty($google_api_key) ? '🟢 Configured' : '🔴 Not configured'; ?>
                        </span>
                        <button type="button" class="button button-secondary test-api" data-service="google_maps" style="margin-top: 10px; width: 100%;">
                            Test Connection
                        </button>
                    </div>

                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0;">MLS Data</h4>
                        <span class="status-indicator" id="mls-status">
                            <?php echo !empty($mls_api_url) && !empty($mls_api_key) ? '🟢 Configured' : '🔴 Not configured'; ?>
                        </span>
                        <button type="button" class="button button-secondary test-api" data-service="mls_data" style="margin-top: 10px; width: 100%;">
                            Test Connection
                        </button>
                    </div>

                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0;">Property Data</h4>
                        <span class="status-indicator" id="property-status">
                            <?php echo !empty($property_data_api_url) && !empty($property_data_api_key) ? '🟢 Configured' : '🔴 Not configured'; ?>
                        </span>
                        <button type="button" class="button button-secondary test-api" data-service="property_data" style="margin-top: 10px; width: 100%;">
                            Test Connection
                        </button>
                    </div>

                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0;">Market Analytics</h4>
                        <span class="status-indicator" id="analytics-status">
                            <?php echo !empty($market_analytics_api_url) && !empty($market_analytics_api_key) ? '🟢 Configured' : '🔴 Not configured'; ?>
                        </span>
                        <button type="button" class="button button-secondary test-api" data-service="market_analytics" style="margin-top: 10px; width: 100%;">
                            Test Connection
                        </button>
                    </div>
                </div>

                <div id="test-results" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; display: none;">
                    <h4>Test Results:</h4>
                    <div id="test-output"></div>
                </div>
            </div>

            <!-- Cache Management -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 style="margin-top: 0;">💾 Cache Management</h2>
                <p>Manage API response caching to improve performance.</p>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="button button-secondary" id="clear-api-cache">
                        Clear API Cache
                    </button>
                    <button type="button" class="button button-secondary" id="view-cache-stats">
                        View Cache Statistics
                    </button>
                    <button type="button" class="button button-secondary" id="view-usage-stats">
                        View Usage Statistics
                    </button>
                </div>

                <div id="cache-stats" style="margin-top: 15px; display: none;">
                    <div id="cache-stats-content"></div>
                </div>
            </div>

        </div>

        <?php submit_button('Save API Settings'); ?>
    </form>
</div>

<style>
.hph-api-settings .card {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.hph-api-settings .card h2 {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.status-indicator {
    font-weight: bold;
    font-size: 14px;
}

.test-results {
    background: #f0f8ff;
    border: 1px solid #c3d9ff;
    border-radius: 4px;
    padding: 10px;
    margin-top: 10px;
}

.test-results.success {
    background: #f0fff4;
    border-color: #c3e6cb;
    color: #155724;
}

.test-results.error {
    background: #fff5f5;
    border-color: #f5c6cb;
    color: #721c24;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Test API connections
    $('.test-api').on('click', function() {
        const button = $(this);
        const service = button.data('service');
        const originalText = button.text();
        
        button.text('Testing...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'hph_test_api_connection',
            service: service
        }, function(response) {
            $('#test-results').show();
            
            if (response.success) {
                $('#test-output').html(`
                    <div class="test-results success">
                        <strong>${service}:</strong> ${response.data.message || 'Connection successful'}
                    </div>
                `);
            } else {
                $('#test-output').html(`
                    <div class="test-results error">
                        <strong>${service}:</strong> ${response.data || 'Connection failed'}
                    </div>
                `);
            }
        }).fail(function() {
            $('#test-results').show();
            $('#test-output').html(`
                <div class="test-results error">
                    <strong>${service}:</strong> Request failed - check network connection
                </div>
            `);
        }).always(function() {
            button.text(originalText).prop('disabled', false);
        });
    });
    
    // Clear API cache
    $('#clear-api-cache').on('click', function() {
        const button = $(this);
        
        if (!confirm('Clear all API cache? This may temporarily slow down API responses.')) {
            return;
        }
        
        button.text('Clearing...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'hph_refresh_api_cache'
        }, function(response) {
            alert('API cache cleared successfully!');
        }).fail(function() {
            alert('Failed to clear cache. Please try again.');
        }).always(function() {
            button.text('Clear API Cache').prop('disabled', false);
        });
    });
    
    // View cache statistics
    $('#view-cache-stats').on('click', function() {
        const button = $(this);
        button.text('Loading...').prop('disabled', true);
        
        // This would be implemented in the API Integration Manager
        $.post(ajaxurl, {
            action: 'hph_get_cache_stats'
        }, function(response) {
            if (response.success) {
                const stats = response.data;
                $('#cache-stats-content').html(`
                    <h4>Cache Statistics:</h4>
                    <p><strong>Total Entries:</strong> ${stats.total_entries || 0}</p>
                    <p><strong>Cache Size:</strong> ${stats.total_size_mb || 0} MB</p>
                    <p><strong>Hit Rate:</strong> ${stats.hit_rate || 0}%</p>
                `);
                $('#cache-stats').show();
            }
        }).always(function() {
            button.text('View Cache Statistics').prop('disabled', false);
        });
    });
    
    // View usage statistics
    $('#view-usage-stats').on('click', function() {
        const button = $(this);
        button.text('Loading...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'hph_get_usage_stats'
        }, function(response) {
            if (response.success) {
                const stats = response.data;
                let statsHtml = '<h4>API Usage Statistics:</h4>';
                
                Object.keys(stats).forEach(function(service) {
                    const usage = stats[service];
                    statsHtml += `
                        <p><strong>${service}:</strong> 
                        ${usage.current_usage}/${usage.rate_limit} requests 
                        (${usage.percentage}% used)</p>
                    `;
                });
                
                $('#cache-stats-content').html(statsHtml);
                $('#cache-stats').show();
            }
        }).always(function() {
            button.text('View Usage Statistics').prop('disabled', false);
        });
    });
});
</script>
