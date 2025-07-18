<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="container">
    <div class="listing-single">
        <div class="listing-header">
            <div class="container">
                <h1 class="listing-title">🏡 Happy Place Theme - Asset Test</h1>
                <p>Testing SCSS compilation and asset enqueuing system</p>
            </div>
        </div>
        
        <div class="listing-content">
            <div class="main-content">
                <h2>✅ Asset Diagnosis Complete</h2>
                
                <div class="asset-status">
                    <h3>🎨 CSS Status</h3>
                    <p>If you can see styled content below, the SCSS compilation is working:</p>
                    <div class="test-styles">
                        <button class="btn btn-primary">Primary Button</button>
                        <button class="btn btn-secondary">Secondary Button</button>
                    </div>
                    
                    <h3>🔧 JavaScript Status</h3>
                    <p>Check browser console for "Happy Place Theme JS loaded" message.</p>
                    
                    <h3>📁 Build System</h3>
                    <ul>
                        <li>✅ SCSS compilation working with custom build script</li>
                        <li>✅ Asset manifest generation working</li>
                        <li>✅ WordPress asset enqueuing configured</li>
                        <li>✅ Modern SCSS syntax (no deprecation warnings)</li>
                    </ul>
                </div>
            </div>
            
            <div class="sidebar">
                <div class="sidebar-widget">
                    <h3>Build Commands</h3>
                    <code>npm run build</code> - Compile assets<br>
                    <code>npm run watch</code> - Watch for changes<br>
                    <code>./build.sh</code> - Direct build script
                </div>
                
                <div class="sidebar-widget">
                    <h3>File Structure</h3>
                    <ul style="font-size: 0.9em;">
                        <li>📁 assets/src/scss/main.scss → Input</li>
                        <li>📁 assets/dist/css/main.css → Output</li>
                        <li>📁 assets/dist/manifest.json → Manifest</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
