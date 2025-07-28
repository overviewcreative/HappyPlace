<?php
namespace HappyPlace\Users;

class User_Registration_Manager {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('init', [$this, 'register_registration_shortcode']);
        add_action('wp_ajax_hph_user_registration', [$this, 'process_user_registration']);
        add_action('wp_ajax_nopriv_hph_user_registration', [$this, 'process_user_registration']);
        add_filter('registration_errors', [$this, 'validate_registration_fields'], 10, 3);
    }

    /**
     * Register custom registration shortcode
     */
    public function register_registration_shortcode(): void {
        add_shortcode('hph_registration_form', [$this, 'render_registration_form']);
    }

    /**
     * Render registration form
     */
    public function render_registration_form($atts = []): string {
        $atts = shortcode_atts([
            'role' => 'hph_client'
        ], $atts, 'hph_registration_form');

        // Validate role
        $allowed_roles = ['hph_client', 'hph_agent'];
        $role = in_array($atts['role'], $allowed_roles) ? $atts['role'] : 'hph_client';

        ob_start();
        ?>
        <form id="hph-registration-form" class="hph-form">
            <input type="hidden" name="action" value="hph_user_registration">
            <input type="hidden" name="role" value="<?php echo esc_attr($role); ?>">
            <?php wp_nonce_field('hph_user_registration', 'registration_nonce'); ?>

            <div class="hph-form-group">
                <label for="first_name">First Name</label>
                <input 
                    type="text" 
                    name="first_name" 
                    id="first_name" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <label for="last_name">Last Name</label>
                <input 
                    type="text" 
                    name="last_name" 
                    id="last_name" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <label for="phone">Phone Number</label>
                <input 
                    type="tel" 
                    name="phone" 
                    id="phone" 
                    class="hph-form-input"
                >
            </div>

            <?php if ($role === 'hph_agent'): ?>
                <div class="hph-form-group">
                    <label for="license_number">Real Estate License Number</label>
                    <input 
                        type="text" 
                        name="license_number" 
                        id="license_number" 
                        class="hph-form-input"
                        required
                    >
                </div>
            <?php endif; ?>

            <div class="hph-form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    id="confirm_password" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <button type="submit" class="hph-btn hph-btn--primary">
                    Register
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Process user registration
     */
    public function process_user_registration(): void {
        // Verify nonce
        check_ajax_referer('hph_user_registration', 'registration_nonce');

        // Sanitize inputs
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitize_text_field($_POST['role'] ?? 'hph_client');

        // Validate inputs
        $errors = [];

        if (empty($first_name) || empty($last_name)) {
            $errors[] = 'First and last name are required.';
        }

        if (!is_email($email)) {
            $errors[] = 'Invalid email address.';
        }

        if (email_exists($email)) {
            $errors[] = 'Email address is already registered.';
        }

        if (empty($password) || $password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        // Role-specific validations
        if ($role === 'hph_agent') {
            $license_number = sanitize_text_field($_POST['license_number'] ?? '');
            
            if (empty($license_number)) {
                $errors[] = 'License number is required for agents.';
            }

            // Optional: Validate license number format
            if (!preg_match('/^[A-Z]{2}\d{5,6}$/', $license_number)) {
                $errors[] = 'Invalid license number format.';
            }
        }

        // Return errors if any
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Registration failed',
                'errors' => $errors
            ]);
        }

        // Prepare user data
        $user_data = [
            'user_login' => $email,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_pass' => $password,
            'role' => $role
        ];

        // Create user
        $user_id = wp_insert_user($user_data);

        // Handle user creation errors
        if (is_wp_error($user_id)) {
            wp_send_json_error([
                'message' => 'User registration failed',
                'errors' => [$user_id->get_error_message()]
            ]);
        }

        // Save additional user meta
        update_user_meta($user_id, 'hph_phone', $phone);

        // Role-specific meta
        if ($role === 'hph_agent') {
            update_user_meta($user_id, 'hph_license_number', $license_number);
        }

        // Trigger welcome email
        $this->send_welcome_email($user_id);

        // Sync with CRM
        $this->sync_user_to_crm($user_id);

        // Auto-login after registration
        wp_set_auth_cookie($user_id);

        // Successful registration response
        wp_send_json_success([
            'message' => 'Registration successful',
            'redirect' => $this->get_registration_redirect($role)
        ]);
    }

    /**
     * Send welcome email
     */
    private function send_welcome_email(int $user_id): void {
        $user = get_userdata($user_id);
        
        $subject = 'Welcome to Happy Place Real Estate';
        $message = sprintf(
            "Hello %s,\n\n" .
            "Thank you for registering with Happy Place Real Estate.\n\n" .
            "You can now access your dashboard and start exploring our platform.\n\n" .
            "Best regards,\n" .
            "Happy Place Team",
            $user->first_name
        );

        wp_mail(
            $user->user_email, 
            $subject, 
            $message
        );
    }

    /**
     * Sync user to CRM
     */
    private function sync_user_to_crm(int $user_id): void {
        $user = get_userdata($user_id);
        
        // Prepare CRM data
        $crm_data = [
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => get_user_meta($user_id, 'hph_phone', true),
            'role' => $user->roles[0],
            'source' => 'website_registration'
        ];

        // Trigger CRM sync action
        do_action('hph_crm_user_sync', $user_id, $crm_data);
    }

    /**
     * Get registration redirect based on role
     */
    private function get_registration_redirect(string $role): string {
        $dashboards = [
            'hph_client' => home_url('/client-dashboard/'),
            'hph_agent' => home_url('/agent-dashboard/'),
            'hph_broker' => home_url('/broker-dashboard/')
        ];

        return $dashboards[$role] ?? home_url('/dashboard/');
    }

    /**
     * Custom login form
     */
    public function render_login_form($atts = []): string {
        $atts = shortcode_atts([
            'redirect' => ''
        ], $atts, 'hph_login_form');

        ob_start();
        ?>
        <form id="hph-login-form" class="hph-form">
            <input type="hidden" name="action" value="hph_user_login">
            <input type="hidden" name="redirect" value="<?php echo esc_attr($atts['redirect']); ?>">
            <?php wp_nonce_field('hph_user_login', 'login_nonce'); ?>

            <div class="hph-form-group">
                <label for="login_email">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    id="login_email" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <label for="login_password">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="login_password" 
                    class="hph-form-input" 
                    required
                >
            </div>

            <div class="hph-form-group">
                <button type="submit" class="hph-btn hph-btn--primary">
                    Log In
                </button>
            </div>

            <div class="hph-form-links">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                    Forgot Password?
                </a>
                <a href="<?php echo esc_url(home_url('/register')); ?>">
                    Create an Account
                </a>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Process user login
     */
    public function process_user_login(): void {
        // Verify nonce
        check_ajax_referer('hph_user_login', 'login_nonce');

        // Sanitize inputs
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = esc_url_raw($_POST['redirect'] ?? home_url('/dashboard/'));

        // Validate inputs
        $errors = [];

        if (!is_email($email)) {
            $errors[] = 'Invalid email address.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        // Return errors if any
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Login failed',
                'errors' => $errors
            ]);
        }

        // Attempt login
        $user = wp_signon([
            'user_login' => $email,
            'user_password' => $password,
            'remember' => true
        ], false);

        // Check login result
        if (is_wp_error($user)) {
            wp_send_json_error([
                'message' => 'Login failed',
                'errors' => [$user->get_error_message()]
            ]);
        }

        // Log login attempt
        $this->log_login_attempt($user->ID, true);

        // Successful login response
        wp_send_json_success([
            'message' => 'Login successful',
            'redirect' => $redirect
        ]);
    }

    /**
     * Log login attempts
     */
    private function log_login_attempt(int $user_id, bool $success): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hph_login_logs';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'login_time' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'success' => $success ? 1 : 0
            ],
            ['%d', '%s', '%s', '%d']
        );
    }

    /**
     * Create login logs table
     */
    public function create_login_logs_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hph_login_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            login_time datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) NOT NULL,
            success tinyint(1) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Initialize hooks
     */
    public function init(): void {
        // Registration shortcode
        add_shortcode('hph_registration_form', [$this, 'render_registration_form']);
        
        // Login shortcode
        add_shortcode('hph_login_form', [$this, 'render_login_form']);

        // AJAX actions
        add_action('wp_ajax_hph_user_registration', [$this, 'process_user_registration']);
        add_action('wp_ajax_nopriv_hph_user_registration', [$this, 'process_user_registration']);
        
        add_action('wp_ajax_hph_user_login', [$this, 'process_user_login']);
        add_action('wp_ajax_nopriv_hph_user_login', [$this, 'process_user_login']);
    }
}

// Initialize User Registration Manager
$user_registration_manager = User_Registration_Manager::get_instance();
$user_registration_manager->init();