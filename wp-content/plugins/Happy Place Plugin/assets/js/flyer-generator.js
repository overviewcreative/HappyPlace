/**
 * Flyer Generator JavaScript
 * 
 * @package HappyPlace
 * @subpackage Assets
 */

(function($) {
    'use strict';

    let canvas = null;
    let fabric = null;

    $(document).ready(function() {
        initializeFlyerGenerator();
    });

    function initializeFlyerGenerator() {
        // Initialize Fabric.js canvas
        if (typeof window.fabric !== 'undefined') {
            fabric = window.fabric;
            canvas = new fabric.Canvas('flyer-canvas', {
                width: 850,
                height: 1100,
                backgroundColor: '#ffffff'
            });
        }

        // Bind event handlers
        $('#generate-flyer').on('click', generateFlyer);
        $('#download-flyer').on('click', downloadPNG);
        $('#download-pdf').on('click', downloadPDF);
        $('#listing-select').on('change', updatePreview);
    }

    function generateFlyer() {
        const listingId = $('#listing-select').val();
        const template = $('#template-select').val();

        if (!listingId) {
            alert('Please select a listing first.');
            return;
        }

        if (!canvas) {
            alert('Canvas not initialized. Please refresh the page.');
            return;
        }

        showLoading(true);
        
        // Get listing data via AJAX
        $.ajax({
            url: flyerGenerator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_listing_data_for_flyer',
                listing_id: listingId,
                nonce: flyerGenerator.nonce
            },
            success: function(response) {
                if (response.success) {
                    createFlyerDesign(response.data, template);
                } else {
                    alert('Error loading listing data: ' + response.data);
                    showLoading(false);
                }
            },
            error: function() {
                alert('Error loading listing data. Please try again.');
                showLoading(false);
            }
        });
    }

    function createFlyerDesign(listingData, template) {
        // Clear canvas
        canvas.clear();
        canvas.backgroundColor = '#ffffff';

        // Apply template-specific design
        switch (template) {
            case 'parker_group':
                createParkerGroupDesign(listingData);
                break;
            case 'luxury':
                createLuxuryDesign(listingData);
                break;
            case 'modern':
                createModernDesign(listingData);
                break;
            default:
                createParkerGroupDesign(listingData);
        }

        canvas.renderAll();
        showLoading(false);
        $('#download-flyer, #download-pdf').show();
    }

    function createParkerGroupDesign(data) {
        // Blue background for entire flyer
        const background = new fabric.Rect({
            left: 0,
            top: 0,
            width: 850,
            height: 1100,
            fill: '#4ECDC4', // Teal blue color from image
            selectable: false
        });
        canvas.add(background);

        // "FOR SALE" text at top
        const forSaleText = new fabric.Text('FOR SALE', {
            left: 60,
            top: 40,
            fontSize: 72,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(forSaleText);

        // Main property photo area (large)
        let mainPhotoTop = 120;
        if (data.featured_image) {
            fabric.Image.fromURL(data.featured_image, function(img) {
                img.set({
                    left: 45,
                    top: mainPhotoTop,
                    width: 760,
                    height: 320,
                    scaleX: 760 / img.width,
                    scaleY: 320 / img.height,
                    selectable: false
                });
                canvas.add(img);
                canvas.renderAll();
            });
        } else {
            const mainPhoto = new fabric.Rect({
                left: 45,
                top: mainPhotoTop,
                width: 760,
                height: 320,
                fill: '#ffffff',
                stroke: '#cccccc',
                strokeWidth: 2,
                selectable: false
            });
            canvas.add(mainPhoto);
        }

        // Three smaller photos below main photo
        const smallPhotoTop = mainPhotoTop + 330;
        const photoWidth = 245;
        const photoHeight = 130;
        const photoSpacing = 12;

        for (let i = 0; i < 3; i++) {
            const smallPhoto = new fabric.Rect({
                left: 45 + (i * (photoWidth + photoSpacing)),
                top: smallPhotoTop,
                width: photoWidth,
                height: photoHeight,
                fill: '#ffffff',
                stroke: '#cccccc',
                strokeWidth: 1,
                selectable: false
            });
            canvas.add(smallPhoto);
        }

        // Dark teal section at bottom
        const bottomSection = new fabric.Rect({
            left: 0,
            top: smallPhotoTop + photoHeight + 20,
            width: 850,
            height: 50,
            fill: '#2C5F5D', // Dark teal
            selectable: false
        });
        canvas.add(bottomSection);

        // Property stats in dark section
        const statsY = smallPhotoTop + photoHeight + 35;
        const statsTextColor = '#ffffff';
        
        // Bed icon and text
        const bedText = new fabric.Text('🛏️ ' + (data.bedrooms || '3') + ' Bed', {
            left: 60,
            top: statsY,
            fontSize: 18,
            fontFamily: 'Arial, sans-serif',
            fill: statsTextColor,
            selectable: false
        });
        canvas.add(bedText);

        // Bath icon and text
        const bathText = new fabric.Text('🚿 ' + (data.bathrooms || '2') + ' Bath', {
            left: 200,
            top: statsY,
            fontSize: 18,
            fontFamily: 'Arial, sans-serif',
            fill: statsTextColor,
            selectable: false
        });
        canvas.add(bathText);

        // Square footage
        const sqftText = new fabric.Text('📐 ' + (data.sqft ? number_format(data.sqft) : '2,350') + ' Ft²', {
            left: 360,
            top: statsY,
            fontSize: 18,
            fontFamily: 'Arial, sans-serif',
            fill: statsTextColor,
            selectable: false
        });
        canvas.add(sqftText);

        // Acres
        const acresText = new fabric.Text('🌳 0.08 Acres', {
            left: 540,
            top: statsY,
            fontSize: 18,
            fontFamily: 'Arial, sans-serif',
            fill: statsTextColor,
            selectable: false
        });
        canvas.add(acresText);

        // Price (large, right side)
        if (data.price) {
            const price = new fabric.Text('$' + number_format(data.price), {
                left: 780,
                top: statsY - 5,
                fontSize: 32,
                fontFamily: 'Arial, sans-serif',
                fontWeight: 'bold',
                fill: statsTextColor,
                textAlign: 'right',
                originX: 'right',
                selectable: false
            });
            canvas.add(price);
        }

        // White content section
        const contentTop = smallPhotoTop + photoHeight + 80;
        const contentSection = new fabric.Rect({
            left: 0,
            top: contentTop,
            width: 850,
            height: 350,
            fill: '#ffffff',
            selectable: false
        });
        canvas.add(contentSection);

        // Address (large, blue)
        if (data.address) {
            const address = new fabric.Text(data.address, {
                left: 60,
                top: contentTop + 30,
                fontSize: 48,
                fontFamily: 'Arial, sans-serif',
                fontWeight: 'bold',
                fill: '#4ECDC4',
                selectable: false
            });
            canvas.add(address);
        }

        // City, State ZIP
        const cityStateText = new fabric.Text((data.city || 'Berlin') + ', ' + (data.state || 'MD') + ' ' + (data.zip || '21811'), {
            left: 60,
            top: contentTop + 90,
            fontSize: 24,
            fontFamily: 'Arial, sans-serif',
            fill: '#333333',
            selectable: false
        });
        canvas.add(cityStateText);

        // Description text
        const descriptionText = new fabric.Text(
            'Experience easy coastal living in this end-unit\ntownhouse in River Run. Enjoy golf course views, a\nfirst-floor primary suite, open living space, and\naccess to a pool, tennis, boat dock, and more—\nminutes from beaches and downtown Berlin.',
            {
                left: 60,
                top: contentTop + 140,
                fontSize: 16,
                fontFamily: 'Arial, sans-serif',
                fill: '#333333',
                lineHeight: 1.4,
                selectable: false
            }
        );
        canvas.add(descriptionText);

        // Parker Group logo area (right side)
        const logoArea = new fabric.Rect({
            left: 550,
            top: contentTop + 140,
            width: 240,
            height: 160,
            fill: '#ffffff',
            stroke: '#e0e0e0',
            strokeWidth: 1,
            selectable: false
        });
        canvas.add(logoArea);

        // Parker Group logo placeholder
        const logoText = new fabric.Text('🏠\nthe parker group\nfind your happy place', {
            left: 670,
            top: contentTop + 180,
            fontSize: 16,
            fontFamily: 'Arial, sans-serif',
            fill: '#4ECDC4',
            textAlign: 'center',
            originX: 'center',
            lineHeight: 1.3,
            selectable: false
        });
        canvas.add(logoText);

        // Agent section at bottom
        const agentTop = contentTop + 320;
        
        // Agent photo placeholder
        const agentPhoto = new fabric.Circle({
            left: 60,
            top: agentTop,
            radius: 35,
            fill: '#f0f0f0',
            stroke: '#cccccc',
            strokeWidth: 2,
            selectable: false
        });
        canvas.add(agentPhoto);

        // Agent info
        const agentName = new fabric.Text('Cheyenne Reardon', {
            left: 140,
            top: agentTop - 15,
            fontSize: 20,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: '#4ECDC4',
            selectable: false
        });
        canvas.add(agentName);

        const agentTitle = new fabric.Text('REALTOR®', {
            left: 140,
            top: agentTop + 10,
            fontSize: 14,
            fontFamily: 'Arial, sans-serif',
            fill: '#666666',
            selectable: false
        });
        canvas.add(agentTitle);

        const agentEmail = new fabric.Text('📧 cheyenne@theparkergroup.com', {
            left: 140,
            top: agentTop + 30,
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fill: '#666666',
            selectable: false
        });
        canvas.add(agentEmail);

        const agentPhone = new fabric.Text('📞 302.406.4367', {
            left: 140,
            top: agentTop + 45,
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fill: '#666666',
            selectable: false
        });
        canvas.add(agentPhone);

        // Contact section (right side)
        const contactSection = new fabric.Text('Get in touch\n📧 cheers@theparkergroup.com\n📞 302.217.6692\n📍 673 N Bedford St, Georgetown, DE', {
            left: 550,
            top: agentTop - 10,
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fill: '#666666',
            lineHeight: 1.4,
            selectable: false
        });
        canvas.add(contactSection);

        // QR Code placeholder
        const qrCode = new fabric.Rect({
            left: 450,
            top: agentTop,
            width: 60,
            height: 60,
            fill: '#333333',
            selectable: false
        });
        canvas.add(qrCode);

        const qrText = new fabric.Text('QR', {
            left: 480,
            top: agentTop + 20,
            fontSize: 16,
            fontFamily: 'Arial, sans-serif',
            fill: '#ffffff',
            textAlign: 'center',
            originX: 'center',
            selectable: false
        });
        canvas.add(qrText);
    }

    function createLuxuryDesign(data) {
        // Elegant gold and white design
        canvas.backgroundColor = '#fafafa';

        // Gold accent bar
        const accent = new fabric.Rect({
            left: 0,
            top: 50,
            width: 850,
            height: 8,
            fill: '#d4af37',
            selectable: false
        });
        canvas.add(accent);

        // Luxury styling with serif fonts and elegant layout
        if (data.price) {
            const price = new fabric.Text('$' + number_format(data.price), {
                left: 425,
                top: 80,
                fontSize: 48,
                fontFamily: 'Georgia, serif',
                fontWeight: 'normal',
                fill: '#2c2c2c',
                textAlign: 'center',
                originX: 'center',
                selectable: false
            });
            canvas.add(price);
        }

        if (data.address) {
            const address = new fabric.Text(data.address, {
                left: 425,
                top: 140,
                fontSize: 28,
                fontFamily: 'Georgia, serif',
                fill: '#666666',
                textAlign: 'center',
                originX: 'center',
                selectable: false
            });
            canvas.add(address);
        }
    }

    function createModernDesign(data) {
        // Clean, minimalist design
        canvas.backgroundColor = '#ffffff';

        // Modern typography and layout
        if (data.address) {
            const address = new fabric.Text(data.address.toUpperCase(), {
                left: 50,
                top: 50,
                fontSize: 20,
                fontFamily: 'Arial, sans-serif',
                fontWeight: 'normal',
                fill: '#333333',
                letterSpacing: 2,
                selectable: false
            });
            canvas.add(address);
        }

        if (data.price) {
            const price = new fabric.Text('$' + number_format(data.price), {
                left: 50,
                top: 100,
                fontSize: 60,
                fontFamily: 'Arial, sans-serif',
                fontWeight: '300',
                fill: '#000000',
                selectable: false
            });
            canvas.add(price);
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

    function showLoading(show) {
        $('.flyer-loading').toggle(show);
        $('#generate-flyer').prop('disabled', show);
    }

    function number_format(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

})(jQuery);
