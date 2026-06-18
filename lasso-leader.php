<?php
/**
 * Plugin Name:       Lasso Leader
 * Plugin URI:        https://jacksonmurphy.com/lasso-leader
 * Description:       Connects Gravity Forms and Contact Form 7 submissions to Lasso CRM. Includes On-Site Registration features and automatic updates.
 * Version:           6.3.2
 * Update URI:        https://github.com/jacksonmurphy/lasso-leader-eva-onsite
 * Author:            Jackson Murphy
 * Author URI:        https://jacksonmurphy.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lasso-leader
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// --- DEFINE PLUGIN CONSTANTS ---
define( 'LASSO_LEADER_VERSION', '6.3.2' );
define( 'LASSO_LEADER_PATH', plugin_dir_path( __FILE__ ) );
define( 'LASSO_LEADER_URL', plugin_dir_url( __FILE__ ) );

// EVA auto-update smoke test marker.

// Load license system first (before other functionality)
require_once plugin_dir_path(__FILE__) . 'includes/class-lasso-license-manager.php';
// require_once plugin_dir_path(__FILE__) . 'license-admin.php';

// License check functions
function lasso_leader_check_license() {
    if (!class_exists('Lasso_Leader_License_Manager')) {
        return false; // No license manager, assume unlicensed
    }
    $license_manager = new Lasso_Leader_License_Manager();
    return $license_manager->is_license_valid();
}

function lasso_leader_verify_license_before_processing() {
    if (!lasso_leader_check_license()) {
        error_log('Lasso Leader: Unlicensed form submission attempt blocked');
        return false;
    }
    return true;
}

// Hook license verification into form processing
add_filter('lasso_leader_can_process_form', 'lasso_leader_verify_license_before_processing');

// Add license status to plugin list
add_filter('plugin_row_meta', function($plugin_meta, $plugin_file) {
    if ($plugin_file === plugin_basename(__FILE__)) {
        $license_valid = lasso_leader_check_license();
        $status = $license_valid ? 
            '<span style="color: green;">✅ Licensed</span>' : 
            '<span style="color: red;">❌ Unlicensed</span>';
        $plugin_meta[] = $status;
    }
    return $plugin_meta;
}, 10, 2);

// Add license link to plugin actions
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $license_link = '<a href="' . admin_url('options-general.php?page=lasso-leader-license') . '">License</a>';
    array_unshift($links, $license_link);
    return $links;
});

// --- PLUGIN UPDATE CHECKER (PROPERLY HOOKED) ---
add_action('plugins_loaded', 'lasso_leader_init_update_checker');
add_filter('auto_update_plugin', 'lasso_leader_enable_automatic_updates', 10, 2);

function lasso_leader_get_github_token() {
    if (defined('LASSO_LEADER_GITHUB_TOKEN') && LASSO_LEADER_GITHUB_TOKEN) {
        return LASSO_LEADER_GITHUB_TOKEN;
    }

    $token = getenv('LASSO_LEADER_GITHUB_TOKEN');
    return $token ? $token : '';
}

function lasso_leader_enable_automatic_updates($update, $item) {
    if (isset($item->plugin) && $item->plugin === plugin_basename(__FILE__)) {
        return lasso_leader_check_license();
    }

    return $update;
}

function lasso_leader_init_update_checker() {
    // Load the update checker library
    $update_checker_path = plugin_dir_path(__FILE__) . 'lib/plugin-update-checker/plugin-update-checker.php';
    
    if (file_exists($update_checker_path)) {
        require_once $update_checker_path;
        
        // Initialize the update checker only if licensed
        if (lasso_leader_check_license()) {
            try {
                // Check if the class exists before using it
                if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
                    $lasso_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                        'https://github.com/jacksonmurphy/lasso-leader-eva-onsite',
                        __FILE__,
                        'lasso-leader'
                    );

                    $github_token = lasso_leader_get_github_token();
                    if (!empty($github_token)) {
                        $lasso_update_checker->setAuthentication($github_token);
                    }

                    // Set the branch that contains the stable release
                    $lasso_update_checker->setBranch('main');
                    
                    // Enable release assets for better version management
                    $lasso_update_checker->getVcsApi()->enableReleaseAssets();
                    
                    // Add license validation to update checks
                    $lasso_update_checker->addResultFilter(function($update, $result) {
                        if ($update && !lasso_leader_check_license()) {
                            return null; // Block updates for unlicensed installations
                        }
                        return $update;
                    });
                    
                    // error_log('[LASSO DEBUG] Update checker initialized successfully for licensed installation');
                } else {
                    error_log('[LASSO DEBUG] PucFactory class not found after loading library');
                }
            } catch (Exception $e) {
                error_log('[LASSO DEBUG] Update checker initialization failed: ' . $e->getMessage());
                
                // Show admin notice for debugging
                add_action('admin_notices', function() use ($e) {
                    if (current_user_can('manage_options')) {
                        echo '<div class="notice notice-warning is-dismissible">';
                        echo '<p><strong>Lasso Leader:</strong> Update checker error: ' . esc_html($e->getMessage()) . '</p>';
                        echo '</div>';
                    }
                });
            }
        } else {
            error_log('[LASSO DEBUG] Update checker disabled - no valid license');
        }
    } else {
        error_log('[LASSO DEBUG] Update checker library not found at: ' . $update_checker_path);
        
        // Show admin notice
        add_action('admin_notices', function() {
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>Lasso Leader:</strong> Update checker library not found. Expected at: <code>lib/plugin-update-checker/plugin-update-checker.php</code></p>';
                echo '</div>';
            }
        });
    }
}
/**
 * The main Lasso Leader plugin class.
 * This class handles the loading of all plugin components and modules.
 */
