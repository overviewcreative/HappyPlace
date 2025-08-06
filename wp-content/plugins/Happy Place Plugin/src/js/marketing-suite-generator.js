/**
 * Happy Place Plugin - Marketing Suite Generator
 * Comprehensive multi-format marketing generator with flyer functionality
 * Updated to match PDF template design
 */

(function($) {
    'use strict';

    let canvases = {};
    let isGenerating = false;
    let currentCampaignType = 'listing';
    let selectedFormats = [];
    let generationResults = [];
    let currentListingData = null;
    let openHouseData = {};
    
    // Fabric.js fallback loading mechanism
    function loadFabricFallback() {
        console.log('Marketing Suite: Attempting to load Fabric.js fallback...');
        
        // Try multiple CDN sources
        const fabricSources = [
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
            'https://unpkg.com/fabric@5.3.0/dist/fabric.min.js',
            'https://cdn.jsdelivr.net/npm/fabric@5.3.0/dist/fabric.min.js'
        ];
        
        let attemptIndex = 0;
        
        function tryLoadFabric() {
            if (attemptIndex >= fabricSources.length) {
                console.error('Marketing Suite: All Fabric.js CDN sources failed');
                showError('Unable to load Fabric.js library. Please check your internet connection and refresh the page.');
                return;
            }
            
            const script = document.createElement('script');
            script.src = fabricSources[attemptIndex];
            script.onload = function() {
                console.log('Marketing Suite: Fabric.js loaded successfully from:', fabricSources[attemptIndex]);
                // Retry initialization after successful load
                setTimeout(initializeMarketingSuite, 100);
            };
            script.onerror = function() {
                console.warn('Marketing Suite: Failed to load from:', fabricSources[attemptIndex]);
                attemptIndex++;
                tryLoadFabric();
            };
            
            document.head.appendChild(script);
        }
        
        // Check if Fabric might load shortly (network delay)
        let retryCount = 0;
        const checkFabric = setInterval(() => {
            retryCount++;
            if (typeof fabric !== 'undefined') {
                clearInterval(checkFabric);
                console.log('Marketing Suite: Fabric.js became available, continuing initialization');
                initializeMarketingSuite();
                return;
            }
            
            if (retryCount >= 30) { // 3 seconds
                clearInterval(checkFabric);
                tryLoadFabric();
            }
        }, 100);
    }

    // Format configurations with canvas specifications
    const formatConfigs = {
        full_flyer: { 
            width: 850, 
            height: 1100, 
            name: 'Full Flyer',
            description: '8.5" x 11" print flyer',
            category: 'print'
        },
        instagram_post: { 
            width: 1080, 
            height: 1080, 
            name: 'Instagram Post',
            description: 'Square format for Instagram',
            category: 'social'
        },
        instagram_story: { 
            width: 1080, 
            height: 1920, 
            name: 'Instagram Story',
            description: 'Vertical story format',
            category: 'social'
        },
        facebook_post: { 
            width: 1200, 
            height: 630, 
            name: 'Facebook Post',
            description: 'Optimized for Facebook sharing',
            category: 'social'
        },
        twitter_post: { 
            width: 1200, 
            height: 675, 
            name: 'Twitter Post',
            description: 'Twitter card format',
            category: 'social'
        },
        web_banner: { 
            width: 1200, 
            height: 400, 
            name: 'Web Banner',
            description: 'Website hero banner',
            category: 'web'
        },
        featured_listing: { 
            width: 600, 
            height: 400, 
            name: 'Featured Listing',
            description: 'Website featured property',
            category: 'web'
        },
        email_header: { 
            width: 800, 
            height: 300, 
            name: 'Email Header',
            description: 'Email newsletter header',
            category: 'email'
        },
        postcard: { 
            width: 600, 
            height: 400, 
            name: 'Postcard',
            description: '6" x 4" postcard format',
            category: 'print'
        },
        business_card: { 
            width: 350, 
            height: 200, 
            name: 'Business Card',
            description: 'Quick reference card',
            category: 'print'
        }
    };

    $(document).ready(function() {
        console.log('Marketing Suite Generator: Document ready');
        initializeMarketingSuite();
    });

    function initializeMarketingSuite() {
        console.log('Marketing Suite Generator: Initializing...');
        
        // Check for Fabric.js with retry mechanism
        if (typeof fabric === 'undefined') {
            console.warn('Marketing Suite: Fabric.js not immediately available, attempting to load...');
            loadFabricFallback();
            return;
        }

        // Check for configuration objects - multiple possible sources
        let config = null;
        if (typeof flyerGenerator !== 'undefined') {
            config = flyerGenerator;
            console.log('Marketing Suite: Using flyerGenerator config');
        } else if (typeof marketingSuite !== 'undefined') {
            config = marketingSuite;
            console.log('Marketing Suite: Using marketingSuite config');
        } else if (typeof marketingSuiteAjax !== 'undefined') {
            config = marketingSuiteAjax;
            console.log('Marketing Suite: Using marketingSuiteAjax config');
        }

        if (!config) {
            console.error('Marketing Suite: No configuration object found');
            // Create fallback configuration
            config = {
                ajaxUrl: window.ajaxurl || '/wp-admin/admin-ajax.php',
                nonce: '',
                dashboardNonce: ''
            };
            console.warn('Marketing Suite: Using fallback configuration');
        }

        // Make config globally available as flyerGenerator for compatibility
        window.flyerGenerator = config;

        // Also make formatConfigs globally available
        window.formatConfigs = formatConfigs;

        // Initialize all canvases
        initializeCanvases();
        
        // Bind event handlers
        bindEventHandlers();
        
        // Initialize form state
        initializeFormState();
        
        console.log('Marketing Suite Generator: Initialization complete');
    }

    function initializeCanvases() {
        console.log('Marketing Suite: Initializing canvases...');
        
        if (typeof fabric === 'undefined') {
            console.error('Marketing Suite: Fabric.js is not available for canvas initialization');
            showError('Fabric.js library is not loaded. Please refresh the page.');
            return;
        }

        Object.keys(formatConfigs).forEach(formatKey => {
            const config = formatConfigs[formatKey];
            console.log(`Marketing Suite: Setting up canvas for ${formatKey}:`, config);
            
            // Create canvas element if it doesn't exist
            let canvasElement = document.getElementById(`canvas-${formatKey}`);
            if (!canvasElement) {
                console.log(`Marketing Suite: Creating canvas element for ${formatKey}`);
                canvasElement = document.createElement('canvas');
                canvasElement.id = `canvas-${formatKey}`;
                canvasElement.width = config.width;
                canvasElement.height = config.height;
                canvasElement.style.display = 'none';
                document.body.appendChild(canvasElement);
            }

            try {
                canvases[formatKey] = new fabric.Canvas(`canvas-${formatKey}`, {
                    backgroundColor: '#ffffff',
                    selection: false,
                    width: config.width,
                    height: config.height
                });
                console.log(`Marketing Suite: Canvas initialized successfully for ${formatKey}:`, config.width, 'x', config.height);
            } catch (error) {
                console.error(`Marketing Suite: Error initializing canvas for ${formatKey}:`, error);
                showError(`Failed to initialize canvas for ${formatKey}: ${error.message}`);
            }
        });
        
        console.log('Marketing Suite: Canvas initialization complete. Total canvases:', Object.keys(canvases).length);
    }

    function bindEventHandlers() {
        // Campaign type selection
        $('input[name="campaign_type"]').on('change', handleCampaignTypeChange);
        
        // Format selection
        $('input[name="formats[]"]').on('change', updateSelectedFormats);
        
        // Quick select functions (defined globally)
        window.selectFormats = function(type) {
            const checkboxes = $('input[name="formats[]"]');
            checkboxes.prop('checked', false);
            
            if (type === 'social') {
                checkboxes.filter('[value="instagram_post"], [value="instagram_story"], [value="facebook_post"], [value="twitter_post"]').prop('checked', true);
            } else if (type === 'print') {
                checkboxes.filter('[value="full_flyer"], [value="postcard"], [value="business_card"]').prop('checked', true);
            } else if (type === 'web') {
                checkboxes.filter('[value="web_banner"], [value="featured_listing"], [value="email_header"]').prop('checked', true);
            } else if (type === 'all') {
                checkboxes.prop('checked', true);
            }
            
            updateSelectedFormats();
        };
        
        // Main generation button
        $('#generate-marketing-suite').on('click', handleGenerateMarketingSuite);
        
        // Download buttons  
        $('#download-all-zip').on('click', downloadAllAsZip);
        $('#download-individual').on('click', showIndividualDownloads);
        
        // Listing selection
        $('#listing-select').on('change', handleListingChange);
        
        // Open house fields
        $('#open-house-date').on('change', handleDateChange);
        $('#open-house-start, #open-house-end').on('change', updateOpenHousePreview);
        $('#hosting-agent').on('change', updateOpenHousePreview);
        
        // Price change calculations
        $('#old-price, #new-price').on('input', calculatePriceReduction);
        
        // Campaign option visual feedback
        $('.campaign-option').on('click', function() {
            $('.campaign-option').removeClass('active');
            $(this).addClass('active');
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        });

        // Format option visual feedback
        $('.format-option').on('click', function() {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        // Template selection
        $('input[name="template"]').on('change', function() {
            $('.template-option').removeClass('active');
            $(this).closest('.template-option').addClass('active');
        });

        // Error dismissal
        $('.dismiss-error').on('click', hideError);
    }

    function initializeFormState() {
        updateSelectedFormats();
        handleListingChange();
        
        // Set default date to next Saturday
        const today = new Date();
        const nextSaturday = new Date();
        nextSaturday.setDate(today.getDate() + (6 - today.getDay()));
        $('#open-house-date').val(nextSaturday.toISOString().split('T')[0]);
        handleDateChange();
    }

    function handleCampaignTypeChange() {
        const selectedType = $('input[name="campaign_type"]:checked').val();
        currentCampaignType = selectedType;
        
        console.log('Campaign type changed to:', selectedType);
        
        // Show/hide conditional sections
        $('.open-house-section').toggle(selectedType === 'open_house');
        $('.price-change-section').toggle(selectedType === 'price_change');
        
        clearResults();
        updateGenerateButtonState();
    }

    function handleDateChange() {
        const dateValue = $('#open-house-date').val();
        if (dateValue) {
            const date = new Date(dateValue);
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $('#open-house-day').val(dayNames[date.getDay()]);
            updateOpenHousePreview();
        }
    }

    function updateOpenHousePreview() {
        const date = $('#open-house-date').val();
        const startTime = $('#open-house-start').val();
        const endTime = $('#open-house-end').val();
        const agentId = $('#hosting-agent').val();
        const agentName = agentId ? $('#hosting-agent option:selected').text() : 'Listing Agent';
        
        openHouseData = {
            date: date,
            startTime: startTime,
            endTime: endTime,
            agentId: agentId,
            agentName: agentName
        };
        
        // Update preview display
        $('.oh-date span').text(date ? new Date(date).toLocaleDateString() : 'Not set');
        $('.oh-time span').text(startTime && endTime ? `${formatTime(startTime)} - ${formatTime(endTime)}` : 'Not set');
        $('.oh-agent span').text(agentName || 'Not set');
        
        updateGenerateButtonState();
    }

    function formatTime(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }

    function updateSelectedFormats() {
        selectedFormats = [];
        $('input[name="formats[]"]:checked').each(function() {
            selectedFormats.push($(this).val());
        });
        
        console.log('Selected formats:', selectedFormats);
        updateGenerateButtonState();
        
        // Update format count display
        const count = selectedFormats.length;
        $('.format-count').text(count > 0 ? `${count} format${count !== 1 ? 's' : ''} selected` : 'No formats selected');
    }

    function updateGenerateButtonState() {
        const listingSelected = $('#listing-select').val();
        const formatsSelected = selectedFormats.length > 0;
        
        let canGenerate = listingSelected && formatsSelected;
        
        // Additional validation for campaign types
        if (currentCampaignType === 'open_house') {
            canGenerate = canGenerate && $('#open-house-date').val() && $('#open-house-start').val() && $('#open-house-end').val();
        } else if (currentCampaignType === 'price_change') {
            canGenerate = canGenerate && $('#old-price').val() && $('#new-price').val();
        }
        
        $('#generate-marketing-suite').prop('disabled', !canGenerate);
    }

    function handleListingChange() {
        const listingId = $('#listing-select').val();
        console.log('Listing changed to:', listingId);
        
        clearResults();
        updateGenerateButtonState();
        
        // Could fetch listing data here for preview
        if (listingId) {
            fetchListingPreview(listingId);
        }
    }

    function fetchListingPreview(listingId) {
        // Optional: Fetch listing data for preview/validation
        // This could populate fields or show a preview
    }

    function calculatePriceReduction() {
        const oldPrice = parseFloat($('#old-price').val()) || 0;
        const newPrice = parseFloat($('#new-price').val()) || 0;
        
        if (oldPrice > 0 && newPrice > 0) {
            const reduction = oldPrice - newPrice;
            const percentage = ((reduction / oldPrice) * 100).toFixed(1);
            
            $('.price-reduction').text(`$${reduction.toLocaleString()} (${percentage}%)`);
            $('.price-savings').text(`Save $${reduction.toLocaleString()}!`);
        }
        
        updateGenerateButtonState();
    }

    function handleGenerateMarketingSuite() {
        console.log('Generate marketing suite button clicked');
        
        if (isGenerating) {
            console.log('Already generating, ignoring click');
            return;
        }

        const listingId = $('#listing-select').val();
        if (!listingId) {
            showError('Please select a listing.');
            return;
        }

        if (selectedFormats.length === 0) {
            showError('Please select at least one format.');
            return;
        }

        console.log('Starting marketing suite generation for listing:', listingId);
        console.log('Selected formats:', selectedFormats);
        console.log('Campaign type:', currentCampaignType);

        generateMarketingSuite(listingId);
    }

    function generateMarketingSuite(listingId) {
        isGenerating = true;
        showProgress(true);
        updateProgress(0, 'Preparing generation...');
        hideError();

        const startTime = Date.now();

        // Prepare AJAX data
        const ajaxData = {
            action: 'hph_generate_marketing_suite',
            nonce: flyerGenerator.nonce,
            listing_id: listingId,
            campaign_type: currentCampaignType,
            formats: selectedFormats,
            open_house_data: currentCampaignType === 'open_house' ? openHouseData : null,
            price_change_data: currentCampaignType === 'price_change' ? {
                old_price: $('#old-price').val(),
                new_price: $('#new-price').val()
            } : null,
            template: $('input[name="template"]:checked').val() || 'parker_group'
        };

        console.log('AJAX data:', ajaxData);

        updateProgress(10, 'Fetching listing data...');

        $.ajax({
            url: flyerGenerator.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            timeout: 60000, // 60 second timeout for multiple formats
            success: function(response) {
                console.log('Marketing suite generation response:', response);
                handleGenerationSuccess(response, startTime);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Request Failed:', {xhr, status, error});
                handleGenerationError(xhr, status, error);
            },
            complete: function() {
                isGenerating = false;
            }
        });
    }

    function handleGenerationSuccess(response, startTime) {
        console.log('Marketing suite generation response:', response);
        
        if (response.success && response.data) {
            const data = response.data;
            console.log('Received listing data:', data);
            
            currentListingData = data;
            updateProgress(30, 'Creating marketing graphics...');
            
            // Generate all selected formats
            generateAllFormats(data, startTime);
            
        } else {
            const errorMessage = response.data?.message || response.data || 'Unknown error occurred';
            console.error('Generation Error:', response);
            showError('Error: ' + errorMessage);
            hideProgress();
        }
    }

    function generateAllFormats(listingData, startTime) {
        generationResults = [];
        let completed = 0;
        const total = selectedFormats.length;

        selectedFormats.forEach((formatKey, index) => {
            setTimeout(() => {
                updateProgress(30 + (index * 60 / total), `Creating ${formatConfigs[formatKey].name}...`);
                updateFormatProgress(formatKey, 'processing');
                
                try {
                    createFormatGraphic(formatKey, listingData);
                    
                    // Simulate generation time
                    setTimeout(() => {
                        completed++;
                        updateFormatProgress(formatKey, 'completed');
                        
                        // Store result
                        const canvas = canvases[formatKey];
                        const dataURL = canvas.toDataURL('image/png');
                        const fileSize = Math.round((dataURL.length * 3) / 4); // Approximate size
                        
                        generationResults.push({
                            format: formatKey,
                            name: formatConfigs[formatKey].name,
                            canvas: canvas,
                            dataURL: dataURL,
                            fileSize: fileSize
                        });
                        
                        console.log(`Generated ${formatKey}:`, formatConfigs[formatKey].name);
                        
                        if (completed === total) {
                            const totalTime = Date.now() - startTime;
                            const totalSize = generationResults.reduce((sum, result) => sum + result.fileSize, 0);
                            
                            updateProgress(100, 'Marketing suite complete!');
                            setTimeout(() => {
                                showResults(generationResults, totalSize, totalTime);
                                hideProgress();
                            }, 1000);
                        }
                    }, 500 + (index * 200)); // Stagger completion
                    
                } catch (error) {
                    console.error(`Error creating ${formatKey}:`, error);
                    updateFormatProgress(formatKey, 'error');
                    completed++;
                    
                    if (completed === total) {
                        hideProgress();
                        showError(`Some formats failed to generate. Check console for details.`);
                    }
                }
            }, index * 300); // Stagger start times
        });
    }

    function createFormatGraphic(formatKey, listingData) {
        const canvas = canvases[formatKey];
        const config = formatConfigs[formatKey];
        
        console.log(`Creating ${formatKey} graphic (${config.width}x${config.height})`);
        
        // Clear canvas
        canvas.clear();
        canvas.backgroundColor = '#ffffff';
        
        // Route to appropriate creation function
        switch (formatKey) {
            case 'full_flyer':
                createFullFlyer(canvas, listingData, config);
                break;
            case 'instagram_post':
                createInstagramPost(canvas, listingData, config);
                break;
            case 'instagram_story':
                createInstagramStory(canvas, listingData, config);
                break;
            case 'facebook_post':
            case 'twitter_post':
                createSocialMediaPost(canvas, listingData, config, formatKey);
                break;
            case 'web_banner':
                createWebBanner(canvas, listingData, config);
                break;
            case 'featured_listing':
                createFeaturedListing(canvas, listingData, config);
                break;
            case 'email_header':
                createEmailHeader(canvas, listingData, config);
                break;
            case 'postcard':
                createPostcard(canvas, listingData, config);
                break;
            case 'business_card':
                createBusinessCard(canvas, listingData, config);
                break;
            default:
                console.warn(`No creation function for format: ${formatKey}`);
                createGenericFormat(canvas, listingData, config);
        }
        
        canvas.renderAll();
    }

    function createFullFlyer(canvas, data, config) {
        // This matches the PDF design exactly
        console.log('Creating full flyer matching PDF template');
        
        // Map data for compatibility
        const mappedData = mapListingData(data);
        
        // White background
        const background = new fabric.Rect({
            left: 0,
            top: 0,
            width: config.width,
            height: config.height,
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(background);
        
        // Blue header section
        const headerBg = new fabric.Rect({
            left: 0,
            top: 0,
            width: config.width,
            height: 730,
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(headerBg);
        
        // Title text - "FOR SALE" at the top
        const titleText = new fabric.Text(getCampaignTitle(), {
            left: config.width / 2,
            top: 50,
            fontSize: 80,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            originX: 'center',
            selectable: false
        });
        canvas.add(titleText);
        
        // Main photo container
        const photoTop = 150;
        const photoHeight = 340;
        const photoMargin = 30;
        const photoWidth = config.width - (photoMargin * 2);
        
        if (mappedData.gallery && mappedData.gallery.length > 0) {
            addMainPhoto(canvas, mappedData.gallery[0], photoMargin, photoTop, photoWidth, photoHeight);
        }
        
        // Three smaller photos below main photo
        const smallPhotoTop = photoTop + photoHeight + 15;
        const smallPhotoHeight = 110;
        const smallPhotoWidth = (photoWidth - 30) / 3; // 3 photos with gaps
        
        if (mappedData.gallery && mappedData.gallery.length > 1) {
            for (let i = 0; i < Math.min(3, mappedData.gallery.length - 1); i++) {
                const leftPos = photoMargin + (i * (smallPhotoWidth + 15));
                addMainPhoto(canvas, mappedData.gallery[i + 1], leftPos, smallPhotoTop, smallPhotoWidth, smallPhotoHeight);
            }
        }
        
        // Property stats bar
        const statsTop = smallPhotoTop + smallPhotoHeight + 15;
        const statsHeight = 60;
        
        // Stats background (darker blue)
        const statsBg = new fabric.Rect({
            left: photoMargin,
            top: statsTop,
            width: photoWidth,
            height: statsHeight,
            fill: 'rgba(0,0,0,0.2)',
            selectable: false
        });
        canvas.add(statsBg);
        
        // Property stats icons and text
        const beds = mappedData.bedrooms || '4';
        const baths = mappedData.bathrooms || '3';
        const sqft = mappedData.square_feet || '1,800';
        
        const statsText = new fabric.Text(`🛏 ${beds} Bed    🚿 ${baths} Bath    📐 ${sqft.toLocaleString()} Ft²`, {
            left: config.width / 2,
            top: statsTop + (statsHeight / 2),
            fontSize: 24,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#ffffff',
            originX: 'center',
            originY: 'center',
            selectable: false
        });
        canvas.add(statsText);
        
        // Price on the right side
        const price = formatPrice(mappedData.price || '805000');
        const priceText = new fabric.Text(price, {
            left: config.width - photoMargin - 20,
            top: statsTop + (statsHeight / 2),
            fontSize: 48,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            originX: 'right',
            originY: 'center',
            selectable: false
        });
        canvas.add(priceText);
        
        // Address section
        const addressTop = 760;
        const streetAddress = mappedData.street_address || '120 McFee St.';
        const cityStateZip = `${mappedData.city || 'Lewes'}, ${mappedData.state || 'DE'} ${mappedData.zip_code || '19958'}`;
        
        const addressText = new fabric.Text(streetAddress, {
            left: 60,
            top: addressTop,
            fontSize: 36,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(addressText);
        
        const cityText = new fabric.Text(cityStateZip, {
            left: 60,
            top: addressTop + 40,
            fontSize: 22,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(cityText);
        
        // Description/Lorem ipsum text
        const descriptionText = mappedData.description || 
            'lkahsdlakjsd askdjlaskdj sldkjals kdlskjd alksdj sdal skdjlskdj\n' +
            'sldkjalskd lskdj sdlaksjda sdlksjdl ksdalskjdla skdjalskdj\n' +
            'asdjijalskdj';
        
        const descText = new fabric.Textbox(descriptionText, {
            left: 60,
            top: addressTop + 80,
            width: config.width - 120,
            fontSize: 14,
            fontFamily: 'Arial, sans-serif',
            fontWeight: '400',
            fill: '#666666',
            lineHeight: 1.4,
            selectable: false
        });
        canvas.add(descText);
        
        // Agent section at bottom
        const agentTop = 960;
        
        // Agent photo placeholder (circle)
        const agentPhotoSize = 80;
        const agentPhoto = new fabric.Circle({
            left: 60,
            top: agentTop,
            radius: agentPhotoSize / 2,
            fill: '#e0e0e0',
            selectable: false
        });
        canvas.add(agentPhoto);
        
        // Agent info
        const agentName = data.agent?.display_name || 'Pat Gallagher';
        const agentTitle = 'REALTOR®';
        
        const agentNameText = new fabric.Text(agentName, {
            left: 160,
            top: agentTop + 5,
            fontSize: 24,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(agentNameText);
        
        const agentTitleText = new fabric.Text(agentTitle, {
            left: 160,
            top: agentTop + 35,
            fontSize: 14,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#666666',
            selectable: false
        });
        canvas.add(agentTitleText);
        
        // Contact info
        const email = data.agent?.email || 'patrick@theparkergroup.com';
        const phone = data.agent?.phone || '3026821702';
        
        const contactText = new fabric.Text(`📧 ${email}\n📱 ${formatPhoneNumber(phone)}`, {
            left: 160,
            top: agentTop + 55,
            fontSize: 12,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#666666',
            lineHeight: 1.4,
            selectable: false
        });
        canvas.add(contactText);
        
        // Parker Group branding on the right
        addParkerGroupLogo(canvas, config.width - 250, addressTop, 180, 60);
        
        // QR Code section
        addQRCodeSection(canvas, config.width - 160, agentTop, 120);
        
        // Additional address line at bottom if needed
        const bottomAddress = '673 N Bedford St, Georgetown, DE';
        const bottomAddressText = new fabric.Text(bottomAddress, {
            left: config.width - 250,
            top: agentTop + 100,
            fontSize: 11,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#666666',
            selectable: false
        });
        canvas.add(bottomAddressText);
    }

    function addParkerGroupLogo(canvas, left, top, width, height) {
        // Parker Group logo/branding
        const logoGroup = new fabric.Group([
            // House icon placeholder
            new fabric.Rect({
                left: 0,
                top: 0,
                width: 40,
                height: 35,
                fill: '#51bae0'
            }),
            new fabric.Triangle({
                left: 0,
                top: -15,
                width: 40,
                height: 20,
                fill: '#51bae0'
            })
        ], {
            left: left,
            top: top,
            selectable: false
        });
        canvas.add(logoGroup);
        
        const logoText = new fabric.Text('the parker group', {
            left: left + 50,
            top: top,
            fontSize: 20,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#333333',
            selectable: false
        });
        canvas.add(logoText);
        
        const taglineText = new fabric.Text('find your happy place', {
            left: left + 50,
            top: top + 25,
            fontSize: 14,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fontStyle: 'italic',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(taglineText);
    }

    function addQRCodeSection(canvas, left, top, size) {
        // QR code placeholder
        const qrBg = new fabric.Rect({
            left: left,
            top: top,
            width: size,
            height: size,
            fill: '#f0f0f0',
            stroke: '#333333',
            strokeWidth: 2,
            selectable: false
        });
        canvas.add(qrBg);
        
        // QR pattern placeholder (simplified)
        for (let i = 0; i < 5; i++) {
            for (let j = 0; j < 5; j++) {
                if (Math.random() > 0.5) {
                    const square = new fabric.Rect({
                        left: left + 10 + (i * 20),
                        top: top + 10 + (j * 20),
                        width: 15,
                        height: 15,
                        fill: '#333333',
                        selectable: false
                    });
                    canvas.add(square);
                }
            }
        }
        
        // "Get in touch" label above QR
        const labelText = new fabric.Text('Get in touch', {
            left: left + (size / 2),
            top: top - 20,
            fontSize: 12,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#666666',
            originX: 'center',
            selectable: false
        });
        canvas.add(labelText);
        
        // Contact info icons below
        const iconText = new fabric.Text('📧  📱  📍', {
            left: left + (size / 2),
            top: top + size + 10,
            fontSize: 16,
            originX: 'center',
            selectable: false
        });
        canvas.add(iconText);
    }

    function formatPhoneNumber(phone) {
        // Format phone number as XXX.XXX.XXXX
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 10) {
            return `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6)}`;
        }
        return phone;
    }

    function createInstagramPost(canvas, listingData, config) {
        const size = config.width; // 1080x1080
        
        // Blue background
        const background = new fabric.Rect({
            left: 0,
            top: 0,
            width: size,
            height: size,
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(background);
        
        // Main photo (large, centered)
        if (listingData.gallery && listingData.gallery.length > 0) {
            addMainPhoto(canvas, listingData.gallery[0], 60, 60, size - 120, (size - 120) * 0.6);
        }
        
        // Price overlay
        const price = formatPrice(listingData.price || listingData.listing?.price);
        const priceText = new fabric.Text(price, {
            left: 80,
            top: size - 300,
            fontSize: 72,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            selectable: false,
            shadow: new fabric.Shadow({
                color: 'rgba(0,0,0,0.5)',
                blur: 10,
                offsetX: 2,
                offsetY: 2
            })
        });
        canvas.add(priceText);
        
        // Address
        const address = getShortAddress(listingData);
        const addressText = new fabric.Text(address, {
            left: 80,
            top: size - 220,
            fontSize: 36,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#ffffff',
            selectable: false,
            shadow: new fabric.Shadow({
                color: 'rgba(0,0,0,0.5)',
                blur: 8,
                offsetX: 2,
                offsetY: 2
            })
        });
        canvas.add(addressText);
        
        // Logo/branding
        addBrandingLogo(canvas, size - 200, 60, 140, 60);
        
        // Campaign specific elements
        if (currentCampaignType === 'open_house') {
            addOpenHouseDetails(canvas, size / 2, size - 150, 36);
        }
    }

    function createInstagramStory(canvas, listingData, config) {
        const width = config.width; // 1080
        const height = config.height; // 1920
        
        // Background gradient
        const background = new fabric.Rect({
            left: 0,
            top: 0,
            width: width,
            height: height,
            fill: new fabric.Gradient({
                type: 'linear',
                coords: { x1: 0, y1: 0, x2: 0, y2: height },
                colorStops: [
                    { offset: 0, color: '#51bae0' },
                    { offset: 1, color: '#3a9bc1' }
                ]
            }),
            selectable: false
        });
        canvas.add(background);
        
        // Main photo (full width, centered vertically)
        if (listingData.gallery && listingData.gallery.length > 0) {
            addMainPhoto(canvas, listingData.gallery[0], 40, height * 0.25, width - 80, height * 0.4);
        }
        
        // Vertical text layout
        const centerX = width / 2;
        
        // Campaign title
        const title = getCampaignTitle();
        const titleText = new fabric.Text(title, {
            left: centerX,
            top: 100,
            fontSize: 48,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            originX: 'center',
            selectable: false
        });
        canvas.add(titleText);
        
        // Price
        const price = formatPrice(listingData.price || listingData.listing?.price);
        const priceText = new fabric.Text(price, {
            left: centerX,
            top: height * 0.7,
            fontSize: 64,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            originX: 'center',
            selectable: false
        });
        canvas.add(priceText);
        
        // Address
        const address = getShortAddress(listingData);
        const addressText = new fabric.Text(address, {
            left: centerX,
            top: height * 0.78,
            fontSize: 32,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#ffffff',
            originX: 'center',
            selectable: false
        });
        canvas.add(addressText);
        
        // Open house details for story format
        if (currentCampaignType === 'open_house') {
            addOpenHouseDetails(canvas, centerX, height * 0.85, 28);
        }
    }

    function createSocialMediaPost(canvas, listingData, config, formatType) {
        const width = config.width;
        const height = config.height;
        
        // Background
        const background = new fabric.Rect({
            left: 0,
            top: 0,
            width: width,
            height: height,
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(background);
        
        // Layout: Image left, text right for landscape format
        const imageWidth = width * 0.6;
        const textWidth = width * 0.35;
        const textLeft = width * 0.65;
        
        // Main photo
        if (listingData.gallery && listingData.gallery.length > 0) {
            addMainPhoto(canvas, listingData.gallery[0], 0, 0, imageWidth, height);
        }
        
        // Text overlay on right side
        const centerY = height / 2;
        
        // Campaign title
        const title = getCampaignTitle();
        const titleText = new fabric.Text(title, {
            left: textLeft,
            top: centerY - 120,
            fontSize: 32,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(titleText);
        
        // Price
        const price = formatPrice(listingData.price || listingData.listing?.price);
        const priceText = new fabric.Text(price, {
            left: textLeft,
            top: centerY - 60,
            fontSize: 42,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(priceText);
        
        // Address
        const address = getShortAddress(listingData);
        const addressText = new fabric.Textbox(address, {
            left: textLeft,
            top: centerY + 10,
            width: textWidth - 40,
            fontSize: 18,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(addressText);
        
        // Property stats
        addPropertyStats(canvas, listingData, textLeft, centerY + 80, 16);
    }

    // ... Rest of the helper functions remain the same ...

    // Helper functions for graphic creation
    function mapListingData(data) {
        // Map various data formats to consistent structure
        const mapped = {
            price: data.price || data.listing?.price || 805000,
            address: data.address || data.listing?.address || '120 McFee St.',
            street_address: data.street_address || data.address || data.listing?.address || '120 McFee St.',
            city: data.city || data.listing?.city || 'Lewes',
            state: data.state || data.listing?.state || 'DE',
            zip_code: data.zip_code || data.zip || data.listing?.zip || '19958',
            bedrooms: data.bedrooms || data.beds || data.listing?.bedrooms || 4,
            bathrooms: data.bathrooms || data.baths || data.listing?.bathrooms || 3,
            square_feet: data.square_feet || data.sqft || data.listing?.square_feet || 1800,
            lot_size: data.lot_size || data.listing?.lot_size || '',
            description: data.description || data.listing?.description || '',
            gallery: []
        };
        
        // Handle gallery/photos
        if (data.gallery && Array.isArray(data.gallery)) {
            mapped.gallery = data.gallery.map(img => {
                if (typeof img === 'object' && img.url) return img.url;
                if (typeof img === 'string') return img;
                return null;
            }).filter(url => url !== null);
        } else if (data.listing?.gallery) {
            mapped.gallery = Array.isArray(data.listing.gallery) ? data.listing.gallery : [data.listing.gallery];
        } else if (data.main_photo) {
            mapped.gallery = [data.main_photo];
        }
        
        return mapped;
    }

    function getCampaignTitle() {
        switch (currentCampaignType) {
            case 'listing':
                return 'FOR SALE';
            case 'open_house':
                return 'OPEN HOUSE';
            case 'price_change':
                return 'PRICE REDUCED';
            case 'under_contract':
                return 'UNDER CONTRACT';
            case 'sold':
                return 'SOLD';
            default:
                return 'FOR SALE';
        }
    }

    function formatPrice(price) {
        if (!price || price === 0) return '$805,000';
        
        const numPrice = typeof price === 'string' ? parseFloat(price.replace(/[^0-9.]/g, '')) : price;
        
        return '$' + numPrice.toLocaleString();
    }

    function getShortAddress(listingData) {
        const mapped = mapListingData(listingData);
        
        if (mapped.street_address && mapped.city) {
            return `${mapped.street_address}, ${mapped.city}`;
        } else if (mapped.street_address) {
            return mapped.street_address;
        } else if (mapped.address) {
            return mapped.address;
        } else {
            return '120 McFee St., Lewes';
        }
    }

    function addMainPhoto(canvas, photoUrl, left, top, width, height) {
        if (!photoUrl) {
            // Add placeholder if no photo URL
            const placeholder = new fabric.Rect({
                left: left,
                top: top,
                width: width,
                height: height,
                fill: '#e0e0e0',
                selectable: false
            });
            canvas.add(placeholder);
            return;
        }
        
        fabric.Image.fromURL(photoUrl, function(img) {
            // Calculate scale to cover (fill) the container
            const scaleX = width / img.width;
            const scaleY = height / img.height;
            const scale = Math.max(scaleX, scaleY);
            
            // Calculate position to center the scaled image
            const scaledWidth = img.width * scale;
            const scaledHeight = img.height * scale;
            const imageLeft = left + (width - scaledWidth) / 2;
            const imageTop = top + (height - scaledHeight) / 2;
            
            img.set({
                left: imageLeft,
                top: imageTop,
                scaleX: scale,
                scaleY: scale,
                selectable: false,
                crossOrigin: 'anonymous'
            });
            
            // Create a mask to clip the image to exact container bounds
            const mask = new fabric.Rect({
                left: left,
                top: top,
                width: width,
                height: height,
                absolutePositioned: true
            });
            
            img.clipPath = mask;
            canvas.add(img);
            canvas.renderAll();
        }, { crossOrigin: 'anonymous' });
    }

    function addPropertyStats(canvas, listingData, left, top, fontSize) {
        const mapped = mapListingData(listingData);
        const beds = mapped.bedrooms || '4';
        const baths = mapped.bathrooms || '3';
        const sqft = mapped.square_feet || '1,800';
        
        let statText = '';
        if (beds !== 'N/A') statText += `${beds} Bed`;
        if (baths !== 'N/A') statText += (statText ? ' • ' : '') + `${baths} Bath`;
        if (sqft !== 'N/A') statText += (statText ? ' • ' : '') + `${sqft.toLocaleString()} Ft²`;
        
        if (statText) {
            const statsText = new fabric.Text(statText, {
                left: left,
                top: top,
                fontSize: fontSize,
                fontFamily: 'Poppins, sans-serif',
                fontWeight: '500',
                fill: '#ffffff',
                selectable: false
            });
            canvas.add(statsText);
        }
    }

    function addBrandingLogo(canvas, left, top, width, height) {
        // Add logo/branding element
        const logoBg = new fabric.Rect({
            left: left,
            top: top,
            width: width,
            height: height,
            fill: 'rgba(255,255,255,0.9)',
            rx: 8,
            ry: 8,
            selectable: false
        });
        canvas.add(logoBg);
        
        const logoText = new fabric.Text('PARKER GROUP', {
            left: left + width/2,
            top: top + height/2,
            fontSize: 14,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#51bae0',
            originX: 'center',
            originY: 'center',
            selectable: false
        });
        canvas.add(logoText);
    }

    function addOpenHouseDetails(canvas, centerX, top, fontSize) {
        if (currentCampaignType !== 'open_house' || !openHouseData.date) return;
        
        const date = new Date(openHouseData.date);
        const dateStr = date.toLocaleDateString('en-US', { 
            weekday: 'long', 
            month: 'long', 
            day: 'numeric' 
        });
        
        const timeStr = `${formatTime(openHouseData.startTime)} - ${formatTime(openHouseData.endTime)}`;
        
        const openHouseText = new fabric.Text(`${dateStr}\n${timeStr}`, {
            left: centerX,
            top: top,
            fontSize: fontSize,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#ffffff',
            originX: 'center',
            textAlign: 'center',
            selectable: false,
            shadow: new fabric.Shadow({
                color: 'rgba(0,0,0,0.5)',
                blur: 5,
                offsetX: 1,
                offsetY: 1
            })
        });
        canvas.add(openHouseText);
    }

    // ... Rest of the functions remain the same ...

    function handleGenerationError(xhr, status, error) {
        console.error('AJAX Request Failed:', {xhr, status, error});
        hideProgress();
        
        let errorMessage = 'Error generating marketing suite. Please try again.';
        
        if (xhr.status === 403) {
            errorMessage = 'Permission denied. Please refresh and try again.';
        } else if (xhr.status === 404) {
            errorMessage = 'Service not found. Please contact support.';
        } else if (xhr.status === 500) {
            errorMessage = 'Server error. Please try again later.';
        } else if (status === 'timeout') {
            errorMessage = 'Request timed out. Please try with fewer formats.';
        } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            errorMessage = xhr.responseJSON.data.message;
        }
        
        showError(errorMessage);
    }

    function showProgress(show) {
        if (show) {
            $('.generation-progress').show();
            $('#generate-marketing-suite').prop('disabled', true);
            $('.generation-results').hide();
        } else {
            $('.generation-progress').hide();
            $('#generate-marketing-suite').prop('disabled', false);
        }
    }

    function hideProgress() {
        showProgress(false);
    }

    function updateProgress(percentage, text) {
        $('.progress-fill').css('width', percentage + '%');
        $('.progress-text').text(text);
        updateFormatProgress();
    }

    function updateFormatProgress(formatKey = null, status = null) {
        if (!formatKey) return;
        
        const container = $('.format-progress');
        let progressItem = container.find(`[data-format="${formatKey}"]`);
        
        if (progressItem.length === 0) {
            progressItem = $(`<div class="format-progress-item" data-format="${formatKey}">${formatConfigs[formatKey].name}</div>`);
            container.append(progressItem);
        }
        
        if (status) {
            progressItem.removeClass('processing completed error').addClass(status);
        }
    }

    function showResults(results, totalSize, generationTime) {
        const formatCount = results.length;
        const successCount = results.filter(r => r.dataURL).length;
        const avgSizeKB = Math.round(totalSize / 1024 / formatCount);
        const timeSeconds = Math.round(generationTime / 1000);
        
        // Update summary stats
        $('.stat-number').each(function(index) {
            const values = [formatCount, successCount, avgSizeKB + 'KB', timeSeconds + 's'];
            $(this).text(values[index] || '0');
        });
        
        // Create preview grid
        const previewGrid = $('.preview-grid');
        previewGrid.empty();
        
        results.forEach(result => {
            const previewItem = createPreviewItem(result);
            previewGrid.append(previewItem);
        });
        
        $('.generation-results').show();
        
        // Scroll to results
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('.generation-results').offset().top - 100
            }, 500);
        }, 500);
    }

    function createPreviewItem(result) {
        const config = formatConfigs[result.format];
        const canvas = result.canvas;
        const previewUrl = canvas.toDataURL('image/jpeg', 0.3); // Lower quality for preview
        
        return $(`
            <div class="preview-item">
                <div class="preview-image" style="background-image: url('${previewUrl}')"></div>
                <div class="preview-title">${result.name}</div>
                <div class="preview-dimensions">${config.width} × ${config.height}</div>
                <div class="preview-actions">
                    <button class="btn btn-mini btn-outline" onclick="previewFormat('${result.format}')">Preview</button>
                    <button class="btn btn-mini btn-primary" onclick="downloadFormat('${result.format}')">Download</button>
                </div>
            </div>
        `);
    }

    // Download functions
    window.downloadFormat = function(formatKey) {
        const result = generationResults.find(r => r.format === formatKey);
        if (!result) return;
        
        const config = formatConfigs[formatKey];
        const canvas = result.canvas;
        const dataURL = canvas.toDataURL('image/png');
        
        const link = document.createElement('a');
        link.download = `${currentCampaignType}-${formatKey}-${Date.now()}.png`;
        link.href = dataURL;
        link.click();
    };

    window.previewFormat = function(formatKey) {
        const result = generationResults.find(r => r.format === formatKey);
        if (!result) return;
        
        const canvas = result.canvas;
        const dataURL = canvas.toDataURL('image/png');
        
        const previewWindow = window.open('', '_blank');
        previewWindow.document.write(`
            <html>
                <head><title>${result.name} Preview</title></head>
                <body style="margin:0;padding:20px;text-align:center;background:#f0f0f0;">
                    <h2>${result.name}</h2>
                    <img src="${dataURL}" style="max-width:100%;height:auto;border:1px solid #ccc;">
                </body>
            </html>
        `);
    };

    function downloadAllAsZip() {
        if (typeof JSZip === 'undefined') {
            showError('JSZip library not loaded. Cannot create ZIP file.');
            return;
        }
        
        if (generationResults.length === 0) {
            showError('No results to download.');
            return;
        }
        
        const zip = new JSZip();
        const promises = [];
        
        generationResults.forEach(result => {
            const canvas = result.canvas;
            const promise = new Promise((resolve) => {
                canvas.toBlob((blob) => {
                    zip.file(`${currentCampaignType}-${result.format}.png`, blob);
                    resolve();
                }, 'image/png');
            });
            promises.push(promise);
        });
        
        Promise.all(promises).then(() => {
            zip.generateAsync({type: 'blob'}).then((content) => {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(content);
                link.download = `marketing-suite-${currentCampaignType}-${Date.now()}.zip`;
                link.click();
            });
        }).catch((error) => {
            console.error('ZIP generation failed:', error);
            showError('Failed to create ZIP file.');
        });
    }

    function showIndividualDownloads() {
        // Show individual download interface
        $('.preview-grid').toggle();
    }

    function clearResults() {
        $('.generation-results').hide();
        $('.preview-grid').empty();
        generationResults = [];
    }

    function showError(message) {
        console.error('Marketing Suite Generator Error:', message);
        
        const errorContainer = $('.flyer-error');
        if (errorContainer.length) {
            errorContainer.find('.error-message').text(message);
            errorContainer.show();
        } else {
            // Create error container if it doesn't exist
            const errorHtml = `
                <div class="flyer-error">
                    <div class="error-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="error-message">${message}</span>
                        <button type="button" class="dismiss-error">&times;</button>
                    </div>
                </div>
            `;
            $('body').append(errorHtml);
            
            // Bind dismiss event
            $('.dismiss-error').on('click', hideError);
        }
        
        // Auto-hide after 5 seconds
        setTimeout(hideError, 5000);
    }

    function hideError() {
        $('.flyer-error').fadeOut();
    }

    // Legacy flyer generator compatibility
    window.downloadPNG = function() {
        if (generationResults.length > 0) {
            const flyerResult = generationResults.find(r => r.format === 'full_flyer');
            if (flyerResult) {
                downloadFormat('full_flyer');
            }
        }
    };

    window.downloadPDF = function() {
        // PDF generation would require jsPDF integration
        showError('PDF download feature coming soon.');
    };
    
    // Expose initializeMarketingSuite globally for modal loading
    window.initializeMarketingSuite = initializeMarketingSuite;

})(jQuery);