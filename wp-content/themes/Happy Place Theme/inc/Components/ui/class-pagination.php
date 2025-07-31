<?php
/**
 * Pagination Component
 * 
 * @package Happy_Place_Theme
 * @since 1.0.0
 */

namespace HappyPlace\Components\UI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pagination Component Class
 */
class Pagination extends \HappyPlace\Components\Base_Component {
    
    /**
     * Get component name
     * 
     * @return string Component name
     */
    protected function get_component_name() {
        return 'pagination';
    }
    
    /**
     * Get default properties
     * 
     * @return array Default properties
     */
    protected function get_defaults() {
        return [
            'total_pages' => 1,
            'current_page' => 1,
            'prev_text' => '← Previous',
            'next_text' => 'Next →',
            'show_numbers' => true,
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'base_url' => '',
            'format' => 'page/%#%/',
            'add_args' => [],
            'add_fragment' => '',
            'screen_reader_text' => 'Navigation',
            'aria_label' => 'Posts navigation'
        ];
    }
    
    /**
     * Validate properties
     */
    protected function validate_props() {
        // Ensure total_pages is at least 1
        $this->props['total_pages'] = max(1, intval($this->props['total_pages']));
        
        // Ensure current_page is within valid range
        $this->props['current_page'] = max(1, min($this->props['total_pages'], intval($this->props['current_page'])));
        
        // Ensure numeric values for pagination logic
        $this->props['end_size'] = max(1, intval($this->props['end_size']));
        $this->props['mid_size'] = max(0, intval($this->props['mid_size']));
    }
    
    /**
     * Check if component should render
     * 
     * @return bool Whether to render the component
     */
    protected function should_render() {
        return $this->props['total_pages'] > 1;
    }
    
    /**
     * Render the pagination component
     * 
     * @return string HTML output
     */
    protected function render() {
        if (!$this->should_render()) {
            return '';
        }
        
        $current = $this->props['current_page'];
        $total = $this->props['total_pages'];
        
        $links = $this->get_pagination_links();
        
        if (empty($links)) {
            return '';
        }
        
        $output = '<nav class="pagination-wrapper" aria-label="' . esc_attr($this->props['aria_label']) . '">';
        
        if (!empty($this->props['screen_reader_text'])) {
            $output .= '<h2 class="screen-reader-text">' . esc_html($this->props['screen_reader_text']) . '</h2>';
        }
        
        $output .= '<div class="pagination">';
        $output .= implode("\n", $links);
        $output .= '</div>';
        $output .= '</nav>';
        
        return $output;
    }
    
    /**
     * Get pagination links
     * 
     * @return array Array of pagination link HTML
     */
    private function get_pagination_links() {
        $links = [];
        $current = $this->props['current_page'];
        $total = $this->props['total_pages'];
        
        // Previous link
        if ($current > 1) {
            $links[] = $this->get_link(
                $current - 1,
                $this->props['prev_text'],
                'prev',
                'Previous page'
            );
        }
        
        // Number links
        if ($this->props['show_numbers']) {
            $number_links = $this->get_number_links();
            $links = array_merge($links, $number_links);
        }
        
        // Next link
        if ($current < $total) {
            $links[] = $this->get_link(
                $current + 1,
                $this->props['next_text'],
                'next',
                'Next page'
            );
        }
        
        return $links;
    }
    
    /**
     * Get numbered pagination links
     * 
     * @return array Array of numbered link HTML
     */
    private function get_number_links() {
        $links = [];
        $current = $this->props['current_page'];
        $total = $this->props['total_pages'];
        $end_size = $this->props['end_size'];
        $mid_size = $this->props['mid_size'];
        
        if ($this->props['show_all']) {
            // Show all page numbers
            for ($i = 1; $i <= $total; $i++) {
                $links[] = $this->get_number_link($i);
            }
        } else {
            // Calculate which numbers to show
            $dots = false;
            
            for ($n = 1; $n <= $total; $n++) {
                if ($n == $current) {
                    // Current page
                    $links[] = $this->get_current_link($n);
                    $dots = true;
                } elseif (
                    $this->props['show_all'] ||
                    ($n <= $end_size) ||
                    (($current - $mid_size) <= $n && $n <= ($current + $mid_size)) ||
                    ($n > ($total - $end_size))
                ) {
                    // Show this number
                    $links[] = $this->get_number_link($n);
                    $dots = true;
                } elseif ($dots && !$this->props['show_all']) {
                    // Add dots
                    $links[] = $this->get_dots();
                    $dots = false;
                }
            }
        }
        
        return $links;
    }
    
    /**
     * Get a pagination link
     * 
     * @param int $page Page number
     * @param string $text Link text
     * @param string $class CSS class
     * @param string $aria_label ARIA label
     * @return string HTML link
     */
    private function get_link($page, $text, $class = '', $aria_label = '') {
        $url = $this->get_page_url($page);
        
        $classes = ['page-numbers'];
        if ($class) {
            $classes[] = $class;
        }
        
        $aria = $aria_label ? ' aria-label="' . esc_attr($aria_label) . '"' : '';
        
        return sprintf(
            '<a class="%s" href="%s"%s>%s</a>',
            esc_attr(implode(' ', $classes)),
            esc_url($url),
            $aria,
            $text
        );
    }
    
    /**
     * Get a numbered page link
     * 
     * @param int $page Page number
     * @return string HTML link
     */
    private function get_number_link($page) {
        return $this->get_link(
            $page,
            number_format_i18n($page),
            '',
            sprintf('Page %d', $page)
        );
    }
    
    /**
     * Get current page span
     * 
     * @param int $page Page number
     * @return string HTML span
     */
    private function get_current_link($page) {
        return sprintf(
            '<span class="page-numbers current" aria-current="page" aria-label="Current page, page %d">%s</span>',
            $page,
            number_format_i18n($page)
        );
    }
    
    /**
     * Get dots separator
     * 
     * @return string HTML span for dots
     */
    private function get_dots() {
        return '<span class="page-numbers dots">…</span>';
    }
    
    /**
     * Get URL for a specific page
     * 
     * @param int $page Page number
     * @return string Page URL
     */
    private function get_page_url($page) {
        if (!empty($this->props['base_url'])) {
            $base = $this->props['base_url'];
        } else {
            $base = home_url('/');
        }
        
        if ($page <= 1) {
            $url = $base;
        } else {
            $format = $this->props['format'];
            $url = $base . str_replace('%#%', $page, $format);
        }
        
        // Add query arguments
        if (!empty($this->props['add_args'])) {
            $url = add_query_arg($this->props['add_args'], $url);
        }
        
        // Add fragment
        if (!empty($this->props['add_fragment'])) {
            $url .= '#' . $this->props['add_fragment'];
        }
        
        return $url;
    }
}
