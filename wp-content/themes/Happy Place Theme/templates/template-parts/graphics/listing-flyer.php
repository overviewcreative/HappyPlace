<?php
// Check if Happy Place Plugin is active
if (!function_exists('get_field')) {
    wp_die('This template requires the Happy Place Plugin to be installed and activated.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Flyer - <?php echo get_field('street_address'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: white;
            width: 8.5in;
            height: 11in;
            margin: 0 auto;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* Header - Listing Status */
        .hph-flyer-header {
            background: #51bae0;
            height: 120px;
            display: flex;
            align-items: center;
            padding-left: 60px;
        }

        .hph-listing-status {
            color: white;
            font-size: 80px;
            font-weight: 800;
            letter-spacing: 3px;
            margin: 0;
        }

        /* Main Layout Container */
        .hph-flyer-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Property Images Section */
        .hph-property-hero {
            height: 380px;
            background: #f8f9fa;
            background-image: url('<?php echo get_field('featured_image')['url']; ?>');
            background-size: cover;
            background-position: center;
            position: relative;
            padding: 20px;
        }

        .hph-property-hero.placeholder {
            background: #e8f4f8;
            border: 2px dashed #51bae0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #51bae0;
            font-size: 18px;
            font-weight: 600;
        }

        /* Interior Images Gallery */
        .hph-property-gallery {
            height: 200px;
            display: flex;
        }

        .hph-gallery-image {
            flex: 1;
            background: #f8f9fa;
            background-size: cover;
            background-position: center;
            position: relative;
            padding: 15px;
        }

        .hph-gallery-image:nth-child(1) {
            background-image: url('<?php echo get_field('property_gallery')[0]['url']; ?>');
        }

        .hph-gallery-image:nth-child(2) {
            background-image: url('<?php echo get_field('property_gallery')[1]['url']; ?>');
        }

        .hph-gallery-image:nth-child(3) {
            background-image: url('<?php echo get_field('property_gallery')[2]['url']; ?>');
        }

        .hph-gallery-image.placeholder {
            background: #e8f4f8;
            border: 2px dashed #51bae0;
            border-right: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #51bae0;
            font-size: 14px;
            font-weight: 600;
        }

        .hph-gallery-image:last-child.placeholder {
            border-right: 2px dashed #51bae0;
        }

        /* Price and Property Specs Bar */
        .hph-property-specs-bar {
            background: #0c4a6e;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 60px;
        }

        .hph-property-price {
            color: white;
            font-size: 56px;
            font-weight: 800;
            margin: 0;
        }

        .hph-property-specs {
            display: flex;
            align-items: center;
            gap: 45px;
        }

        .hph-spec-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 18px;
            font-weight: 500;
        }

        .hph-spec-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        /* Bottom Two-Column Layout */
        .hph-flyer-bottom {
            background: #51bae0;
            flex: 1;
            display: flex;
        }

        /* Left Column - Property Details & Agent */
        .hph-property-details {
            flex: 1;
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hph-property-address {
            color: white;
            font-size: 36px;
            font-weight: 600;
            margin: 0 0 8px 0;
            line-height: 1.1;
        }

        .hph-property-location {
            color: white;
            font-size: 20px;
            font-weight: 400;
            margin: 0 0 30px 0;
        }

        .hph-property-description {
            color: white;
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .hph-property-cta {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        /* Agent Info in Left Column */
        .hph-agent-info {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }

        .hph-agent-photo {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.9);
            background-size: cover;
            background-position: center;
            border: 3px solid white;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .hph-agent-photo.placeholder {
            border: 3px dashed #51bae0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #51bae0;
            font-size: 11px;
            font-weight: 600;
        }

        .hph-agent-details {
            flex: 1;
        }

        .hph-agent-name {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .hph-agent-title {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 15px 0;
        }

        .hph-agent-contact {
            color: white;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
        }

        .hph-agent-contact p {
            margin: 3px 0;
        }

        /* Right Column - Company Branding */
        .hph-company-branding {
            width: 300px;
            background: white;
            padding: 40px 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Company Logo Section */
        .hph-company-logo {
            text-align: center;
            margin-bottom: 50px;
        }

        .hph-logo-image {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: #51bae0;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hph-logo-image img {
            max-width: 80px;
            max-height: 80px;
        }

        .hph-logo-image svg {
            width: 60px;
            height: 60px;
            fill: white;
        }

        /* Default logo styling if no image */
        .hph-logo-placeholder {
            position: relative;
        }

        .hph-logo-house {
            width: 50px;
            height: 40px;
            background: white;
            position: relative;
            border-radius: 3px;
        }

        .hph-logo-house::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-bottom: 20px solid white;
        }

        .hph-logo-tree {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 25px;
            height: 25px;
            background: #51bae0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hph-logo-tree::before {
            content: '';
            width: 15px;
            height: 15px;
            background: white;
            border-radius: 50%;
        }

        .hph-company-name {
            color: #0c4a6e;
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }

        /* Office Contact Section */
        .hph-office-contact {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hph-qr-contact {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 30px;
            width: 100%;
        }

        .hph-qr-code {
            width: 80px;
            height: 80px;
            background: #000;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: 600;
            background-image:
                repeating-linear-gradient(0deg, #000, #000 3px, #fff 3px, #fff 6px),
                repeating-linear-gradient(90deg, #000, #000 3px, #fff 3px, #fff 6px);
        }

        .hph-office-info {
            flex: 1;
        }

        .hph-contact-heading {
            color: #51bae0;
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .hph-contact-details {
            color: #0c4a6e;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.3;
        }

        .hph-contact-details p {
            margin: 2px 0;
        }

        .hph-company-tagline {
            color: #0c4a6e;
            font-size: 14px;
            font-weight: 400;
            text-align: center;
            font-style: italic;
        }

        /* Print optimization */
        @media print {
            body {
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
            }
        }

        /* Image background utility */
        .hph-bg-image {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>

<body>
    <!-- Header with Listing Status -->
    <div class="hph-flyer-header">
        <h1 class="hph-listing-status">
            <?php
            $listing_status = get_field('listing_status');
            echo $listing_status ? strtoupper($listing_status) : 'FOR SALE';
            ?>
        </h1>
    </div>

    <!-- Main Content Layout -->
    <div class="hph-flyer-content">
        <!-- Hero Property Image -->
        <div class="hph-property-hero <?php echo get_field('featured_image') ? 'hph-bg-image' : 'placeholder'; ?>">
            <?php if (!get_field('featured_image')): ?>
                HERO IMAGE PLACEHOLDER
            <?php endif; ?>
        </div>

        <!-- Property Gallery -->
        <div class="hph-property-gallery">
            <?php
            $gallery = get_field('property_gallery');
            for ($i = 0; $i < 3; $i++):
                $has_image = isset($gallery[$i]) && $gallery[$i];
            ?>
                <div class="hph-gallery-image <?php echo $has_image ? 'hph-bg-image' : 'placeholder'; ?>"
                    <?php if ($has_image): ?>style="background-image: url('<?php echo $gallery[$i]['url']; ?>');" <?php endif; ?>>
                    <?php if (!$has_image): ?>
                        INTERIOR <?php echo $i + 1; ?>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Price and Property Specs -->
        <div class="hph-property-specs-bar">
            <div class="hph-property-price">
                $<?php echo number_format(get_field('price')); ?>
            </div>
            <div class="hph-property-specs">
                <div class="hph-spec-item">
                    <i class="fas fa-bed hph-spec-icon"></i>
                    <span><strong><?php echo get_field('bedrooms'); ?></strong> Bed</span>
                </div>
                <div class="hph-spec-item">
                    <i class="fas fa-bath hph-spec-icon"></i>
                    <span><strong><?php echo get_field('bathrooms'); ?></strong> Bath</span>
                </div>
                <div class="hph-spec-item">
                    <i class="fas fa-ruler-combined hph-spec-icon"></i>
                    <span><strong><?php echo number_format(get_field('square_footage')); ?></strong> Ft²</span>
                </div>
                <div class="hph-spec-item">
                    <i class="fas fa-expand-arrows-alt hph-spec-icon"></i>
                    <span><strong><?php echo get_field('lot_size'); ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Bottom Two-Column Layout -->
        <div class="hph-flyer-bottom">
            <!-- Left Column - Property Details & Agent -->
            <div class="hph-property-details">
                <div>
                    <h2 class="hph-property-address">
                        <?php echo get_field('street_address'); ?>
                    </h2>
                    <p class="hph-property-location">
                        <?php echo get_field('city') . ', ' . get_field('state') . ' ' . get_field('zip_code'); ?>
                    </p>
                    <p class="hph-property-description">
                        <?php echo get_field('description') ?: 'This beautiful property offers exceptional value and lifestyle. Contact us today to schedule your private showing and discover all the features this home has to offer.'; ?>
                    </p>
                    <p class="hph-property-cta">
                        Come see it for yourself and imagine the possibilities!
                    </p>

                    <!-- Agent Information -->
                    <div class="hph-agent-info">
                        <?php
                        $agent_id = get_field('listing_agent');
                        $agent_photo = get_field('agent_photo', 'user_' . $agent_id);
                        ?>
                        <div class="hph-agent-photo <?php echo $agent_photo ? 'hph-bg-image' : 'placeholder'; ?>"
                            <?php if ($agent_photo): ?>style="background-image: url('<?php echo $agent_photo['url']; ?>');" <?php endif; ?>>
                            <?php if (!$agent_photo): ?>
                                AGENT PHOTO
                            <?php endif; ?>
                        </div>
                        <div class="hph-agent-details">
                            <h3 class="hph-agent-name">
                                <?php echo get_field('agent_name', 'user_' . $agent_id) ?: get_userdata($agent_id)->display_name; ?>
                            </h3>
                            <p class="hph-agent-title">
                                <?php echo get_field('agent_title', 'user_' . $agent_id) ?: 'REALTOR®'; ?>
                            </p>
                            <div class="hph-agent-contact">
                                <p><?php echo get_field('agent_email', 'user_' . $agent_id) ?: get_userdata($agent_id)->user_email; ?></p>
                                <p><?php echo get_field('agent_phone', 'user_' . $agent_id); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Company Branding -->
            <div class="hph-company-branding">
                <!-- Company Logo -->
                <div class="hph-company-logo">
                    <div class="hph-logo-image">
                        <?php
                        $company_logo = get_field('company_logo', 'option');
                        if ($company_logo):
                        ?>
                            <img src="<?php echo $company_logo['url']; ?>" alt="Company Logo">
                        <?php else: ?>
                            <div class="hph-logo-placeholder">
                                <div class="hph-logo-house">
                                    <div class="hph-logo-tree"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="hph-company-name">
                        <?php echo get_field('company_name', 'option') ?: 'the parker group'; ?>
                    </h3>
                </div>

                <!-- Office Contact Information -->
                <div class="hph-office-contact">
                    <div class="hph-qr-contact">
                        <div class="hph-qr-code">
                            <img src="<?php echo hph_generate_listing_qr(get_the_ID()); ?>"
                                alt="Scan to view listing"
                                style="width: 100%; height: 100%;">
                        </div>
                        <div class="hph-office-info">
                            <h4 class="hph-contact-heading">Get in touch</h4>
                            <div class="hph-contact-details">
                                <p><?php echo get_field('office_email', 'option') ?: 'cheers@theparkergroup.com'; ?></p>
                                <p><?php echo get_field('office_phone', 'option') ?: '302-217-6692'; ?></p>
                                <p><?php echo get_field('office_address', 'option') ?: '673 N Bedford St. Georgetown, DE'; ?></p>
                            </div>
                        </div>
                    </div>
                    <p class="hph-company-tagline">
                        <?php echo get_field('company_tagline', 'option') ?: 'find your happy place'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>