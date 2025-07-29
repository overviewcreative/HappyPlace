<?php
/**
 * Advanced Search Engine
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * 
 * Handles complex search queries, filtering, sorting, and analytics tracking
 * for the Happy Place listing system.
 */

namespace HappyPlace\Search;

if (!defined('ABSPATH')) {
    exit;
}

class Advanced_Search_Engine
{
    private static ?self $instance = null;
    private array $search_cache = [];
    private array $filter_cache = [];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('init', [$this, 'init_search_features']);
        add_action('wp_ajax_advanced_listing_search', [$this, 'handle_ajax_search']);
        add_action('wp_ajax_nopriv_advanced_listing_search', [$this, 'handle_ajax_search']);
        add_action('wp_ajax_get_search_suggestions', [$this, 'handle_search_suggestions']);
        add_action('wp_ajax_nopriv_get_search_suggestions', [$this, 'handle_search_suggestions']);
        
        // Track search analytics
        add_action('wp_ajax_track_search_interaction', [$this, 'track_search_interaction']);
        add_action('wp_ajax_nopriv_track_search_interaction', [$this, 'track_search_interaction']);
    }

    /**
     * Initialize search features
     */
    public function init_search_features(): void
    {
        // Register search query vars
        add_filter('query_vars', [$this, 'add_search_query_vars']);
        
        // Enhance main query for listings
        add_action('pre_get_posts', [$this, 'enhance_listing_query']);
        
        // Add search tracking
        add_action('wp_head', [$this, 'add_search_tracking_script']);
    }

    /**
     * Add custom query vars for advanced search
     */
    public function add_search_query_vars(array $vars): array
    {
        $search_vars = [
            'min_price', 'max_price', 'min_beds', 'max_beds', 'min_baths', 'max_baths',
            'property_type', 'listing_status', 'min_sqft', 'max_sqft', 'min_lot_size', 'max_lot_size',
            'location_search', 'features', 'lifestyle_features', 'investment_type',
            'search_boost', 'sort_by', 'search_radius', 'buyer_persona', 'transit_access',
            'min_walkability', 'min_cap_rate', 'max_cap_rate', 'search_tags'
        ];
        
        return array_merge($vars, $search_vars);
    }

    /**
     * Enhance main listing query with search parameters
     */
    public function enhance_listing_query(\WP_Query $query): void
    {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }

        if ($query->get('post_type') === 'listing' || $query->is_post_type_archive('listing')) {
            $this->apply_search_filters($query);
            $this->apply_search_sorting($query);
            $this->track_search_query($query);
        }
    }

    /**
     * Apply advanced search filters to query
     */
    private function apply_search_filters(\WP_Query $query): void
    {
        $meta_query = $query->get('meta_query') ?: [];
        $tax_query = $query->get('tax_query') ?: [];

        // Price range filtering
        if ($min_price = get_query_var('min_price')) {
            $meta_query[] = [
                'key' => 'price',
                'value' => (float) $min_price,
                'compare' => '>='
            ];
        }

        if ($max_price = get_query_var('max_price')) {
            $meta_query[] = [
                'key' => 'price',
                'value' => (float) $max_price,
                'compare' => '<='
            ];
        }

        // Bedroom/bathroom filtering
        if ($min_beds = get_query_var('min_beds')) {
            $meta_query[] = [
                'key' => 'bedrooms',
                'value' => (int) $min_beds,
                'compare' => '>='
            ];
        }

        if ($min_baths = get_query_var('min_baths')) {
            $meta_query[] = [
                'key' => 'bathrooms_total',
                'value' => (float) $min_baths,
                'compare' => '>='
            ];
        }

        // Square footage filtering
        if ($min_sqft = get_query_var('min_sqft')) {
            $meta_query[] = [
                'key' => 'square_footage',
                'value' => (int) $min_sqft,
                'compare' => '>='
            ];
        }

        if ($max_sqft = get_query_var('max_sqft')) {
            $meta_query[] = [
                'key' => 'square_footage',
                'value' => (int) $max_sqft,
                'compare' => '<='
            ];
        }

        // Property type filtering
        if ($property_type = get_query_var('property_type')) {
            $meta_query[] = [
                'key' => 'property_type',
                'value' => sanitize_text_field($property_type),
                'compare' => '='
            ];
        }

        // Listing status filtering
        if ($listing_status = get_query_var('listing_status')) {
            $meta_query[] = [
                'key' => 'listing_status',
                'value' => sanitize_text_field($listing_status),
                'compare' => '='
            ];
        }

        // Lifestyle features filtering
        if ($features = get_query_var('lifestyle_features')) {
            $features_array = is_array($features) ? $features : explode(',', $features);
            foreach ($features_array as $feature) {
                $meta_query[] = [
                    'key' => 'lifestyle_features',
                    'value' => sanitize_text_field($feature),
                    'compare' => 'LIKE'
                ];
            }
        }

        // Investment filtering
        if ($investment_type = get_query_var('investment_type')) {
            $meta_query[] = [
                'key' => 'investment_type',
                'value' => sanitize_text_field($investment_type),
                'compare' => '='
            ];
        }

        // Cap rate filtering
        if ($min_cap_rate = get_query_var('min_cap_rate')) {
            $meta_query[] = [
                'key' => 'cap_rate_estimated',
                'value' => (float) $min_cap_rate,
                'compare' => '>='
            ];
        }

        // Walkability filtering
        if ($min_walkability = get_query_var('min_walkability')) {
            $meta_query[] = [
                'key' => 'walkability_score',
                'value' => (int) $min_walkability,
                'compare' => '>='
            ];
        }

        // Apply meta query
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $query->set('meta_query', $meta_query);
        }

        // Apply taxonomy query
        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
    }

    /**
     * Apply search sorting to query
     */
    private function apply_search_sorting(\WP_Query $query): void
    {
        $sort_by = get_query_var('sort_by', 'relevance');

        switch ($sort_by) {
            case 'price_low':
                $query->set('meta_key', 'price');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'ASC');
                break;

            case 'price_high':
                $query->set('meta_key', 'price');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;

            case 'newest':
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
                break;

            case 'oldest':
                $query->set('orderby', 'date');
                $query->set('order', 'ASC');
                break;

            case 'sqft_large':
                $query->set('meta_key', 'square_footage');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;

            case 'sqft_small':
                $query->set('meta_key', 'square_footage');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'ASC');
                break;

            case 'relevance':
            default:
                // Custom relevance scoring
                $this->apply_relevance_sorting($query);
                break;
        }
    }

    /**
     * Apply relevance-based sorting with boost scores
     */
    private function apply_relevance_sorting(\WP_Query $query): void
    {
        // Use search boost score for relevance
        $query->set('meta_key', 'search_boost_score');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');

        // Add secondary sorting by date for ties
        $query->set('orderby', [
            'meta_value_num' => 'DESC',
            'date' => 'DESC'
        ]);
    }

    /**
     * Handle AJAX search requests
     */
    public function handle_ajax_search(): void
    {
        check_ajax_referer('advanced_search_nonce', 'nonce');

        $search_params = [
            'min_price' => floatval($_POST['min_price'] ?? 0),
            'max_price' => floatval($_POST['max_price'] ?? 0),
            'min_beds' => intval($_POST['min_beds'] ?? 0),
            'max_beds' => intval($_POST['max_beds'] ?? 0),
            'min_baths' => floatval($_POST['min_baths'] ?? 0),
            'max_baths' => floatval($_POST['max_baths'] ?? 0),
            'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
            'listing_status' => sanitize_text_field($_POST['listing_status'] ?? ''),
            'min_sqft' => intval($_POST['min_sqft'] ?? 0),
            'max_sqft' => intval($_POST['max_sqft'] ?? 0),
            'lifestyle_features' => array_map('sanitize_text_field', $_POST['lifestyle_features'] ?? []),
            'sort_by' => sanitize_text_field($_POST['sort_by'] ?? 'relevance'),
            'per_page' => intval($_POST['per_page'] ?? 12)
        ];

        $results = $this->execute_advanced_search($search_params);

        wp_send_json_success([
            'listings' => $results['listings'],
            'total' => $results['total'],
            'pages' => $results['pages'],
            'search_params' => $search_params,
            'execution_time' => $results['execution_time']
        ]);
    }

    /**
     * Execute advanced search with caching
     */
    public function execute_advanced_search(array $params): array
    {
        $start_time = microtime(true);
        
        // Generate cache key
        $cache_key = 'adv_search_' . md5(serialize($params));
        
        // Check cache first
        if (isset($this->search_cache[$cache_key])) {
            $cached_result = $this->search_cache[$cache_key];
            $cached_result['execution_time'] = microtime(true) - $start_time;
            $cached_result['from_cache'] = true;
            return $cached_result;
        }

        // Build query args
        $query_args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $params['per_page'],
            'paged' => $params['page'] ?? 1,
            'meta_query' => []
        ];

        // Apply filters
        $this->apply_search_params_to_query($query_args, $params);

        // Execute query
        $query = new \WP_Query($query_args);
        
        // Format results
        $listings = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $listings[] = $this->format_search_result(get_the_ID());
            }
            wp_reset_postdata();
        }

        $result = [
            'listings' => $listings,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'execution_time' => microtime(true) - $start_time,
            'from_cache' => false
        ];

        // Cache result
        $this->search_cache[$cache_key] = $result;

        return $result;
    }

    /**
     * Apply search parameters to WP_Query args
     */
    private function apply_search_params_to_query(array &$query_args, array $params): void
    {
        $meta_query = [];

        // Price filtering
        if (!empty($params['min_price'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => $params['min_price'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        if (!empty($params['max_price'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => $params['max_price'],
                'compare' => '<=',
                'type' => 'NUMERIC'
            ];
        }

        // Bedroom filtering
        if (!empty($params['min_beds'])) {
            $meta_query[] = [
                'key' => 'bedrooms',
                'value' => $params['min_beds'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        // Bathroom filtering
        if (!empty($params['min_baths'])) {
            $meta_query[] = [
                'key' => 'bathrooms_total',
                'value' => $params['min_baths'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        // Square footage filtering
        if (!empty($params['min_sqft'])) {
            $meta_query[] = [
                'key' => 'square_footage',
                'value' => $params['min_sqft'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        if (!empty($params['max_sqft'])) {
            $meta_query[] = [
                'key' => 'square_footage',
                'value' => $params['max_sqft'],
                'compare' => '<=',
                'type' => 'NUMERIC'
            ];
        }

        // Property type filtering
        if (!empty($params['property_type'])) {
            $meta_query[] = [
                'key' => 'property_type',
                'value' => $params['property_type'],
                'compare' => '='
            ];
        }

        // Listing status filtering
        if (!empty($params['listing_status'])) {
            $meta_query[] = [
                'key' => 'listing_status',
                'value' => $params['listing_status'],
                'compare' => '='
            ];
        }

        // Lifestyle features filtering
        if (!empty($params['lifestyle_features'])) {
            foreach ($params['lifestyle_features'] as $feature) {
                $meta_query[] = [
                    'key' => 'lifestyle_features',
                    'value' => $feature,
                    'compare' => 'LIKE'
                ];
            }
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $query_args['meta_query'] = $meta_query;
        }

        // Apply sorting
        $this->apply_sorting_to_query($query_args, $params['sort_by'] ?? 'relevance');
    }

    /**
     * Apply sorting to query args
     */
    private function apply_sorting_to_query(array &$query_args, string $sort_by): void
    {
        switch ($sort_by) {
            case 'price_low':
                $query_args['meta_key'] = 'price';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'ASC';
                break;

            case 'price_high':
                $query_args['meta_key'] = 'price';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;

            case 'newest':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;

            case 'sqft_large':
                $query_args['meta_key'] = 'square_footage';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;

            case 'relevance':
            default:
                $query_args['meta_key'] = 'search_boost_score';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
        }
    }

    /**
     * Format search result for API response
     */
    private function format_search_result(int $listing_id): array
    {
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => get_field('price', $listing_id),
            'price_formatted' => number_format(get_field('price', $listing_id)),
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms' => get_field('bathrooms_total', $listing_id),
            'square_footage' => get_field('square_footage', $listing_id),
            'property_type' => get_field('property_type', $listing_id),
            'listing_status' => get_field('listing_status', $listing_id),
            'street_address' => get_field('street_address', $listing_id),
            'city' => get_field('city', $listing_id),
            'state' => get_field('state', $listing_id),
            'featured_image' => get_the_post_thumbnail_url($listing_id, 'medium'),
            'permalink' => get_permalink($listing_id),
            'search_boost_score' => get_field('search_boost_score', $listing_id),
            'days_on_market' => get_field('days_on_market', $listing_id),
            'price_per_sqft' => get_field('price_per_sqft', $listing_id)
        ];
    }

    /**
     * Handle search suggestions AJAX
     */
    public function handle_search_suggestions(): void
    {
        check_ajax_referer('search_suggestions_nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['term'] ?? '');
        $suggestions = $this->get_search_suggestions($search_term);

        wp_send_json_success($suggestions);
    }

    /**
     * Get search suggestions based on term
     */
    public function get_search_suggestions(string $term): array
    {
        $suggestions = [];

        if (strlen($term) < 2) {
            return $suggestions;
        }

        // Get city suggestions
        $cities = $this->get_city_suggestions($term);
        $suggestions = array_merge($suggestions, $cities);

        // Get property type suggestions
        $property_types = $this->get_property_type_suggestions($term);
        $suggestions = array_merge($suggestions, $property_types);

        // Get feature suggestions
        $features = $this->get_feature_suggestions($term);
        $suggestions = array_merge($suggestions, $features);

        return array_slice($suggestions, 0, 10); // Limit to 10 suggestions
    }

    /**
     * Get city suggestions
     */
    private function get_city_suggestions(string $term): array
    {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT meta_value as city, COUNT(*) as count
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'city'
            AND pm.meta_value LIKE %s
            AND p.post_type = 'listing'
            AND p.post_status = 'publish'
            GROUP BY meta_value
            ORDER BY count DESC, meta_value ASC
            LIMIT 5
        ", '%' . $wpdb->esc_like($term) . '%'));

        $suggestions = [];
        foreach ($results as $result) {
            $suggestions[] = [
                'label' => $result->city . " ({$result->count} listings)",
                'value' => $result->city,
                'type' => 'city',
                'count' => $result->count
            ];
        }

        return $suggestions;
    }

    /**
     * Get property type suggestions
     */
    private function get_property_type_suggestions(string $term): array
    {
        $property_types = [
            'Single Family' => 'Single Family Home',
            'Condominium' => 'Condominium',
            'Townhouse' => 'Townhouse',
            'Multi-Family' => 'Multi-Family',
            'Land' => 'Land/Lot',
            'Commercial' => 'Commercial Property'
        ];

        $suggestions = [];
        foreach ($property_types as $key => $label) {
            if (stripos($label, $term) !== false) {
                $suggestions[] = [
                    'label' => $label,
                    'value' => $key,
                    'type' => 'property_type'
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Get feature suggestions
     */
    private function get_feature_suggestions(string $term): array
    {
        $features = [
            'waterfront', 'mountain view', 'gated community', 'pool', 'garage',
            'fireplace', 'hardwood floors', 'granite countertops', 'stainless steel',
            'walk-in closet', 'master suite', 'open floor plan', 'updated kitchen'
        ];

        $suggestions = [];
        foreach ($features as $feature) {
            if (stripos($feature, $term) !== false) {
                $suggestions[] = [
                    'label' => ucwords($feature),
                    'value' => $feature,
                    'type' => 'feature'
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Track search interaction
     */
    public function track_search_interaction(): void
    {
        check_ajax_referer('search_tracking_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $interaction_type = sanitize_text_field($_POST['interaction_type'] ?? '');
        $search_query = sanitize_text_field($_POST['search_query'] ?? '');

        if (!$listing_id || !$interaction_type) {
            wp_send_json_error('Invalid parameters');
            return;
        }

        $this->record_search_interaction($listing_id, $interaction_type, $search_query);

        wp_send_json_success(['tracked' => true]);
    }

    /**
     * Record search interaction in database
     */
    private function record_search_interaction(int $listing_id, string $interaction_type, string $search_query): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'listing_search_analytics';

        // Create table if it doesn't exist
        $this->maybe_create_analytics_table();

        // Insert interaction record
        $wpdb->insert(
            $table_name,
            [
                'listing_id' => $listing_id,
                'interaction_type' => $interaction_type,
                'search_query' => $search_query,
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        // Update listing analytics fields
        $this->update_listing_analytics($listing_id, $interaction_type);
    }

    /**
     * Update listing analytics fields
     */
    private function update_listing_analytics(int $listing_id, string $interaction_type): void
    {
        switch ($interaction_type) {
            case 'view':
                $current = (int) get_field('total_views', $listing_id);
                update_field('total_views', $current + 1, $listing_id);
                
                $current_30_days = (int) get_field('views_last_30_days', $listing_id);
                update_field('views_last_30_days', $current_30_days + 1, $listing_id);
                break;

            case 'favorite':
                $current = (int) get_field('favorites_count', $listing_id);
                update_field('favorites_count', $current + 1, $listing_id);
                break;

            case 'share':
                $current = (int) get_field('shares_count', $listing_id);
                update_field('shares_count', $current + 1, $listing_id);
                break;

            case 'contact':
                $current = (int) get_field('contact_requests', $listing_id);
                update_field('contact_requests', $current + 1, $listing_id);
                break;

            case 'tour':
                $current = (int) get_field('tour_requests', $listing_id);
                update_field('tour_requests', $current + 1, $listing_id);
                break;
        }
    }

    /**
     * Create analytics table if needed
     */
    private function maybe_create_analytics_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'listing_search_analytics';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            listing_id int(11) NOT NULL,
            interaction_type varchar(50) NOT NULL,
            search_query text,
            user_ip varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY interaction_type (interaction_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get user IP address
     */
    private function get_user_ip(): string
    {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Track search query for analytics
     */
    private function track_search_query(\WP_Query $query): void
    {
        if (!is_admin() && $query->is_main_query()) {
            // Track search parameters for analytics
            $search_data = [
                'timestamp' => current_time('mysql'),
                'query_vars' => $query->query_vars,
                'found_posts' => $query->found_posts ?? 0,
                'user_ip' => $this->get_user_ip()
            ];

            // Store in transient for later processing
            set_transient('search_tracking_' . md5(serialize($search_data)), $search_data, HOUR_IN_SECONDS);
        }
    }

    /**
     * Add search tracking script to head
     */
    public function add_search_tracking_script(): void
    {
        if (!is_singular('listing') && !is_post_type_archive('listing')) {
            return;
        }
        ?>
        <script>
        window.hphSearchTracking = {
            nonce: '<?php echo wp_create_nonce('search_tracking_nonce'); ?>',
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            
            track: function(listingId, interactionType, searchQuery = '') {
                if (!listingId || !interactionType) return;
                
                fetch(this.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'track_search_interaction',
                        nonce: this.nonce,
                        listing_id: listingId,
                        interaction_type: interactionType,
                        search_query: searchQuery
                    })
                });
            }
        };

        // Auto-track page views
        document.addEventListener('DOMContentLoaded', function() {
            const listingId = document.body.getAttribute('data-listing-id');
            if (listingId) {
                window.hphSearchTracking.track(listingId, 'view');
            }
        });
        </script>
        <?php
    }

    /**
     * Get search analytics summary
     */
    public function get_search_analytics_summary(int $days = 30): array
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'listing_search_analytics';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                interaction_type,
                COUNT(*) as count,
                COUNT(DISTINCT listing_id) as unique_listings,
                DATE(timestamp) as date
            FROM {$table_name}
            WHERE timestamp >= %s
            GROUP BY interaction_type, DATE(timestamp)
            ORDER BY timestamp DESC
        ", $date_from));

        return $results;
    }
}

// Initialize the search engine
add_action('init', function() {
    Advanced_Search_Engine::get_instance();
});
