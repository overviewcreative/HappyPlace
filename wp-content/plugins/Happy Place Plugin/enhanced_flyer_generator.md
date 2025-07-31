# Enhanced Flyer Generator with Open House Support

## 🏠 **Overview**

This enhancement adds comprehensive open house flyer support to your existing flyer generator, including:

- **Flyer Type Selection** (Listing vs Open House)
- **Open House Form Fields** (Date, Time, Hosting Agent)  
- **Dynamic Flyer Layouts** based on type
- **Enhanced AJAX Handler** support
- **Improved UI/UX** with conditional fields

---

## 📋 **1. Enhanced HTML Structure**

**File:** `templates/admin/flyer-generator.php` (or wherever your flyer generator admin page is)

```php
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="flyer-generator-container">
        
        <!-- Flyer Type Selection -->
        <div class="flyer-type-section">
            <h3>Flyer Type</h3>
            <div class="flyer-type-options">
                <label class="flyer-type-option active">
                    <input type="radio" name="flyer_type" value="listing" checked>
                    <span class="option-content">
                        <i class="fas fa-home"></i>
                        <strong>Listing Flyer</strong>
                        <small>Standard property listing flyer</small>
                    </span>
                </label>
                
                <label class="flyer-type-option">
                    <input type="radio" name="flyer_type" value="open_house">
                    <span class="option-content">
                        <i class="fas fa-door-open"></i>
                        <strong>Open House Flyer</strong>
                        <small>Open house event flyer</small>
                    </span>
                </label>
            </div>
        </div>

        <!-- Listing Selection -->
        <div class="form-section">
            <h3>Select Property</h3>
            <select id="listing-select" class="form-control">
                <option value="">Choose a listing...</option>
                <?php
                $listings = get_posts([
                    'post_type' => 'listing',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ]);
                
                foreach ($listings as $listing) {
                    $address = get_field('address', $listing->ID) ?: $listing->post_title;
                    $price = get_field('price', $listing->ID);
                    $price_formatted = $price ? ' - $' . number_format($price) : '';
                    
                    echo '<option value="' . esc_attr($listing->ID) . '">';
                    echo esc_html($address . $price_formatted);
                    echo '</option>';
                }
                ?>
            </select>
        </div>

        <!-- Open House Details (shown only for open house flyers) -->
        <div class="form-section open-house-section" style="display: none;">
            <h3>Open House Details</h3>
            
            <div class="form-row">
                <div class="form-col">
                    <label for="open-house-date">Date</label>
                    <input type="date" id="open-house-date" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-col">
                    <label for="open-house-day">Day of Week</label>
                    <input type="text" id="open-house-day" class="form-control" readonly 
                           placeholder="Will auto-populate">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <label for="open-house-start">Start Time</label>
                    <input type="time" id="open-house-start" class="form-control" value="14:00">
                </div>
                
                <div class="form-col">
                    <label for="open-house-end">End Time</label>
                    <input type="time" id="open-house-end" class="form-control" value="16:00">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col full-width">
                    <label for="hosting-agent">Hosting Agent</label>
                    <select id="hosting-agent" class="form-control">
                        <option value="">Same as listing agent</option>
                        <?php
                        // Get all users with agent/realtor capabilities
                        $agents = get_users([
                            'meta_key' => 'user_type',
                            'meta_value' => 'agent',
                            'orderby' => 'display_name'
                        ]);
                        
                        foreach ($agents as $agent) {
                            echo '<option value="' . esc_attr($agent->ID) . '">';
                            echo esc_html($agent->display_name);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="open-house-preview">
                <div class="preview-card">
                    <h4>Open House Preview</h4>
                    <div id="open-house-summary">
                        <p class="oh-date">Date: <span>Not set</span></p>
                        <p class="oh-time">Time: <span>Not set</span></p>
                        <p class="oh-agent">Host: <span>Not set</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Selection -->
        <div class="form-section template-section">
            <h3>Template Style</h3>
            <div class="template-options">
                <label class="template-option active">
                    <input type="radio" name="template" value="parker_group" checked>
                    <div class="template-preview">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/parker-group-template.jpg" 
                             alt="Parker Group Template">
                        <span>Parker Group</span>
                    </div>
                </label>
                
                <label class="template-option">
                    <input type="radio" name="template" value="modern">
                    <div class="template-preview">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/modern-template.jpg" 
                             alt="Modern Template">
                        <span>Modern</span>
                    </div>
                </label>
                
                <label class="template-option">
                    <input type="radio" name="template" value="luxury">
                    <div class="template-preview">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/luxury-template.jpg" 
                             alt="Luxury Template">
                        <span>Luxury</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Generation Controls -->
        <div class="form-section">
            <button id="generate-flyer" class="btn btn-primary" disabled>
                <i class="fas fa-magic"></i>
                Generate Flyer
            </button>
        </div>

        <!-- Loading State -->
        <div class="flyer-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Generating your flyer...</p>
        </div>

        <!-- Error Display -->
        <div class="flyer-error" style="display: none;">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="error-message"></span>
                <button class="dismiss-error">&times;</button>
            </div>
        </div>

        <!-- Canvas Container -->
        <div class="flyer-canvas-container">
            <canvas id="flyer-canvas" width="850" height="1100"></canvas>
        </div>

        <!-- Download Controls -->
        <div class="flyer-download-controls" style="display: none;">
            <h3>Download Your Flyer</h3>
            <div class="download-buttons">
                <button id="download-flyer" class="btn btn-success">
                    <i class="fas fa-download"></i>
                    Download PNG
                </button>
                <button id="download-pdf" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i>
                    Download PDF
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## 🎨 **2. Enhanced CSS Styles**

**File:** `assets/css/flyer-generator.css`

```css
/* Flyer Generator Enhanced Styles */

