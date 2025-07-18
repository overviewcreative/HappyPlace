<?php
/**
 * Base Post Type Class
 *
 * Abstract class for registering custom post types
 *
 * @package HappyPlace
 * @subpackage PostTypes
 */

namespace HappyPlace\PostTypes;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Post_Type {
    /**
     * Post type name
     *
     * @var string
     */
    protected $post_type;

    /**
     * Instance of the class
     *
     * @var static
     */
    protected static $instances = [];

    /**
     * Get instance of the class
     */
    public static function get_instance() {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Constructor
     */
    protected function __construct() {
        $this->init();
    }

    /**
     * Initialize the post type
     */
    protected function init() {
        add_action('init', [$this, 'register']);
        add_action('init', [$this, 'register_taxonomies']);
        add_filter('post_updated_messages', [$this, 'updated_messages']);
        add_filter('bulk_post_updated_messages', [$this, 'bulk_updated_messages'], 10, 2);
    }

    /**
     * Register the post type
     */
    abstract public function register();

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Override in child class if needed
    }

    /**
     * Post type updated messages
     */
    public function updated_messages($messages) {
        global $post;

        $permalink = get_permalink($post);
        $preview_url = get_preview_post_link($post);
        $view_link = sprintf(' <a href="%s">%s</a>', esc_url($permalink), __('View ' . $this->get_singular_name(), 'happy-place'));
        $preview_link = sprintf(' <a target="_blank" href="%s">%s</a>', esc_url($preview_url), __('Preview ' . $this->get_singular_name(), 'happy-place'));

        $messages[$this->post_type] = [
            0  => '', // Unused. Messages start at index 1.
            1  => __($this->get_singular_name() . ' updated.', 'happy-place') . $view_link,
            2  => __('Custom field updated.', 'happy-place'),
            3  => __('Custom field deleted.', 'happy-place'),
            4  => __($this->get_singular_name() . ' updated.', 'happy-place'),
            5  => isset($_GET['revision']) ? sprintf(__($this->get_singular_name() . ' restored to revision from %s', 'happy-place'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => __($this->get_singular_name() . ' published.', 'happy-place') . $view_link,
            7  => __($this->get_singular_name() . ' saved.', 'happy-place'),
            8  => __($this->get_singular_name() . ' submitted.', 'happy-place') . $preview_link,
            9  => sprintf(
                __($this->get_singular_name() . ' scheduled for: <strong>%1$s</strong>.', 'happy-place'),
                date_i18n(__('M j, Y @ G:i', 'happy-place'), strtotime($post->post_date))
            ) . $preview_link,
            10 => __($this->get_singular_name() . ' draft updated.', 'happy-place') . $preview_link,
        ];

        return $messages;
    }

    /**
     * Bulk post type updated messages
     */
    public function bulk_updated_messages($bulk_messages, $bulk_counts) {
        $bulk_messages[$this->post_type] = [
            'updated'   => _n('%s ' . $this->get_singular_name() . ' updated.', '%s ' . $this->get_plural_name() . ' updated.', $bulk_counts['updated'], 'happy-place'),
            'locked'    => _n('%s ' . $this->get_singular_name() . ' not updated, somebody is editing it.', '%s ' . $this->get_plural_name() . ' not updated, somebody is editing them.', $bulk_counts['locked'], 'happy-place'),
            'deleted'   => _n('%s ' . $this->get_singular_name() . ' permanently deleted.', '%s ' . $this->get_plural_name() . ' permanently deleted.', $bulk_counts['deleted'], 'happy-place'),
            'trashed'   => _n('%s ' . $this->get_singular_name() . ' moved to the Trash.', '%s ' . $this->get_plural_name() . ' moved to the Trash.', $bulk_counts['trashed'], 'happy-place'),
            'untrashed' => _n('%s ' . $this->get_singular_name() . ' restored from the Trash.', '%s ' . $this->get_plural_name() . ' restored from the Trash.', $bulk_counts['untrashed'], 'happy-place'),
        ];

        return $bulk_messages;
    }

    /**
     * Get singular name
     */
    abstract protected function get_singular_name();

    /**
     * Get plural name
     */
    abstract protected function get_plural_name();
}
