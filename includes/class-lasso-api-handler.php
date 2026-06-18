<?php
/**
 * Handles all communication with the Lasso CRM API.
 * VERSION 2.1.3-UNIVERSAL-FORMAT-FIX
 * CRITICAL FIX: Handles both CF7 and Gravity Forms formats properly
 * - Detects if answer is numeric ID and converts to "answerId" format
 * - Keeps text answers in "answer" format
 * Last Updated: July 14, 2025 - Universal Format Fix
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_API_Handler {
    
    const LASSO_API_URL = 'https://api.lassocrm.com/v1/registrants';
    const TIMEOUT = 45;
    const VERSION = '2.1.3-UNIVERSAL-FORMAT-FIX';

    public function send_to_lasso( $payload, $api_key ) {
        // VERSION CHECK LOG - KEEP THIS FOR PRODUCTION REFERENCE
        error_log('=== LASSO API HANDLER VERSION: ' . self::VERSION . ' ===');
        // error_log('=== UNIVERSAL FORMAT: HANDLES BOTH CF7 AND GRAVITY FORMS ===');
        
        if ( empty( $api_key ) ) {
            return new WP_Error('missing_api_key', 'Lasso API Key is not configured.');
        }
        return $this->process_unified_payload( $payload, $api_key );
    }

    private function process_unified_payload( $payload, $api_key ) {
        // Step 1: Prepare and send the main registrant data.
        
        // FIXED: Handle both object and array formats
        $person_data = array();
        $person = isset($payload['person']) ? $payload['person'] : array();
        
        // Convert stdClass to array if needed
        if ( is_object($person) ) {
            $person = (array) $person;
        }
        
        if ( isset($person['firstName']) ) {
            $person_data['firstName'] = $person['firstName'];
        }
        if ( isset($person['lastName']) ) {
            $person_data['lastName'] = $person['lastName'];
        }
        if ( isset($person['message']) ) {
            $person_data['message'] = $person['message'];
        }
        
        $registrant_data = array(
            'person'     => $person_data,
            'emails'     => isset($payload['emails']) ? $payload['emails'] : array(),
            'phones'     => isset($payload['phones']) ? $payload['phones'] : array(),
            'sourceType' => array('sourceType' => 'Walk In')
        );
        
        // PRESERVED: Handle notes as both string and array (from 2.1.1)
        if ( isset($payload['notes']) ) {
            $notes_value = '';
            
            if ( is_array($payload['notes']) ) {
                // Handle array notes (from Gravity Forms)
                if ( !empty($payload['notes']) ) {
                    $notes_value = implode(', ', $payload['notes']);
                    // error_log('LASSO API: Converting array notes to string: ' . $notes_value);
                }
            } elseif ( is_string($payload['notes']) ) {
                // Handle string notes (from Contact Form 7)
                $notes_value = trim($payload['notes']);
                // error_log('LASSO API: Using string notes: ' . $notes_value);
            }
            
            // Only include notes if we have content
            if ( !empty($notes_value) ) {
                $registrant_data['notes'] = $notes_value;
                // error_log('LASSO API: Including notes in payload: ' . $notes_value);
            } else {
                // error_log('LASSO API: No notes content - skipping notes field entirely');
            }
        } else {
            // error_log('LASSO API: No notes to include - skipping notes field entirely');
        }
        
        // Add ProjectId if present
        if ( isset($payload['ProjectId']) && !empty($payload['ProjectId']) ) {
            $registrant_data['ProjectId'] = $payload['ProjectId'];
        }

        if ( isset($payload['assignedSalesReps']) && !empty($payload['assignedSalesReps']) ) {
            $registrant_data['assignedSalesReps'] = $payload['assignedSalesReps'];
        }
        
        // FIXED: Uncommented the actual API call - THIS IS CRITICAL!
        $response = $this->send_request( $registrant_data, $api_key );
        
        // Keep basic error handling for production
        if ( is_wp_error($response) ) {
            error_log('LASSO API WP_ERROR: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ( $response_code >= 400 ) {
            error_log('LASSO API ERROR ' . $response_code . ': ' . wp_remote_retrieve_body($response));
            return $response;
        }

        // Step 2: If successful and there are questions, send them.
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        $registrant_id = isset($response_body['registrantId']) ? $response_body['registrantId'] : null;
        $questions = isset($payload['questions']) ? $payload['questions'] : array();

        if ( $registrant_id && ! empty( $questions ) ) {
            // error_log('SENDING QUESTIONS FOR REGISTRANT: ' . $registrant_id);
            $this->send_questions_to_lasso( $api_key, $registrant_id, $questions );
        } else {
            // error_log('NO QUESTIONS TO SEND - registrant_id: ' . $registrant_id . ', question_count: ' . count($questions));
        }
        
        return $response;
    }

    private function send_questions_to_lasso( $api_key, $registrant_id, $questions ) {
        // error_log('🎯 QUESTION SUBMISSION START - VERSION 2.1.4-MULTISELECT-FIX');
        
        if ( empty( $questions ) ) {
            // error_log('🎯 Questions array is empty, nothing to send');
            return;
        }

        $url = self::LASSO_API_URL . '/' . $registrant_id . '/questions';
        // error_log('🎯 QUESTION ENDPOINT: ' . $url);
        
        // Answer ID to Label mapping (from your CSV)
        $answer_mappings = [
            // Marketing Source answers
            396528 => 'Magazine',
            396527 => 'Walk/Drive By', 
            396526 => 'Friend/Family',
            396525 => 'Referral',
            396524 => 'Google Search',
            396523 => 'Word of Mouth',
            396522 => 'Real Estate Agent',
            396521 => 'Site Signage',
            396520 => 'Social Media',
            396519 => 'Print - Atlanta Business Chronicle',
            396518 => 'Print - KNOW Atlanta',
            396517 => 'Print - Atlanta Home',
            396516 => 'Print - Atlanta Intown',
            396515 => 'Print - Atlanta Magazine',
            396514 => 'Digital - Atlanta Magazine',
            396513 => 'Digital - Atlanta Business Chronicle',
            396512 => 'Zillow',
            
            // Agent answers
            395864 => 'Yes',
            395863 => 'No',
            
            // Add more mappings as needed...
        ];
        
        // UNIVERSAL FORMAT: Handle both CF7 and Gravity Forms properly
        foreach ( $questions as $index => $question ) {
            $question_id = isset($question['questionId']) ? $question['questionId'] : null;
            $answers = isset($question['answers']) ? $question['answers'] : array();
            
            if ( $question_id && !empty($answers) ) {
                $first_answer = $answers[0];
                $answer_value = null;
                
                // Get the answer value from whatever format it comes in
                if (isset($first_answer['answer'])) {
                    $answer_value = $first_answer['answer'];
                } elseif (isset($first_answer['answerId'])) {
                    $answer_value = $first_answer['answerId'];
                } else {
                    // error_log("⚠️  SKIPPED QUESTION #" . ($index + 1) . " - No valid answer format found");
                    continue;
                }
                
                // CRITICAL FIX: Format based on question type and content
                if (is_numeric($answer_value) && intval($answer_value) > 100000) {
                    $answer_id = intval($answer_value);
                    $answer_label = isset($answer_mappings[$answer_id]) ? $answer_mappings[$answer_id] : $answer_id;
                    
                    // Use the WORKING format from your example
                    $payload = array(
                        "questionId" => $question_id,
                        "answers" => array(
                            array(
                                "answerId" => $answer_id,
                                "answer" => $answer_label  // Include both ID and label!
                            )
                        )
                    );
                    // error_log("🎯 PROCESSING QUESTION #" . ($index + 1) . " - ID: $question_id, AnswerID: $answer_id, Label: $answer_label (using WORKING format)");
                } else {
                    // This looks like free text
                    $payload = array(
                        "questionId" => $question_id,
                        "answers" => array(
                            array("answer" => $answer_value)
                        )
                    );
                    // error_log("🎯 PROCESSING QUESTION #" . ($index + 1) . " - ID: $question_id, Answer: $answer_value (text format)");
                }
                
                // error_log('🎯 SENDING INDIVIDUAL QUESTION: ' . json_encode($payload));
                
                $args = array(
                    'headers' => array( 
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json; charset=utf-8'
                    ),
                    'body'    => json_encode( $payload ),
                    'timeout' => self::TIMEOUT,
                );

                $response = wp_remote_post( $url, $args );
                
                // Keep basic error handling for production
                if ( is_wp_error( $response ) ) {
                    error_log('Question submission failed for QID ' . $question_id . ': ' . $response->get_error_message());
                } else {
                    $response_code = wp_remote_retrieve_response_code($response);
                    if ( $response_code >= 400 ) {
                        $response_body = wp_remote_retrieve_body($response);
                        error_log('Question QID ' . $question_id . ' failed - Response Code: ' . $response_code . ' | Body: ' . $response_body);
                    }
                    // Success responses are silent in production
                }
            } else {
                // error_log('⚠️  SKIPPED QUESTION #' . ($index + 1) . ' - Missing ID or answers (ID: ' . $question_id . ', Answers: ' . json_encode($answers) . ')');
            }
        }
        
        // error_log('🎯 QUESTION SUBMISSION COMPLETE');
    }

    private function send_request( $body, $api_key, $endpoint_suffix = '' ) {
        $url = self::LASSO_API_URL . $endpoint_suffix;
        $args = array(
            'headers' => array( 
                'Authorization' => 'Bearer ' . $api_key,  // CONFIRMED: Bearer format
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body'    => json_encode( $body ),
            'timeout' => self::TIMEOUT,
        );

        // Comment out detailed request logging for production
        // error_log('SENDING REQUEST TO: ' . $url);
        // error_log('REQUEST BODY: ' . json_encode($body));
        
        return wp_remote_post( $url, $args );
    }
}

/*
 * ==========================================
 * HOW THIS FIXES BOTH CF7 AND GRAVITY FORMS
 * ==========================================
 * 
 * BEFORE (Broken):
 * - CF7 sends: 'answer' => 396552
 * - Gravity Forms sends: 'answer' => 396552  
 * - API Handler sends: 'answer' => 396552
 * - Lasso displays: "396552" (raw ID)
 * 
 * AFTER (Fixed):
 * - CF7 sends: 'answer' => 396552
 * - Gravity Forms sends: 'answer' => 396552
 * - API Handler detects: 396552 is numeric > 100000
 * - API Handler sends: 'answerId' => 396552
 * - Lasso displays: "Own" (proper label)
 * 
 * FOR TEXT ANSWERS:
 * - CF7 sends: 'answer' => "John Smith"
 * - Gravity Forms sends: 'answer' => "John Smith"
 * - API Handler detects: "John Smith" is not a large number
 * - API Handler sends: 'answer' => "John Smith"
 * - Lasso displays: "John Smith" (as expected)
 */
?>