.flyer-generator-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Flyer Type Selection */
.flyer-type-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.flyer-type-options {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.flyer-type-option {
    flex: 1;
    cursor: pointer;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fafafa;
}

.flyer-type-option:hover {
    border-color: #51bae0;
    background: #f0f9ff;
}

.flyer-type-option.active {
    border-color: #51bae0;
    background: #f0f9ff;
    box-shadow: 0 2px 8px rgba(81, 186, 224, 0.2);
}

.flyer-type-option input[type="radio"] {
    display: none;
}

.option-content i {
    font-size: 24px;
    color: #51bae0;
    margin-bottom: 10px;
    display: block;
}

.option-content strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.option-content small {
    color: #666;
    font-size: 12px;
}

/* Form Sections */
.form-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    border-bottom: 2px solid #51bae0;
    padding-bottom: 8px;
}

/* Open House Section */
.open-house-section {
    transition: all 0.3s ease;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-col {
    flex: 1;
}

.form-col.full-width {
    flex: 100%;
}

.form-col label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #51bae0;
    box-shadow: 0 0 0 2px rgba(81, 186, 224, 0.2);
}

/* Open House Preview */
.open-house-preview {
    margin-top: 20px;
}

.preview-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
}

.preview-card h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #51bae0;
}

#open-house-summary p {
    margin: 5px 0;
    font-size: 14px;
}

#open-house-summary span {
    font-weight: 600;
    color: #333;
}

/* Template Selection */
.template-options {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.template-option {
    flex: 1;
    cursor: pointer;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fff;
}

.template-option:hover {
    border-color: #51bae0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.template-option.active {
    border-color: #51bae0;
    background: #f0f9ff;
    box-shadow: 0 2px 8px rgba(81, 186, 224, 0.2);
}

.template-option input[type="radio"] {
    display: none;
}

.template-preview img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 10px;
}

.template-preview span {
    display: block;
    font-weight: 600;
    color: #333;
}

/* Canvas Container */
.flyer-canvas-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

