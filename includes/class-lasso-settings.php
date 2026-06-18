<?php
/**
 * Handles plugin settings and all admin pages.
 * Version: 6.3.0-PRODUCTION - Client Ready
 */
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_Settings {

    private $main_menu_slug = 'lasso-leader';
    private $standard_lasso_fields = [
        '' => '-- Select Standard Field --', 
        'firstName' => 'First Name', 
        'lastName' => 'Last Name',
        'email' => 'Email', 
        'phone' => 'Phone', 
        'message' => 'Message/Notes',
    ];

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'load_cf7_mapping_page_logic' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Enqueue admin CSS and JS for Lasso Leader pages
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Only load on Lasso Leader admin pages
        if ( strpos( $hook_suffix, 'lasso-leader' ) !== false || 
             strpos( $hook_suffix, 'page_lasso-leader' ) !== false ) {
            
            // Enqueue CSS
            wp_enqueue_style(
                'lasso-leader-admin-styles',
                LASSO_LEADER_URL . 'admin/css/lasso-leader-admin.css',
                array(),
                LASSO_LEADER_VERSION
            );

            // Enqueue JS if needed
            wp_enqueue_script(
                'lasso-leader-admin-script',
                LASSO_LEADER_URL . 'admin/js/lasso-leader-admin.js',
                array( 'jquery' ),
                LASSO_LEADER_VERSION,
                true
            );
        }
    }

    public function register_settings() {
        $global_page = 'lasso_leader_global_page';
        $cf7_page = 'lasso_leader_cf7_settings_page';
        $dashboard_page = 'lasso_leader_dashboard_page';

        // Dashboard Page Settings
        register_setting( 'lasso_leader_dashboard_settings', 'lasso_leader_onsite_enabled', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ] );
        add_settings_section( 'lasso_leader_modules_section', __('Add-On Modules', 'lasso-leader'), null, $dashboard_page );
        add_settings_field( 'onsite_enabled', __('Enable On-Site Registration', 'lasso-leader'), [$this, 'checkbox_field_callback'], $dashboard_page, 'lasso_leader_modules_section', ['name' => 'lasso_leader_onsite_enabled', 'description' => 'Enables the On-Site Registration functionality, including custom post types and shortcodes.']);

        // Global Page Settings
        $global_group = 'lasso_leader_global_settings';
        register_setting( $global_group, 'lasso_leader_api_key' );
        register_setting( $global_group, 'lasso_leader_project_id' );
        register_setting( $global_group, 'lasso_leader_enable_tracking' );
        register_setting( $global_group, 'lasso_leader_analytics_account_id_global' );
        register_setting( $global_group, 'lasso_leader_custom_tracking_map' );
        register_setting( $global_group, 'lasso_leader_pages_to_exclude' );
        register_setting( $global_group, 'lasso_leader_debug_mode' );
        register_setting( $global_group, 'lasso_leader_debug_email' );
        
        add_settings_section( 'lasso_leader_api_section', __('Lasso CRM API Settings', 'lasso-leader'), null, $global_page );
        add_settings_field('api_key', __('Lasso API Key', 'lasso-leader'), [$this, 'api_key_field_callback'], $global_page, 'lasso_leader_api_section', ['name' => 'lasso_leader_api_key']);
        add_settings_field('project_id', __('Default Project ID', 'lasso-leader'), [$this, 'text_field_callback'], $global_page, 'lasso_leader_api_section', ['name' => 'lasso_leader_project_id']);

        add_settings_section( 'lasso_leader_tracking_section', __('Tracking Settings', 'lasso-leader'), null, $global_page );
        add_settings_field('enable_tracking', __('Enable Frontend Tracking', 'lasso-leader'), [$this, 'checkbox_field_callback'], $global_page, 'lasso_leader_tracking_section', ['name' => 'lasso_leader_enable_tracking']);
        add_settings_field('analytics_id', __('Lasso Analytics Account ID (Global)', 'lasso-leader'), [$this, 'text_field_callback'], $global_page, 'lasso_leader_tracking_section', ['name' => 'lasso_leader_analytics_account_id_global', 'description' => 'Your global Lasso Analytics Account ID (e.g., LAS-ABC).']);
        add_settings_field('custom_map', __('Custom Tracking Page IDs & Account IDs', 'lasso-leader'), [$this, 'textarea_field_callback'], $global_page, 'lasso_leader_tracking_section', ['name' => 'lasso_leader_custom_tracking_map', 'description' => 'Enter page IDs and their specific Lasso Account IDs, one per line, separated by an equals sign (=). Example: 123=LAS-XYZ']);
        add_settings_field('exclude_pages', __('Pages to Exclude from Tracking', 'lasso-leader'), [$this, 'textarea_field_callback'], $global_page, 'lasso_leader_tracking_section', ['name' => 'lasso_leader_pages_to_exclude', 'description' => 'Enter page IDs (comma or newline separated) where Lasso tracking should be completely disabled.']);
        
        add_settings_section( 'lasso_leader_debug_section', __('Debug Settings', 'lasso-leader'), null, $global_page );
        add_settings_field('debug_mode', __('Enable Debug Mode', 'lasso-leader'), [$this, 'checkbox_field_callback'], $global_page, 'lasso_leader_debug_section', ['name' => 'lasso_leader_debug_mode']);
        add_settings_field('debug_email', __('Debug Email Address', 'lasso-leader'), [$this, 'text_field_callback'], $global_page, 'lasso_leader_debug_section', ['name' => 'lasso_leader_debug_email']);

        // Contact Form 7 Page Settings
        if ( defined( 'WPCF7_VERSION' ) ) {
            register_setting( 'lasso_leader_cf7_settings', 'lasso_leader_cf7_enabled_forms', [ 'type' => 'array', 'sanitize_callback' => [ $this, 'sanitize_array_of_ints' ] ] );
            register_setting( 'lasso_leader_cf7_mappings_group', 'lasso_leader_cf7_mappings' );
            add_settings_section( 'lasso_leader_cf7_enable_section', __('Enable Forms', 'lasso-leader'), null, $cf7_page );
            add_settings_field( 'cf7_enabled_forms', __('Enable Lasso Integration for:', 'lasso-leader'), [$this, 'cf7_enabled_forms_field_callback'], $cf7_page, 'lasso_leader_cf7_enable_section' );
        }
    }

    public function add_admin_menu() {
        add_menu_page( 'Lasso Leader', 'Lasso Leader', 'manage_options', $this->main_menu_slug, [ $this, 'create_dashboard_page' ], 'dashicons-superhero', 80 );
        if ( defined( 'WPCF7_VERSION' ) ) {
            add_submenu_page($this->main_menu_slug, 'CF7 Settings', 'Contact Form 7', 'manage_options', 'lasso-leader-cf7-settings', [ $this, 'create_cf7_settings_page' ]);
            add_submenu_page(null, 'Map CF7 Fields', 'Map CF7 Fields', 'manage_options', 'lasso-leader-cf7-mapping', [ $this, 'create_cf7_mapping_page' ]);
        }
        add_submenu_page($this->main_menu_slug, 'Global Settings', 'Global Settings', 'manage_options', 'lasso-leader-global-settings', [ $this, 'create_global_settings_page' ]);
    }
    
    /**
     * Dashboard Page
     */
    public function create_dashboard_page() {
        ?>
        <div class="wrap lasso-admin lasso-leader-settings-wrap">
            <div class="lasso-settings-section">
                <h3>Lasso Leader Dashboard</h3>
                
                <div class="inside">
                    <p>Lasso Leader is a purpose-built integration that captures form submissions and transmits that data to the correct projects and agents within Lasso CRM. It includes a user-friendly settings interface within the WordPress admin to manage the connection, as well as advanced, per-form controls for complex routing and data mapping. The plugin correctly handles the submission of both standard registrant data and complex custom questions via Lasso's two-step API process. It also includes a module for injecting Lasso's frontend analytics tracking script.</p>
                    
                    <h4>Core Features</h4>
                    <ul style="list-style: disc; padding-left: 20px;">
                        <li><strong>Global Settings:</strong> A centralized dashboard to manage global settings, including a primary Lasso API Key, a default Project ID, and settings for add-on modules.</li>
                        <li><strong>Gravity Forms Integration:</strong> Enable integration on a form-by-form basis with per-form overrides and a detailed visual interface for field mapping.</li>
                        <li><strong>Contact Form 7 Integration:</strong> Enable specific forms and use a full visual interface to map form fields, eliminating the need for custom code.</li>
                        <li><strong>Frontend Analytics Tracking:</strong> Injects the Lasso Analytics tracking script across the website with global and page-specific override controls.</li>
                        <li><strong>Dynamic Form Generator:</strong> Visual form builder with complete Lasso questions database and auto-mapping functionality.</li>
                    </ul>
                    
                    <!-- Quick Access Button -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e1e1e1;">
                        <h4>Quick Access</h4>
                        <a href="<?php echo admin_url('admin.php?page=lasso-leader-global-settings'); ?>" 
                           class="lasso-quick-action-btn" 
                           style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 4px; transition: all 0.3s ease; font-size: 14px; font-weight: 500;">
                            <span class="dashicons dashicons-admin-settings" style="font-size: 16px;"></span>
                            Configure Global Settings
                        </a>
                        <p style="margin-top: 10px; color: #666; font-size: 13px;">
                            Set up your Lasso API key, default project ID, and other global configuration options.
                        </p>
                    </div>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('lasso_leader_dashboard_settings');
                do_settings_sections('lasso_leader_dashboard_page');
                ?>
                <button type="submit" class="lasso-save-mappings">
                    <span class="dashicons dashicons-saved"></span>
                    Save Module Settings
                </button>
            </form>

            <div class="lasso-settings-section">
                <h3>System Information</h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th>Plugin Version</th>
                            <td><?php echo esc_html(defined('LASSO_LEADER_VERSION') ? LASSO_LEADER_VERSION : 'Unknown'); ?></td>
                        </tr>
                        <tr>
                            <th>WordPress Version</th>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <th>PHP Version</th>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th>Gravity Forms</th>
                            <td>
                                <?php if (class_exists('GFForms')): ?>
                                    <span class="lasso-status-indicator success">Active</span>
                                    Version: <?php echo esc_html(GFForms::$version); ?>
                                <?php else: ?>
                                    <span class="lasso-status-indicator error">Not Installed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Contact Form 7</th>
                            <td>
                                <?php if (defined('WPCF7_VERSION')): ?>
                                    <span class="lasso-status-indicator success">Active</span>
                                    Version: <?php echo esc_html(WPCF7_VERSION); ?>
                                <?php else: ?>
                                    <span class="lasso-status-indicator error">Not Installed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>API Key Status</th>
                            <td>
                                <?php if (get_option('lasso_leader_api_key')): ?>
                                    <span class="lasso-status-indicator success">Configured</span>
                                <?php else: ?>
                                    <span class="lasso-status-indicator error">Not Configured</span>
                                    <a href="<?php echo admin_url('admin.php?page=lasso-leader-global-settings'); ?>" 
                                       style="margin-left: 10px; font-size: 12px; color: #0073aa;">
                                        Configure Now
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="lasso-leader-footer" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; color: #777;">
                <p><?php echo sprintf( esc_html__( 'Lasso Leader Version: %s', 'lasso-leader' ), esc_html( LASSO_LEADER_VERSION ) ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * CF7 Settings Page
     */
    public function create_cf7_settings_page() {
        ?>
        <div class="wrap lasso-admin lasso-leader-settings-wrap">
            <div class="lasso-settings-section">
                <h3>Lasso Leader - Contact Form 7 Settings</h3>
                
                <div class="inside">
                    <p>Configure which Contact Form 7 forms should integrate with Lasso CRM. Enable forms below and then configure field mappings for each enabled form.</p>

                    <form method="post" action="options.php">
                        <?php
                        settings_fields('lasso_leader_cf7_settings');
                        
                        echo '<h4>Enable Forms</h4>';
                        echo '<p>Select which Contact Form 7 forms should send data to Lasso CRM. You can configure field mappings for each enabled form.</p>';
                        
                        $this->cf7_enabled_forms_field_callback();
                        ?>
                        
                        <button type="submit" class="lasso-save-mappings">
                            <span class="dashicons dashicons-saved"></span>
                            Save CF7 Settings
                        </button>
                    </form>
                    
                    <!-- Help Section -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e1e1e1;">
                        <h4>Contact Form 7 Integration Help</h4>
                        <ul style="list-style: disc; padding-left: 20px;">
                            <li><strong>Enable Integration:</strong> Check the forms you want to connect to Lasso CRM.</li>
                            <li><strong>Configure Mapping:</strong> After enabling a form, use the "Configure Mapping" button to set up field mappings.</li>
                            <li><strong>Standard Fields:</strong> Map basic contact information (name, email, phone)</li>
                            <li><strong>Custom Questions:</strong> Map form fields to specific Lasso question IDs</li>
                            <li><strong>Form Processing:</strong> Enabled forms will automatically send submissions to Lasso CRM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Global Settings Page
     */
    public function create_global_settings_page() {
        ?>
        <div class="wrap">
            <div class="lasso-mapping-container">
                <div class="lasso-mapping-header">
                    <h1>
                        <span class="dashicons dashicons-admin-settings"></span>
                        Lasso Leader - Global Settings
                    </h1>
                    <p>Configure global settings including API credentials, tracking, and debug options.</p>
                </div>

                <form method="post" action="options.php" class="lasso-global-settings-form">
                    <?php
                    settings_fields('lasso_leader_global_settings');
                    
                    $this->render_api_settings_section();
                    $this->render_tracking_settings_section();
                    $this->render_debug_settings_section();
                    ?>
                    
                    <button type="submit" class="lasso-save-mappings">
                        <span class="dashicons dashicons-saved"></span>
                        Save Global Settings
                    </button>
                </form>
                
                <!-- Help Section -->
                <div class="lasso-mapping-help">
                    <h3>Global Settings Help</h3>
                    <p><strong>API Settings:</strong> Configure your Lasso CRM connection with JWT API key and default project ID.</p>
                    <p><strong>Tracking Settings:</strong> Enable frontend analytics tracking across your website.</p>
                    <p><strong>Debug Settings:</strong> Enable debugging mode and email notifications for troubleshooting.</p>
                    <ul>
                        <li><strong>API Key:</strong> Your JWT token from Lasso CRM (starts with "eyJ")</li>
                        <li><strong>Project ID:</strong> Default project ID for form submissions</li>
                        <li><strong>Debug Mode:</strong> Shows additional logging and debug information</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render API Settings Section
     */
    private function render_api_settings_section() {
        ?>
        <div class="lasso-cf7-content">
            <h2>
                <span class="dashicons dashicons-admin-network"></span>
                Lasso CRM API Settings
            </h2>
            <p>Configure your connection to Lasso CRM with your API credentials.</p>
            
            <div class="lasso-settings-grid">
                <div class="lasso-form-row">
                    <label class="lasso-form-label" for="lasso_leader_api_key">
                        Lasso API Key <span class="lasso-required">*</span>
                    </label>
                    <div class="lasso-form-control">
                        <?php $this->api_key_field_callback(['name' => 'lasso_leader_api_key']); ?>
                    </div>
                </div>
                
                <div class="lasso-form-row">
                    <label class="lasso-form-label" for="lasso_leader_project_id">
                        Default Project ID
                    </label>
                    <div class="lasso-form-control">
                        <?php $this->text_field_callback(['name' => 'lasso_leader_project_id']); ?>
                        <span class="lasso-field-description">
                            The default project ID to use when no specific project is configured.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Tracking Settings Section
     */
    private function render_tracking_settings_section() {
        ?>
        <div class="lasso-cf7-content">
            <h2>
                <span class="dashicons dashicons-chart-line"></span>
                Tracking Settings
            </h2>
            <p>Configure frontend analytics tracking for your website.</p>
            
            <div class="lasso-settings-grid">
                <div class="lasso-form-row">
                    <label class="lasso-form-label">
                        <input type="checkbox" 
                               id="lasso_leader_enable_tracking" 
                               name="lasso_leader_enable_tracking" 
                               value="1" 
                               <?php checked(1, get_option('lasso_leader_enable_tracking', false)); ?>>
                        Enable Frontend Tracking
                    </label>
                    <span class="lasso-field-description">
                        Enables the Lasso Analytics tracking script on your website.
                    </span>
                </div>
                
                <div class="lasso-form-row">
                    <label class="lasso-form-label" for="lasso_leader_analytics_account_id_global">
                        Lasso Analytics Account ID (Global)
                    </label>
                    <div class="lasso-form-control">
                        <?php $this->text_field_callback([
                            'name' => 'lasso_leader_analytics_account_id_global',
                            'description' => 'Your global Lasso Analytics Account ID (e.g., LAS-ABC).'
                        ]); ?>
                    </div>
                </div>
                
                <div class="lasso-form-row">
                    <label class="lasso-form-label" for="lasso_leader_custom_tracking_map">
                        Custom Tracking Page IDs & Account IDs
                    </label>
                    <div class="lasso-form-control">
                        <?php $this->textarea_field_callback([
                            'name' => 'lasso_leader_custom_tracking_map',
                            'description' => 'Enter page IDs and their specific Lasso Account IDs, one per line, separated by an equals sign (=). Example: 123=LAS-XYZ'
                        ]); ?>
                    </div>
                </div>
                
                <div class="lasso-form-row">
                    <label class="lasso-form-label" for="lasso_leader_pages_to_exclude">
                        Pages to Exclude from Tracking
                    </label>
                    <div class="lasso-form-control">
                        <?php $this->textarea_field_callback([
                            'name' => 'lasso_leader_pages_to_exclude',
                            'description' => 'Enter page IDs (comma or newline separated) where Lasso tracking should be completely disabled.'
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Debug Settings Section
     */
    private function render_debug_settings_section() {
        ?>
        <div class="lasso-cf7-content">
            <h2>
                <span class="dashicons dashicons-admin-tools"></span>
                Debug Settings
            </h2>
            <p>Configure debugging options for troubleshooting and development.</p>
            
            <div class="lasso-settings-grid">
                <div class="lasso-form-row">
                    <label class="lasso-form-label">
                        <input type="checkbox" 
                               id="lasso_leader_debug_mode" 
                               name="lasso_leader_debug_mode" 
                               value="1" 
                               <?php checked(1, get_option('lasso_leader_debug_mode', false)); ?>>
                        Enable Debug Mode
                    </label>
                    <span class="lasso-field-description">
                        Shows debug notices and enables detailed logging for troubleshooting.
                    </span>
                </div>
                
                <div class="lasso-form-row">
                    <label class="lasso-form-label" for="lasso_leader_debug_email">
                        Debug Email Address
                    </label>
                    <div class="lasso-form-control">
                        <?php $this->text_field_callback(['name' => 'lasso_leader_debug_email']); ?>
                        <span class="lasso-field-description">
                            Email address to receive debug notifications and error reports.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * CF7 Mapping Page
     */
    public function create_cf7_mapping_page() {
        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        
        if ( ! $form_id || ! class_exists('WPCF7_ContactForm') ) { 
            wp_die('Invalid form ID or Contact Form 7 not active.'); 
        }
        
        $form = WPCF7_ContactForm::get_instance($form_id);
        if (!$form) {
            wp_die('Form not found.');
        }
        
        $form_tags = $form->scan_form_tags();
        $all_mappings = get_option('lasso_leader_cf7_mappings', []);
        $current_mappings = $all_mappings[$form_id] ?? [];
        
        // Show success message if form was submitted
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>Mappings saved successfully!</p></div>';
        }
        ?>
        
        <div class="wrap lasso-admin lasso-leader-settings-wrap">
            <div class="lasso-settings-section">
                <h3>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php echo sprintf( esc_html__( 'Map Fields for: %s', 'lasso-leader' ), esc_html( $form->title() ) ); ?>
                </h3>
                
                <div class="inside">
                    <p><?php esc_html_e( 'Configure the field mappings for this specific form below.', 'lasso-leader' ); ?></p>

                    <form method="post" action="">
                        <?php wp_nonce_field( 'lasso_leader_cf7_save_mappings_' . $form_id, '_lasso_leader_cf7_nonce' ); ?>
                        
                        <table class="lasso-mapping-table">
                            <thead>
                                <tr>
                                    <th>Contact Form 7 Field</th>
                                    <th>Map to Standard Lasso Field</th>
                                    <th>OR Map to Custom Lasso Question</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($form_tags as $tag) : 
                                    if (empty($tag->name)) continue;
                                    $field_name = $tag->name;
                                    $mapping = $current_mappings[$field_name] ?? []; 
                                ?>
                                <tr>
                                    <td>
                                        <div class="lasso-field-name">
                                            <?php echo esc_html($field_name); ?>
                                        </div>
                                        <div class="lasso-field-meta">
                                            Type: <?php echo esc_html($tag->type); ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <select name="mappings[<?php echo esc_attr($field_name); ?>][standard_field]" 
                                                class="lasso-mapping-select">
                                            <?php foreach ($this->standard_lasso_fields as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" 
                                                        <?php selected( ($mapping['type'] ?? '') === 'standard' && ($mapping['value'] ?? '') === $value ); ?>>
                                                    <?php echo esc_html($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    
                                    <td>
                                        <div class="lasso-mapping-controls">
                                            <input type="text" 
                                                   name="mappings[<?php echo esc_attr($field_name); ?>][question_id]" 
                                                   placeholder="Question ID"
                                                   value="<?php echo esc_attr( ($mapping['type'] ?? '') === 'question' ? ($mapping['question_id'] ?? '') : '' ); ?>" 
                                                   class="lasso-question-input">
                                            
                                            <select name="mappings[<?php echo esc_attr($field_name); ?>][question_type]" 
                                                    class="lasso-type-select">
                                                <option value="">-- Type --</option>
                                                <option value="text_answer" 
                                                        <?php selected( ($mapping['type'] ?? '') === 'question' && ($mapping['question_type'] ?? '') === 'text_answer' ); ?>>
                                                    Text
                                                </option>
                                                <option value="answer_id" 
                                                        <?php selected( ($mapping['type'] ?? '') === 'question' && ($mapping['question_type'] ?? '') === 'answer_id' ); ?>>
                                                    Answer ID
                                                </option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <button type="submit" name="save_mappings" class="lasso-save-mappings">
                            <span class="dashicons dashicons-saved"></span>
                            Save Mappings
                        </button>
                    </form>
                    
                    <!-- Help Section -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e1e1e1;">
                        <h4>Field Mapping Help</h4>
                        <ul style="list-style: disc; padding-left: 20px;">
                            <li><strong>Standard Fields:</strong> Map common form fields like name, email, phone to Lasso's standard registrant fields.</li>
                            <li><strong>Custom Questions:</strong> Map form fields to specific Lasso question IDs for detailed lead capture.</li>
                            <li><strong>Question ID:</strong> Enter the numerical ID of the Lasso question</li>
                            <li><strong>Answer Type:</strong> Choose "Text" for open-ended responses or "Answer ID" for predefined options</li>
                            <li><strong>Answer ID Format:</strong> For dropdown/radio fields, use the numerical answer ID from Lasso</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enhanced API Key field with large textarea display
     */
    public function api_key_field_callback($args) {
        $option_name = $args['name'];
        $value = get_option($option_name, '');
        ?>
        <div class="lasso-api-key-container">
            <textarea name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($option_name); ?>" 
                      rows="4" style="width: 100%; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #8c8f94; border-radius: 4px; background: #f9f9f9;"
                      placeholder="Enter your Lasso CRM API key here..."><?php echo esc_textarea($value); ?></textarea>
            <span class="lasso-field-description">
                Your JWT API key from Lasso CRM. This is project-specific and should start with "eyJ".
            </span>
        </div>
        <?php
    }

    public function text_field_callback($args) {
        $option_name = $args['name'];
        $value = get_option($option_name, '');
        echo '<input type="text" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($value) . '" class="regular-text">';
        if (!empty($args['description'])) {
            echo '<span class="lasso-field-description">' . esc_html($args['description']) . '</span>';
        }
    }

    public function checkbox_field_callback($args) {
        $option_name = $args['name'];
        $checked = get_option($option_name, false);
        echo '<input type="checkbox" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="1" ' . checked(1, $checked, false) . '>';
        if (!empty($args['description'])) {
            echo '<label for="' . esc_attr($option_name) . '"> ' . esc_html($args['description']) . '</label>';
        }
    }

    public function textarea_field_callback($args) {
        $option_name = $args['name'];
        $value = get_option($option_name, '');
        echo '<textarea id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        if (!empty($args['description'])) {
            echo '<span class="lasso-field-description">' . esc_html($args['description']) . '</span>';
        }
    }
    
    /**
     * CF7 Form Callback - Clean button styling
     */
    public function cf7_enabled_forms_field_callback() {
        $forms = get_posts(['post_type' => 'wpcf7_contact_form', 'numberposts' => -1]);
        $enabled_forms = get_option('lasso_leader_cf7_enabled_forms', []);
        
        if ( empty($forms) ) { 
            echo '<div style="background: #d1ecf1; border: 1px solid #bee5eb; border-left: 4px solid #17a2b8; border-radius: 4px; padding: 15px; margin: 15px 0; display: flex; align-items: center; gap: 15px;">';
            echo '<span class="dashicons dashicons-info" style="color: #17a2b8; font-size: 16px; flex-shrink: 0;"></span>';
            echo '<p style="margin: 0; color: #0c5460;">' . esc_html__('No Contact Form 7 forms found. Create a form first, then return here to enable Lasso integration.', 'lasso-leader') . '</p>';
            echo '</div>';
            return; 
        }
        
        foreach ($forms as $form) {
            $is_checked = is_array($enabled_forms) && in_array($form->ID, $enabled_forms);
            $mapping_url = admin_url('admin.php?page=lasso-leader-cf7-mapping&form_id=' . $form->ID);
            
            echo '<div class="lasso-form-card' . ($is_checked ? ' enabled' : '') . '">';
            
            echo '<div class="lasso-form-info">';
            echo '<h3>';
            echo '<input type="checkbox" name="lasso_leader_cf7_enabled_forms[]" value="' . esc_attr($form->ID) . '" ' . checked($is_checked, true, false) . '>';
            echo ' ' . esc_html($form->post_title);
            echo '<span class="form-id">ID: ' . esc_html($form->ID) . '</span>';
            echo '</h3>';
            echo '</div>';
            
            echo '<div class="lasso-form-actions">';
            if ($is_checked) { 
                echo '<a href="' . esc_url($mapping_url) . '" class="lasso-configure-btn">';
                echo '<span class="dashicons dashicons-admin-settings"></span>';
                echo 'Configure Mapping';
                echo '</a>';
            }
            echo '</div>';
            
            echo '</div>';
        }
    }
    
    public function save_cf7_mappings() {
        if ( !isset($_POST['mappings']) || !isset($_GET['form_id']) ) { return; }
        $form_id = absint($_GET['form_id']);
        if ( ! $form_id || !isset($_POST['_lasso_leader_cf7_nonce']) || !wp_verify_nonce($_POST['_lasso_leader_cf7_nonce'], 'lasso_leader_cf7_save_mappings_' . $form_id) ) { return; }
        $all_mappings = get_option('lasso_leader_cf7_mappings', []);
        $new_mappings_for_form = [];
        foreach ($_POST['mappings'] as $field_name => $data) {
            $field_name = sanitize_text_field($field_name);
            if (!empty($data['standard_field'])) {
                $new_mappings_for_form[$field_name] = ['type'  => 'standard', 'value' => sanitize_text_field($data['standard_field'])];
            } elseif (!empty($data['question_id']) && !empty($data['question_type'])) {
                $new_mappings_for_form[$field_name] = ['type' => 'question', 'question_id' => absint($data['question_id']), 'question_type' => sanitize_text_field($data['question_type'])];
            }
        }
        $all_mappings[$form_id] = $new_mappings_for_form;
        update_option('lasso_leader_cf7_mappings', $all_mappings);
        add_settings_error('lasso_leader_cf7_mappings', 'settings_updated', __('Mappings saved successfully.'), 'updated');
    }
    
    public function load_cf7_mapping_page_logic() {
        if (isset($_GET['page']) && $_GET['page'] === 'lasso-leader-cf7-mapping' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save_cf7_mappings();
        }
    }
    
    public function sanitize_array_of_ints( $input ) {
        if ( ! is_array( $input ) ) { return []; }
        return array_map('absint', $input);
    }
}
                                            