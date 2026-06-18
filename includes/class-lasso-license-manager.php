<?php
/**
 * Lasso Leader License Manager - Fixed Version
 * This file should be: includes/class-lasso-license-manager.php
 */

class Lasso_Leader_License_Manager {
    
    private $license_option = 'lasso_leader_license_key';
    private $license_status_option = 'lasso_leader_license_status';
    private $github_repo = 'jacksonmurphy/lasso-leader-licenses'; // UPDATE THIS!
    private $master_secret = '4044064367'; // Same secret as your generator
    private static $instance = null;
    private $page_rendered = false; // Prevent duplicate rendering
    
    public function __construct() {
        // Prevent multiple instances
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = $this;
        
        // Hook into WordPress
        add_action('admin_init', array($this, 'check_license_status'));
        add_action('admin_notices', array($this, 'license_admin_notices'));
        add_action('wp_ajax_lasso_activate_license', array($this, 'activate_license'));
        add_action('wp_ajax_lasso_deactivate_license', array($this, 'deactivate_license'));
        add_action('admin_menu', array($this, 'add_license_page'));
        
        // Prevent plugin functionality if unlicensed
        if (!$this->is_license_valid()) {
            add_action('admin_init', array($this, 'disable_plugin_functionality'));
        }
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Validate license key
     */
    public function validate_license($license_key) {
        if (empty($license_key)) {
            return array('status' => 'invalid', 'message' => 'License key is required');
        }
        
        // Split license and signature
        $parts = explode('.', $license_key);
        if (count($parts) !== 2) {
            return array('status' => 'invalid', 'message' => 'Invalid license format');
        }
        
        $license_string = $parts[0];
        $signature = $parts[1];
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', $license_string, $this->master_secret);
        if (!hash_equals($expected_signature, $signature)) {
            return array('status' => 'invalid', 'message' => 'License signature invalid');
        }
        
        // Decode license data
        $license_data = json_decode(base64_decode($license_string), true);
        if (!$license_data) {
            return array('status' => 'invalid', 'message' => 'License data corrupted');
        }
        
        // Check expiry
        if (time() > $license_data['expires']) {
            return array('status' => 'expired', 'message' => 'License has expired');
        }
        
        // Check domain (allow localhost for development)
        $current_domain = parse_url(get_site_url(), PHP_URL_HOST);
        $licensed_domain = $license_data['domain'];
        
        if ($current_domain !== $licensed_domain && 
            $licensed_domain !== 'localhost' && 
            $current_domain !== 'localhost') {
            return array('status' => 'invalid', 'message' => 'License not valid for this domain');
        }
        
        return array(
            'status' => 'valid', 
            'message' => 'License valid',
            'data' => $license_data
        );
    }
    
    /**
     * Check if current license is valid
     */
    public function is_license_valid() {
        $license_key = get_option($this->license_option);
        $license_status = get_option($this->license_status_option);
        
        if (empty($license_key)) {
            return false;
        }
        
        // Quick check of stored status
        if ($license_status === 'valid') {
            // Re-validate periodically (every 7 days)
            $last_check = get_option('lasso_license_last_check', 0);
            if (time() - $last_check < (7 * 24 * 60 * 60)) {
                return true;
            }
        }
        
        // Full validation
        $validation = $this->validate_license($license_key);
        update_option($this->license_status_option, $validation['status']);
        update_option('lasso_license_last_check', time());
        
        return $validation['status'] === 'valid';
    }
    
    /**
     * Check license status on admin pages
     */
    public function check_license_status() {
        if (!$this->is_license_valid()) {
            // Deactivate plugin functionality
            $this->disable_plugin_functionality();
        }
    }
    
    /**
     * Display admin notices for license issues (only once per page)
     */
    public function license_admin_notices() {
        static $notice_shown = false;
        
        if ($notice_shown) {
            return; // Prevent duplicate notices
        }
        
        $license_status = get_option($this->license_status_option);
        
        if ($license_status !== 'valid') {
            $message = '';
            switch ($license_status) {
                case 'expired':
                    $message = 'Your Lasso Leader license has expired. Please contact Jackson Murphy for renewal.';
                    break;
                case 'invalid':
                    $message = 'Your Lasso Leader license is invalid. Plugin functionality is disabled.';
                    break;
                default:
                    $message = 'Lasso Leader requires a valid license key to function. Please enter your license key.';
            }
            
            echo '<div class="notice notice-error"><p><strong>Lasso Leader:</strong> ' . esc_html($message) . ' <a href="' . admin_url('options-general.php?page=lasso-leader-license') . '">Enter License Key</a></p></div>';
            $notice_shown = true;
        }
    }
    
    /**
     * AJAX handler for license activation
     */
    public function activate_license() {
        check_ajax_referer('lasso_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        $validation = $this->validate_license($license_key);
        
        if ($validation['status'] === 'valid') {
            update_option($this->license_option, $license_key);
            update_option($this->license_status_option, 'valid');
            update_option('lasso_license_last_check', time());
            
            wp_send_json_success(array(
                'message' => 'License activated successfully!',
                'expires' => date('Y-m-d', $validation['data']['expires']),
                'domain' => $validation['data']['domain']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $validation['message']
            ));
        }
    }
    
    /**
     * AJAX handler for license deactivation
     */
    public function deactivate_license() {
        check_ajax_referer('lasso_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        delete_option($this->license_option);
        delete_option($this->license_status_option);
        delete_option('lasso_license_last_check');
        
        wp_send_json_success(array(
            'message' => 'License deactivated successfully!'
        ));
    }
    
    /**
     * Disable plugin functionality for unlicensed installations
     */
    public function disable_plugin_functionality() {
        // Remove Gravity Forms hooks
        remove_all_actions('gform_after_submission');
        
        // Remove Contact Form 7 hooks
        remove_all_actions('wpcf7_mail_sent');
        
        // Remove any other Lasso Leader hooks
        remove_all_actions('lasso_process_submission');
        
        // Disable form processing
        add_filter('lasso_leader_enabled', '__return_false');
    }
    
    /**
     * Add license settings page (only once)
     */
    public function add_license_page() {
        static $page_added = false;
        
        if ($page_added) {
            return; // Prevent duplicate menu items
        }
        
        add_options_page(
            'Lasso Leader License',
            'Lasso License',
            'manage_options',
            'lasso-leader-license',
            array($this, 'license_page_html')
        );
        
        $page_added = true;
    }
    
    /**
     * License settings page HTML (render only once)
     */
    public function license_page_html() {
        // Prevent duplicate rendering
        if ($this->page_rendered) {
            return;
        }
        $this->page_rendered = true;
        
        $license_key = get_option($this->license_option);
        $license_status = get_option($this->license_status_option);
        $license_data = null;
        
        if ($license_key && $license_status === 'valid') {
            $validation = $this->validate_license($license_key);
            if ($validation['status'] === 'valid') {
                $license_data = $validation['data'];
            }
        }
        ?>
        <div class="wrap">
            <h1>🔑 Lasso Leader License</h1>
            <p>Manage your Lasso Leader plugin license activation.</p>
            
            <div id="license-message"></div>
            
            <div style="background: white; padding: 20px; border: 1px solid #ddd; margin: 20px 0; border-radius: 5px;">
                <form id="lasso-license-form">
                    <?php wp_nonce_field('lasso_license_nonce', 'nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">License Key</th>
                            <td>
                                <input type="text" 
                                       id="license_key" 
                                       name="license_key" 
                                       value="<?php echo esc_attr($license_key ? substr($license_key, 0, 50) . '...' : ''); ?>" 
                                       class="large-text" 
                                       placeholder="Enter your license key here" />
                                <p class="description">Enter the license key provided by Jackson Murphy</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td>
                                <span class="license-status license-<?php echo esc_attr($license_status ?: 'inactive'); ?>">
                                    <?php 
                                    switch($license_status) {
                                        case 'valid':
                                            echo '✅ Active';
                                            break;
                                        case 'expired':
                                            echo '⏰ Expired';
                                            break;
                                        case 'invalid':
                                            echo '❌ Invalid';
                                            break;
                                        default:
                                            echo '⚪ Not Activated';
                                    }
                                    ?>
                                </span>
                            </td>
                        </tr>
                        
                        <?php if ($license_data): ?>
                        <tr>
                            <th scope="row">Licensed Domain</th>
                            <td><?php echo esc_html($license_data['domain']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Expires</th>
                            <td><?php echo date('F j, Y', $license_data['expires']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Version</th>
                            <td><?php echo esc_html($license_data['version']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="activate-license" class="button button-primary">
                            <?php echo $license_status === 'valid' ? 'Update License' : 'Activate License'; ?>
                        </button>
                        <?php if ($license_status === 'valid'): ?>
                        <button type="button" id="deactivate-license" class="button">
                            Deactivate License
                        </button>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <?php if ($license_status !== 'valid'): ?>
            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 5px;">
                <h4>⚠️ Plugin Functionality Disabled</h4>
                <p>The Lasso Leader plugin requires a valid license to function. Without a license:</p>
                <ul>
                    <li>Form submissions will not be sent to Lasso CRM</li>
                    <li>API integration is disabled</li>
                    <li>Field mapping is unavailable</li>
                </ul>
                <p><strong>Contact Jackson Murphy</strong> to obtain a license key for this domain: <code><?php echo esc_html(parse_url(get_site_url(), PHP_URL_HOST)); ?></code></p>
            </div>
            <?php endif; ?>
            
            <div style="background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 20px 0; border-radius: 5px;">
                <h4>📖 License Information</h4>
                <ul>
                    <li><strong>Domain Binding:</strong> Each license is tied to a specific domain</li>
                    <li><strong>Feature Access:</strong> Valid licenses enable all plugin functionality</li>
                    <li><strong>Support:</strong> Licensed users receive priority support</li>
                    <li><strong>Updates:</strong> Automatic updates require valid license</li>
                </ul>
            </div>
        </div>
        
        <style>
            .license-valid { color: #46b450; font-weight: bold; }
            .license-invalid, .license-expired { color: #dc3232; font-weight: bold; }
            .license-inactive { color: #666; font-weight: bold; }
            #license-message { margin-top: 20px; }
            .notice { padding: 10px; margin: 10px 0; border-left: 4px solid; border-radius: 5px; }
            .notice-success { background: #d4edda; border-color: #46b450; color: #155724; }
            .notice-error { background: #f8d7da; border-color: #dc3232; color: #721c24; }
            .large-text { width: 100%; max-width: 600px; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#activate-license').click(function() {
                var licenseKey = $('#license_key').val();
                var nonce = $('[name="nonce"]').val();
                var button = $(this);
                
                if (!licenseKey.trim()) {
                    showMessage('Please enter a license key', 'error');
                    return;
                }
                
                button.prop('disabled', true).text('Activating...');
                
                $.post(ajaxurl, {
                    action: 'lasso_activate_license',
                    license_key: licenseKey,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        showMessage('License activated successfully! Expires: ' + response.data.expires, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage('Activation failed: ' + response.data.message, 'error');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Activate License');
                });
            });
            
            $('#deactivate-license').click(function() {
                if (!confirm('Are you sure you want to deactivate this license?')) {
                    return;
                }
                
                var nonce = $('[name="nonce"]').val();
                var button = $(this);
                
                button.prop('disabled', true).text('Deactivating...');
                
                $.post(ajaxurl, {
                    action: 'lasso_deactivate_license',
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        showMessage('License deactivated successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Deactivate License');
                });
            });
            
            function showMessage(message, type) {
                var messageClass = type === 'success' ? 'notice-success' : 'notice-error';
                $('#license-message').html('<div class="notice ' + messageClass + '"><p>' + message + '</p></div>');
            }
        });
        </script>
        <?php
    }
}

// Initialize the license manager (singleton pattern)
Lasso_Leader_License_Manager::get_instance();
?>