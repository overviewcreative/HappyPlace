/**
 * Single Listing JavaScript
 * Happy Place Real Estate Theme
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initGallery();
        initMap();
        initMortgageCalculator();
        initContactForm();
        initFavoriteButton();
        initNearbyPlaces();
    });

    /**
     * Initialize Photo Gallery
     */
    function initGallery() {
        const $galleryItems = $('.hph-gallery-item');
        const $galleryModal = $('#gallery-modal');
        const $modalImage = $('.hph-gallery-modal-image');
        const $modalClose = $('.hph-gallery-modal-close');
        const $galleryPrev = $('.hph-gallery-prev');
        const $galleryNext = $('.hph-gallery-next');
        const $galleryThumbs = $('.hph-gallery-thumb');
        let currentIndex = 0;
        const totalImages = $galleryThumbs.length;

        // Open gallery modal when clicking on an image
        $galleryItems.on('click', function() {
            const imageUrl = $(this).find('img').data('full');
            const index = $(this).index();
            
            openGalleryModal(imageUrl, index);
        });

        // Also handle the "more photos" button
        $('.hph-gallery-more').on('click', function() {
            const firstHiddenImage = $galleryThumbs.eq(5).find('img');
            const imageUrl = firstHiddenImage.data('full');
            
            openGalleryModal(imageUrl, 5);
        });

        // Open the gallery modal with specified image and index
        function openGalleryModal(imageUrl, index) {
            $modalImage.attr('src', imageUrl);
            currentIndex = index;
            updateActiveThumb();
            $galleryModal.addClass('active');
            
            // Prevent body scrolling
            $('body').css('overflow', 'hidden');
        }

        // Close modal when clicking the close button
        $modalClose.on('click', function() {
            $galleryModal.removeClass('active');
            
            // Restore body scrolling
            $('body').css('overflow', '');
        });

        // Close modal when clicking outside the image
        $galleryModal.on('click', function(e) {
            if ($(e.target).hasClass('hph-gallery-modal')) {
                $galleryModal.removeClass('active');
                $('body').css('overflow', '');
            }
        });

        // Navigate to previous image
        $galleryPrev.on('click', function(e) {
            e.stopPropagation();
            currentIndex = (currentIndex - 1 + totalImages) % totalImages;
            updateGalleryImage();
        });

        // Navigate to next image
        $galleryNext.on('click', function(e) {
            e.stopPropagation();
            currentIndex = (currentIndex + 1) % totalImages;
            updateGalleryImage();
        });

        // Thumbnail navigation
        $galleryThumbs.on('click', function(e) {
            e.stopPropagation();
            currentIndex = $(this).data('index');
            updateGalleryImage();
        });

        // Update the modal image based on current index
        function updateGalleryImage() {
            const $currentThumb = $galleryThumbs.eq(currentIndex);
            const imageUrl = $currentThumb.find('img').data('full');
            
            $modalImage.fadeOut(200, function() {
                $(this).attr('src', imageUrl).fadeIn(200);
            });
            
            updateActiveThumb();
        }

        // Update active thumbnail
        function updateActiveThumb() {
            $galleryThumbs.removeClass('active');
            $galleryThumbs.eq(currentIndex).addClass('active');
        }

        // Keyboard navigation
        $(document).keydown(function(e) {
            if (!$galleryModal.hasClass('active')) return;
            
            if (e.keyCode === 37) { // Left arrow
                $galleryPrev.trigger('click');
            } else if (e.keyCode === 39) { // Right arrow
                $galleryNext.trigger('click');
            } else if (e.keyCode === 27) { // Escape
                $modalClose.trigger('click');
            }
        });
    }

    /**
     * Initialize Google Maps
     */
    function initMap() {
        const $propertyMap = $('#property-map');
        
        if ($propertyMap.length === 0 || typeof google === 'undefined' || typeof google.maps === 'undefined') {
            return;
        }
        
        const lat = parseFloat($propertyMap.data('lat'));
        const lng = parseFloat($propertyMap.data('lng'));
        const title = $propertyMap.data('title');
        const address = $propertyMap.data('address');
        
        if (isNaN(lat) || isNaN(lng)) {
            return;
        }
        
        const mapOptions = {
            center: { lat: lat, lng: lng },
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: true,
            scrollwheel: false,
            styles: [
                {
                    "featureType": "administrative",
                    "elementType": "labels.text.fill",
                    "stylers": [{"color": "#444444"}]
                },
                {
                    "featureType": "landscape",
                    "elementType": "all",
                    "stylers": [{"color": "#f2f2f2"}]
                },
                {
                    "featureType": "poi",
                    "elementType": "all",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "road",
                    "elementType": "all",
                    "stylers": [{"saturation": -100}, {"lightness": 45}]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "all",
                    "stylers": [{"visibility": "simplified"}]
                },
                {
                    "featureType": "road.arterial",
                    "elementType": "labels.icon",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "transit",
                    "elementType": "all",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "water",
                    "elementType": "all",
                    "stylers": [{"color": "#0ea5e9"}, {"visibility": "on"}]
                }
            ]
        };
        
        const map = new google.maps.Map($propertyMap[0], mapOptions);
        
        const marker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
            title: title,
            animation: google.maps.Animation.DROP,
            icon: {
                url: hph_vars.theme_url + '/assets/images/map-marker.png',
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div class="hph-map-info-window">
                    <h4>${title}</h4>
                    <p>${address}</p>
                </div>
            `
        });
        
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });
        
        // Store map in global variable for nearby places feature
        window.propertyMap = map;
        window.propertyMarker = marker;
        
        // Initialize map bounds for nearby places
        window.mapBounds = new google.maps.LatLngBounds();
        window.mapBounds.extend(marker.position);
    }

    /**
     * Initialize Nearby Places
     */
    function initNearbyPlaces() {
        if (typeof google === 'undefined' || typeof google.maps === 'undefined' || !window.propertyMap) {
            return;
        }
        
        const $nearbyButtons = $('.hph-nearby-places-btn');
        let placesService;
        let nearbyMarkers = [];
        
        if (window.propertyMap) {
            placesService = new google.maps.places.PlacesService(window.propertyMap);
        }
        
        $nearbyButtons.on('click', function() {
            const placeType = $(this).data('type');
            $nearbyButtons.removeClass('active');
            $(this).addClass('active');
            
            // Clear existing markers
            clearNearbyMarkers();
            
            // Search for nearby places
            const request = {
                location: window.propertyMarker.getPosition(),
                radius: 1500, // 1.5km radius
                type: placeType
            };
            
            placesService.nearbySearch(request, handleNearbyPlacesResults);
        });
        
        function handleNearbyPlacesResults(results, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                // Reset bounds to property marker
                window.mapBounds = new google.maps.LatLngBounds();
                window.mapBounds.extend(window.propertyMarker.getPosition());
                
                // Add markers for results
                for (let i = 0; i < Math.min(results.length, 10); i++) {
                    createNearbyMarker(results[i]);
                }
                
                // Fit map to bounds
                window.propertyMap.fitBounds(window.mapBounds);
            }
        }
        
        function createNearbyMarker(place) {
            const marker = new google.maps.Marker({
                map: window.propertyMap,
                position: place.geometry.location,
                title: place.name,
                icon: {
                    url: place.icon,
                    scaledSize: new google.maps.Size(24, 24)
                },
                animation: google.maps.Animation.DROP
            });
            
            nearbyMarkers.push(marker);
            window.mapBounds.extend(place.geometry.location);
            
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="hph-map-info-window">
                        <h4>${place.name}</h4>
                        <p>${place.vicinity}</p>
                        <div class="hph-place-rating">
                            ${place.rating ? '★'.repeat(Math.round(place.rating)) + '☆'.repeat(5 - Math.round(place.rating)) + ' ' + place.rating : 'No ratings yet'}
                        </div>
                    </div>
                `
            });
            
            marker.addListener('click', function() {
                infoWindow.open(window.propertyMap, marker);
            });
        }
        
        function clearNearbyMarkers() {
            for (let i = 0; i < nearbyMarkers.length; i++) {
                nearbyMarkers[i].setMap(null);
            }
            nearbyMarkers = [];
        }
    }

    /**
     * Initialize Mortgage Calculator
     */
    function initMortgageCalculator() {
        const $calculator = $('#mortgage-calculator-form');
        
        if ($calculator.length === 0) {
            return;
        }
        
        const $propertyPrice = $('#property-price');
        const $downPayment = $('#down-payment');
        const $downPaymentSlider = $('#down-payment-slider');
        const $interestRate = $('#interest-rate');
        const $interestRateSlider = $('#interest-rate-slider');
        const $loanTerm = $('#loan-term');
        const $loanTermSlider = $('#loan-term-slider');
        const $monthlyPayment = $('#monthly-payment');
        
        // Initialize values
        const propertyPrice = parseFloat($propertyPrice.val());
        
        // Initial calculation
        calculateMortgage();
        
        // Bind events
        $downPayment.on('input', function() {
            $downPaymentSlider.val($(this).val());
            calculateMortgage();
        });
        
        $downPaymentSlider.on('input', function() {
            $downPayment.val($(this).val());
            calculateMortgage();
        });
        
        $interestRate.on('input', function() {
            $interestRateSlider.val($(this).val());
            calculateMortgage();
        });
        
        $interestRateSlider.on('input', function() {
            $interestRate.val($(this).val());
            calculateMortgage();
        });
        
        $loanTerm.on('input', function() {
            $loanTermSlider.val($(this).val());
            calculateMortgage();
        });
        
        $loanTermSlider.on('input', function() {
            $loanTerm.val($(this).val());
            calculateMortgage();
        });
        
        function calculateMortgage() {
            const downPayment = parseFloat($downPayment.val()) || 0;
            const interestRate = parseFloat($interestRate.val()) || 0;
            const loanTerm = parseInt($loanTerm.val()) || 30;
            
            const loanAmount = propertyPrice - downPayment;
            const monthlyInterest = interestRate / 100 / 12;
            const totalPayments = loanTerm * 12;
            
            let monthlyPayment = 0;
            
            if (loanAmount > 0 && interestRate > 0) {
                monthlyPayment = loanAmount * 
                    (monthlyInterest * Math.pow(1 + monthlyInterest, totalPayments)) / 
                    (Math.pow(1 + monthlyInterest, totalPayments) - 1);
            } else if (loanAmount > 0) {
                // Simple division if interest rate is 0
                monthlyPayment = loanAmount / totalPayments;
            }
            
            $monthlyPayment.text('$' + monthlyPayment.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        }
    }

    /**
     * Initialize Contact Form
     */
    function initContactForm() {
        const $form = $('#property-inquiry-form');
        const $successMessage = $('#inquiry-success');
        
        if ($form.length === 0) {
            return;
        }
        
        $form.on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Prepare form data
            const formData = {
                action: 'hph_property_inquiry',
                nonce: hph_vars.nonce,
                property_id: $form.find('input[name="property_id"]').val(),
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val(),
                phone: $form.find('input[name="phone"]').val(),
                message: $form.find('textarea[name="message"]').val(),
                consent: $form.find('input[name="consent"]').is(':checked') ? 1 : 0
            };
            
            // Submit form via AJAX
            $.ajax({
                type: 'POST',
                url: hph_vars.ajax_url,
                data: formData,
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        $form[0].reset();
                        $successMessage.fadeIn().delay(5000).fadeOut();
                    } else {
                        alert(response.data.message || 'There was an error submitting your inquiry. Please try again.');
                    }
                },
                error: function() {
                    alert('There was an error connecting to the server. Please try again later.');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Message');
                }
            });
        });
        
        function validateForm() {
            let isValid = true;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // Reset validation styles
            $form.find('.error').removeClass('error');
            
            // Validate name
            if ($form.find('input[name="name"]').val().trim() === '') {
                $form.find('input[name="name"]').addClass('error');
                isValid = false;
            }
            
            // Validate email
            const email = $form.find('input[name="email"]').val().trim();
            if (email === '' || !emailRegex.test(email)) {
                $form.find('input[name="email"]').addClass('error');
                isValid = false;
            }
            
            // Validate message
            if ($form.find('textarea[name="message"]').val().trim() === '') {
                $form.find('textarea[name="message"]').addClass('error');
                isValid = false;
            }
            
            // Validate consent
            if (!$form.find('input[name="consent"]').is(':checked')) {
                $form.find('input[name="consent"]').addClass('error');
                isValid = false;
            }
            
            return isValid;
        }
    }

    /**
     * Initialize Favorite Button
     */
    function initFavoriteButton() {
        const $favoriteBtn = $('.hph-btn-favorite');
        
        if ($favoriteBtn.length === 0) {
            return;
        }
        
        $favoriteBtn.on('click', function() {
            const $btn = $(this);
            const propertyId = $btn.data('id');
            const nonce = $btn.data('nonce');
            
            // Check if user is logged in
            if (!hph_vars.is_user_logged_in) {
                // Redirect to login page with redirect back to this page
                window.location.href = hph_vars.login_url + '?redirect_to=' + encodeURIComponent(window.location.href);
                return;
            }
            
            // Toggle favorite status
            $.ajax({
                type: 'POST',
                url: hph_vars.ajax_url,
                data: {
                    action: 'hph_toggle_favorite',
                    nonce: nonce,
                    property_id: propertyId
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.is_favorite) {
                            $btn.addClass('is-favorite');
                            $btn.find('i').removeClass('far').addClass('fas');
                            $btn.find('span').text('Saved');
                        } else {
                            $btn.removeClass('is-favorite');
                            $btn.find('i').removeClass('fas').addClass('far');
                            $btn.find('span').text('Save');
                        }
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Schedule Showing Modal
     */
    $('.hph-btn-schedule').on('click', function() {
        // This would typically open a modal for scheduling
        // For this template, we'll just scroll to the contact form
        $('html, body').animate({
            scrollTop: $('#property-inquiry-form').offset().top - 100
        }, 500);
        
        // Update the message field with scheduling text
        $('#inquiry-message').val('I would like to schedule a showing for this property. Please contact me with available times.');
    });

    /**
     * Share Button Functionality
     */
    $('.hph-btn-share').on('click', function() {
        // Check if Web Share API is supported
        if (navigator.share) {
            navigator.share({
                title: document.title,
                url: window.location.href
            })
            .catch(console.error);
        } else {
            // Fallback for browsers that don't support Web Share API
            const $shareBtn = $(this);
            
            // Create a temporary input to copy the URL
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(window.location.href).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show feedback
            const $span = $shareBtn.find('span');
            const originalText = $span.text();
            $span.text('Link Copied!');
            
            setTimeout(function() {
                $span.text(originalText);
            }, 2000);
        }
    });

})(jQuery);