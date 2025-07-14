<?php

/**
 * Architecture Map Feature
 *
 * Generates and maintains a documentation file showing the structure of Happy Place Plugin and Theme.
 *
 * @package HappyPlace\Features
 * @since 1.0.0
 */

namespace HappyPlace\Features;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Architecture Map class
 */
class Architecture_Map
{
    /**
     * Instance of the feature
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Output directory for documentation
     *
     * @var string
     */
    private string $output_dir;

    /**
     * Get instance of the feature
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->output_dir = WP_CONTENT_DIR . '/architecture-docs';
        $this->init();
    }

    /**
     * Initialize the feature
     *
     * @return void
     */
    private function init(): void
    {
        // Create documentation directory if it doesn't exist
        if (!file_exists($this->output_dir)) {
            wp_mkdir_p($this->output_dir);
        }

        // Add admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);

        // Add automatic generation on plugin/theme updates
        add_action('upgrader_process_complete', [$this, 'generateArchitectureMap'], 10, 2);

        // Generate on activation
        register_activation_hook(HAPPY_PLACE_PLUGIN_FILE, [$this, 'generateArchitectureMap']);
    }

    /**
     * Add admin menu item
     *
     * @return void
     */
    public function addAdminMenu(): void
    {
        add_submenu_page(
            'tools.php',
            'Site Architecture',
            'Site Architecture',
            'manage_options',
            'happy-place-architecture',
            [$this, 'renderAdminPage']
        );
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function renderAdminPage(): void
    {
        if (isset($_POST['generate_architecture']) && check_admin_referer('generate_architecture_map')) {
            $this->generateArchitectureMap();
            echo '<div class="notice notice-success"><p>Architecture map has been generated.</p></div>';
        }

?>
        <div class="wrap">
            <h1>Site Architecture</h1>
            <form method="post">
                <?php wp_nonce_field('generate_architecture_map'); ?>
                <p>
                    <input type="submit" name="generate_architecture" class="button button-primary" value="Generate Architecture Map">
                </p>
            </form>
            <?php
            $map_file = $this->output_dir . '/architecture.md';
            if (file_exists($map_file)) {
                echo '<h2>Current Architecture Map</h2>';
                echo '<pre style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 500px; overflow: auto;">';
                echo esc_html(file_get_contents($map_file));
                echo '</pre>';
            }
            ?>
        </div>
<?php
    }

    /**
     * Generate architecture map
     *
     * @param mixed $upgrader Optional upgrader object
     * @param array $options  Optional array of options
     * @return void
     */
    public function generateArchitectureMap($upgrader = null, array $options = []): void
    {
        $output = "# Happy Place Site Architecture\n\n";
        $output .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        // Plugin structure
        $output .= "## Plugin Structure\n\n";
        $plugin_dir = WP_PLUGIN_DIR . '/happy-place-plugin';
        $output .= $this->mapDirectory($plugin_dir);

        // Theme structure
        $output .= "\n## Theme Structure\n\n";
        $theme_dir = get_template_directory();
        $output .= $this->mapDirectory($theme_dir);

        // Save to file
        $map_file = $this->output_dir . '/architecture.md';
        file_put_contents($map_file, $output);
    }

    /**
     * Map a directory structure
     *
     * @param string $dir   Directory to map
     * @param int    $depth Current depth
     * @return string
     */
    private function mapDirectory(string $dir, int $depth = 0): string
    {
        $output = '';
        $files = scandir($dir);
        $indent = str_repeat('  ', $depth);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Skip certain directories and files
            if (in_array($file, ['.git', 'node_modules', 'vendor', '.DS_Store'])) {
                continue;
            }

            $path = $dir . '/' . $file;
            $relativePath = str_replace([WP_CONTENT_DIR, '\\'], ['', '/'], $path);

            if (is_dir($path)) {
                $output .= "{$indent}- 📁 `{$file}/`\n";
                $output .= $this->mapDirectory($path, $depth + 1);
            } else {
                // Get file info for PHP files
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $fileContent = file_get_contents($path);
                    preg_match('|/\*\*[\s\S]*?\*/|', $fileContent, $docblock);

                    if (!empty($docblock[0])) {
                        // Extract description from DocBlock
                        preg_match('/\*\s*([^@][^\n]+)/', $docblock[0], $description);
                        if (!empty($description[1])) {
                            $output .= "{$indent}- 📄 `{$file}` - " . trim($description[1]) . "\n";
                            continue;
                        }
                    }
                }

                $output .= "{$indent}- 📄 `{$file}`\n";
            }
        }

        return $output;
    }
}
