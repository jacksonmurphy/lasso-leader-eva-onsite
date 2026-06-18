<?php
/**
 * Enhanced Debug Email System for Lasso Leader
 * Provides comprehensive email notifications for debugging purposes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_Debug_Email_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }
    
    public function init() {
        // Hook into WordPress mail system to ensure proper delivery
        add_action( 'wp_mail_failed', array( $this, 'log_mail_failure' ) );
        
        // Add email testing capability for admin
        add_action( 'wp_ajax_lasso_test_debug_email', array( $this, 'test_debug_email' ) );
        
        // Add admin notice for email configuration issues
        add_action( 'admin_notices', array( $this, 'check_email_configuration' ) );
    }
    
    /**
     * Send debug email notification with comprehensive details
     */
    public function send_debug_notification( $recipient, $type, $message, $context = array() ) {
        if ( empty( $recipient ) || ! is_email( $recipient ) ) {
            error_log( 'Lasso Leader: Invalid debug email recipient: ' . $recipient );
            return false;
        }
        
        $subject = $this->get_email_subject( $type, $context );
        $body = $this->build_email_body( $type, $message, $context );
        $headers = $this->get_email_headers();
        
        // Log the attempt
        error_log( 'Lasso Leader: Sending debug email to ' . $recipient );
        
        // Send the email
        $sent = wp_mail( $recipient, $subject, $body, $headers );
        
        if ( ! $sent ) {
            error_log( 'Lasso Leader: Failed to send debug email to ' . $recipient );
            
            // Try alternative method if WordPress mail fails
            $this->try_alternative_email_method( $recipient, $subject, $body );
        } else {
            error_log( 'Lasso Leader: Debug email sent successfully to ' . $recipient );
        }
        
        return $sent;
    }
    
    /**
     * Generate appropriate email subject based on notification type
     */
    private function get_email_subject( $type, $context ) {
        $site_name = get_bloginfo( 'name' );
        $base_subject = '[Lasso Leader Debug] ';
        
        switch ( $type ) {
            case 'success':
                $subject = $base_subject . '✅ Successful Submission';
                break;
            case 'error':
                $subject = $base_subject . '❌ Submission Error';
                break;
            case 'api_failure':
                $subject = $base_subject . '🚨 API Connection Failed';
                break;
            case 'validation_error':
                $subject = $base_subject . '⚠️ Data Validation Error';
                break;
            case 'test':
                $subject = $base_subject . '🧪 Test Email';
                break;
            default:
                $subject = $base_subject . 'Notification';
        }
        
        // Add form name if available
        if ( isset( $context['form_name'] ) ) {
            $subject .= ' - ' . $context['form_name'];
        }
        
        // Add site name
        $subject .= ' (' . $site_name . ')';
        
        return $subject;
    }
    
    /**
     * Build comprehensive email body with all relevant details
     */
    private function build_email_body( $type, $message, $context ) {
        $body = "Lasso Leader Debug Notification\n";
        $body .= str_repeat( '=', 50 ) . "\n\n";
        
        // Basic information
        $body .= "Timestamp: " . current_time( 'mysql' ) . " (" . get_option( 'timezone_string', 'UTC' ) . ")\n";
        $body .= "Site: " . get_bloginfo( 'name' ) . " (" . home_url() . ")\n";
        $body .= "Notification Type: " . ucfirst( $type ) . "\n\n";
        
        // Main message
        $body .= "MESSAGE:\n";
        $body .= str_repeat( '-', 20 ) . "\n";
        $body .= $message . "\n\n";
        
        // Form details if available
        if ( isset( $context['form'] ) ) {
            $form = $context['form'];
            $body .= "FORM DETAILS:\n";
            $body .= str_repeat( '-', 20 ) . "\n";
            $body .= "Form Title: " . ( isset( $form['title'] ) ? $form['title'] : 'Unknown' ) . "\n";
            $body .= "Form ID: " . ( isset( $form['id'] ) ? $form['id'] : 'Unknown' ) . "\n";
            $body .= "Form Type: " . ( isset( $context['form_type'] ) ? $context['form_type'] : 'Unknown' ) . "\n\n";
        }
        
        // Entry data if available
        if ( isset( $context['entry'] ) ) {
            $entry = $context['entry'];
            $body .= "SUBMISSION DATA:\n";
            $body .= str_repeat( '-', 20 ) . "\n";
            $body .= "Entry ID: " . ( isset( $entry['id'] ) ? $entry['id'] : 'Unknown' ) . "\n";
            $body .= "IP Address: " . ( isset( $entry['ip'] ) ? $entry['ip'] : 'Unknown' ) . "\n";
            $body .= "User Agent: " . ( isset( $entry['user_agent'] ) ? $entry['user_agent'] : 'Unknown' ) . "\n\n";
            
            // Form field data
            if ( isset( $context['form'] ) && isset( $context['form']['fields'] ) ) {
                $body .= "FIELD VALUES:\n";
                $body .= str_repeat( '-', 20 ) . "\n";
                foreach ( $context['form']['fields'] as $field ) {
                    if ( isset( $field->label ) && isset( $entry[ $field->id ] ) ) {
                        $value = $entry[ $field->id ];
                        if ( ! empty( $value ) ) {
                            $body .= $field->label . ": " . $value . "\n";
                        }
                    }
                }
                $body .= "\n";
            }
        }
        
        // API details if available
        if ( isset( $context['api_response'] ) ) {
            $body .= "API RESPONSE:\n";
            $body .= str_repeat( '-', 20 ) . "\n";
            $body .= "Response Code: " . ( isset( $context['response_code'] ) ? $context['response_code'] : 'Unknown' ) . "\n";
            $body .= "Response Body: " . $context['api_response'] . "\n\n";
        }
        
        // Project information if available
        if ( isset( $context['project_id'] ) ) {
            $body .= "PROJECT INFORMATION:\n";
            $body .= str_repeat( '-', 20 ) . "\n";
            $body .= "Project ID: " . $context['project_id'] . "\n";
            if ( isset( $context['project_name'] ) ) {
                $body .= "Project Name: " . $context['project_name'] . "\n";
            }
            $body .= "\n";
        }
        
        // Configuration details
        $body .= "CONFIGURATION:\n";
        $body .= str_repeat( '-', 20 ) . "\n";
        $body .= "Global API Key Set: " . ( get_option( 'lasso_leader_api_key' ) ? 'Yes' : 'No' ) . "\n";
        $body .= "Debug Mode: " . ( get_option( 'lasso_leader_debug_mode' ) ? 'Enabled' : 'Disabled' ) . "\n";
        $body .= "Plugin Version: " . ( defined( 'LASSO_LEADER_VERSION' ) ? LASSO_LEADER_VERSION : 'Unknown' ) . "\n";
        $body .= "WordPress Version: " . get_bloginfo( 'version' ) . "\n";
        $body .= "PHP Version: " . PHP_VERSION . "\n\n";
        
        // Server information
        $body .= "SERVER INFORMATION:\n";
        $body .= str_repeat( '-', 20 ) . "\n";
        $body .= "Server Time: " . date( 'Y-m-d H:i:s T' ) . "\n";
        $body .= "Memory Limit: " . ini_get( 'memory_limit' ) . "\n";
        $body .= "Max Execution Time: " . ini_get( 'max_execution_time' ) . "s\n\n";
        
        // Additional context
        if ( isset( $context['additional_info'] ) ) {
            $body .= "ADDITIONAL INFORMATION:\n";
            $body .= str_repeat( '-', 20 ) . "\n";
            $body .= $context['additional_info'] . "\n\n";
        }
        
        $body .= str_repeat( '=', 50 ) . "\n";
        $body .= "This is an automated message from Lasso Leader Debug System.\n";
        
        return $body;
    }
    
    /**
     * Get appropriate email headers
     */
    private function get_email_headers() {
        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );
        
        $headers = array();
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'From: ' . $site_name . ' <' . $admin_email . '>';
        $headers[] = 'Reply-To: ' . $admin_email;
        
        return $headers;
    }
    
    /**
     * Try alternative email method if WordPress mail fails
     */
    private function try_alternative_email_method( $recipient, $subject, $body ) {
        // Try using PHP's mail() function directly
        $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: " . get_bloginfo( 'name' ) . " <" . get_option( 'admin_email' ) . ">\r\n";
        
        $sent = mail( $recipient, $subject, $body, $headers );
        
        if ( $sent ) {
            error_log( 'Lasso Leader: Debug email sent via PHP mail() function' );
        } else {
            error_log( 'Lasso Leader: All email methods failed for debug notification' );
        }
        
        return $sent;
    }
    
    /**
     * Log mail failures for debugging
     */
    public function log_mail_failure( $wp_error ) {
        error_log( 'Lasso Leader: WordPress mail failed - ' . $wp_error->get_error_message() );
    }
    
    /**
     * AJAX handler for testing debug emails
     */
    public function test_debug_email() {
        // Verify nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'lasso_test_email' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        
        $email = sanitize_email( $_POST['email'] );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( 'Invalid email address' );
        }
        
        $context = array(
            'form_name' => 'Test Form',
            'additional_info' => 'This is a test email to verify the debug email system is working correctly.'
        );
        
        $sent = $this->send_debug_notification( 
            $email, 
            'test', 
            'Debug email system test completed successfully!', 
            $context 
        );
        
        if ( $sent ) {
            wp_send_json_success( 'Test email sent successfully!' );
        } else {
            wp_send_json_error( 'Failed to send test email. Check error logs for details.' );
        }
    }
    
    /**
     * Check email configuration and show admin notices
     */
    public function check_email_configuration() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'lasso-leader' ) === false ) {
            return;
        }
        
        // Check if debug mode is enabled but no email is set
        if ( get_option( 'lasso_leader_debug_mode' ) && ! get_option( 'lasso_leader_debug_email' ) ) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>Lasso Leader:</strong> Debug mode is enabled but no debug email address is configured. 
                    <a href="<?php echo admin_url( 'admin.php?page=lasso-leader-global-settings' ); ?>">
                        Set a debug email address
                    </a> to receive notifications.
                </p>
            </div>
            <?php
        }
        
        // Test if WordPress can send emails
        if ( ! $this->test_wp_mail_capability() ) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Lasso Leader:</strong> Your WordPress installation may have issues sending emails. 
                    Debug notifications might not be delivered. Consider installing an SMTP plugin or 
                    contacting your hosting provider.
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Test WordPress mail capability
     */
    private function test_wp_mail_capability() {
        // Simple test to see if mail function exists and is configured
        if ( ! function_exists( 'wp_mail' ) ) {
            return false;
        }
        
        // Check if SMTP is configured (basic check)
        if ( defined( 'WPMS_ON' ) || defined( 'WPMS_SMTP_PASS' ) ) {
            return true; // SMTP plugin is likely configured
        }
        
        // Check if server has mail capability
        if ( ! function_exists( 'mail' ) ) {
            return false;
        }
        
        return true; // Assume it works unless we detect obvious issues
    }
    
    /**
     * Send a quick success notification
     */
    public function send_success_notification( $recipient, $registrant_id, $context = array() ) {
        $message = "Form submission processed successfully!\n\n";
        $message .= "Registrant ID: " . $registrant_id . "\n";
        $message .= "The lead has been successfully added to Lasso CRM.";
        
        $context['additional_info'] = "This submission was processed without any errors.";
        
        return $this->send_debug_notification( $recipient, 'success', $message, $context );
    }
    
    /**
     * Send an error notification
     */
    public function send_error_notification( $recipient, $error_message, $context = array() ) {
        $message = "Form submission encountered an error!\n\n";
        $message .= "Error Details: " . $error_message . "\n";
        $message .= "Please review the submission details below and check your Lasso CRM configuration.";
        
        return $this->send_debug_notification( $recipient, 'error', $message, $context );
    }
}