final class Lasso_Leader {

    private static $_instance = null;
    public $api_handler;
    private $active_integrations = array();

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        // Check license before initializing anything
        if (!lasso_leader_check_license()) {
            add_action('admin_notices', array($this, 'show_license_notice'));
            return; // Don't initialize plugin functionality
        }
        
        // License is valid - proceed with normal initialization
        $this->detect_active_integrations();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Show license notice for unlicensed installations
     */
    public function show_license_notice() {
        $admin_url = admin_url('options-general.php?page=lasso-leader-license');
        echo '<div class="notice notice-error"><p><strong>Lasso Leader:</strong> This plugin requires a valid license to function. <a href="' . $admin_url . '">Enter your license key</a> or contact Jackson Murphy for licensing.</p></div>';
    }
	



    /**
     * Detect which form plugins are active and which integrations should be loaded.
     * This allows the plugin to only load necessary components based on available plugins.
     */
    private function detect_active_integrations() {
        // Check for Gravity Forms
        if ( class_exists( 'GFForms' ) ) {
            $this->active_integrations[] = 'gravity_forms';
        }
        
        // Check for Contact Form 7
        if ( defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' ) ) {
            $this->active_integrations[] = 'contact_form_7';
        }
        
        // Check if On-Site Registration is enabled using dedicated method
        if ( $this->detect_onsite_registration() ) {
            $this->active_integrations[] = 'onsite_registration';
        }
        
        // error_log( '[LASSO DEBUG] Active integrations detected: ' . implode( ', ', $this->active_integrations ) );
    }

    /**
     * Detect if On-Site Registration should be enabled.
     * This feature provides custom post types for Projects and Agents.
     * 
     * @return bool True if onsite registration should be enabled
     */
    private function detect_onsite_registration() {
        // Check if explicitly enabled in settings
        $onsite_enabled = get_option( 'lasso_leader_enable_onsite_registration', false );
        if ( $onsite_enabled ) {
            return true;
        }
        
        // Auto-enable if we have existing project or agent posts
        $existing_projects = get_posts( array(
            'post_type' => 'lasso_project',
            'numberposts' => 1,
            'post_status' => 'any',
            'fields' => 'ids'
        ) );
        
        $existing_agents = get_posts( array(
            'post_type' => 'lasso_agent', 
            'numberposts' => 1,
            'post_status' => 'any',
            'fields' => 'ids'
        ) );
        
        if ( ! empty( $existing_projects ) || ! empty( $existing_agents ) ) {
            // Auto-enable the setting if we have existing data
            update_option( 'lasso_leader_enable_onsite_registration', true );
            return true;
        }
        
        // Check if theme or other plugin explicitly requests onsite registration
        $force_enable = apply_filters( 'lasso_leader_force_onsite_registration', false );
        if ( $force_enable ) {
            return true;
        }
        
        return false;
    }

