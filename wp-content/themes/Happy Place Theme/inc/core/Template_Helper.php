<?php
namespace HappyPlace\Core;

class Template_Helper {
    /**
     * Load a template part with variables in scope
     *
     * @param string $slug The slug name for the generic template
     * @param string $name The name of the specialized template
     * @param array  $vars Variables to pass to the template
     */
    public static function get_template_part($slug, $name = '', $vars = []) {
        if (!empty($vars) && is_array($vars)) {
            extract($vars);
        }

        $templates = [];
        if (!empty($name)) {
            $templates[] = "template-parts/{$slug}-{$name}.php";
            $templates[] = "templates/listing/{$slug}-{$name}.php";
            $templates[] = "happy-place/{$slug}-{$name}.php";
        }
        $templates[] = "template-parts/{$slug}.php";
        $templates[] = "templates/listing/{$slug}.php";
        $templates[] = "happy-place/{$slug}.php";

        // Look for template file
        $located = '';
        foreach ($templates as $template) {
            if (file_exists(get_template_directory() . '/' . $template)) {
                $located = get_template_directory() . '/' . $template;
                break;
            }
        }

        if ($located) {
            include($located);
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Template part not found: {$slug}" . ($name ? "-{$name}" : ''));
        }
    }

    /**
     * Load a card template
     *
     * @param string $type The type of card (listing, agent, etc.)
     * @param array  $vars Variables to pass to the template
     */
    public static function get_card($type, $vars = []) {
        self::get_template_part('cards/card', $type, $vars);
    }

    /**
     * Load a dashboard component
     *
     * @param string $component The component name
     * @param array  $vars Variables to pass to the template
     */
    public static function get_dashboard_component($component, $vars = []) {
        self::get_template_part('dashboard/components/' . $component, '', $vars);
    }

    /**
     * Load a global component
     *
     * @param string $component The component name
     * @param array  $vars Variables to pass to the template
     */
    public static function get_global_component($component, $vars = []) {
        self::get_template_part('global/' . $component, '', $vars);
    }
}
