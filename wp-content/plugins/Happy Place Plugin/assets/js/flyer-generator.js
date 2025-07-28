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
        
        const flyerType = $('#flyer-type-select').val() || 'listing';
        
        showLoading(true);
        canvas.clear();
        
        // Make AJAX request to get listing data
        $.ajax({
            url: flyerGenerator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_flyer',
                listing_id: listingId,
                flyer_type: flyerType,
                nonce: flyerGenerator.nonce
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                if (response.success) {
                    const data = response.data;
                    console.log('Received data:', data);
                    
                    // Always use Parker Group style - template selection removed
                    try {
                        createParkerGroupDesign(data, flyerType);
                        
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

    function createParkerGroupDesign(data, flyerType = 'listing') {
        console.log('Creating Parker Group flyer with data:', data, 'Type:', flyerType);
        
        // Determine which agent data to use based on flyer type
        let agentData = data.agent;
        let flyerTitleText = 'FOR SALE';
        
        if (flyerType === 'open_house' && data.hosting_agent) {
            console.log('Using hosting agent data for open house flyer:', data.hosting_agent);
            agentData = data.hosting_agent;
            flyerTitleText = 'OPEN HOUSE';
        }
        
        console.log('Final agent data being used:', agentData);
        
        try {
            // Map the data structure from PHP to expected format with null safety
            const mappedData = {
                gallery: [],
                price: data.listing?.price || data.price || null,
                bedrooms: data.listing?.bedrooms || data.bedrooms || null,
                bathrooms: data.listing?.bathrooms || data.bathrooms || null,
                square_feet: data.listing?.square_footage || data.square_footage || null,
                lot_size: data.listing?.lot_size || data.listing?.lot_square_feet || data.listing?.lot_acres || data.lot_size || data.lot_acres || data.acreage || null,
                street_address: data.listing?.street_address || data.listing?.address || data.address || null,
                address: data.listing?.address || data.listing?.full_address || data.address || data.full_address || null,
                full_address: data.listing?.full_address || data.full_address || null,
                city: data.listing?.city || data.city || null,
                state: data.listing?.state || data.listing?.region || data.state || data.region || null,
                zip_code: data.listing?.zip_code || data.listing?.zip || data.zip_code || data.zip || null,
                zip: data.listing?.zip || data.listing?.zip_code || data.zip || data.zip_code || null,
                agent: agentData || {}
            };
            
        console.log('Original data received:', data);
        console.log('Agent data structure:', data.agent);
        console.log('Complete agent object keys:', data.agent ? Object.keys(data.agent) : 'No agent data');
        console.log('Complete listing object keys:', data.listing ? Object.keys(data.listing) : 'No listing data');            // Debug logging
            console.log('Mapped data types:', {
                price: typeof mappedData.price,
                bedrooms: typeof mappedData.bedrooms,
                bathrooms: typeof mappedData.bathrooms,
                square_feet: typeof mappedData.square_feet,
                lot_size: typeof mappedData.lot_size,
                street_address: typeof mappedData.street_address,
                address: typeof mappedData.address,
                city: typeof mappedData.city,
                state: typeof mappedData.state,
                zip: typeof mappedData.zip
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
        
        // Create full address if not already provided
        if (!mappedData.full_address) {
            const addressParts = [];
            if (mappedData.street_address || mappedData.address) {
                addressParts.push(mappedData.street_address || mappedData.address);
            }
            if (mappedData.city) addressParts.push(mappedData.city);
            if (mappedData.state) addressParts.push(mappedData.state);
            if (mappedData.zip_code || mappedData.zip) addressParts.push(mappedData.zip_code || mappedData.zip);
            
            mappedData.full_address = addressParts.length > 0 ? addressParts.join(', ') : 'Address Not Available';
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
        
        // Flyer title text at top (FOR SALE or OPEN HOUSE)
        const forSaleText = new fabric.Text(flyerTitleText, {
            left: 55,
            top: 50,
            fontSize: 80,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            textBaseline: 'alphabetic',
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
                textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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
                textBaseline: 'alphabetic',
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
                textBaseline: 'alphabetic',
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
                textBaseline: 'alphabetic',
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
                textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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

        // Address with light blue color - street number and name only
        let streetAddress = mappedData.street_address || mappedData.address || 'Address Not Available';
        streetAddress = String(streetAddress); // Ensure it's a string
        
        // If we have a full address, try to extract just the street portion
        if (streetAddress.includes(',')) {
            streetAddress = streetAddress.split(',')[0].trim();
        }
        
        if (streetAddress.length > 50) {
            streetAddress = streetAddress.substring(0, 47) + '...';
        }
        
        console.log('Street address value and type:', streetAddress, typeof streetAddress);

        const addressText = new fabric.Text(streetAddress, {
            left: 55,
            top: 760,
            fontSize: 32,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '600',
            fill: '#51bae0',
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(addressText);
        console.log('Successfully added street address text');

        // City, State, Zip line - positioned below street address
        const city = String(mappedData.city || '');
        const state = String(mappedData.state || mappedData.region || '');
        const zip = String(mappedData.zip_code || mappedData.zip || '');
        
        let cityStateZip = '';
        if (city) cityStateZip += city;
        if (state) {
            if (cityStateZip) cityStateZip += ', ';
            cityStateZip += state;
        }
        if (zip) {
            if (cityStateZip) cityStateZip += ' ';
            cityStateZip += zip;
        }
        
        console.log('City/State/Zip value and type:', cityStateZip, typeof cityStateZip);
        
        const cityText = new fabric.Text(cityStateZip, {
            left: 55,
            top: 800,
            fontSize: 18,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(cityText);
        console.log('Successfully added city/state/zip text');

        // Property description with better spacing using bridge function
        let description = getPropertyDescription(data);
        description = String(description); // Ensure it's a string
        
        // Strip HTML tags for clean text display
        description = description.replace(/<[^>]*>/g, '');
        
        if (description.length > 400) {
            description = description.substring(0, 397) + '...';
        }
        
        console.log('Description value and type:', description.substring(0, 50) + '...', typeof description);

        const descriptionText = new fabric.Textbox(description, {
            left: 55,
            top: 835,
            width: 400, // Constrain to left column
            fontSize: 13,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#333333',
            lineHeight: 1.5,
            textBaseline: 'alphabetic',
            selectable: false,
            splitByGrapheme: false, // Better word wrapping
            breakWords: true // Allow word breaks for long words
        });
        canvas.add(descriptionText);

        console.log('Successfully added description text');

        // Agent section - positioned on the left with better alignment
        const agentTop = 980;
        
        // Get agent data
        const agent = agentData || {};
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
            'user_photo', 'agent_photo', 'profile_image', 'profile_pic'
        ];
        
        for (let field of photoFields) {
            if (agent[field]) {
                console.log(`Found ${field} field:`, agent[field]);
                
                if (typeof agent[field] === 'string') {
                    agentPhotoUrl = agent[field];
                    console.log(`Using ${field} as string URL:`, agentPhotoUrl);
                    break;
                } else if (agent[field] && typeof agent[field] === 'object') {
                    console.log(`${field} is object, checking sub-fields:`, Object.keys(agent[field]));
                    
                    // Check various URL sub-fields
                    if (agent[field].url) {
                        agentPhotoUrl = agent[field].url;
                        console.log(`Using ${field}.url:`, agentPhotoUrl);
                        break;
                    } else if (agent[field].sizes && agent[field].sizes.medium) {
                        agentPhotoUrl = agent[field].sizes.medium;
                        console.log(`Using ${field}.sizes.medium:`, agentPhotoUrl);
                        break;
                    } else if (agent[field].sizes && agent[field].sizes.full) {
                        agentPhotoUrl = agent[field].sizes.full;
                        console.log(`Using ${field}.sizes.full:`, agentPhotoUrl);
                        break;
                    } else if (agent[field].src) {
                        agentPhotoUrl = agent[field].src;
                        console.log(`Using ${field}.src:`, agentPhotoUrl);
                        break;
                    }
                }
            }
        }
        
        console.log('Agent photo URL found:', agentPhotoUrl);
        
        // Agent photo with proper error handling and positioning
        function addAgentPhotoPlaceholder() {
            const agentPhotoPlaceholder = new fabric.Circle({
                left: 90,
                top: agentTop + 35,
                radius: 35, // Back to 35px radius (70px diameter)
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
            fabric.Image.fromURL(agentPhotoUrl, function(img) {
                console.log('Agent photo loaded successfully, original size:', img.width, 'x', img.height);
                
                // Create a simple circular container approach
                const circleSize = 70; // Circle diameter
                const circleRadius = circleSize / 2; // 35px radius
                const centerX = 90;
                const centerY = agentTop + 35;
                
                // Calculate scale to cover the circle completely
                const scaleX = circleSize / img.width;
                const scaleY = circleSize / img.height;
                const scale = Math.max(scaleX, scaleY);
                
                console.log('Simple scaling approach:', {
                    circleSize: circleSize,
                    originalImage: { width: img.width, height: img.height },
                    scaleX: scaleX,
                    scaleY: scaleY,
                    finalScale: scale,
                    finalSize: { width: img.width * scale, height: img.height * scale }
                });
                
                // Position and scale the image
                img.set({
                    left: centerX,
                    top: centerY,
                    originX: 'center',
                    originY: 'center',
                    scaleX: scale,
                    scaleY: scale,
                    selectable: false,
                    crossOrigin: 'anonymous'
                });
                
                // Create a simple circular mask
                const circleMask = new fabric.Circle({
                    radius: circleRadius,
                    left: centerX,
                    top: centerY,
                    originX: 'center',
                    originY: 'center',
                    absolutePositioned: true
                });
                
                // Apply the mask
                img.clipPath = circleMask;
                
                // Add to canvas
                canvas.add(img);
                canvas.renderAll();
                
                console.log('Agent photo added successfully with simple approach');
            }, { 
                crossOrigin: 'anonymous',
                onError: function() {
                    console.log('Agent photo failed to load from URL:', agentPhotoUrl, 'using placeholder');
                    addAgentPhotoPlaceholder();
                }
            });
        } else {
            console.log('No agent photo URL found, using placeholder');
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
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(agentEmailText);
        
        const emailIcon = new fabric.Text(getFontAwesomeIcon('envelope'), {
            left: 140,
            top: agentTop + 45,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(agentPhoneText);
        
        const phoneIcon = new fabric.Text(getFontAwesomeIcon('phone'), {
            left: 140,
            top: agentTop + 62,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(phoneIcon);

        // Company logo section - using actual Parker Group logo
        const logoUrl = '/wp-content/uploads/primarylogo.png';
        
        fabric.Image.fromURL(logoUrl, function(logoImg) {
            // Scale logo to fit within designated space
            const maxWidth = 200;
            const maxHeight = 100;
            const scale = Math.min(maxWidth / logoImg.width, maxHeight / logoImg.height);
            
            logoImg.set({
                left: 675,
                top: 840,
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
                    textBaseline: 'alphabetic',
                    originX: 'center',
                    originY: 'center',
                    selectable: false
                });
                canvas.add(logoText);
            }
        });

        // Company tagline with better positioning
        const taglineText = new fabric.Text('find your happy place', {
            left: 675,
            top: 890,
            fontSize: 12,
            fontFamily: 'Poppins, sans-serif',
            fontWeight: '400',
            fill: '#51bae0',
            textAlign: 'center',
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(contactEmail);

        const contactEmailIcon = new fabric.Text(getFontAwesomeIcon('envelope'), {
            left: 625,
            top: 1005,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(contactPhone);

        const contactPhoneIcon = new fabric.Text(getFontAwesomeIcon('phone'), {
            left: 625,
            top: 1022,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            textBaseline: 'alphabetic',
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
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(contactAddress);

        const contactLocationIcon = new fabric.Text(getFontAwesomeIcon('location-dot'), {
            left: 625,
            top: 1039,
            fontSize: 11,
            fontFamily: 'FontAwesome',
            fill: '#51bae0',
            textBaseline: 'alphabetic',
            selectable: false
        });
        canvas.add(contactLocationIcon);
        
        // Generate QR code (placeholder for now)
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
        if (!canvas) return;

        // Check if jsPDF is available (it's loaded via UMD, so check window.jspdf)
        const { jsPDF } = window.jspdf || {};
        
        if (!jsPDF) {
            alert('PDF generation not available. Please try PNG download.');
            return;
        }

        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        // Calculate dimensions to fit A4 (210mm x 297mm)
        const canvasAspectRatio = canvas.width / canvas.height;
        const a4AspectRatio = 210 / 297;
        
        let imgWidth, imgHeight;
        
        if (canvasAspectRatio > a4AspectRatio) {
            // Canvas is wider, fit to width
            imgWidth = 210;
            imgHeight = 210 / canvasAspectRatio;
        } else {
            // Canvas is taller, fit to height
            imgHeight = 297;
            imgWidth = 297 * canvasAspectRatio;
        }
        
        // Center the image on the page
        const x = (210 - imgWidth) / 2;
        const y = (297 - imgHeight) / 2;
        
        pdf.addImage(imgData, 'PNG', x, y, imgWidth, imgHeight);
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
        console.log('Getting property description from data:', data);
        console.log('Data structure keys:', Object.keys(data));
        console.log('Listing structure keys:', data.listing ? Object.keys(data.listing) : 'No listing object');
        
        // Bridge function for property description with multiple fallback options
        const descriptionFields = [
            data.listing?.property_description,
            data.listing?.short_description,
            data.listing?.description, 
            data.listing?.public_remarks,
            data.listing?.marketing_remarks,
            data.listing?.brief_description,
            data.listing?.remarks,
            data.property_description,
            data.description,
            data.public_remarks,
            data.marketing_remarks,
            data.brief_description,
            data.remarks
        ];
        
        console.log('Checking description fields:', descriptionFields);
        
        // Find first non-empty description
        for (let desc of descriptionFields) {
            if (desc && typeof desc === 'string' && desc.trim().length > 0) {
                console.log('Found description:', desc.substring(0, 100) + '...');
                return String(desc.trim()); // Ensure string return
            }
        }
        
        console.log('No description found, using fallback');
        return 'Beautiful property with great potential. Contact us for more details.'; // Better fallback
    }

    function generateQRCode(propertyData) {
        // Get the listing URL from various possible fields
        const listingUrl = propertyData.listing?.listing_url || 
                          propertyData.listing?.url || 
                          propertyData.listing?.permalink || 
                          propertyData.listing_url || 
                          propertyData.url || 
                          propertyData.permalink ||
                          'https://theparkergroup.com'; // Fallback URL
        
        console.log('Generating QR code for URL:', listingUrl);
        console.log('QRCode library available:', typeof QRCode !== 'undefined');
        console.log('Property data for QR:', propertyData);
        
        // Use qr.js library if available, otherwise use online QR service
        if (typeof QRCode !== 'undefined') {
            console.log('Using QRCode library for generation');
            // Use qr.js library (if loaded)
            try {
                const qrCanvas = document.createElement('canvas');
                QRCode.toCanvas(qrCanvas, listingUrl, {
                    width: 60,
                    margin: 1,
                    color: {
                        dark: '#082f49',
                        light: '#f5f5f4'
                    }
                }, function(error) {
                    if (error) {
                        console.error('QR Code generation error:', error);
                        generateQRCodeFallback(listingUrl);
                        return;
                    }
                    
                    console.log('QR code canvas generated successfully');
                    
                    // Convert canvas to fabric.js image
                    const qrDataUrl = qrCanvas.toDataURL();
                    fabric.Image.fromURL(qrDataUrl, function(qrImg) {
                        qrImg.set({
                            left: 530,
                            top: 1000,
                            width: 60,
                            height: 60,
                            selectable: false
                        });
                        
                        // Remove the placeholder pattern and add the real QR code
                        const objectsToRemove = [];
                        canvas.getObjects().forEach(obj => {
                            if ((obj.left >= 530 && obj.left <= 590) && (obj.top >= 1000 && obj.top <= 1060) && obj.type === 'rect') {
                                objectsToRemove.push(obj);
                            }
                        });
                        
                        objectsToRemove.forEach(obj => canvas.remove(obj));
                        
                        canvas.add(qrImg);
                        canvas.renderAll();
                        console.log('QR code generated and added to canvas successfully');
                    });
                });
            } catch (error) {
                console.error('Error with QR library:', error);
                generateQRCodeFallback(listingUrl);
            }
        } else {
            console.log('QRCode library not available, using fallback');
            // Fallback to online QR code service
            generateQRCodeFallback(listingUrl);
        }
    }
    
    function generateQRCodeFallback(url) {
        // Use Google Charts API as fallback
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=${encodeURIComponent(url)}&bgcolor=f5f5f4&color=082f49`;
        
        console.log('Using fallback QR service for URL:', url);
        
        fabric.Image.fromURL(qrUrl, function(qrImg) {
            qrImg.set({
                left: 530,
                top: 1000,
                width: 60,
                height: 60,
                selectable: false,
                crossOrigin: 'anonymous'
            });
            
            // Remove the placeholder pattern
            const objectsToRemove = [];
            canvas.getObjects().forEach(obj => {
                if ((obj.left >= 530 && obj.left <= 590) && (obj.top >= 1000 && obj.top <= 1060) && obj.type === 'rect') {
                    objectsToRemove.push(obj);
                }
            });
            
            objectsToRemove.forEach(obj => canvas.remove(obj));
            
            canvas.add(qrImg);
            canvas.renderAll();
            console.log('Fallback QR code generated and added to canvas');
        }, {
            crossOrigin: 'anonymous',
            onError: function() {
                console.log('QR code generation failed, keeping placeholder');
            }
        });
    }

    // Attach functions to the window object for global access
    window.downloadPNG = downloadPNG;
    window.downloadPDF = downloadPDF;

})(jQuery);