<?php
/**
 * Handles all Gravity Forms integration for Lasso Leader using the Add-On Framework.
 * Version: 4.5.0 - PRODUCTION READY
 * This version keeps all functionality while removing verbose debugging
 */

if ( ! class_exists( 'GFAddOn' ) ) {
    return;
}

class Lasso_Gravity_Forms extends GFAddOn {

    protected $_version = LASSO_LEADER_VERSION;
    protected $_min_gravityforms_version = '2.5';
    protected $_slug = 'lassoleader';
    protected $_path = 'lasso-leader/lasso-leader.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Lasso Leader';
    protected $_short_title = 'Lasso Leader';
    
    private static $_instance = null;

    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Simple initialization
     */
    public function init() {
        parent::init();
        add_action( 'gform_after_submission', array( $this, 'process_submission' ), 10, 2 );
    }

    /**
     * Form settings fields configuration
     */
    public function form_settings_fields( $form ) {
        return array(
            array(
                'title'  => esc_html__( 'Lasso Leader Settings', 'lasso-leader' ),
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'Enable Integration', 'lasso-leader' ),
                        'type'    => 'checkbox',
                        'name'    => 'enable_integration',
                        'choices' => array( array( 'label' => esc_html__( 'Enable Lasso Leader for this form', 'lasso-leader' ), 'name'  => 'enable_integration_cb' ) )
                    ),
                    array(
                        'label' => esc_html__( 'Lasso API Key (Override)', 'lasso-leader' ),
                        'type'  => 'text',
                        'name'  => 'api_key_override',
                        'class' => 'medium',
                    ),
                    array(
                        'label' => esc_html__( 'Lasso Project ID (Override)', 'lasso-leader' ),
                        'type'  => 'text',
                        'name'  => 'project_id_override',
                        'class' => 'medium',
                    ),
                    array(
                        'type'    => 'render_field_map_table',
                        'name'    => 'field_mapping',
                        'form'    => $form,
                    ),
                    array(
                        'label'   => esc_html__( 'Enable Debugging', 'lasso-leader' ),
                        'type'    => 'checkbox',
                        'name'    => 'enable_debugging',
                        'choices' => array( array( 'label' => esc_html__( 'Enable entry notes and debug emails', 'lasso-leader' ), 'name'  => 'enable_debugging_cb' ) )
                    ),
                    array(
                        'label' => esc_html__( 'Debug Email Recipient', 'lasso-leader' ),
                        'type'  => 'text',
                        'name'  => 'debug_email',
                        'class' => 'medium',
                        'tooltip' => esc_html__( 'Email address to receive debug notifications when forms are submitted', 'lasso-leader' ),
                    ),
                )
            )
        );
    }
    
    /**
     * Field mapping interface
     */
    public function settings_render_field_map_table( $field ) {
        $form = $field['form'];
        $settings = $this->get_form_settings($form);
        
        // error_log('[LASSO DEBUG] === Field Mapping Interface ===');
        // error_log('[LASSO DEBUG] Form ID: ' . $form['id']);
        
        $standard_fields = array(
            ''           => '-- Select Standard Field --', 
            'firstName'  => 'First Name', 
            'lastName'   => 'Last Name',
            'email'      => 'Email', 
            'phone'      => 'Phone', 
            'message'    => 'Message/Notes',
        );
        
        $question_types = array(
            ''           => '-- Select Type --',
            'text'       => 'Text',
            'answer_id'  => 'Answer ID',
        );
        ?>
        <div class="lasso-field-mapping">
            <h4><?php esc_html_e( 'Field Mapping', 'lasso-leader' ); ?></h4>
            <p class="description">
                <?php esc_html_e( 'Map your form fields to Lasso CRM fields and questions.', 'lasso-leader' ); ?>
            </p>
            
            <?php if (!empty($settings['field_mapping'])): ?>
            <div style="background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;">
                <h4 style="margin-top: 0;">? Current Field Mappings</h4>
                <ul style="margin: 0;">
                    <?php foreach ($settings['field_mapping'] as $field_id => $mapping): ?>
                        <li>
                            <strong>Field <?php echo $field_id; ?>:</strong>
                            <?php if (!empty($mapping['standard'])): ?>
                                Standard = <em><?php echo esc_html($mapping['standard']); ?></em>
                            <?php endif; ?>
                            <?php if (!empty($mapping['question_id'])): ?>
                                Question = <em><?php echo esc_html($mapping['question_id']); ?></em> 
                                (<?php echo esc_html($mapping['question_type']); ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ffc107;">
                <p style="margin: 0;"><strong>No field mappings configured yet.</strong> Map your form fields below and click "Update Settings" to save.</p>
            </div>
            <?php endif; ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30%;">Form Field</th>
                        <th style="width: 25%;">Standard Field</th>
                        <th style="width: 20%;">Question ID</th>
                        <th style="width: 20%;">Question Type</th>
                        <th style="width: 5%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $form['fields'] as $form_field ) : 
                    if ( in_array( $form_field->type, array( 'section', 'page', 'html', 'captcha' ) ) ) continue;
                    $field_id = $form_field->id;
                    
                    // Get current mapping data
                    $current_mapping = isset($settings['field_mapping'][$field_id]) ? $settings['field_mapping'][$field_id] : array();
                    
                    $has_mapping = !empty($current_mapping['standard']) || !empty($current_mapping['question_id']);
                    
                    // Get individual values
                    $standard_value = !empty($current_mapping['standard']) ? $current_mapping['standard'] : '';
                    $question_id_value = !empty($current_mapping['question_id']) ? $current_mapping['question_id'] : '';
                    $question_type_value = !empty($current_mapping['question_type']) ? $current_mapping['question_type'] : '';
                ?>
                    <tr <?php echo $has_mapping ? 'style="background-color: #f0f8f0;"' : ''; ?>>
                        <td>
                            <strong><?php echo esc_html( $form_field->label ); ?></strong><br>
                            <small style="color: #666;">
                                ID: <?php echo esc_html( $field_id ); ?> � Type: <?php echo esc_html( $form_field->type ); ?>
                            </small>
                        </td>
                        <td>
                            <select name="_gaddon_setting_field_mapping[<?php echo $field_id; ?>][standard]" style="width: 100%;">
                                <?php foreach ( $standard_fields as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $standard_value, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" 
                                   name="_gaddon_setting_field_mapping[<?php echo $field_id; ?>][question_id]" 
                                   placeholder="e.g. 157191 or custom" 
                                   value="<?php echo esc_attr( $question_id_value ); ?>" 
                                   style="width: 100%;">
                        </td>
                        <td>
                            <select name="_gaddon_setting_field_mapping[<?php echo $field_id; ?>][question_type]" style="width: 100%;">
                                <?php foreach ( $question_types as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $question_type_value, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <?php if ( $has_mapping ) : ?>
                                <span style="color: green; font-weight: bold;" title="Mapped"><span class="checkmark">&#10003;</span></span>
                            <?php else : ?>
                                <span style="color: #ccc;" title="Not mapped">?</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="background: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 4px; padding: 15px; margin: 15px 0;">
                <h4 style="margin: 0 0 10px 0;">? How to Add Custom Questions</h4>
                <ul style="margin: 0;">
                    <li><strong>Standard Fields:</strong> Use for basic contact info (First Name, Last Name, Email, Phone, Messages)</li>
                    <li><strong>Custom Question ID:</strong> Enter ANY custom question ID number (e.g. 157191, 156759, etc.)</li>
                    <li><strong>For Questions NOT in dropdown:</strong> Just type the Question ID in the "Question ID" field!</li>
                    <li><strong>Answer ID Type:</strong> Use when Lasso expects a specific answer ID number</li>
                    <li><strong>Text Type:</strong> Use for free-form text responses</li>
                </ul>
                <p style="margin-top: 10px; font-weight: bold; color: #0073aa;">The Question ID field accepts ANY number - you're not limited to the dropdown!</p>
            </div>
        </div>
        
        <style>
        .lasso-field-mapping table {
            margin: 15px 0;
        }
        .lasso-field-mapping th {
            background-color: #f8f9fa;
            font-weight: bold;
            padding: 12px;
        }
        .lasso-field-mapping td {
            padding: 12px;
            vertical-align: top;
        }
        .lasso-field-mapping select,
        .lasso-field-mapping input[type="text"] {
            font-size: 13px;
        }
        </style>
        <?php
    }

    /**
     * Save form settings with redirect fix
     */
    public function save_form_settings($form, $settings) {
        // error_log('[LASSO DEBUG] === Save Form Settings ===');
        // error_log('[LASSO DEBUG] Original settings: ' . print_r($settings, true));
        
        // Process field mapping data
        if (isset($_POST['_gaddon_setting_field_mapping']) && is_array($_POST['_gaddon_setting_field_mapping'])) {
            // error_log('[LASSO DEBUG] Field mapping data found! Processing...');
            
            $field_mappings = array();
            
            foreach ($_POST['_gaddon_setting_field_mapping'] as $field_id => $mapping_data) {
                if (is_array($mapping_data)) {
                    $clean_mapping = array();
                    
                    // Process standard field mapping
                    if (isset($mapping_data['standard']) && $mapping_data['standard'] !== '') {
                        $clean_mapping['standard'] = sanitize_text_field($mapping_data['standard']);
                        // error_log("[LASSO DEBUG] Field {$field_id} STANDARD: '{$clean_mapping['standard']}'");
                    }
                    
                    // Process question ID mapping
                    if (isset($mapping_data['question_id']) && $mapping_data['question_id'] !== '') {
                        $clean_mapping['question_id'] = sanitize_text_field($mapping_data['question_id']);
                        // error_log("[LASSO DEBUG] Field {$field_id} QUESTION_ID: '{$clean_mapping['question_id']}'");
                    }
                    
                    // Process question type mapping
                    if (isset($mapping_data['question_type']) && $mapping_data['question_type'] !== '') {
                        $clean_mapping['question_type'] = sanitize_text_field($mapping_data['question_type']);
                        // error_log("[LASSO DEBUG] Field {$field_id} QUESTION_TYPE: '{$clean_mapping['question_type']}'");
                    }
                    
                    // Only save if there's actual mapping data
                    if (!empty($clean_mapping)) {
                        $field_mappings[$field_id] = $clean_mapping;
                        // error_log("[LASSO DEBUG] Field {$field_id} SUCCESSFULLY MAPPED: " . print_r($clean_mapping, true));
                    }
                }
            }
            
            // Save the field mappings to settings
            $settings['field_mapping'] = $field_mappings;
            // error_log('[LASSO DEBUG] ALL FIELD MAPPINGS SAVED: ' . print_r($field_mappings, true));
            // error_log('[LASSO DEBUG] TOTAL MAPPINGS SAVED: ' . count($field_mappings));
            
        } else {
            // error_log('[LASSO DEBUG] No field mapping data found in POST');
            $settings['field_mapping'] = array();
        }
        
        // error_log('[LASSO DEBUG] Final settings being saved: ' . print_r($settings, true));
        
        // Let the parent class handle the actual saving
        $settings = parent::save_form_settings($form, $settings);
        
        // Redirect fix to prevent cache issues
        $current_url = $this->get_current_settings_url();
        $redirect_url = add_query_arg(array('updated' => 'true'), $current_url);
        
        // error_log('[LASSO DEBUG] REDIRECTING to: ' . $redirect_url);
        
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Get the current settings URL for redirect
     */
    private function get_current_settings_url() {
        $form_id = rgget('id');
        $base_url = admin_url('admin.php');
        
        return add_query_arg(array(
            'page' => 'gf_edit_forms',
            'view' => 'settings',
            'subview' => $this->_slug,
            'id' => $form_id
        ), $base_url);
    }

    /**
     * Process form submission
     */
    public function process_submission( $entry, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( ! rgar( $settings, 'enable_integration_cb' ) ) {
            return;
        }

        $api_handler = new Lasso_API_Handler();
        
        $api_key = get_option('lasso_leader_api_key');
        if ( ! empty( $settings['api_key_override'] ) ) { 
            $api_key = $settings['api_key_override']; 
        }
        
        $project_id = rgar( $settings, 'project_id_override' );

        // Check for hidden field with project ID
        foreach ( $form['fields'] as $field ) {
            if ( $field instanceof GF_Field_Hidden && $field->inputName === 'lassoLeaderFormProjectId' ) {
                $submitted_project_id = rgar( $entry, (string) $field->id );
                if ( ! empty( $submitted_project_id ) ) { 
                    $project_id = $submitted_project_id; 
                    break; 
                }
            }
        }
        
        // Get project-specific API key if available
        if ( $project_id ) {
            $project_posts = get_posts(array(
                'post_type' => 'lasso_project', 
                'posts_per_page' => 1,
                'meta_query' => array( 
                    array( 'key' => '_lasso_project_id', 'value' => $project_id ) 
                )
            ));
            if ( !empty($project_posts) ) {
                $project_api_key = get_post_meta($project_posts[0]->ID, '_lasso_api_key', true);
                if ( !empty($project_api_key) ) { 
                    $api_key = $project_api_key; 
                }
            }
        }

        $field_mappings = rgar( $settings, 'field_mapping' );
        
        // Initialize person as stdClass object
        $payload = array( 
            'person' => new stdClass(), 
            'questions' => array(), 
            'emails' => array(), 
            'phones' => array(), 
            'notes' => array() 
        );
        
        if ( $project_id ) { 
            $payload['ProjectId'] = $project_id; 
        }
        
        // Add default rating
        $payload['RatingId'] = 15644987;
        $assigned_sales_rep = null;
        
        // Process field mappings
        foreach ( $form['fields'] as $field ) {
            $field_id = $field->id;
            $value = rgar( $entry, (string) $field_id );

            if ( ! empty( $value ) && $this->is_agent_name_field( $field ) ) {
                $assigned_sales_rep = $this->build_sales_rep_assignment( $value );
            }

            if ( isset( $field_mappings[$field_id] ) ) {
                $map = $field_mappings[$field_id];
                
                if ( ! empty( $value ) ) {
                    if ( ! empty( $map['standard'] ) ) {
                        if ( $map['standard'] === 'email' ) {
                            $payload['emails'][] = array( 'type' => 'primary', 'email' => $value );
                        } elseif ( $map['standard'] === 'phone' ) {
                            $payload['phones'][] = array( 'type' => 'primary', 'phone' => $value );
                        } else {
                            $payload['person']->{$map['standard']} = $value;
                        }
                    } elseif ( ! empty( $map['question_id'] ) && ! empty( $map['question_type'] ) ) {
                        $payload['questions'][] = array( 
                            'questionId' => intval($map['question_id']), 
                            'answers' => array( array( 'answer' => $value ) )
                        );
                    }
                }
            }
        }

        if ( ! empty( $assigned_sales_rep ) ) {
            $payload['assignedSalesReps'] = array( $assigned_sales_rep );
        }
        
        // Use Project Override system if available
        if (class_exists('Lasso_Project_Override')) {
            $project_override = new Lasso_Project_Override($api_handler);
            $response = $project_override->process_with_override($payload, 'gravity_forms');
        } else {
            // Fallback to original method if override class doesn't exist
            $response = $api_handler->send_to_lasso( $payload, $api_key );
        }
        
        // Handle response and debug notifications
        $debug_enabled = rgar( $settings, 'enable_debugging_cb' );
        $debug_email = rgar( $settings, 'debug_email' );
        
        if ( is_wp_error( $response ) ) {
            $error_message = 'Lasso API Error: ' . $response->get_error_message();
            $this->add_note( $entry['id'], $error_message, 'error' );
            
            if ( $debug_enabled && $debug_email ) {
                $this->send_debug_email( $debug_email, 'Lasso API Error', $error_message, $entry, $form );
            }
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( $response_code >= 200 && $response_code < 300 && isset($response_body['registrantId']) ) {
                $success_message = 'Successfully sent to Lasso API. Registrant ID: ' . $response_body['registrantId'];
                
                // Add override information to success message if available
                if (is_array($response) && isset($response['override_info'])) {
                    $success_message .= "\n--- Project Override Info ---";
                    $success_message .= "\nSettings Source: " . $response['override_info']['source'];
                    $success_message .= "\nProject ID Used: " . $response['override_info']['project_id'];
                    if (!empty($response['override_info']['project_name'])) {
                        $success_message .= "\nProject Name: " . $response['override_info']['project_name'];
                    }
                    $success_message .= "\nAPI Key Source: " . $response['override_info']['api_key_source'];
                }
                
                $this->add_note( $entry['id'], $success_message, 'success' );
                
                if ( $debug_enabled && $debug_email ) {
                    $this->send_debug_email( $debug_email, 'Lasso API Success', $success_message, $entry, $form );
                }
            } else {
                $error_message = 'Lasso API submission failed. Status: ' . $response_code . ' | Response: ' . wp_remote_retrieve_body( $response );
                $this->add_note( $entry['id'], $error_message, 'error' );
                
                if ( $debug_enabled && $debug_email ) {
                    $this->send_debug_email( $debug_email, 'Lasso API Failure', $error_message, $entry, $form );
                }
            }
        }
    }

    private function is_agent_name_field( $field ) {
        $input_name = isset( $field->inputName ) ? $field->inputName : '';
        $label = isset( $field->label ) ? $field->label : '';

        return $input_name === 'agent_name' || strcasecmp( $label, 'Agent Name' ) === 0;
    }

    private function build_sales_rep_assignment( $agent_name ) {
        $agent_name = trim( preg_replace( '/\s+/', ' ', sanitize_text_field( $agent_name ) ) );

        if ( empty( $agent_name ) || strcasecmp( $agent_name, 'N/A' ) === 0 ) {
            return null;
        }

        $name_parts = explode( ' ', $agent_name, 2 );
        if ( count( $name_parts ) < 2 ) {
            return null;
        }

        return array(
            'firstName' => $name_parts[0],
            'lastName'  => $name_parts[1],
            'isPrimary' => true,
        );
    }
    
    /**
     * Send debug email - KEEP THIS for production debugging
     */
    private function send_debug_email( $to, $subject, $message, $entry, $form ) {
        if ( empty( $to ) ) {
            // error_log('[LASSO DEBUG] Debug email not sent - no recipient specified');
            return false;
        }
        
        $email_subject = 'Lasso Leader Debug: ' . $subject;
        $email_message = $message . "\n\n";
        $email_message .= "Form: " . $form['title'] . "\n";
        $email_message .= "Entry ID: " . $entry['id'] . "\n";
        $email_message .= "Timestamp: " . current_time('mysql') . "\n";
        
        $sent = wp_mail( $to, $email_subject, $email_message );
        
        if ( $sent ) {
            // error_log("[LASSO DEBUG] Debug email sent successfully to: {$to}");
        } else {
            error_log("[LASSO DEBUG] Failed to send debug email to: {$to}");
        }
        
        return $sent;
    }
}