#flyer-canvas {
    max-width: 100%;
    height: auto;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #51bae0;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #3a9bc1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(81, 186, 224, 0.3);
}

.btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

/* Loading State */
.flyer-loading {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e3e3e3;
    border-top: 4px solid #51bae0;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Error Display */
.flyer-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 15px;
    margin: 20px 0;
}

.error-content {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #721c24;
}

.error-content i {
    color: #dc3545;
}

.dismiss-error {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    margin-left: auto;
    color: #721c24;
}

/* Download Controls */
.flyer-download-controls {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.download-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 15px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .flyer-type-options,
    .template-options,
    .download-buttons {
        flex-direction: column;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .flyer-generator-container {
        padding: 10px;
    }
}
```

---

## 💻 **3. Enhanced JavaScript**

**File:** `assets/js/enhanced-flyer-generator.js`

```javascript
(function($) {
    'use strict';

    let canvas;
    let isGenerating = false;
    let currentFlyerType = 'listing';
    let openHouseData = {};

    $(document).ready(function() {
        console.log('Enhanced Flyer Generator: Document ready');
        initializeEnhancedFlyerGenerator();
    });

    function initializeEnhancedFlyerGenerator() {
        console.log('Enhanced Flyer Generator: Initializing...');
        
        if (typeof fabric === 'undefined') {
            console.error('Fabric.js library not loaded');
            showError('Fabric.js library not loaded. Please refresh the page.');
            return;
        }

        if (typeof flyerGenerator === 'undefined') {
            console.error('flyerGenerator object not found');
            showError('Configuration object not found. Please refresh the page.');
            return;
        }

        // Initialize Fabric.js canvas
        try {
            canvas = new fabric.Canvas('flyer-canvas', {
                backgroundColor: '#ffffff',
                selection: false
            });
            
            canvas.setDimensions({
                width: 850,
                height: 1100
            });
            
            console.log('Canvas initialized successfully');
        } catch (error) {
            console.error('Error initializing canvas:', error);
            showError('Failed to initialize canvas. Please refresh the page.');
            return;
        }

        // Bind event handlers
        bindEventHandlers();
        
        // Initialize form state
        initializeFormState();
        
        console.log('Enhanced Flyer Generator: Initialization complete');
    }

    function bindEventHandlers() {
        // Flyer type selection
        $('input[name="flyer_type"]').on('change', handleFlyerTypeChange);
        
        // Open house date/time handlers
        $('#open-house-date').on('change', handleDateChange);
        $('#open-house-start, #open-house-end').on('change', updateOpenHousePreview);
        $('#hosting-agent').on('change', updateOpenHousePreview);
        
        // Template selection
        $('input[name="template"]').on('change', handleTemplateChange);
        
        // Main controls
        $('#generate-flyer').on('click', handleGenerateFlyer);
        $('#download-flyer').on('click', downloadPNG);
        $('#download-pdf').on('click', downloadPDF);
        $('#listing-select').on('change', handleListingChange);
        
        // Error handling
        $('.dismiss-error').on('click', hideError);
        
        // Template option visual feedback
        $('.template-option').on('click', function() {
            $('.template-option').removeClass('active');
            $(this).addClass('active');
        });
        
        // Flyer type option visual feedback
        $('.flyer-type-option').on('click', function() {
            $('.flyer-type-option').removeClass('active');
            $(this).addClass('active');
        });
    }

    function initializeFormState() {
        // Set default date to next Saturday
        const nextSaturday = getNextSaturday();
        $('#open-house-date').val(nextSaturday.toISOString().split('T')[0]);
        handleDateChange();
        
        // Update preview
        updateOpenHousePreview();
        
        // Check if listing is pre-selected
        handleListingChange();
    }

    function handleFlyerTypeChange() {
        const selectedType = $('input[name="flyer_type"]:checked').val();
        currentFlyerType = selectedType;
        
        console.log('Flyer type changed to:', selectedType);
        
        if (selectedType === 'open_house') {
            $('.open-house-section').slideDown(300);
        } else {
            $('.open-house-section').slideUp(300);
        }
        
        // Update generate button state
        updateGenerateButtonState();
        
        // Clear existing flyer if one exists
        if (canvas) {
            canvas.clear();
            $('.flyer-download-controls').hide();
        }
    }

    function handleDateChange() {
        const dateInput = $('#open-house-date');
        const dayInput = $('#open-house-day');
        
        if (dateInput.val()) {
            const selectedDate = new Date(dateInput.val());
            const dayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
            dayInput.val(dayName);
        } else {
            dayInput.val('');
        }
        
        updateOpenHousePreview();
    }

    function updateOpenHousePreview() {
        const date = $('#open-house-date').val();
        const startTime = $('#open-house-start').val();
        const endTime = $('#open-house-end').val();
        const hostingAgentId = $('#hosting-agent').val();
        const hostingAgentName = $('#hosting-agent option:selected').text();
        
        // Update preview display
        if (date) {
            const formattedDate = new Date(date).toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            $('.oh-date span').text(formattedDate);
        } else {
            $('.oh-date span').text('Not set');
        }
        
        if (startTime && endTime) {
            const formattedTime = formatTimeRange(startTime, endTime);
            $('.oh-time span').text(formattedTime);
        } else {
            $('.oh-time span').text('Not set');
        }
        
        if (hostingAgentId && hostingAgentName !== 'Same as listing agent') {
            $('.oh-agent span').text(hostingAgentName);
        } else {
            $('.oh-agent span').text('Listing agent');
        }
        
        // Store open house data
        openHouseData = {
            date: date,
            day: $('#open-house-day').val(),
            start_time: startTime,
            end_time: endTime,
            hosting_agent_id: hostingAgentId,
            hosting_agent_name: hostingAgentName !== 'Same as listing agent' ? hostingAgentName : null
        };
        
        updateGenerateButtonState();
    }

    function handleTemplateChange() {
        const selectedTemplate = $('input[name="template"]:checked').val();
        console.log('Template changed to:', selectedTemplate);
        
        // Clear existing flyer
        if (canvas) {
            canvas.clear();
            $('.flyer-download-controls').hide();
        }
    }

    function handleListingChange() {
        const listingId = $('#listing-select').val();
        console.log('Listing changed to:', listingId);
        
        updateGenerateButtonState();
        
        // Clear existing flyer
        if (canvas) {
            canvas.clear();
            $('.flyer-download-controls').hide();
        }
    }

    function updateGenerateButtonState() {
        const listingSelected = $('#listing-select').val();
        let canGenerate = !!listingSelected;
        
        // Additional validation for open house flyers
        if (currentFlyerType === 'open_house') {
            const hasDate = !!$('#open-house-date').val();
            const hasStartTime = !!$('#open-house-start').val();
            const hasEndTime = !!$('#open-house-end').val();
            
            canGenerate = canGenerate && hasDate && hasStartTime && hasEndTime;
        }
        
        $('#generate-flyer').prop('disabled', !canGenerate);
        
        if (!canGenerate) {
            $('.flyer-download-controls').hide();
        }
    }

    function handleGenerateFlyer() {
        console.log('Generate flyer button clicked');
        
        if (isGenerating) {
            console.log('Already generating, ignoring click');
            return;
        }

        const listingId = $('#listing-select').val();
        if (!listingId) {
            showError('Please select a listing.');
            return;
        }

        // Validate open house data if needed
        if (currentFlyerType === 'open_house') {
            if (!openHouseData.date || !openHouseData.start_time || !openHouseData.end_time) {
                showError('Please fill in all open house details.');
                return;
            }
        }

        generateFlyer(listingId);
    }

    function generateFlyer(listingId) {
        console.log('Starting generateFlyer for listing:', listingId, 'Type:', currentFlyerType);
        
        const template = $('input[name="template"]:checked').val() || 'parker_group';
        
        showLoading(true);
        canvas.clear();
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'hph_generate_flyer', // Updated to use new AJAX system
            listing_id: listingId,
            flyer_type: currentFlyerType,
            template: template,
            nonce: flyerGenerator.nonce
        };
        
        // Add open house data if applicable
        if (currentFlyerType === 'open_house') {
            ajaxData.open_house_data = openHouseData;
        }
        
        console.log('Making AJAX request with data:', ajaxData);
        
        $.ajax({
            url: flyerGenerator.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            timeout: 30000,
            success: handleAjaxSuccess,
            error: handleAjaxError,
            complete: function() {
                console.log('AJAX request completed');
                showLoading(false);
            }
        });
    }

    function handleAjaxSuccess(response) {
        console.log('AJAX Response:', response);
        
        if (response.success && response.data) {
            const data = response.data;
            console.log('Received data:', data);
            
            try {
                if (currentFlyerType === 'open_house') {
                    createOpenHouseFlyer(data);
                } else {
                    createListingFlyer(data);
                }
                
                $('.flyer-download-controls').show();
                
                // Scroll to canvas
                $('html, body').animate({
                    scrollTop: $('.flyer-canvas-container').offset().top - 100
                }, 500);
                
            } catch (error) {
                console.error('Error creating flyer design:', error);
                showError('Error creating flyer design: ' + error.message);
            }
        } else {
            const errorMessage = response.data?.message || response.data || 'Unknown error occurred';
            console.error('AJAX Error:', response);
            showError('Error: ' + errorMessage);
        }
    }

    function createOpenHouseFlyer(data) {
        console.log('Creating open house flyer with data:', data);
        
        // Start with base flyer design
        createBaseFlyer(data);
        
        // Add open house specific elements
        addOpenHouseElements(data);
    }

    function createListingFlyer(data) {
        console.log('Creating listing flyer with data:', data);
        
        // Create standard listing flyer (your existing logic)
        createBaseFlyer(data);
    }

    function addOpenHouseElements(data) {
        console.log('Adding open house elements:', openHouseData);
        
        // Replace "FOR SALE" with "OPEN HOUSE"
        const openHouseTitle = new fabric.Text('OPEN HOUSE', {
            left: 55,
            top: 50,
            fontSize: 80,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            textBaseline: 'alphabetic',
            selectable: false
        });
        
        // Remove existing title if it exists
        const existingTitle = canvas.getObjects().find(obj => 
            obj.type === 'text' && obj.text === 'FOR SALE'
        );
        if (existingTitle) {
            canvas.remove(existingTitle);
        }
        
        canvas.add(openHouseTitle);
        
        // Add open house info box
        const infoBoxTop = 655;
        const infoBoxHeight = 120; // Increased height for open house info
        
        // Update the dark section to accommodate open house info
        const existingDarkSection = canvas.getObjects().find(obj => 
            obj.type === 'rect' && obj.fill === '#082f49'
        );
        if (existingDarkSection) {
            existingDarkSection.set({ height: infoBoxHeight });
        }
        
        // Add open house date and time - prominent display
        const dateText = formatOpenHouseDate(openHouseData.date);
        const timeText = formatTimeRange(openHouseData.start_time, openHouseData.end_time);
        
        const openHouseDateDisplay = new fabric.Text(dateText, {
            left: 60,
            top: infoBoxTop + 15,
            fontSize: 24,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '700',
            fill: '#ffffff',
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(openHouseDateDisplay);
        
        const openHouseTimeDisplay = new fabric.Text(timeText, {
            left: 60,
            top: infoBoxTop + 45,
            fontSize: 20,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#ffffff',
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(openHouseTimeDisplay);
        
        // Add "Hosted by" information if different agent
        if (openHouseData.hosting_agent_name) {
            const hostedByText = new fabric.Text(`Hosted by: ${openHouseData.hosting_agent_name}`, {
                left: 60,
                top: infoBoxTop + 75,
                fontSize: 14,
                fontFamily: 'Poppins, sans-serif',
                fontWeight: '500',
                fill: '#ffffff',
                textBaseline: 'alphabetic',
                selectable: false
            });
            canvas.add(hostedByText);
        }
        
        // Move price to the right side with better positioning
        const existingPrice = canvas.getObjects().find(obj => 
            obj.type === 'text' && obj.text && obj.text.includes('$')
        );
        if (existingPrice) {
            existingPrice.set({
                left: 750,
                top: infoBoxTop + 25,
                originX: 'right'
            });
        }
        
        // Add special open house styling elements
        addOpenHouseAccents();
        
        canvas.renderAll();
    }

    function addOpenHouseAccents() {
        // Add decorative elements for open house flyers
        
        // Add "OPEN HOUSE EVENT" banner
        const banner = new fabric.Rect({
            left: 0,
            top: 140,
            width: 250,
            height: 30,
            fill: '#ff6b35', // Orange accent for urgency
            angle: -5,
            selectable: false
        });
        canvas.add(banner);
        
        const bannerText = new fabric.Text('OPEN HOUSE EVENT', {
            left: 125,
            top: 155,
            fontSize: 14,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '700',
            fill: '#ffffff',
            textAlign: 'center',
            originX: 'center',
            originY: 'center',
            angle: -5,
            selectable: false
        });
        canvas.add(bannerText);
    }

    // Utility functions
    function getNextSaturday() {
        const today = new Date();
        const dayOfWeek = today.getDay();
        const daysUntilSaturday = (6 - dayOfWeek + 7) % 7 || 7;
        const nextSaturday = new Date(today);
        nextSaturday.setDate(today.getDate() + daysUntilSaturday);
        return nextSaturday;
    }

    function formatTimeRange(startTime, endTime) {
        if (!startTime || !endTime) return 'Time not set';
        
        const formatTime = (time) => {
            const [hours, minutes] = time.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        };
        
        return `${formatTime(startTime)} - ${formatTime(endTime)}`;
    }

    function formatOpenHouseDate(dateString) {
        if (!dateString) return 'Date not set';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            weekday: 'long',
            month: 'long', 
            day: 'numeric',
            year: 'numeric'
        });
    }

    // Keep all your existing utility functions (createBaseFlyer, downloadPNG, downloadPDF, etc.)
    // ... [Include all the existing functions from your original code] ...

    function showLoading(show) {
        if (show) {
            isGenerating = true;
            $('.flyer-loading').show();
            $('.flyer-download-controls').hide();
            hideError();
            $('#generate-flyer')
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        } else {
            isGenerating = false;
            $('.flyer-loading').hide();
            $('#generate-flyer')
                .prop('disabled', false)
                .html('<i class="fas fa-magic"></i> Generate Flyer');
        }
    }

    function showError(message) {
        $('.flyer-error .error-message').text(message);
        $('.flyer-error').show();
        console.error('Flyer Generator Error:', message);
    }

    function hideError() {
        $('.flyer-error').hide();
    }

    // ... [Include all other existing functions] ...

})(jQuery);
```

---

## 🔧 **4. Enhanced AJAX Handler**

Add this to your **Flyer_Ajax Handler** (`includes/api/ajax/handlers/class-flyer-ajax.php`):

```php
/**
 * Handle flyer generation with open house support
 */
public function handle_generate_flyer(): void {
    if (!$this->validate_required_params([
        'listing_id' => 'int',
        'flyer_type' => 'string'
    ])) {
        return;
    }
    
    $listing_id = intval($_POST['listing_id']);
    $flyer_type = sanitize_text_field($_POST['flyer_type']);
    $template = sanitize_text_field($_POST['template'] ?? 'parker_group');
    
    // Handle open house data
    $open_house_data = null;
    if ($flyer_type === 'open_house' && isset($_POST['open_house_data'])) {
        $open_house_data = $this->sanitize_open_house_data($_POST['open_house_data']);
        
        // Validate open house data
        if (!$this->validate_open_house_data($open_house_data)) {
            $this->send_error('Invalid open house data provided');
            return;
        }
    }
    
    if (!$this->validate_listing_exists($listing_id)) {
        $this->send_error('Invalid listing ID or listing not found');
        return;
    }
    
    try {
        $listing_data = $this->compile_listing_data($listing_id);
        
        // Add open house data if provided
        if ($open_house_data) {
            $listing_data['open_house'] = $open_house_data;
            
            // Get hosting agent data if different from listing agent
            if (!empty($open_house_data['hosting_agent_id'])) {
                $listing_data['hosting_agent'] = $this->get_agent_data($open_house_data['hosting_agent_id']);
            }
        }
        
        $listing_data['flyer_type'] = $flyer_type;
        
        $this->send_success($listing_data, 'generate_flyer');
        
    } catch (\Exception $e) {
        error_log('HPH Flyer Generation Error: ' . $e->getMessage());
        $this->send_error('Failed to generate flyer data');
    }
}

/**
 * Sanitize open house data
 */
private function sanitize_open_house_data($data): array {
    if (!is_array($data)) {
        return [];
    }
    
    return [
        'date' => sanitize_text_field($data['date'] ?? ''),
        'day' => sanitize_text_field($data['day'] ?? ''),
        'start_time' => sanitize_text_field($data['start_time'] ?? ''),
        'end_time' => sanitize_text_field($data['end_time'] ?? ''),
        'hosting_agent_id' => intval($data['hosting_agent_id'] ?? 0),
        'hosting_agent_name' => sanitize_text_field($data['hosting_agent_name'] ?? '')
    ];
}

/**
 * Validate open house data
 */
private function validate_open_house_data(array $data): bool {
    // Check required fields
    if (empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
        return false;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        return false;
    }
    
    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $data['start_time']) || 
        !preg_match('/^\d{2}:\d{2}$/', $data['end_time'])) {
        return false;
    }
    
    // Check that end time is after start time
    if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
        return false;
    }
    
    return true;
}