    /**
     * Include all required files for the plugin to function.
     * Files are loaded conditionally based on active integrations.
     */
    private function includes() {
        // Core functionality & helpers (always loaded)
        require_once LASSO_LEADER_PATH . 'includes/helper-functions.php';
        require_once LASSO_LEADER_PATH . 'includes/class-lasso-api-handler.php';
        require_once LASSO_LEADER_PATH . 'includes/class-lasso-project-override.php';
        require_once LASSO_LEADER_PATH . 'includes/class-lasso-debugger.php';
        require_once LASSO_LEADER_PATH . 'includes/class-lasso-settings.php';

        // Conditional loading based on active integrations
        if ( in_array( 'gravity_forms', $this->active_integrations ) ) {
            require_once LASSO_LEADER_PATH . 'includes/class-lasso-gravity-forms.php';
            // error_log( '[LASSO DEBUG] Gravity Forms integration loaded' );
        }
        
        if ( in_array( 'contact_form_7', $this->active_integrations ) ) {
            require_once LASSO_LEADER_PATH . 'includes/class-lasso-contact-form-7.php';
            // error_log( '[LASSO DEBUG] Contact Form 7 integration loaded' );
        }
        
        if ( in_array( 'onsite_registration', $this->active_integrations ) ) {
            require_once LASSO_LEADER_PATH . 'includes/class-lasso-onsite-registration.php';
            // error_log( '[LASSO DEBUG] On-Site Registration loaded' );
        }
        
        // Dynamic Form Generator (admin only)
        if ( is_admin() ) {
            require_once LASSO_LEADER_PATH . 'includes/class-lasso-dynamic-form-generator.php';
        }
    }

    /**
     * Hook into WordPress actions and filters.
     */
    private function init_hooks() {
        // Create the API handler for use by other modules
        $this->api_handler = new Lasso_API_Handler();

        // Initialize the Global Settings page and other admin menus
        $settings = new Lasso_Settings();
        if ( method_exists( $settings, 'set_active_integrations' ) ) {
            $settings->set_active_integrations( $this->active_integrations );
        }

        // Initialize integrations based on what's active
        $this->init_active_integrations();
        
        // Enqueue scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        
        // Dynamic Form Generator initialization (admin only)
        if ( is_admin() ) {
            add_action( 'plugins_loaded', array( $this, 'init_dynamic_form_generator' ) );
        }
    }

    /**
     * Initialize only the active integrations.
     */
    private function init_active_integrations() {
        // Initialize On-Site Registration module ONLY if enabled
        if ( in_array( 'onsite_registration', $this->active_integrations ) ) {
            $onsite_registration = new Lasso_OnSite_Registration();
            $onsite_registration->init();
            // error_log( '[LASSO DEBUG] On-Site Registration initialized' );
        }
        
        // Initialize Contact Form 7 integration ONLY if CF7 is active
        if ( in_array( 'contact_form_7', $this->active_integrations ) ) {
            $cf7_integration = new Lasso_Contact_Form_7( $this->api_handler );
            $cf7_integration->init();
            // error_log( '[LASSO DEBUG] Contact Form 7 integration initialized' );
        }

        // Initialize Gravity Forms Add-On ONLY if GF is active
        if ( in_array( 'gravity_forms', $this->active_integrations ) ) {
            add_action( 'gform_loaded', array( $this, 'load_gravity_forms_addon' ), 10 );
        }
    }
    
    /**
     * Check if a specific integration is active.
     */
    public function is_integration_active( $integration ) {
        return in_array( $integration, $this->active_integrations );
    }

    /**
     * Get list of active integrations.
     */
    public function get_active_integrations() {
        return $this->active_integrations;
    }

    /**
     * Initialize Dynamic Form Generator.
     */
    public function init_dynamic_form_generator() {
        if ( is_admin() ) {
            $form_generator = Lasso_Dynamic_Form_Generator::get_instance();
            $form_generator->init();
        }
    }

    /**
     * Load the Gravity Forms Add-On using the proper framework method.
     * This ensures proper integration with the Gravity Forms Add-On framework.
     */
    public function load_gravity_forms_addon() {
        // error_log('[LASSO DEBUG] === LOADING GRAVITY FORMS ADD-ON ===');
        
        // Check if Gravity Forms is active and has the Add-On framework
        if ( ! class_exists( 'GFForms' ) ) {
            error_log('[LASSO DEBUG] GFForms class not found - Gravity Forms may not be active');
            return;
        }
        
        // Include the Add-On framework
        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            error_log('[LASSO DEBUG] GFForms::include_addon_framework method not found');
            return;
        }
        
