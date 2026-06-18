<?php
/**
 * NEW FILE: includes/class-lasso-project-override.php
 * 
 * ZERO RISK APPROACH: This wraps around your existing API handler without modifying it.
 * Your precious API handler stays completely untouched and protected.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_Project_Override {
    
    private $api_handler;
    private $original_settings = array();
    
    public function __construct($api_handler) {
        $this->api_handler = $api_handler;
    }
    
    /**
     * MAIN METHOD: Process form with project override
     * This is the ONLY method other classes need to call
     */
    public function process_with_override($form_data, $source = 'form') {
        error_log('[LASSO OVERRIDE] === STARTING OVERRIDE PROCESSING ===');
        error_log('[LASSO OVERRIDE] Source: ' . $source);
        error_log('[LASSO OVERRIDE] Form data keys: ' . implode(', ', array_keys($form_data)));
        
        // Step 1: Detect project override settings
        $override_settings = $this->detect_project_override($form_data);
        
        // Step 2: If override detected, temporarily change WordPress options
        if ($override_settings['override_detected']) {
            $this->set_temporary_overrides($override_settings);
        }
        
        // Step 3: Enhance form data for better Lasso integration
        $enhanced_form_data = $this->enhance_form_data($form_data, $override_settings);
        
        // Step 4: Get API key (override or global)
        $api_key = $override_settings['api_key'];
        
        // Step 5: Call your EXISTING, UNTOUCHED API handler
        error_log('[LASSO OVERRIDE] Calling API handler with Project ID: ' . $override_settings['project_id']);
        $result = $this->api_handler->send_to_lasso($enhanced_form_data, $api_key);
        
        // Step 6: Restore original settings
        if ($override_settings['override_detected']) {
            $this->restore_original_settings();
        }
        
        // Step 7: Add override information to result for debugging
        if (is_array($result)) {
            $result['override_info'] = array(
                'source' => $override_settings['source'],
                'project_id' => $override_settings['project_id'],
                'project_name' => $override_settings['project_name'],
                'api_key_source' => $override_settings['api_key_source']
            );
        }
        
        error_log('[LASSO OVERRIDE] === OVERRIDE PROCESSING COMPLETE ===');
        return $result;
    }
    
    /**
     * Detect which project settings to use - UPDATED to handle both URL formats
     */
    private function detect_project_override($form_data) {
        // Start with global defaults
        $settings = array(
            'project_id' => get_option('lasso_leader_project_id', '25633'),
            'api_key' => get_option('lasso_leader_api_key', ''),
            'project_name' => '',
            'source' => 'global_default',
            'api_key_source' => 'global',
            'override_detected' => false,
            'debug_info' => array()
        );
        
        $debug = array();
        $debug[] = "Global Project ID: {$settings['project_id']}";
        $debug[] = "Global API Key: " . (empty($settings['api_key']) ? 'EMPTY' : 'SET');
        
        // Priority 1A: NEW! URL Parameters with Answer ID (?project=397189)
        if (isset($_GET['project']) && !empty($_GET['project'])) {
            $url_answer_id = sanitize_text_field($_GET['project']);
            $debug[] = "Found URL project (Answer ID): {$url_answer_id}";
            
            $project_data = $this->get_project_data_by_answer_id($url_answer_id);
            if ($project_data) {
                $settings = array_merge($settings, $project_data);
                $settings['source'] = 'url_answer_id';
                $settings['override_detected'] = true;
                $debug[] = "Applied Answer ID override: {$project_data['project_name']} (Project ID: {$project_data['project_id']})";
            }
        }
        
        // Priority 1B: Legacy URL Parameters with Project ID (?project_id=25485)
        if (!$settings['override_detected'] && isset($_GET['project_id']) && !empty($_GET['project_id'])) {
            $url_project_id = sanitize_text_field($_GET['project_id']);
            $debug[] = "Found URL project_id: {$url_project_id}";
            
            $project_data = $this->get_project_data_by_lasso_id($url_project_id);
            if ($project_data) {
                $settings = array_merge($settings, $project_data);
                $settings['source'] = 'url_parameter';
                $settings['override_detected'] = true;
                $debug[] = "Applied URL override: {$project_data['project_name']}";
            }
        }
        
        // Priority 2: Form Hidden Fields (lasso_project_id)
        if (!$settings['override_detected'] && isset($form_data['lasso_project_id'])) {
            $form_project_id = sanitize_text_field($form_data['lasso_project_id']);
            $debug[] = "Found form field lasso_project_id: {$form_project_id}";
            
            $project_data = $this->get_project_data_by_lasso_id($form_project_id);
            if ($project_data) {
                $settings = array_merge($settings, $project_data);
                $settings['source'] = 'form_field';
                $settings['override_detected'] = true;
                $debug[] = "Applied form field override: {$project_data['project_name']}";
            }
        }
        
        // Priority 3: Project Name Detection (project_name field)
        if (!$settings['override_detected'] && isset($form_data['project_name'])) {
            $project_name = sanitize_text_field($form_data['project_name']);
            $debug[] = "Found project_name field: {$project_name}";
            
            $project_data = $this->get_project_data_by_name($project_name);
            if ($project_data) {
                $settings = array_merge($settings, $project_data);
                $settings['source'] = 'project_name_match';
                $settings['override_detected'] = true;
                $debug[] = "Applied project name override: {$project_data['project_name']}";
            }
        }
        
        $settings['debug_info'] = $debug;
        
        // Log the final decision
        error_log('[LASSO OVERRIDE] Final settings: ' . json_encode(array(
            'source' => $settings['source'],
            'project_id' => $settings['project_id'],
            'project_name' => $settings['project_name'],
            'override_detected' => $settings['override_detected'],
            'api_key_source' => $settings['api_key_source']
        )));
        
        return $settings;
    }
    
    /**
     * NEW METHOD: Get project data from WordPress post by Answer ID
     */
    private function get_project_data_by_answer_id($answer_id) {
        // First try to find by Answer ID meta field
        $project_posts = get_posts(array(
            'post_type' => 'lasso_project',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_lasso_answer_id',
                    'value' => $answer_id,
                    'compare' => '='
                )
            )
        ));
        
        if (!empty($project_posts)) {
            $project_post = $project_posts[0];
            
            // Get project meta
            $project_id = get_post_meta($project_post->ID, '_lasso_project_id', true);
            $project_api_key = get_post_meta($project_post->ID, '_lasso_api_key', true);
            $project_name = get_post_meta($project_post->ID, '_lasso_project_name', true);
            
            return array(
                'project_id' => !empty($project_id) ? $project_id : get_option('lasso_leader_project_id', '25633'),
                'api_key' => !empty($project_api_key) ? $project_api_key : get_option('lasso_leader_api_key', ''),
                'api_key_source' => !empty($project_api_key) ? 'project_specific' : 'global_fallback',
                'project_name' => !empty($project_name) ? $project_name : $project_post->post_title,
                'post_id' => $project_post->ID,
                'answer_id' => $answer_id
            );
        }
        
        // FALLBACK: Try hardcoded mapping if no meta field found
        $answer_id_to_name = array(
            '395601' => 'Dawson Corner',
            '395600' => 'Swanns Bridge', 
            '396258' => 'Dickson Place',
            '397189' => '40 West 12th',
            '397256' => 'Downing Park',
            '397255' => 'Findley Row',
            '397254' => 'J5',
            '397253' => 'Moderns on Memorial',
            '397252' => 'The Harman'
        );
        
        if (isset($answer_id_to_name[$answer_id])) {
            $project_name = $answer_id_to_name[$answer_id];
            
            // Try to find project by name
            $project_data = $this->get_project_data_by_name($project_name);
            if ($project_data) {
                $project_data['answer_id'] = $answer_id;
                return $project_data;
            }
        }
        
        return null;
    }
    
    /**
     * Get project data from WordPress post by Lasso ID
     */
    private function get_project_data_by_lasso_id($lasso_project_id) {
        $project_post = $this->find_project_post_by_lasso_id($lasso_project_id);
        if (!$project_post) {
            return null;
        }
        
        // Get project meta
        $project_api_key = get_post_meta($project_post->ID, '_lasso_api_key', true);
        $project_name = get_post_meta($project_post->ID, '_lasso_project_name', true);
        
        return array(
            'project_id' => $lasso_project_id, // Use the detected ID
            'api_key' => !empty($project_api_key) ? $project_api_key : get_option('lasso_leader_api_key', ''),
            'api_key_source' => !empty($project_api_key) ? 'project_specific' : 'global_fallback',
            'project_name' => !empty($project_name) ? $project_name : $project_post->post_title,
            'post_id' => $project_post->ID
        );
    }
    
    /**
     * Get project data by project name/title
     */
    private function get_project_data_by_name($project_name) {
        // Try exact title match first
        $project_post = get_page_by_title($project_name, OBJECT, 'lasso_project');
        
        if (!$project_post) {
            // Try search
            $projects = get_posts(array(
                'post_type' => 'lasso_project',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                's' => $project_name
            ));
            $project_post = !empty($projects) ? $projects[0] : null;
        }
        
        if (!$project_post) {
            return null;
        }
        
        // Get project meta
        $project_id = get_post_meta($project_post->ID, '_lasso_project_id', true);
        $project_api_key = get_post_meta($project_post->ID, '_lasso_api_key', true);
        $project_name_meta = get_post_meta($project_post->ID, '_lasso_project_name', true);
        
        return array(
            'project_id' => !empty($project_id) ? $project_id : get_option('lasso_leader_project_id', '25633'),
            'api_key' => !empty($project_api_key) ? $project_api_key : get_option('lasso_leader_api_key', ''),
            'api_key_source' => !empty($project_api_key) ? 'project_specific' : 'global_fallback',
            'project_name' => !empty($project_name_meta) ? $project_name_meta : $project_post->post_title,
            'post_id' => $project_post->ID
        );
    }
    
    /**
     * Find project post by Lasso Project ID
     */
    private function find_project_post_by_lasso_id($lasso_project_id) {
        $projects = get_posts(array(
            'post_type' => 'lasso_project',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_lasso_project_id',
                    'value' => $lasso_project_id,
                    'compare' => '='
                )
            )
        ));
        
        return !empty($projects) ? $projects[0] : null;
    }
    
    /**
     * Enhance form data with project-specific information
     */
    private function enhance_form_data($form_data, $override_settings) {
        $enhanced = $form_data;
        
        // Auto-populate project name for Question 156759 if we have override data
        if ($override_settings['override_detected'] && !empty($override_settings['project_name'])) {
            // Add this to the form data so your API handler can use it
            $enhanced['auto_project_156759'] = $override_settings['project_name'];
            error_log('[LASSO OVERRIDE] Enhanced form data with project name: ' . $override_settings['project_name']);
        }
        
        return $enhanced;
    }
    
    /**
     * Temporarily override global WordPress options (SAFE - NO API HANDLER CHANGES)
     */
    private function set_temporary_overrides($override_settings) {
        // Store original values for restoration
        $this->original_settings = array(
            'project_id' => get_option('lasso_leader_project_id'),
            'api_key' => get_option('lasso_leader_api_key')
        );
        
        // Temporarily update the options that your API handler reads
        update_option('lasso_leader_project_id', $override_settings['project_id']);
        
        if (!empty($override_settings['api_key'])) {
            update_option('lasso_leader_api_key', $override_settings['api_key']);
        }
        
        error_log('[LASSO OVERRIDE] Temporary override applied - Project ID: ' . $override_settings['project_id']);
        error_log('[LASSO OVERRIDE] API Key source: ' . $override_settings['api_key_source']);
    }
    
    /**
     * Restore original WordPress options
     */
    private function restore_original_settings() {
        if (empty($this->original_settings)) {
            return;
        }
        
        // Restore the original values
        update_option('lasso_leader_project_id', $this->original_settings['project_id']);
        update_option('lasso_leader_api_key', $this->original_settings['api_key']);
        
        error_log('[LASSO OVERRIDE] Original settings restored');
        
        // Clear stored values
        $this->original_settings = array();
    }
}

