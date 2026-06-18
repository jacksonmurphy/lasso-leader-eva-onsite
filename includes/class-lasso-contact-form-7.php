<?php
/**
 * Handles integration with Contact Form 7.
 * Version: 4.4.0 - PRODUCTION READY
 * CRITICAL FIX: Changed 'answerId' to 'answer' to match working Gravity Forms format
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_Contact_Form_7 {

    private $api_handler;

    public function __construct( Lasso_API_Handler $api_handler ) {
        $this->api_handler = $api_handler;
    }

    public function init() {
        add_action( 'wpcf7_before_send_mail', array( $this, 'process_cf7_submission' ), 10, 3 );
    }

    public function process_cf7_submission( $contact_form, &$abort, $submission ) {
        // error_log('[LASSO CF7 DEBUG] ========== FORM SUBMISSION STARTED ==========');
        
        if ( ! $submission ) { 
            error_log('[LASSO CF7] ERROR: No submission object');
            return; 
        }

        $form_id = (int) $contact_form->id();
        // error_log('[LASSO CF7 DEBUG] Form ID: ' . $form_id);
        
        // Check if form is enabled
        $enabled_forms = get_option('lasso_leader_cf7_enabled_forms', []);
        // error_log('[LASSO CF7 DEBUG] Enabled forms: ' . print_r($enabled_forms, true));
        
        if ( ! is_array($enabled_forms) || ! in_array( $form_id, $enabled_forms ) ) { 
            // error_log('[LASSO CF7 DEBUG] ERROR: Form ' . $form_id . ' NOT ENABLED');
            return; 
        }
        // error_log('[LASSO CF7 DEBUG] Form is enabled');

        // Check API key
        $api_key = (string) get_option('lasso_leader_api_key', '');
        // error_log('[LASSO CF7 DEBUG] API Key length: ' . strlen($api_key) . ' characters');
        
        if ( empty( $api_key ) ) { 
            error_log('[LASSO CF7] ERROR: No API key configured');
            return; 
        }
        // error_log('[LASSO CF7 DEBUG] API key is present');
        
        // Check field mappings
        $all_mappings = get_option('lasso_leader_cf7_mappings', []);
        $field_map = $all_mappings[$form_id] ?? [];
        // error_log('[LASSO CF7 DEBUG] Field mappings: ' . print_r($field_map, true));
        
        if ( empty($field_map) ) { 
            error_log('[LASSO CF7] ERROR: No field mappings configured');
            return; 
        }
        // error_log('[LASSO CF7 DEBUG] Field mappings found: ' . count($field_map) . ' fields');

        // Get form data
        $posted_data = $submission->get_posted_data();
        // error_log('[LASSO CF7 DEBUG] Posted form data: ' . print_r($posted_data, true));
        
        // Get project ID
        $project_id = (string) get_option('lasso_leader_project_id', '');
        // error_log('[LASSO CF7 DEBUG] Project ID: ' . $project_id);

        // FIXED: Use exact same format as working Gravity Forms
        $payload = array( 
            'person' => new stdClass(),
            'questions' => array(), 
            'emails' => array(), 
            'phones' => array()
        );
        
        if ( $project_id ) { 
            $payload['ProjectId'] = intval($project_id); 
        }
        
        // Add default rating
        $payload['RatingId'] = 15644987;

        // error_log('[LASSO CF7 DEBUG] Initial payload structure created');

        // Process each form field
        foreach ( $posted_data as $field_name => $value ) {
            // error_log('[LASSO CF7 DEBUG] Processing field: ' . $field_name . ' = ' . print_r($value, true));
            
            if ( !isset($field_map[$field_name]) ) { 
                // error_log('[LASSO CF7 DEBUG] ??  No mapping for field: ' . $field_name);
                continue; 
            }
            
            $mapping_rules = $field_map[$field_name];
            // error_log('[LASSO CF7 DEBUG] Mapping rules: ' . print_r($mapping_rules, true));
            
            // Handle array values from dropdowns/checkboxes
            if (is_array($value)) {
                $processed_value = $value[0]; // Take first value
                // error_log('[LASSO CF7 DEBUG] Array value, using first: ' . $processed_value);
            } else {
                $processed_value = sanitize_text_field($value);
                // error_log('[LASSO CF7 DEBUG] String value: ' . $processed_value);
            }
            
            if ( empty($processed_value) ) {
                // error_log('[LASSO CF7 DEBUG] Empty value for: ' . $field_name);
                continue;
            }

            if ( $mapping_rules['type'] === 'standard' ) {
                $lasso_prop = $mapping_rules['value'];
                // error_log('[LASSO CF7 DEBUG] Standard mapping: ' . $field_name . ' -> ' . $lasso_prop);
                
                switch ( strtolower( (string)$lasso_prop ) ) {
                    case 'firstname': 
                        $payload['person']->firstName = $processed_value;
                        // error_log('[LASSO CF7 DEBUG] Set firstName: ' . $processed_value);
                        break;
                    case 'lastname':  
                        $payload['person']->lastName = $processed_value;
                        // error_log('[LASSO CF7 DEBUG] Set lastName: ' . $processed_value);
                        break;
                    case 'email':     
                        if ( is_email($processed_value) ) { 
                            $payload['emails'][] = array('type' => 'primary', 'email' => sanitize_email($processed_value)); 
                            // error_log('[LASSO CF7 DEBUG] Added email: ' . $processed_value);
                        } else {
                            // error_log('[LASSO CF7 DEBUG] Invalid email: ' . $processed_value);
                        }
                        break;
                    case 'phone':     
                        $cleaned_phone = preg_replace('/[^0-9+]/', '', $processed_value);
                        $payload['phones'][] = array('type' => 'primary', 'phone' => $cleaned_phone);
                        // error_log('[LASSO CF7 DEBUG] Added phone: ' . $cleaned_phone);
                        break;
                    case 'message':   
                        // error_log('[LASSO CF7 DEBUG] ? Message field found but SKIPPING notes for now: ' . $processed_value);
                        break;
                }
            } elseif ( $mapping_rules['type'] === 'question' ) {
                $question_id = absint($mapping_rules['question_id']);
                $question_type = $mapping_rules['question_type'];
                
                // error_log('[LASSO CF7 DEBUG] Question mapping: ' . $field_name . ' -> QID=' . $question_id . ', Type=' . $question_type . ', Value=' . $processed_value);
                
                if ($question_type === 'answer_id') {
                    $answer_id = null;
                    
                    if ($field_name === 'agent') {
                        $agent_mapping = array(
                            'Yes' => 395864,
                            'No' => 395863
                        );
                        $answer_id = isset($agent_mapping[$processed_value]) ? $agent_mapping[$processed_value] : null;
                        // error_log('[LASSO CF7 DEBUG] Agent mapping: ' . $processed_value . ' -> ' . $answer_id);
                    } elseif (is_numeric($processed_value)) {
                        $answer_id = absint($processed_value);
                        // error_log('[LASSO CF7 DEBUG] Using numeric answer ID: ' . $answer_id);
                    }
                    
                    if ($answer_id) {
                        // CRITICAL FIX: Use 'answer' instead of 'answerId' to match Gravity Forms format
                        $payload['questions'][] = array( 
                            'questionId' => $question_id, 
                            'answers' => array( array( 'answer' => $answer_id ) )  // FIXED: 'answer' not 'answerId'
                        );
                        // error_log('[LASSO CF7 DEBUG] Added question: QID=' . $question_id . ', AnswerID=' . $answer_id);
                    } else {
                        // error_log('[LASSO CF7 DEBUG] Could not map answer for: ' . $field_name . ' = ' . $processed_value);
                    }
                } else { 
                    // FIXED: Use 'answer' instead of 'answerId'
                    $payload['questions'][] = array( 
                        'questionId' => $question_id, 
                        'answers' => array( array( 'answer' => $processed_value ) )  // FIXED: 'answer' not 'answerId'
                    );
                    // error_log('[LASSO CF7 DEBUG] Added text question: QID=' . $question_id . ', Answer=' . $processed_value);
                }
            } else {
                // error_log('[LASSO CF7 DEBUG] Unknown mapping type: ' . $mapping_rules['type']);
            }
        }
        
        // Clean up empty arrays
        foreach(['emails', 'phones'] as $key) {
            if(isset($payload[$key]) && empty($payload[$key])) { 
                unset($payload[$key]); 
                // error_log('[LASSO CF7 DEBUG] Removed empty ' . $key . ' array');
            }
        }

        // Debug email functionality - keep this for production debugging
        $debug_email = get_option('lasso_leader_debug_email', '');
        if (!empty($debug_email)) {
            $this->send_debug_email($debug_email, 'PRE-API', $payload);
        }
        
        // error_log('[LASSO CF7 DEBUG] Complete JSON payload: ' . wp_json_encode($payload, JSON_PRETTY_PRINT));

        // Call API using exact same method as Gravity Forms
        // error_log('[LASSO CF7 DEBUG] ===== CALLING LASSO API =====');
        
        $response = $this->api_handler->send_to_lasso( $payload, $api_key );
        
        // Process response with essential logging only
        if ( is_wp_error( $response ) ) {
            $error_message = 'Lasso CF7 Error: ' . $response->get_error_message();
            error_log('[LASSO CF7] WP_Error: ' . $error_message);
            
            // Send debug email on error
            if (!empty($debug_email)) {
                $this->send_debug_email($debug_email, 'ERROR', array(
                    'payload' => $payload,
                    'error' => $error_message
                ));
            }
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            
            if ( $response_code >= 200 && $response_code < 300 ) {
                $response_data = json_decode( $response_body, true );
                if ( isset($response_data['registrantId']) ) {
                    // error_log('[LASSO CF7 DEBUG] SUCCESS! Registrant ID: ' . $response_data['registrantId']);
                    
                    // Send debug email on success
                    if (!empty($debug_email)) {
                        $this->send_debug_email($debug_email, 'SUCCESS', array(
                            'registrant_id' => $response_data['registrantId'],
                            'payload' => $payload,
                            'response' => $response_data
                        ));
                    }
                } else {
                    error_log('[LASSO CF7] Success response but no registrant ID in: ' . $response_body);
                }
            } else {
                error_log('[LASSO CF7] API Error - Code: ' . $response_code . ' Body: ' . $response_body);
                
                // Send debug email on API error
                if (!empty($debug_email)) {
                    $this->send_debug_email($debug_email, 'API_ERROR', array(
                        'response_code' => $response_code,
                        'response_body' => $response_body,
                        'payload' => $payload
                    ));
                }
            }
        }

        // error_log('[LASSO CF7 DEBUG] ========== FORM SUBMISSION COMPLETE ==========');
    }

    /**
     * Send debug email with detailed information
     * KEEP THIS - Essential for production debugging
     */
    private function send_debug_email($email, $type, $data) {
        $subject = "[Lasso Leader CF7] Debug - {$type} - " . date('Y-m-d H:i:s');
        
        $message = "Lasso Leader Contact Form 7 Debug Report\n";
        $message .= "==========================================\n";
        $message .= "Type: {$type}\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $message .= "Site: " . get_site_url() . "\n\n";
        
        if ($type === 'PRE-API') {
            $message .= "PAYLOAD BEING SENT TO API:\n";
            $message .= "==========================\n";
            $message .= print_r($data, true) . "\n\n";
            $message .= "JSON FORMAT:\n";
            $message .= wp_json_encode($data, JSON_PRETTY_PRINT) . "\n";
            
        } elseif ($type === 'SUCCESS') {
            $message .= "SUCCESSFUL SUBMISSION!\n";
            $message .= "======================\n";
            $message .= "Registrant ID: " . $data['registrant_id'] . "\n\n";
            $message .= "PAYLOAD SENT:\n";
            $message .= print_r($data['payload'], true) . "\n\n";
            $message .= "API RESPONSE:\n";
            $message .= print_r($data['response'], true) . "\n";
            
        } elseif ($type === 'ERROR') {
            $message .= "ERROR OCCURRED!\n";
            $message .= "===============\n";
            $message .= "Error: " . $data['error'] . "\n\n";
            $message .= "PAYLOAD THAT FAILED:\n";
            $message .= print_r($data['payload'], true) . "\n";
            
        } elseif ($type === 'API_ERROR') {
            $message .= "API ERROR!\n";
            $message .= "==========\n";
            $message .= "Response Code: " . $data['response_code'] . "\n";
            $message .= "Response Body: " . $data['response_body'] . "\n\n";
            $message .= "PAYLOAD SENT:\n";
            $message .= print_r($data['payload'], true) . "\n";
        }
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
        // error_log('[LASSO CF7 DEBUG] ? Debug email sent to: ' . $email);
    }
}