        GFForms::include_addon_framework();
        // error_log('[LASSO DEBUG] Gravity Forms Add-On framework included successfully');
        
        // Check if the framework loaded properly
        if ( ! class_exists( 'GFAddOn' ) ) {
            error_log('[LASSO DEBUG] GFAddOn class not available after including framework');
            return;
        }
        
        // Ensure our Add-On class is available
        if ( ! class_exists( 'Lasso_Gravity_Forms' ) ) {
            error_log('[LASSO DEBUG] Lasso_Gravity_Forms class not found - checking file path');
            $class_file = LASSO_LEADER_PATH . 'includes/class-lasso-gravity-forms.php';
            if ( file_exists( $class_file ) ) {
                require_once $class_file;
                error_log('[LASSO DEBUG] Lasso_Gravity_Forms class file loaded manually');
            } else {
                error_log('[LASSO DEBUG] ERROR: Lasso_Gravity_Forms class file not found at: ' . $class_file);
                return;
            }
        }
        
        // Register the Add-On with the framework
        GFAddOn::register( 'Lasso_Gravity_Forms' );
        // error_log('[LASSO DEBUG] Lasso_Gravity_Forms Add-On registered successfully');
        
        // Get the instance to trigger initialization
        $addon_instance = Lasso_Gravity_Forms::get_instance();
        if ( $addon_instance ) {
            // error_log('[LASSO DEBUG] Lasso_Gravity_Forms instance created successfully');
            error_log('[LASSO DEBUG] Add-On version: ' . $addon_instance->get_version());
        } else {
            error_log('[LASSO DEBUG] ERROR: Failed to create Lasso_Gravity_Forms instance');
        }
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        
        if ( strpos($current_page, 'lasso-leader') !== false || 
             strpos($hook_suffix, 'lasso-leader') !== false ) {
            
            // Use time() for cache busting during development
            $version = WP_DEBUG ? time() : LASSO_LEADER_VERSION;
            
            // Main CSS - force load
            wp_enqueue_style(
                'lasso-leader-admin-styles',
                LASSO_LEADER_URL . 'admin/css/lasso-leader-admin.css',
                array(),
                $version
            );
            
            // CF7 CSS if CF7 is active
            if ( defined('WPCF7_VERSION') ) {
                wp_enqueue_style(
                    'lasso-leader-cf7-styles',
                    LASSO_LEADER_URL . 'admin/css/lasso-cf7.css',
                    array('lasso-leader-admin-styles'),
                    $version
                );
            }
            
            // Admin JS
            wp_enqueue_script(
                'lasso-leader-admin-script',
                LASSO_LEADER_URL . 'admin/js/lasso-leader-admin.js',
                array('jquery'),
                $version,
                true
            );
        }
    }

    /**
     * Enqueue public/frontend scripts and styles.
     * Only loads assets when needed to improve performance.
     */
    public function enqueue_public_assets() {
        // Only enqueue on frontend (not admin)
        if ( is_admin() ) {
            return;
        }
        
        // Check if we should load public assets based on active integrations
        $should_load_public = false;
        
        // Load on pages with Lasso shortcodes (if onsite registration is active)
        if ( $this->is_integration_active( 'onsite_registration' ) && $this->page_has_lasso_shortcodes() ) {
            $should_load_public = true;
        }
        
        // Load on pages with Gravity Forms that have Lasso integration
        if ( $this->is_integration_active( 'gravity_forms' ) && $this->page_has_lasso_gravity_forms() ) {
            $should_load_public = true;
        }
        
        // Load on pages with Contact Form 7 that have Lasso integration
        if ( $this->is_integration_active( 'contact_form_7' ) && $this->page_has_lasso_cf7_forms() ) {
            $should_load_public = true;
        }
        
        // Load on custom post type pages (projects, agents) - only if onsite registration is active
        if ( $this->is_integration_active( 'onsite_registration' ) && is_singular( array( 'lasso_project', 'lasso_agent' ) ) ) {
            $should_load_public = true;
        }
        
        // Allow theme/plugin developers to force loading
        $should_load_public = apply_filters( 'lasso_leader_load_public_assets', $should_load_public );
        
        if ( $should_load_public ) {
            // Public CSS
            $public_style_path = LASSO_LEADER_PATH . 'public/css/lasso-leader-public.css';
            if ( file_exists( $public_style_path ) ) {
                wp_enqueue_style(
                    'lasso-leader-public-styles',
                    LASSO_LEADER_URL . 'public/css/lasso-leader-public.css',
                    array(),
                    filemtime( $public_style_path )
                );
            }
            
            // Public JavaScript (if needed)
            $public_script_path = LASSO_LEADER_PATH . 'public/js/lasso-leader-public.js';
            if ( file_exists( $public_script_path ) ) {
                wp_enqueue_script(
                    'lasso-leader-public-script',
                    LASSO_LEADER_URL . 'public/js/lasso-leader-public.js',
                    array( 'jquery' ),
                    filemtime( $public_script_path ),
                    true
                );
                
                // Add localized script data for frontend
                wp_localize_script( 'lasso-leader-public-script', 'lassoLeaderPublic', array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'lasso_leader_public_nonce' ),
                    'version' => LASSO_LEADER_VERSION,
                    'activeIntegrations' => $this->active_integrations
                ));
            }
        }
    }
    
    /**
     * Check if current page has Lasso shortcodes (only relevant for onsite registration).
     */
    private function page_has_lasso_shortcodes() {
        global $post;
        
        if ( ! $post ) {
            return false;
        }
        
        // Check for Lasso shortcodes in post content
        $lasso_shortcodes = array(
            'lasso_onsite_directory',
            'lasso_registration_status',
            'lasso_agent_profile',
            'lasso_project_grid',
            'lasso_registration_form'
        );
        
        foreach ( $lasso_shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if current page has Gravity Forms with Lasso integration.
     */
    private function page_has_lasso_gravity_forms() {
        global $post;
        
        if ( ! $post || ! class_exists( 'GFForms' ) ) {
            return false;
        }
        
        // Check for Gravity Forms shortcodes
        if ( has_shortcode( $post->post_content, 'gravityform' ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if current page has Contact Form 7 with Lasso integration.
     */
    private function page_has_lasso_cf7_forms() {
        global $post;
        
        if ( ! $post || ! defined( 'WPCF7_VERSION' ) ) {
            return false;
        }
        
        // Check for CF7 shortcodes
        if ( has_shortcode( $post->post_content, 'contact-form-7' ) ) {
            return true;
        }
        
        return false;
    }
}

/**
 * Helper function to add EV styling class to body (only if onsite registration is active).
 */
function lasso_leader_add_ev_body_class( $classes ) {
    $lasso_leader = Lasso_Leader::instance();
    
    // Only add EV styling if onsite registration is active
    if ( $lasso_leader->is_integration_active( 'onsite_registration' ) &&
         ( is_singular( array( 'lasso_project', 'lasso_agent' ) ) ||
           is_page( array( 'registration', 'register', 'signup', 'contact' ) ) ) ) {
        $classes[] = 'lasso-leader-frontend';
        $classes[] = 'ev-branded';
    }
    
    return $classes;
}
add_filter( 'body_class', 'lasso_leader_add_ev_body_class' );

/**
 * Helper function to wrap content with EV styling (only if onsite registration is active).
 */
function lasso_leader_wrap_content( $content ) {
    $lasso_leader = Lasso_Leader::instance();
    
    // Only wrap if onsite registration is active
    if ( $lasso_leader->is_integration_active( 'onsite_registration' ) &&
         ( is_singular( array( 'lasso_project', 'lasso_agent' ) ) ||
           is_page( array( 'registration', 'register', 'signup', 'contact' ) ) ) ) {
        $content = '<div class="lasso-leader-frontend">' . $content . '</div>';
    }
    
    return $content;
}
add_filter( 'the_content', 'lasso_leader_wrap_content' );

/**
 * The main function for running the plugin.
 */
function run_lasso_leader() {
    return Lasso_Leader::instance();
}

// Initialize the plugin
run_lasso_leader();

/**
 * Add a "Settings" link to the plugin's action links on the Plugins page.
 */
function add_lasso_leader_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=lasso-leader">' . __( 'Settings', 'lasso_leader' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_lasso_leader_settings_link' );

/**
 * Activation hook - create necessary database tables or options.
 */
function lasso_leader_activation() {
    // Set default options
    if ( ! get_option( 'lasso_leader_version' ) ) {
        update_option( 'lasso_leader_version', LASSO_LEADER_VERSION );
    }
    
    // Flush rewrite rules for custom post types (only if onsite registration will be used)
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'lasso_leader_activation' );

/**
 * Deactivation hook - clean up if necessary.
 */
function lasso_leader_deactivation() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'lasso_leader_deactivation' );

/**
 * Uninstall hook - remove all plugin data if desired.
 * This would only run if the plugin is deleted.
 */
function lasso_leader_uninstall() {
    // Clean up options, database tables, etc.
    // delete_option( 'lasso_leader_api_key' );
    // delete_option( 'lasso_leader_version' );
}

// EMERGENCY CSS LOADER - This will definitely work
add_action('admin_head', function() {
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    
    if (strpos($current_page, 'lasso-leader') !== false) {
        ?>
        <style type="text/css" id="lasso-emergency-css">
        /* EMERGENCY LASSO LEADER CSS */
        .lasso-admin, .lasso-leader-settings-wrap {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f1f1f1;
            margin: 20px 0;
        }
        
        .lasso-settings-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .lasso-settings-section h3 {
            background: #f8f9fa;
            border-bottom: 1px solid #e1e5e9;
            margin: 0;
            padding: 15px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #32373c;
        }
        
        .lasso-settings-section .inside {
            padding: 20px;
        }
        
        .lasso-api-key-container textarea {
            width: 100%;
            font-family: monospace;
            font-size: 12px;
            padding: 10px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .lasso-field-description {
            color: #646970;
            font-size: 13px;
            font-style: italic;
            display: block;
            margin-top: 5px;
        }
        
        .lasso-status-indicator {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .lasso-status-indicator.success {
            background: #d4edda;
            color: #155724;
        }
        
        .lasso-status-indicator.error {
            background: #f8d7da;
            color: #721c24;
        }
        </style>

        <!--	
        <script>
        console.log('Lasso Leader: Emergency CSS loaded!');
        </script>-->

        <?php
    }
}, 999);

/* 
 * =============================================================================
 * DEVELOPER NOTES FOR FUTURE DEVELOPMENT
 * =============================================================================
 * 
 * CRITICAL: API Handler VERSION 2.0-FINAL-FIX is working perfectly - DO NOT MODIFY
 * 
 * Current Status (98% Complete):
 * ✅ Core Infrastructure: Complete WordPress plugin with modular architecture
 * ✅ Lasso API Integration: Bearer authentication, two-payload architecture working
 * ✅ Gravity Forms Integration: Complete Add-On framework with visual field mapping
 * ✅ Contact Form 7 Integration: Hook-based integration with field mapping
 * ✅ Question Database: 38+ mapped Lasso questions organized by category
 * ✅ Project Association: Auto-population of Question 156759 with project selection
 * ✅ License System: Master license validation with GitHub integration
 * 
 * API Configuration:
 * - URL: https://api.lassocrm.com/v1/registrants
 * - Auth: Authorization: Bearer [JWT_TOKEN]
 * - Project ID: 25633 (Engel & Volkers Atlanta Onsite)
 * - Method: Two-payload (registrant creation, then questions)
 * 
 * License System:
 * - Master Secret: 4044064367
 * - License Generator: Tools → Generate Licenses
 * - License Manager: Settings → Lasso License
 * - GitHub Repository: Private license validation
 * 
 * Next Development Priorities:
 * 1. Master Form Creation with conditional logic
 * 2. Project-Specific Question Configuration
 * 3. Dynamic API Routing based on project selection
 * 4. Enhanced CF7 Integration
 * 
 * Key Working Mappings:
 * - Question 156759: "Which Projects are you interested in?" (Auto-populated)
 * - Question 157502: Contact Preference
 * - Question 156877: Are you an Agent?
 * - Standard Fields: First Name, Last Name, Email, Phone mapped correctly
 * 
 * Files NOT to Modify (Working Perfectly):
 * - class-lasso-api-handler.php (VERSION 2.0-FINAL-FIX)
 * - Two-payload architecture
 * - Bearer token authentication method
 * - Core field mapping logic
 * 
 * =============================================================================
 */
?>
