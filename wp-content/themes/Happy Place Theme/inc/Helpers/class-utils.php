<?php
namespace HappyPlace\Theme\Utils;

/**
 * Utility Class
 * Common utility functions for the theme
 */
class Utils {
    /**
     * Format price with proper currency symbol and separators
     */
    public static function format_price(float $price, bool $show_cents = false): string {
        return sprintf(
            $show_cents ? '$%s' : '$%s',
            number_format($price, $show_cents ? 2 : 0, '.', ',')
        );
    }

    /**
     * Format phone number consistently
     */
    public static function format_phone(string $phone): string {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format as (XXX) XXX-XXXX if 10 digits
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s',
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6)
            );
        }
        
        return $phone;
    }

    /**
     * Get time ago in human readable format
     */
    public static function time_ago(string $datetime): string {
        $time = strtotime($datetime);
        $now = current_time('timestamp');
        $diff = $now - $time;

        if ($diff < 60) {
            return __('just now', 'happy-place-theme');
        }

        $intervals = [
            31536000 => __('year', 'happy-place-theme'),
            2592000 => __('month', 'happy-place-theme'),
            604800 => __('week', 'happy-place-theme'),
            86400 => __('day', 'happy-place-theme'),
            3600 => __('hour', 'happy-place-theme'),
            60 => __('minute', 'happy-place-theme')
        ];

        foreach ($intervals as $seconds => $label) {
            $count = floor($diff / $seconds);
            if ($count > 0) {
                if ($count === 1) {
                    return sprintf(__('%s %s ago', 'happy-place-theme'), $count, $label);
                } else {
                    return sprintf(__('%s %ss ago', 'happy-place-theme'), $count, $label);
                }
            }
        }

        return $datetime;
    }

    /**
     * Generate excerpt with custom length
     */
    public static function get_excerpt(string $content, int $length = 55, string $more = '...'): string {
        $excerpt = strip_shortcodes($content);
        $excerpt = strip_tags($excerpt);
        $excerpt = substr($excerpt, 0, $length);
        $excerpt = substr($excerpt, 0, strrpos($excerpt, ' '));
        $excerpt .= $more;
        
        return $excerpt;
    }

    /**
     * Clean up phone number for standardization
     */
    public static function sanitize_phone(string $phone): string {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Generate random string
     */
    public static function random_string(int $length = 10): string {
        return substr(str_shuffle(
            str_repeat(
                $x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                ceil($length/strlen($x))
            )
        ), 1, $length);
    }

    /**
     * Check if URL is external
     */
    public static function is_external_url(string $url): bool {
        $home_url = home_url();
        return strpos($url, $home_url) !== 0 && strpos($url, '/') !== 0;
    }

    /**
     * Get YouTube video ID from URL
     */
    public static function get_youtube_id(string $url): ?string {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? null;
    }

    /**
     * Format file size in human readable format
     */
    public static function format_size(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return sprintf('%.2f %s', $bytes, $units[$pow]);
    }

    /**
     * Get SVG icon
     */
    public static function get_icon(string $name, array $attrs = []): string {
        $icon_path = get_template_directory() . '/assets/icons/' . $name . '.svg';
        
        if (!file_exists($icon_path)) {
            return '';
        }

        $svg = file_get_contents($icon_path);
        
        // Add custom attributes
        if (!empty($attrs)) {
            $attr_string = '';
            foreach ($attrs as $key => $value) {
                $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
            }
            $svg = str_replace('<svg', '<svg' . $attr_string, $svg);
        }
        
        return $svg;
    }

    /**
     * Get breadcrumbs array
     */
    public static function get_breadcrumbs(): array {
        $breadcrumbs = [];
        
        // Add home
        $breadcrumbs[] = [
            'title' => __('Home', 'happy-place-theme'),
            'url' => home_url()
        ];

        if (is_singular('listing')) {
            $breadcrumbs[] = [
                'title' => __('Listings', 'happy-place-theme'),
                'url' => get_post_type_archive_link('listing')
            ];
            $breadcrumbs[] = [
                'title' => get_the_title(),
                'url' => ''
            ];
        } elseif (is_post_type_archive('listing')) {
            $breadcrumbs[] = [
                'title' => __('Listings', 'happy-place-theme'),
                'url' => ''
            ];
        } elseif (is_singular('agent')) {
            $breadcrumbs[] = [
                'title' => __('Agents', 'happy-place-theme'),
                'url' => get_post_type_archive_link('agent')
            ];
            $breadcrumbs[] = [
                'title' => get_the_title(),
                'url' => ''
            ];
        } elseif (is_post_type_archive('agent')) {
            $breadcrumbs[] = [
                'title' => __('Agents', 'happy-place-theme'),
                'url' => ''
            ];
        }

        return $breadcrumbs;
    }
}