/**
 * MINIMAL MODIFICATION: Updated Gravity Forms process_submission method
 * 
 * REPLACE the process_submission method in class-lasso-gravity-forms.php with this:
 */

/*
public function process_submission( $entry, $form ) {
    $settings = $this->get_form_settings( $form );
    if ( ! rgar( $settings, 'enable_integration_cb' ) ) {
        return;
    }

    $api_handler = new Lasso_API_Handler();
    
    // Build form data array (your existing logic)
    $field_mappings = rgar( $settings, 'field_mapping' );
    
    $payload = array( 
        'person' => new stdClass(), 
        'questions' => array(), 
        'emails' => array(), 
        'phones' => array(), 
        'notes' => array() 
    );
    
    // Add default rating
    $payload['RatingId'] = 15644987;
    
    // Process field mappings (your existing logic stays exactly the same)
    foreach ( $form['fields'] as $field ) {
        $field_id = $field->id;
        if ( isset( $field_mappings[$field_id] ) ) {
            $map = $field_mappings[$field_id];
            $value = rgar( $entry, (string) $field_id );
            
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
    
    // NEW: Use Project Override system instead of calling API handler directly
    $project_override = new Lasso_Project_Override($api_handler);
    $result = $project_override->process_with_override($payload, 'gravity_forms');
    
    // Enhanced entry notes with override information
    $debug_enabled = rgar( $settings, 'enable_debugging_cb' );
    $debug_email = rgar( $settings, 'debug_email' );
    
    if ( is_wp_error( $result ) ) {
        $error_message = 'Lasso API Error: ' . $result->get_error_message();
        $this->add_note( $entry['id'], $error_message, 'error' );
        
        if ( $debug_enabled && $debug_email ) {
            $this->send_debug_email( $debug_email, 'Lasso API Error', $error_message, $entry, $form );
        }
    } else {
        $response_code = wp_remote_retrieve_response_code( $result );
        $response_body = json_decode( wp_remote_retrieve_body( $result ), true );
        
        if ( $response_code >= 200 && $response_code < 300 && isset($response_body['registrantId']) ) {
            $success_message = 'Successfully sent to Lasso API. Registrant ID: ' . $response_body['registrantId'];
            
            // Add override information to success message
            if (isset($result['override_info'])) {
                $success_message .= "\nSettings Source: " . $result['override_info']['source'];
                $success_message .= "\nProject ID: " . $result['override_info']['project_id'];
                if (!empty($result['override_info']['project_name'])) {
                    $success_message .= "\nProject Name: " . $result['override_info']['project_name'];
                }
            }
            
            $this->add_note( $entry['id'], $success_message, 'success' );
            
            if ( $debug_enabled && $debug_email ) {
                $this->send_debug_email( $debug_email, 'Lasso API Success', $success_message, $entry, $form );
            }
        } else {
            $error_message = 'Lasso API submission failed. Status: ' . $response_code . ' | Response: ' . wp_remote_retrieve_body( $result );
            $this->add_note( $entry['id'], $error_message, 'error' );
            
            if ( $debug_enabled && $debug_email ) {
                $this->send_debug_email( $debug_email, 'Lasso API Failure', $error_message, $entry, $form );
            }
        }
    }
}
*/