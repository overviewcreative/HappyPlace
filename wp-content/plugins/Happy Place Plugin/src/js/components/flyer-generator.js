(function($) {
    'use strict';

    let canvas;

    $(document).ready(function() {
        // Initialize Fabric.js canvas
        canvas = new fabric.Canvas('flyer-canvas');

        $('#generate-flyer').on('click', function() {
            const listingId = $('#listing-select').val();
            if (!listingId) {
                alert('Please select a listing.');
                return;
            }

            generateFlyer(listingId);
        });
        
        // Add download button handlers
        $('#download-flyer').on('click', downloadPNG);
        $('#download-pdf').on('click', downloadPDF);
    });

    function showLoading(show) {
        if (show) {
            $('.flyer-loading').show();
            $('#generate-flyer').prop('disabled', true).text('Generating...');
        } else {
            $('.flyer-loading').hide();
            $('#generate-flyer').prop('disabled', false).text('Generate Flyer');
        }
    }

    function generateFlyer(listingId) {
        // Check if flyerGenerator is defined
        if (typeof flyerGenerator === 'undefined') {
            console.error('flyerGenerator object not found. Check script localization.');
            alert('Configuration error. Please refresh the page and try again.');
            return;
        }
        
        showLoading(true);
        canvas.clear();
        
        // Make AJAX request to get listing data
        $.ajax({
            url: flyerGenerator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_flyer',
                listing_id: listingId,
                nonce: flyerGenerator.nonce
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                if (response.success) {
                    const data = response.data;
                    console.log('Received data:', data);
                    
                    // Always use Parker Group style - template selection removed
                    try {
                        createParkerGroupDesign(data);
                        
                        // Show download buttons
                        $('#download-flyer, #download-pdf').show();
                    } catch (error) {
                        console.error('Error creating flyer design:', error);
                        alert('Error creating flyer design: ' + error.message);
                    }
                } else {
                    console.error('AJAX Error:', response.data);
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Request Failed:', {xhr, status, error});
                alert('Error generating flyer. Please try again. Status: ' + status);
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    function getFontAwesomeIcon(iconType) {
        const icons = {
            'envelope': '\uf0e0',      // FontAwesome envelope icon
            'phone': '\uf095',         // FontAwesome phone icon
            'location': '\uf3c5',      // FontAwesome map-marker-alt icon
            'location-dot': '\uf3c5',  // FontAwesome map-marker-alt icon
            'house': '\uf015',         // FontAwesome house icon
            'building': '\uf1ad',      // FontAwesome building icon
            'star': '\uf005',          // FontAwesome star icon
            'heart': '\uf004',         // FontAwesome heart icon
            'camera': '\uf030',        // FontAwesome camera icon
            'map': '\uf279',           // FontAwesome map icon
            'bath': '\uf2cd',          // FontAwesome bath icon
            'bed': '\uf236',           // FontAwesome bed icon
            'ruler': '\uf545',         // FontAwesome ruler icon
            'dollar': '\uf155',        // FontAwesome dollar icon
            'email': '\uf0e0',         // Alias for envelope
            'mobile': '\uf10b',        // FontAwesome mobile icon
            'address': '\uf3c5',       // Alias for location
            'pin': '\uf276',           // FontAwesome map-pin icon
            'marker': '\uf3c5'         // Alias for location
        };
        
        return icons[iconType] || '';
    }

    function createParkerGroupDesign(data) {
        console.log('Creating Parker Group flyer with data:', data);
        
        try {
            // Map the data structure from PHP to expected format with null safety
            const mappedData = {
                gallery: [],
                price: data.listing?.price || data.price || null,
                bedrooms: data.listing?.bedrooms || data.bedrooms || null,
                bathrooms: data.listing?.bathrooms || data.bathrooms || null,
                square_feet: data.listing?.square_footage || data.square_footage || null,
                lot_size: data.listing?.lot_size || data.listing?.lot_square_feet || data.listing?.lot_acres || data.lot_size || data.lot_acres || data.acreage || null,
                address: data.listing?.street_address || data.address || null,
                city: data.listing?.city || data.city || null,
                agent: data.agent || {}
            };
            
            // Debug logging
            console.log('Mapped data types:', {
                price: typeof mappedData.price,
                bedrooms: typeof mappedData.bedrooms,
                bathrooms: typeof mappedData.bathrooms,
                square_feet: typeof mappedData.square_feet,
                lot_size: typeof mappedData.lot_size,
                address: typeof mappedData.address,
                city: typeof mappedData.city
            });
        
        // Handle photo gallery
        if (data.listing?.photo_gallery && Array.isArray(data.listing.photo_gallery)) {
            mappedData.gallery = data.listing.photo_gallery.map(photo => {
                if (typeof photo === 'object' && photo.url) {
                    return photo.url;
                } else if (typeof photo === 'string') {
                    return photo;
                }
                return null;
            }).filter(url => url !== null);
        } else if (data.listing?.main_photo) {
            const mainPhoto = data.listing.main_photo;
            if (typeof mainPhoto === 'object' && mainPhoto.url) {
                mappedData.gallery = [mainPhoto.url];
            } else if (typeof mainPhoto === 'string') {
                mappedData.gallery = [mainPhoto];
            }
        }
        
        // Create full address
        if (mappedData.address && mappedData.city) {
            mappedData.full_address = `${mappedData.address}, ${mappedData.city}`;
        } else {
            mappedData.full_address = mappedData.address || mappedData.city || 'Address Not Available';
        }
        
        console.log('Mapped data:', mappedData);
        
        // Primary blue background for entire flyer
        const background = new fabric.Rect({
            left: 0,
            top: 0,
            width: 850,
            height: 1100,
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(background);
        
        // "FOR SALE" text at top
        const forSaleText = new fabric.Text('FOR SALE', {
            left: 55,
            top: 50,
            fontSize: 80,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(forSaleText);
        
        console.log('Successfully added FOR SALE text');
        
        // Main photo section
        const mainPhotoTop = 150;
        
        if (mappedData.gallery && mappedData.gallery.length > 0) {
            const mainPhotoUrl = mappedData.gallery[0];
            console.log('Loading main photo:', mainPhotoUrl);
            
            fabric.Image.fromURL(mainPhotoUrl, function(img) {
                const containerLeft = 55;
                const containerTop = mainPhotoTop;
                const containerWidth = 740;
                const containerHeight = 310;
                
                // Calculate scale to cover (fill) the container
                const scaleX = containerWidth / img.width;
                const scaleY = containerHeight / img.height;
                const scale = Math.max(scaleX, scaleY);
                
                // Calculate position to center the scaled image
                const scaledWidth = img.width * scale;
                const scaledHeight = img.height * scale;
                const imageLeft = containerLeft + (containerWidth - scaledWidth) / 2;
                const imageTop = containerTop + (containerHeight - scaledHeight) / 2;
                
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
                    left: containerLeft,
                    top: containerTop,
                    width: containerWidth,
                    height: containerHeight,
                    absolutePositioned: true
                });
                
                img.clipPath = mask;
                canvas.add(img);
                canvas.renderAll();
            });
        } else {
            // Fallback placeholder if no main photo
            const mainPhoto = new fabric.Rect({
                left: 55,
                top: mainPhotoTop,
                width: 740,
                height: 310,
                fill: '#f5f5f4',
                stroke: '#cccccc',
                strokeWidth: 2,
                selectable: false
            });
            canvas.add(mainPhoto);
            
            const noImageText = new fabric.Text('No Image Available', {
                left: 425,
                top: mainPhotoTop + 155,
                fontSize: 24,
                fontFamily: 'Poppins, sans-serif',
                fill: '#cccccc',
                textAlign: 'center',
                originX: 'center',
                selectable: false
            });
            canvas.add(noImageText);
        }
        
        // Small photos section - aligned perfectly with main photo
        const smallPhotosTop = 480;
        
        if (mappedData.gallery && mappedData.gallery.length > 1) {
            const availablePhotos = mappedData.gallery.slice(1, 4);
            const photoWidth = 240; // Increased from 235 to 240
            const photoHeight = 155;
            const spacing = 10;
            const startX = 55;
            
            availablePhotos.forEach((photoUrl, index) => {
                fabric.Image.fromURL(photoUrl, function(img) {
                    const containerLeft = startX + (photoWidth + spacing) * index;
                    const containerTop = smallPhotosTop;
                    
                    const scaleX = photoWidth / img.width;
                    const scaleY = photoHeight / img.height;
                    const scale = Math.max(scaleX, scaleY);
                    
                    const scaledWidth = img.width * scale;
                    const scaledHeight = img.height * scale;
                    const imageLeft = containerLeft + (photoWidth - scaledWidth) / 2;
                    const imageTop = containerTop + (photoHeight - scaledHeight) / 2;
                    
                    img.set({
                        left: imageLeft,
                        top: imageTop,
                        scaleX: scale,
                        scaleY: scale,
                        selectable: false,
                        crossOrigin: 'anonymous'
                    });
                    
                    const mask = new fabric.Rect({
                        left: containerLeft,
                        top: containerTop,
                        width: photoWidth,
                        height: photoHeight,
                        absolutePositioned: true
                    });
                    
                    img.clipPath = mask;
                    canvas.add(img);
                    canvas.renderAll();
                });
            });
        } else {
            // Placeholder for missing photos - updated width
            for (let i = 0; i < 3; i++) {
                const placeholder = new fabric.Rect({
                    left: 55 + (240 + 10) * i, // Updated to match new width
                    top: smallPhotosTop,
                    width: 240, // Updated width
                    height: 155,
                    fill: '#f5f5f4',
                    stroke: '#cccccc',
                    strokeWidth: 1,
                    selectable: false
                });
                canvas.add(placeholder);
            }
        }
        
        // Dark blue section background
        const darkSection = new fabric.Rect({
            left: 0,
            top: 655,
            width: 850,
            height: 75,
            fill: '#082f49',
            selectable: false
        });
        canvas.add(darkSection);

        // Property stats with FontAwesome icons - ensure all values are strings
        const statsTop = 675;
        const beds = String(mappedData.bedrooms || mappedData.beds || 'N/A');
        const baths = String(mappedData.bathrooms || mappedData.baths || 'N/A');
        const sqft = mappedData.square_feet || mappedData.sqft || mappedData.size || 'N/A';
        const lotSize = mappedData.lot_size || 'N/A';

        // Bed icon and text
        const bedIcon = new fabric.Text(getFontAwesomeIcon('bed'), {
            left: 75,
            top: statsTop,
            fontSize: 20,
            fontFamily: 'FontAwesome',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(bedIcon);

        const bedsText = new fabric.Text(`${beds} Bed`, {
            left: 105,
            top: statsTop + 5,
            fontSize: 16,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(bedsText);
        console.log('Successfully added beds text:', `${beds} Bed`);

        // Bath icon and text  
        const bathIcon = new fabric.Text(getFontAwesomeIcon('bath'), {
            left: 190,
            top: statsTop,
            fontSize: 20,
            fontFamily: 'FontAwesome',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(bathIcon);

        const bathsText = new fabric.Text(`${baths} Bath`, {
            left: 220,
            top: statsTop + 5,
            fontSize: 16,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '500',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(bathsText);
        console.log('Successfully added baths text:', `${baths} Bath`);

        // Square footage icon and text
        if (sqft !== 'N/A' && sqft !== null && sqft !== undefined) {
            const sqftFormatted = typeof sqft === 'number' ? number_format(sqft) : String(sqft);
            
            const sqftIcon = new fabric.Text(getFontAwesomeIcon('ruler'), {
                left: 310,
                top: statsTop,
                fontSize: 20,
                fontFamily: 'FontAwesome',
                fill: '#ffffff',
                selectable: false
            });
            canvas.add(sqftIcon);

            const sqftText = new fabric.Text(`${sqftFormatted} Ft²`, {
                left: 340,
                top: statsTop + 5,
                fontSize: 16,
                fontFamily: 'Poppins, sans-serif',
                fontWeight: '500',
                fill: '#ffffff',
                selectable: false
            });
            canvas.add(sqftText);
        }

        // Lot size icon and text
        if (lotSize !== 'N/A' && lotSize !== null && lotSize !== undefined) {
            let lotFormatted = String(lotSize);
            
            // Format lot size based on type
            if (typeof lotSize === 'number' && !isNaN(lotSize)) {
                if (lotSize < 1) {
                    lotFormatted = Math.round(lotSize * 43560) + ' sq ft'; // Convert acres to sq ft
                } else if (lotSize < 100) {
                    lotFormatted = lotSize + ' acres';
                } else {
                    lotFormatted = number_format(lotSize) + ' sq ft';
                }
            }
            
            const lotIcon = new fabric.Text(getFontAwesomeIcon('map'), {
                left: 450,
                top: statsTop,
                fontSize: 20,
                fontFamily: 'FontAwesome',
                fill: '#ffffff',
                selectable: false
            });
            canvas.add(lotIcon);

            const lotText = new fabric.Text(String(lotFormatted), {
                left: 480,
                top: statsTop + 5,
                fontSize: 16,
                fontFamily: 'Poppins, sans-serif',
                fontWeight: '500',
                fill: '#ffffff',
                selectable: false
            });
            canvas.add(lotText);
        }

        // Price - positioned on the right with better alignment and null safety
        const price = mappedData.price || mappedData.list_price || 'Price Available Upon Request';
        let priceFormatted = 'Price Available Upon Request'; // Default fallback
        
        try {
            if (price && price !== 'Price Available Upon Request') {
                if (typeof price === 'number' && !isNaN(price)) {
                    priceFormatted = '$' + number_format(price);
                } else if (typeof price === 'string') {
                    if (price.includes('$')) {
                        priceFormatted = price;
                    } else {
                        const cleanPrice = price.replace(/[^\d.-]/g, ''); // Remove non-numeric chars except decimal and minus
                        const numPrice = parseFloat(cleanPrice);
                        if (!isNaN(numPrice)) {
                            priceFormatted = '$' + number_format(Math.round(numPrice));
                        } else {
                            priceFormatted = String(price); // Use original if can't parse
                        }
                    }
                }
            }
            
            console.log('Price formatting: original =', price, 'formatted =', priceFormatted, 'type =', typeof priceFormatted);
            
        } catch (priceError) {
            console.error('Error formatting price:', priceError);
            priceFormatted = 'Price Available Upon Request';
        }

        const priceText = new fabric.Text(String(priceFormatted), {
            left: 750,
            top: statsTop - 5,
            fontSize: 32,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '700',
            fill: '#ffffff',
            originX: 'right',
            selectable: false
        });
        canvas.add(priceText);
        
        console.log('Successfully added price text');

        // Light section background
        const whiteSection = new fabric.Rect({
            left: 0,
            top: 730,
            width: 850,
            height: 370,
            fill: '#f5f5f4',
            selectable: false
        });
        canvas.add(whiteSection);

        // Address with light blue color - ensure string conversion
        let address = mappedData.full_address || mappedData.address || 'Address Not Available';
        address = String(address); // Ensure it's a string
        if (address.length > 50) {
            address = address.substring(0, 47) + '...';
        }
        
        console.log('Address value and type:', address, typeof address);

        const addressText = new fabric.Text(address, {
            left: 55,
            top: 760,
            fontSize: 32,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(addressText);
        console.log('Successfully added address text');

        // City/State line - ensure string conversion
        const city = String(mappedData.city || '');
        console.log('City value and type:', city, typeof city);
        
        const cityText = new fabric.Text(city, {
            left: 55,
            top: 800,
            fontSize: 18,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(cityText);
        console.log('Successfully added city text');

        // Property description with better spacing using bridge function
        let description = getPropertyDescription(data);
        description = String(description); // Ensure it's a string
        if (description.length > 280) {
            description = description.substring(0, 277) + '...';
        }
        
        console.log('Description value and type:', description.substring(0, 50) + '...', typeof description);

        const descriptionText = new fabric.Text(description, {
            left: 55,
            top: 835,
            width: 400,
            fontSize: 13,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            lineHeight: 1.5,
            selectable: false
        });
        canvas.add(descriptionText);

        console.log('Successfully added description text');

        // Agent section - positioned on the left with better alignment
        const agentTop = 980;
        
        // Get agent data
        const agent = mappedData.agent || {};
        console.log('Agent data received:', agent);
        console.log('Agent object keys and values:');
        if (agent && typeof agent === 'object') {
            Object.keys(agent).forEach(key => {
                console.log(`  ${key}:`, agent[key]);
                // If it's an object, show its structure too
                if (agent[key] && typeof agent[key] === 'object' && !Array.isArray(agent[key])) {
                    console.log(`    ${key} structure:`, Object.keys(agent[key]));
                }
            });
        }
        
        // Handle different agent name formats with null safety
        let agentName = 'Agent Name Not Available';
        if (agent.display_name) {
            agentName = String(agent.display_name);
        } else if (agent.name) {
            agentName = String(agent.name);
        } else if (agent.first_name || agent.last_name) {
            const firstName = agent.first_name ? String(agent.first_name) : '';
            const lastName = agent.last_name ? String(agent.last_name) : '';
            agentName = `${firstName} ${lastName}`.trim();
        } else if (agent.full_name) {
            agentName = String(agent.full_name);
        }
        
        const agentEmail = agent.email || agent.contact_email || 'info@theparkergroup.com';
        const agentPhone = agent.phone || agent.mobile_phone || agent.office_phone || agent.contact_phone || '302.217.6692';
        
        // Handle different photo field structures
        let agentPhotoUrl = null;
        console.log('Looking for agent photo in:', agent);
        
        // Extended search for agent photo with more field variations
        const photoFields = [
            'profile_photo', 'photo', 'headshot', 'image', 'picture', 'avatar', 
            'user_photo', 'agent_photo', 'profile_image', 'profile_pic', 'thumbnail',
            'featured_image', 'media', 'attachment'
        ];
        
        for (let field of photoFields) {
            if (agent[field]) {
                console.log(`Found ${field} field:`, agent[field]);
                
                if (typeof agent[field] === 'string' && agent[field].trim() !== '') {
                    agentPhotoUrl = agent[field];
                    console.log(`Using ${field} as string URL:`, agentPhotoUrl);
                    break;
                } else if (agent[field] && typeof agent[field] === 'object') {
                    console.log(`${field} is object, checking sub-fields:`, Object.keys(agent[field]));
                    
                    // Check various URL sub-fields - expanded list
                    const urlFields = ['url', 'src', 'href', 'link', 'file', 'path'];
                    const sizeFields = ['medium', 'full', 'large', 'thumbnail', 'medium_large'];
                    
                    // Direct URL fields
                    for (let urlField of urlFields) {
                        if (agent[field][urlField] && typeof agent[field][urlField] === 'string') {
                            agentPhotoUrl = agent[field][urlField];
                            console.log(`Using ${field}.${urlField}:`, agentPhotoUrl);
                            break;
                        }
                    }
                    
                    // If no direct URL found, check sizes object
                    if (!agentPhotoUrl && agent[field].sizes && typeof agent[field].sizes === 'object') {
                        console.log(`Checking ${field}.sizes:`, Object.keys(agent[field].sizes));
                        for (let sizeField of sizeFields) {
                            if (agent[field].sizes[sizeField]) {
                                if (typeof agent[field].sizes[sizeField] === 'string') {
                                    agentPhotoUrl = agent[field].sizes[sizeField];
                                    console.log(`Using ${field}.sizes.${sizeField}:`, agentPhotoUrl);
                                    break;
                                } else if (agent[field].sizes[sizeField].url) {
                                    agentPhotoUrl = agent[field].sizes[sizeField].url;
                                    console.log(`Using ${field}.sizes.${sizeField}.url:`, agentPhotoUrl);
                                    break;
                                }
                            }
                        }
                    }
                    
                    if (agentPhotoUrl) break;
                }
            }
        }
        
        // WordPress specific checks if still no photo found
        if (!agentPhotoUrl) {
            console.log('No standard photo field found, checking WordPress specific fields...');
            
            // Check for WordPress attachment ID and get URL
            if (agent.profile_photo_id || agent.photo_id || agent.image_id) {
                const attachmentId = agent.profile_photo_id || agent.photo_id || agent.image_id;
                console.log('Found attachment ID:', attachmentId);
                // Note: In a real WordPress environment, you'd fetch the URL from the attachment ID
                // For now, we'll log this for debugging
            }
            
            // Check for ACF (Advanced Custom Fields) format
            if (agent.acf && typeof agent.acf === 'object') {
                console.log('Checking ACF fields:', Object.keys(agent.acf));
                for (let field of photoFields) {
                    if (agent.acf[field]) {
                        console.log(`Found ACF ${field}:`, agent.acf[field]);
                        if (typeof agent.acf[field] === 'string') {
                            agentPhotoUrl = agent.acf[field];
                            break;
                        } else if (agent.acf[field].url) {
                            agentPhotoUrl = agent.acf[field].url;
                            break;
                        }
                    }
                }
            }
        }
        
        console.log('Final agent photo URL:', agentPhotoUrl);
        
        // Validate URL format
        if (agentPhotoUrl) {
            // Check if it's a relative URL and make it absolute
            if (agentPhotoUrl.startsWith('/')) {
                agentPhotoUrl = window.location.origin + agentPhotoUrl;
                console.log('Converted relative URL to absolute:', agentPhotoUrl);
            }
            
            // Basic URL validation
            try {
                new URL(agentPhotoUrl);
                console.log('URL validation passed');
            } catch (e) {
                console.log('Invalid URL format:', agentPhotoUrl, 'Error:', e.message);
                agentPhotoUrl = null;
            }
        }
        
        // Agent photo with proper error handling and positioning
        function addAgentPhotoPlaceholder() {
            console.log('Adding agent photo placeholder');
            const agentPhotoPlaceholder = new fabric.Circle({
                left: 90,
                top: agentTop + 35,
                radius: 35,
                fill: '#f0f0f0',
                stroke: '#cccccc',
                strokeWidth: 2,
                originX: 'center',
                originY: 'center',
                selectable: false
            });
            canvas.add(agentPhotoPlaceholder);
            canvas.renderAll();
        }

        if (agentPhotoUrl) {
            console.log('Attempting to load agent photo from URL:', agentPhotoUrl);
            
            // Test if image URL is accessible before trying to load
            const testImg = new Image();
            testImg.onload = function() {
                console.log('Image URL is accessible, proceeding with Fabric.js load');
                
                fabric.Image.fromURL(agentPhotoUrl, function(img) {
                    console.log('Agent photo loaded successfully via Fabric.js');
                    const size = 70;
                    const centerX = 90;
                    const centerY = agentTop + 35;
                    
                    // Proper cover scaling - use max to ensure full coverage
                    const scaleX = size / img.width;
                    const scaleY = size / img.height;
                    const scale = Math.max(scaleX, scaleY); // Use max for cover behavior
                    
                    // Single img.set() call with all properties including clipPath
                    img.set({
                        left: centerX,
                        top: centerY,
                        originX: 'center',
                        originY: 'center',
                        scaleX: scale,
                        scaleY: scale,
                        selectable: false,
                        crossOrigin: 'anonymous',
                        clipPath: new fabric.Circle({
                            radius: size / 2,
                            originX: 'center',
                            originY: 'center',
                            left: 0, // Relative to image center
                            top: 0   // Relative to image center
                        })
                    });
                    
                    canvas.add(img);
                    canvas.renderAll();
                }, { 
                    crossOrigin: 'anonymous',
                    // Add error handling
                    onError: function(error) {
                        console.log('Fabric.js failed to load agent photo from URL:', agentPhotoUrl);
                        console.log('Fabric.js error details:', error);
                        addAgentPhotoPlaceholder();
                    }
                });
            };
            
            testImg.onerror = function() {
                console.log('Image URL test failed - image not accessible:', agentPhotoUrl);
                console.log('Possible issues: CORS, 404, invalid URL, or authentication required');
                addAgentPhotoPlaceholder();
            };
            
            testImg.crossOrigin = 'anonymous';
            testImg.src = agentPhotoUrl;
            
        } else {
            console.log('No valid agent photo URL found, using placeholder');
            addAgentPhotoPlaceholder();
        }

        // Agent name with better styling - ensure string conversion
        const agentNameText = new fabric.Text(String(agentName), {
            left: 140,
            top: agentTop,
            fontSize: 18,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(agentNameText);

        // Agent title
        const agentTitle = new fabric.Text('REALTOR®', {
            left: 140,
            top: agentTop + 24,
            fontSize: 12,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(agentTitle);

        // Agent email with better alignment - ensure string conversion
        const agentEmailText = new fabric.Text(String(agentEmail), {
            left: 162,
            top: agentTop + 45,
            fontSize: 11,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(agentEmailText);
        
        const emailIcon = new fabric.Text(getFontAwesomeIcon('envelope'), {
            left: 140,
            top: agentTop + 45,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(emailIcon);

        // Agent phone with better alignment - ensure string conversion
        const agentPhoneText = new fabric.Text(String(agentPhone), {
            left: 162,
            top: agentTop + 62,
            fontSize: 11,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(agentPhoneText);
        
        const phoneIcon = new fabric.Text(getFontAwesomeIcon('phone'), {
            left: 140,
            top: agentTop + 62,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(phoneIcon);

        // Company logo section - using actual Parker Group logo
        const logoUrl = '/wp-content/uploads/primarylogo.png';
        
        fabric.Image.fromURL(logoUrl, function(logoImg) {
            // Scale logo to fit within designated space
            const maxWidth = 140;
            const maxHeight = 100;
            const scale = Math.min(maxWidth / logoImg.width, maxHeight / logoImg.height);
            
            logoImg.set({
                left: 680,
                top: 780,
                scaleX: scale,
                scaleY: scale,
                originX: 'center',
                originY: 'center',
                selectable: false,
                crossOrigin: 'anonymous'
            });
            
            canvas.add(logoImg);
            canvas.renderAll();
        }, { 
            crossOrigin: 'anonymous',
            // Fallback if logo doesn't load
            onError: function() {
                // Fallback logo placeholder with brand colors
                const logoPlaceholder = new fabric.Rect({
                    left: 610,
                    top: 740,
                    width: 140,
                    height: 100,
                    fill: '#51bae0',
                    rx: 8,
                    ry: 8,
                    selectable: false
                });
                canvas.add(logoPlaceholder);

                const logoText = new fabric.Text('THE PARKER\nGROUP', {
                    left: 680,
                    top: 790,
                    fontSize: 16,
                    fontFamily: 'Poppins, sans-serif',
                    fontWeight: '700',
                    fill: '#ffffff',
                    textAlign: 'center',
                    originX: 'center',
                    originY: 'center',
                    selectable: false
                });
                canvas.add(logoText);
            }
        });

        // Company tagline with better positioning
        const taglineText = new fabric.Text('find your happy place', {
            left: 680,
            top: 860,
            fontSize: 12,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#51bae0',
            textAlign: 'center',
            originX: 'center',
            selectable: false
        });
        canvas.add(taglineText);

        // QR Code section - positioned to the left of contact info
        const qrCodeContainer = new fabric.Rect({
            left: 520,
            top: 990,
            width: 80,
            height: 80,
            fill: '#f5f5f4',
            stroke: '#51bae0',
            strokeWidth: 2,
            rx: 4,
            ry: 4,
            selectable: false
        });
        canvas.add(qrCodeContainer);

        // QR Code placeholder pattern - simulating QR appearance
        const qrPattern = new fabric.Rect({
            left: 530,
            top: 1000,
            width: 60,
            height: 60,
            fill: '#082f49',
            rx: 2,
            ry: 2,
            selectable: false
        });
        canvas.add(qrPattern);

        // QR inner pattern for visual effect
        for (let i = 0; i < 3; i++) {
            for (let j = 0; j < 3; j++) {
                if ((i + j) % 2 === 0) {
                    const qrSquare = new fabric.Rect({
                        left: 535 + (i * 16),
                        top: 1005 + (j * 16),
                        width: 12,
                        height: 12,
                        fill: '#f5f5f4',
                        selectable: false
                    });
                    canvas.add(qrSquare);
                }
            }
        }

        // Company contact section - repositioned to the right of QR code
        const contactTitle = new fabric.Text('Get in touch', {
            left: 620,
            top: 980,
            fontSize: 14,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(contactTitle);

        // Contact email with improved spacing
        const contactEmail = new fabric.Text('cheers@theparkergroup.com', {
            left: 650,
            top: 1005,
            fontSize: 11,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(contactEmail);

        const contactEmailIcon = new fabric.Text(getFontAwesomeIcon('envelope'), {
            left: 625,
            top: 1005,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(contactEmailIcon);

        // Contact phone with improved spacing
        const contactPhone = new fabric.Text('302.217.6692', {
            left: 650,
            top: 1022,
            fontSize: 11,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(contactPhone);

        const contactPhoneIcon = new fabric.Text(getFontAwesomeIcon('phone'), {
            left: 625,
            top: 1022,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(contactPhoneIcon);

        // Contact address with improved spacing
        const contactAddress = new fabric.Text('673 N Bedford St, Georgetown, DE', {
            left: 650,
            top: 1039,
            fontSize: 11,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            selectable: false
        });
        canvas.add(contactAddress);

        const contactLocationIcon = new fabric.Text(getFontAwesomeIcon('location-dot'), {
            left: 625,
            top: 1039,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            selectable: false
        });
        canvas.add(contactLocationIcon);
        
        // Generate QR code for listing URL
        generateQRCode(mappedData);
        
        } catch (error) {
            console.error('Error in createParkerGroupDesign:', error);
            console.error('Error stack:', error.stack);
            throw error; // Re-throw to be caught by the calling function
        }
    }

    function downloadPNG() {
        if (!canvas) return;

        const dataURL = canvas.toDataURL({
            format: 'png',
            quality: 1,
            multiplier: 2
        });

        const link = document.createElement('a');
        link.download = 'property-flyer.png';
        link.href = dataURL;
        link.click();
    }

    function downloadPDF() {
        if (!canvas || typeof window.jsPDF === 'undefined') {
            alert('PDF generation not available. Please try PNG download.');
            return;
        }

        const imgData = canvas.toDataURL('image/png');
        const pdf = new window.jsPDF('p', 'mm', 'a4');
        
        // Calculate dimensions to fit A4
        const imgWidth = 210;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
        pdf.save('property-flyer.pdf');
    }

    function updatePreview() {
        // Auto-generate when listing changes
        if ($('#listing-select').val()) {
            $('#generate-flyer').prop('disabled', false);
        } else {
            $('#generate-flyer').prop('disabled', true);
            $('#download-flyer, #download-pdf').hide();
        }
    }

    function number_format(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function getPropertyDescription(data) {
        // Bridge function for property description with multiple fallback options
        const descriptionFields = [
            data.listing?.short_description,
            data.listing?.description, 
            data.listing?.public_remarks,
            data.listing?.marketing_remarks,
            data.listing?.brief_description,
            data.description,
            data.public_remarks,
            data.marketing_remarks,
            data.brief_description,
            data.remarks
        ];
        
        // Find first non-empty description
        for (let desc of descriptionFields) {
            if (desc && typeof desc === 'string' && desc.trim().length > 0) {
                return String(desc.trim()); // Ensure string return
            }
        }
        
        return 'No description available.'; // Always return a string
    }

    function generateQRCode(propertyData) {
        // Generate QR code using the Google Charts API via PHP helper
        const listingId = propertyData.listing_id || propertyData.ID;
        
        if (!listingId) {
            console.error('No listing ID available for QR code generation');
            return null;
        }
        
        // Create QR code URL using Google Charts API
        const qrSize = 150; // Size in pixels
        const listingUrl = propertyData.listing_url || propertyData.permalink || window.location.href;
        const encodedUrl = encodeURIComponent(listingUrl);
        const qrUrl = `https://chart.googleapis.com/chart?cht=qr&chs=${qrSize}x${qrSize}&chl=${encodedUrl}&choe=UTF-8`;
        
        // Create and return QR code image element
        const qrImage = new Image();
        qrImage.crossOrigin = 'anonymous';
        qrImage.onload = function() {
            console.log('QR code generated successfully for listing:', listingId);
        };
        qrImage.onerror = function() {
            console.error('Failed to generate QR code for listing:', listingId);
        };
        qrImage.src = qrUrl;
        
        return {
            image: qrImage,
            url: qrUrl,
            size: qrSize
        };
    }

    // Attach functions to the window object for global access
    window.downloadPNG = downloadPNG;
    window.downloadPDF = downloadPDF;

})(jQuery);