<?php
/**
 * Template Part: Search Form
 * 
 * This template part displays the global property search form.
 * 
 * @package HappyPlace
 */

$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : '';
$beds = isset($_GET['beds']) ? intval($_GET['beds']) : '';
$baths = isset($_GET['baths']) ? intval($_GET['baths']) : '';
$property_type = isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '';
$location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
?>

<form role="search" method="get" class="hph-search-form hph-card hph-p-6" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="hidden" name="post_type" value="listing">
    
    <div class="hph-grid hph-gap-4 md:hph-grid-cols-2 lg:hph-grid-cols-4">
        <div class="hph-form-group">
            <label for="location" class="hph-form-label"><?php esc_html_e('Location', 'happy-place'); ?></label>
            <div class="hph-input-group">
                <span class="hph-input-group-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </span>
                <input type="text" 
                       id="location" 
                       name="location" 
                       value="<?php echo esc_attr($location); ?>" 
                       placeholder="<?php esc_attr_e('City, State, or ZIP', 'happy-place'); ?>" 
                       class="hph-form-input" 
                       autocomplete="off">
            </div>
        </div>

        <div class="hph-form-group">
            <label class="hph-form-label"><?php esc_html_e('Price Range', 'happy-place'); ?></label>
            <div class="hph-grid hph-grid-cols-2 hph-gap-2">
                <div class="hph-input-group">
                    <span class="hph-input-group-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </span>
                    <input type="number" 
                           id="min_price" 
                           name="min_price" 
                           value="<?php echo esc_attr($min_price); ?>" 
                           placeholder="<?php esc_attr_e('Min', 'happy-place'); ?>" 
                           class="hph-form-input">
                </div>
                <div class="hph-input-group">
                    <span class="hph-input-group-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </span>
                    <input type="number" 
                           id="max_price" 
                           name="max_price" 
                           value="<?php echo esc_attr($max_price); ?>" 
                           placeholder="<?php esc_attr_e('Max', 'happy-place'); ?>" 
                           class="hph-form-input">
                </div>
            </div>
        </div>

        <div class="hph-form-group">
            <label for="beds" class="hph-form-label"><?php esc_html_e('Bedrooms', 'happy-place'); ?></label>
            <div class="hph-input-group">
                <span class="hph-input-group-icon">
                    <i class="fas fa-bed"></i>
                </span>
                <select id="beds" name="beds" class="hph-form-select">
                    <option value=""><?php esc_html_e('Any Beds', 'happy-place'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($beds, $i); ?>>
                            <?php echo $i . '+' . _n(' Bed', ' Beds', $i, 'happy-place'); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="hph-form-group">
            <label for="baths" class="hph-form-label"><?php esc_html_e('Bathrooms', 'happy-place'); ?></label>
            <div class="hph-input-group">
                <span class="hph-input-group-icon">
                    <i class="fas fa-bath"></i>
                </span>
                <select id="baths" name="baths" class="hph-form-select">
                    <option value=""><?php esc_html_e('Any Baths', 'happy-place'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($baths, $i); ?>>
                            <?php echo $i . '+' . _n(' Bath', ' Baths', $i, 'happy-place'); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="hph-form-group lg:hph-col-span-4">
            <label for="property_type" class="hph-form-label"><?php esc_html_e('Property Type', 'happy-place'); ?></label>
            <div class="hph-input-group">
                <span class="hph-input-group-icon">
                    <i class="fas fa-home"></i>
                </span>
                <select id="property_type" name="property_type" class="hph-form-select">
                    <option value=""><?php esc_html_e('Any Type', 'happy-place'); ?></option>
                    <?php 
                    $types = get_terms([
                        'taxonomy' => 'property_type',
                        'hide_empty' => false,
                    ]);
                    if (!is_wp_error($types)) :
                        foreach ($types as $type) : ?>
                            <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($property_type, $type->slug); ?>>
                                <?php echo esc_html($type->name); ?>
                            </option>
                        <?php endforeach;
                    endif; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="hph-form-actions hph-mt-6 hph-text-center">
        <button type="submit" class="hph-btn hph-btn--primary hph-w-full md:hph-w-auto">
            <i class="fas fa-search hph-mr-2"></i>
            <?php esc_html_e('Search Properties', 'happy-place'); ?>
        </button>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    // Toggle advanced search
    $('.search-advanced__toggle').on('click', function() {
        $('.search-advanced__content').slideToggle();
        $(this).toggleClass('active');
    });

    // Location autocomplete
    $('#location').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: happy_place.ajax_url,
                dataType: "json",
                data: {
                    action: 'happy_place_location_search',
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2
    });
});
</script>
