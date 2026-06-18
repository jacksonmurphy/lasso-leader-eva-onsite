<?php
/**
 * The core plugin class.
 */
class Lasso_Leader {

    protected $loader;
    protected $plugin_name;
    protected $version;
    private $api_handler;

    public function __construct( $version, Lasso_API_Handler $api_handler ) {
        $this->plugin_name = 'lasso-leader';
        $this->version = $version;
        $this->api_handler = $api_handler;

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_integrations();
    }

    private function load_dependencies() {
        $this->loader = new Lasso_Leader_Loader();
    }

    private function define_admin_hooks() {
        $plugin_settings = new Lasso_Settings();
        $this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );
        $this->loader->add_action( 'admin_menu', $plugin_settings, 'add_admin_menu' );
        // The admin_enqueue_scripts hook is now handled in the main plugin file.
    }

    private function define_public_hooks() {
        // Public hooks logic here...
    }

    private function define_integrations() {
        // Load Gravity Forms Add-On
        add_action( 'gform_loaded', array( $this, 'load_gravity_forms_addon' ), 5 );
        
        // Load On-Site Registration Module
        if ( get_option( 'lasso_leader_onsite_enabled' ) ) {
            require_once( plugin_dir_path( __FILE__ ) . 'class-lasso-onsite-registration.php' );
            $onsite_module = new Lasso_OnSite_Registration();
            $onsite_module->init();
        }
    }

    public function load_gravity_forms_addon() {
        if ( method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
            require_once( plugin_dir_path( __FILE__ ) . 'class-lasso-gravity-forms.php' );
            GFAddOn::register( 'Lasso_Gravity_Forms' );
        }
    }

    public function run() {
        $this->loader->run();
    }
}