<?php
/**
 * Property Search Form
 *
 * @package HappyPlace
 */
?>

<form class="hph-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="hidden" name="post_type" value="listing">
    
    <div class="hph-search-fields">
        <!-- Location Search -->
        <div class="hph-search-field hph-location-field">
            <label for="search-location" class="hph-search-label">
                <i class="fas fa-map-marker-alt"></i>
                <?php esc_html_e('Location', 'happy-place'); ?>
            </label>
            <input type="text" 
                   id="search-location" 
                   name="location" 
                   placeholder="<?php esc_attr_e('Enter city, neighborhood, or ZIP', 'happy-place'); ?>"
                   value="<?php echo esc_attr(get_query_var('location')); ?>">
        </div>

        <!-- Property Type -->
        <div class="hph-search-field hph-type-field">
            <label for="search-type" class="hph-search-label">
                <i class="fas fa-home"></i>
                <?php esc_html_e('Property Type', 'happy-place'); ?>
            </label>
            <select id="search-type" name="property_type">
                <option value=""><?php esc_html_e('Any Type', 'happy-place'); ?></option>
                <option value="house" <?php selected(get_query_var('property_type'), 'house'); ?>>
                    <?php esc_html_e('House', 'happy-place'); ?>
                </option>
                <option value="apartment" <?php selected(get_query_var('property_type'), 'apartment'); ?>>
                    <?php esc_html_e('Apartment', 'happy-place'); ?>
                </option>
                <option value="condo" <?php selected(get_query_var('property_type'), 'condo'); ?>>
                    <?php esc_html_e('Condo', 'happy-place'); ?>
                </option>
                <option value="townhouse" <?php selected(get_query_var('property_type'), 'townhouse'); ?>>
                    <?php esc_html_e('Townhouse', 'happy-place'); ?>
                </option>
            </select>
        </div>

        <!-- Price Range -->
        <div class="hph-search-field hph-price-field">
            <label for="search-price" class="hph-search-label">
                <i class="fas fa-dollar-sign"></i>
                <?php esc_html_e('Price Range', 'happy-place'); ?>
            </label>
            <select id="search-price" name="price_range">
                <option value=""><?php esc_html_e('Any Price', 'happy-place'); ?></option>
                <option value="0-200000" <?php selected(get_query_var('price_range'), '0-200000'); ?>>
                    <?php esc_html_e('Under $200,000', 'happy-place'); ?>
                </option>
                <option value="200000-400000" <?php selected(get_query_var('price_range'), '200000-400000'); ?>>
                    <?php esc_html_e('$200,000 - $400,000', 'happy-place'); ?>
                </option>
                <option value="400000-600000" <?php selected(get_query_var('price_range'), '400000-600000'); ?>>
                    <?php esc_html_e('$400,000 - $600,000', 'happy-place'); ?>
                </option>
                <option value="600000-800000" <?php selected(get_query_var('price_range'), '600000-800000'); ?>>
                    <?php esc_html_e('$600,000 - $800,000', 'happy-place'); ?>
                </option>
                <option value="800000-1000000" <?php selected(get_query_var('price_range'), '800000-1000000'); ?>>
                    <?php esc_html_e('$800,000 - $1,000,000', 'happy-place'); ?>
                </option>
                <option value="1000000-" <?php selected(get_query_var('price_range'), '1000000-'); ?>>
                    <?php esc_html_e('Over $1,000,000', 'happy-place'); ?>
                </option>
            </select>
        </div>

        <!-- Bedrooms -->
        <div class="hph-search-field hph-bedrooms-field">
            <label for="search-bedrooms" class="hph-search-label">
                <i class="fas fa-bed"></i>
                <?php esc_html_e('Bedrooms', 'happy-place'); ?>
            </label>
            <select id="search-bedrooms" name="bedrooms">
                <option value=""><?php esc_html_e('Any', 'happy-place'); ?></option>
                <option value="1" <?php selected(get_query_var('bedrooms'), '1'); ?>>1+</option>
                <option value="2" <?php selected(get_query_var('bedrooms'), '2'); ?>>2+</option>
                <option value="3" <?php selected(get_query_var('bedrooms'), '3'); ?>>3+</option>
                <option value="4" <?php selected(get_query_var('bedrooms'), '4'); ?>>4+</option>
                <option value="5" <?php selected(get_query_var('bedrooms'), '5'); ?>>5+</option>
            </select>
        </div>

        <!-- Search Button -->
        <div class="hph-search-field hph-submit-field">
            <button type="submit" class="hph-search-submit action-btn action-btn--primary">
                <i class="fas fa-search"></i>
                <?php esc_html_e('Search Properties', 'happy-place'); ?>
            </button>
        </div>
    </div>
</form>