/**
 * Get agent data by ID
 */
private function get_agent_data(int $agent_id): array {
    $user = get_user_by('ID', $agent_id);
    
    if (!$user) {
        return [];
    }
    
    return [
        'id' => $user->ID,
        'name' => $user->display_name,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'phone' => get_user_meta($user->ID, 'phone', true),
        'profile_photo' => get_user_meta($user->ID, 'profile_photo', true)
    ];
}
```

---

## 📊 **5. Database Enhancements (Optional)**

If you want to track open house events, add this to your database setup:

```sql
CREATE TABLE IF NOT EXISTS wp_hph_open_houses (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id bigint(20) UNSIGNED NOT NULL,
    open_house_date date NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    hosting_agent_id bigint(20) UNSIGNED,
    status varchar(20) DEFAULT 'scheduled',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_listing_id (listing_id),
    INDEX idx_date (open_house_date),
    INDEX idx_hosting_agent (hosting_agent_id)
);
```

---

## ✅ **Summary of Enhancements**

### **New Features Added:**
1. **Flyer Type Selection** - Radio buttons for Listing vs Open House
2. **Open House Form** - Date, time, and hosting agent selection
3. **Dynamic UI** - Form sections show/hide based on flyer type
4. **Smart Defaults** - Next Saturday date, reasonable time defaults
5. **Real-time Preview** - Shows formatted open house details
6. **Enhanced Validation** - Proper validation for all open house fields
7. **AJAX Handler Support** - Backend processing for open house data
8. **Visual Styling** - Professional UI with proper responsive design

### **Open House Flyer Features:**
- **"OPEN HOUSE" title** instead of "FOR SALE"
- **Prominent date/time display** in the info section
- **Hosting agent information** if different from listing agent
- **Special visual accents** to highlight the event
- **All original listing information** preserved

This enhancement maintains full backward compatibility while adding comprehensive open house functionality!
