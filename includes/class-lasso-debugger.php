<?php
/**
 * Enhanced Lasso Debugger - Complete File
 * Handles debugging utilities for Lasso Leader with enhanced email system and comprehensive logging.
 * Version: 6.1.2 - Updated Jul 7, 2025
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_Debugger {

    private static $instance = null;
    private $debug_enabled;
    private $debug_email;

    public function __construct() {
        $this->debug_enabled = get_option('lasso_leader_debug_mode', false);
        $this->debug_email = get_option('lasso_leader_debug_email', '');
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Original GF debug email method (enhanced)
     * Sends a detailed debug email for a Gravity Forms submission.
     *
     * @param array $form      The Gravity Forms form object.
     * @param array $settings  The add-on's settings for the form.
     * @param array $payload   The data payload sent to the Lasso API.
     * @param array|WP_Error $response The response from the Lasso API.
     * @param string $api_key  The API key used for the request.
     * @param string $api_key_source  The source of the API Key (Global or Override).
     * @param string $project_id_source The source of the Project ID (Global or Override).
     */
    public static function send_gf_debug_email( $form, $settings, $payload, $response, $api_key, $api_key_source, $project_id_source ) {
        
        // Only proceed if debugging is enabled and a recipient is set.
        if ( ! rgar( $settings, 'enable_debugging' ) || empty( $settings['debug_email'] ) ) {
            return;
        }

        $recipient = rgar( $settings, 'debug_email' );
        $subject   = 'Lasso Leader GF Debug - Form: ' . rgar( $form, 'title' );
        
        // Build the enhanced email body
        $body  = "A submission was processed for this form.\n\n";
        $body .= "--- CONFIGURATION ---\n";
        $body .= "Project ID Used: " . rgar( $payload, 'ProjectId', 'Not Set' ) . "\n";
        $body .= "API Key Used: " . substr($api_key, 0, 8) . '...' . substr($api_key, -4) . "\n";
        $body .= "Project ID Source: " . $project_id_source . "\n";
        $body .= "API Key Source: " . $api_key_source . "\n";
        $body .= "Timestamp: " . current_time('mysql') . "\n";
        $body .= "Site URL: " . home_url() . "\n\n";

        $body .= "--- PAYLOAD SENT TO LASSO ---\n";
        $body .= print_r( $payload, true );
        $body .= "\n\n--- RESPONSE FROM LASSO ---\n";
        
        if ( is_wp_error( $response ) ) {
            $body .= "WP_Error: " . $response->get_error_message() . "\n";
            $body .= "Error Code: " . $response->get_error_code() . "\n";
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $response_headers = wp_remote_retrieve_headers( $response );
            
            $body .= "Status Code: " . $response_code . "\n";
            $body .= "Response Body: " . $response_body . "\n";
            $body .= "Response Headers: " . print_r($response_headers, true) . "\n";
        }

        $body .= "\n--- TROUBLESHOOTING TIPS ---\n";
        if (is_wp_error($response)) {
            $body .= "• Check your internet connection and server configuration\n";
            $body .= "• Verify WordPress can make external HTTP requests\n";
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            switch ($response_code) {
                case 401:
                    $body .= "• 401 Unauthorized: Check if your API key is valid and not expired\n";
                    $body .= "• Verify the API key format and ensure there are no extra spaces\n";
                    $body .= "• Contact Lasso support to verify key permissions\n";
                    break;
                case 403:
                    $body .= "• 403 Forbidden: Your API key may not have access to this project\n";
                    $body .= "• API keys in Lasso are project-specific\n";
                    $body .= "• Check if the Project ID matches your API key's permissions\n";
                    break;
                case 422:
                    $body .= "• 422 Validation Error: Check the data being sent\n";
                    $body .= "• Verify all required fields are mapped correctly\n";
                    $body .= "• Check for invalid data formats (emails, phones, etc.)\n";
                    break;
                case 500:
                    $body .= "• 500 Server Error: Issue on Lasso's end\n";
                    $body .= "• Try again in a few minutes\n";
                    $body .= "• Contact Lasso support if issue persists\n";
                    break;
            }
        }

        // Send the email with enhanced headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );

        wp_mail( $recipient, $subject, $body, $headers );
    }

    /**
     * Enhanced debug email system for comprehensive error reporting
     */
    public function send_debug_email($type, $subject, $message, $data = array(), $level = 'error') {
        // Check if debug emails are enabled
        if (!$this->is_debug_enabled()) {
            return false;
        }

        // Check if this level should be sent
        if (!$this->should_send_level($level)) {
            return false;
        }

        $recipients = $this->get_debug_recipients();
        if (empty($recipients)) {
            return false;
        }

        $email_subject = '[Lasso Leader Debug] ' . $subject;
        $email_body = $this->format_debug_email($type, $message, $data, $level);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );

        $sent = wp_mail($recipients, $email_subject, $email_body, $headers);

        // Log the email attempt
        $this->log_debug_email($type, $subject, $sent, $recipients);

        return $sent;
    }

    /**
     * Send API error debug email
     */
    public function send_api_error_email($endpoint, $error_message, $request_data = array(), $response_data = array()) {
        $subject = 'API Error - ' . $endpoint;
        $data = array(
            'endpoint' => $endpoint,
            'error_message' => $error_message,
            'request_data' => $request_data,
            'response_data' => $response_data,
            'timestamp' => current_time('mysql'),
            'site_url' => home_url(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );

        $message = "An API error occurred while communicating with Lasso CRM.\n\n";
        $message .= "Endpoint: {$endpoint}\n";
        $message .= "Error: {$error_message}\n";

        return $this->send_debug_email('api_error', $subject, $message, $data, 'error');
    }

    /**
     * Send form submission debug email
     */
    public function send_form_submission_debug($form_id, $form_type, $submission_data, $api_response = null, $success = true) {
        $subject = ($success ? 'Form Submission Success' : 'Form Submission Failed') . ' - ' . $form_type . ' #' . $form_id;

        $data = array(
            'form_id' => $form_id,
            'form_type' => $form_type,
            'submission_data' => $submission_data,
            'api_response' => $api_response,
            'success' => $success,
            'timestamp' => current_time('mysql'),
            'site_url' => home_url(),
            'user_ip' => $this->get_user_ip()
        );

        $message = "Form submission " . ($success ? 'processed successfully' : 'failed') . ".\n\n";
        $message .= "Form Type: {$form_type}\n";
        $message .= "Form ID: {$form_id}\n";

        $level = $success ? 'info' : 'error';

        return $this->send_debug_email('form_submission', $subject, $message, $data, $level);
    }

    /**
     * Send general debug email
     */
    public function send_general_debug($subject, $message, $data = array(), $level = 'info') {
        return $this->send_debug_email('general', $subject, $message, $data, $level);
    }

    /**
     * Send API key validation debug email
     */
    public function send_api_key_debug($api_key, $is_valid, $error_message = '') {
        $subject = 'API Key Validation ' . ($is_valid ? 'Success' : 'Failed');

        $data = array(
            'api_key' => substr($api_key, 0, 8) . '...' . substr($api_key, -4), // Mask the key
            'is_valid' => $is_valid,
            'error_message' => $error_message,
            'timestamp' => current_time('mysql'),
            'site_url' => home_url()
        );

        $message = "API key validation " . ($is_valid ? 'successful' : 'failed') . ".\n\n";
        if (!$is_valid && $error_message) {
            $message .= "Error: {$error_message}\n";
        }

        $level = $is_valid ? 'info' : 'warning';

        return $this->send_debug_email('api_key', $subject, $message, $data, $level);
    }

    /**
     * Integration method for Gravity Forms debugging
     */
    public function send_gravity_forms_debug($form, $settings, $payload, $response, $api_key, $api_key_source, $project_id_source) {
        if (!$this->is_debug_enabled() || empty($this->get_debug_recipients())) {
            return false;
        }

        $subject = 'Gravity Forms Submission - Form: ' . rgar($form, 'title');
        
        $data = array(
            'form_id' => rgar($form, 'id'),
            'form_title' => rgar($form, 'title'),
            'payload' => $payload,
            'api_response' => $response,
            'api_key_source' => $api_key_source,
            'project_id_source' => $project_id_source,
            'api_key_masked' => substr($api_key, 0, 8) . '...' . substr($api_key, -4),
            'timestamp' => current_time('mysql'),
            'site_url' => home_url()
        );

        $message = "Gravity Forms submission processed.\n\n";
        $message .= "Form: " . rgar($form, 'title') . " (ID: " . rgar($form, 'id') . ")\n";
        $message .= "API Key Source: {$api_key_source}\n";
        $message .= "Project ID Source: {$project_id_source}\n";

        $level = 'info';
        if (is_wp_error($response)) {
            $level = 'error';
            $message .= "Status: FAILED\n";
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code >= 200 && $response_code < 300) {
                $level = 'success';
                $message .= "Status: SUCCESS\n";
            } else {
                $level = 'error';
                $message .= "Status: FAILED (HTTP {$response_code})\n";
            }
        }

        return $this->send_debug_email('gravity_forms', $subject, $message, $data, $level);
    }

    /**
     * Check if debug emails are enabled
     */
    private function is_debug_enabled() {
        return get_option('lasso_leader_debug_mode', false);
    }

    /**
     * Get debug email recipients
     */
    private function get_debug_recipients() {
        $email = get_option('lasso_leader_debug_email', '');
        return !empty($email) ? array($email) : array();
    }

    /**
     * Check if this debug level should be sent
     */
    private function should_send_level($level) {
        $debug_level = get_option('lasso_leader_debug_level', 'error');
        
        $levels = array('info' => 1, 'warning' => 2, 'error' => 3, 'success' => 1);
        $current_level = $levels[$debug_level] ?? 2;
        $message_level = $levels[$level] ?? 1;
        
        return $message_level >= $current_level;
    }

    /**
     * Log debug email attempts
     */
    private function log_debug_email($type, $subject, $sent, $recipients) {
        if (!$this->is_debug_enabled()) {
            return;
        }

        $log_message = "Lasso Leader Debug Email " . ($sent ? 'SENT' : 'FAILED') . "\n";
        $log_message .= "Type: {$type}\n";
        $log_message .= "Subject: {$subject}\n";
        $log_message .= "Recipients: " . implode(', ', $recipients);
        
        error_log($log_message);
    }

    /**
     * Get user IP address safely
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Basic IP validation
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    /**
     * Format debug email HTML
     */
    private function format_debug_email($type, $message, $data, $level) {
        $site_name = get_option('blogname');
        $site_url = home_url();
        $timestamp = current_time('F j, Y g:i a');
        
        $level_colors = array(
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8',
            'success' => '#28a745'
        );
        
        $color = $level_colors[$level] ?? '#6c757d';
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Lasso Leader Debug</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: <?php echo $color; ?>; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; background: #f8f9fa; }
                .data-section { margin: 20px 0; padding: 15px; background: white; border-left: 4px solid <?php echo $color; ?>; border-radius: 4px; }
                .data-title { font-weight: bold; margin-bottom: 10px; color: <?php echo $color; ?>; }
                .data-item { margin: 5px 0; }
                .data-key { font-weight: bold; display: inline-block; width: 150px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; }
                pre { background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
                .troubleshooting { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 15px 0; }
                .troubleshooting h4 { margin-top: 0; color: #856404; }
                .troubleshooting ul { margin: 10px 0; padding-left: 20px; }
                .troubleshooting li { margin: 5px 0; color: #856404; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 Lasso Leader Debug</h1>
                    <p>Type: <?php echo strtoupper($type); ?> | Level: <?php echo strtoupper($level); ?></p>
                </div>
                
                <div class="content">
                    <div class="data-section">
                        <div class="data-title">📝 Message</div>
                        <p><?php echo nl2br(esc_html($message)); ?></p>
                    </div>
                    
                    <div class="data-section">
                        <div class="data-title">🌐 Site Information</div>
                        <div class="data-item"><span class="data-key">Site:</span> <?php echo esc_html($site_name); ?></div>
                        <div class="data-item"><span class="data-key">URL:</span> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a></div>
                        <div class="data-item"><span class="data-key">Timestamp:</span> <?php echo esc_html($timestamp); ?></div>
                        <div class="data-item"><span class="data-key">Plugin Version:</span> <?php echo esc_html(defined('LASSO_LEADER_VERSION') ? LASSO_LEADER_VERSION : 'Unknown'); ?></div>
                    </div>
                    
                    <?php if (!empty($data)): ?>
                        <div class="data-section">
                            <div class="data-title">🔍 Debug Data</div>
                            <?php foreach ($data as $key => $value): ?>
                                <div class="data-item">
                                    <span class="data-key"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</span>
                                    <?php if (is_array($value) || is_object($value)): ?>
                                        <pre><?php echo esc_html(wp_json_encode($value, JSON_PRETTY_PRINT)); ?></pre>
                                    <?php else: ?>
                                        <?php echo esc_html($value); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($level === 'error' && isset($data['api_response'])): ?>
                        <div class="troubleshooting">
                            <h4>🛠️ Troubleshooting Tips</h4>
                            <?php
                            $response = $data['api_response'];
                            if (is_wp_error($response)) {
                                echo '<ul>';
                                echo '<li>Check your internet connection and server configuration</li>';
                                echo '<li>Verify WordPress can make external HTTP requests</li>';
                                echo '<li>Check for firewall or security plugin blocks</li>';
                                echo '</ul>';
                            } else {
                                $response_code = wp_remote_retrieve_response_code($response);
                                echo '<ul>';
                                switch ($response_code) {
                                    case 401:
                                        echo '<li><strong>401 Unauthorized:</strong> Check if your API key is valid and not expired</li>';
                                        echo '<li>Verify the API key format and ensure there are no extra spaces</li>';
                                        echo '<li>Contact Lasso support to verify key permissions</li>';
                                        break;
                                    case 403:
                                        echo '<li><strong>403 Forbidden:</strong> Your API key may not have access to this project</li>';
                                        echo '<li>API keys in Lasso are project-specific</li>';
                                        echo '<li>Check if the Project ID matches your API key\'s permissions</li>';
                                        break;
                                    case 422:
                                        echo '<li><strong>422 Validation Error:</strong> Check the data being sent</li>';
                                        echo '<li>Verify all required fields are mapped correctly</li>';
                                        echo '<li>Check for invalid data formats (emails, phones, etc.)</li>';
                                        break;
                                    case 500:
                                        echo '<li><strong>500 Server Error:</strong> Issue on Lasso\'s end</li>';
                                        echo '<li>Try again in a few minutes</li>';
                                        echo '<li>Contact Lasso support if issue persists</li>';
                                        break;
                                    default:
                                        echo '<li>Unexpected response code: ' . $response_code . '</li>';
                                        echo '<li>Check Lasso API documentation for this status code</li>';
                                        break;
                                }
                                echo '</ul>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer">
                    <p>This debug email was sent by Lasso Leader plugin</p>
                    <p>You can disable debug emails in Lasso Leader → Global Settings</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Log general debug messages
     */
    public static function log($message, $level = 'info') {
        if (!get_option('lasso_leader_debug_mode', false)) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$level}] Lasso Leader: {$message}";
        
        error_log($log_message);
    }

    /**
     * Add admin notice for debug mode
     */
    public static function debug_admin_notice() {
        if (get_option('lasso_leader_debug_mode', false) && current_user_can('manage_options')) {
            $screen = get_current_screen();
            if (strpos($screen->id, 'lasso-leader') !== false) {
                echo '<div class="notice notice-info"><p><strong>Lasso Leader:</strong> Debug mode is enabled. Remember to disable it in production.</p></div>';
            }
        }
    }
}

// Initialize debug admin notice
add_action('admin_notices', array('Lasso_Debugger', 'debug_admin_notice'));