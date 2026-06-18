<?php
/**
 * Global helper functions for the Lasso Leader plugin.
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lasso_leader_write_log' ) ) {
    function lasso_leader_write_log( $message, $level = 'INFO', $context = [] ) {
        if ( ! defined('LASSO_LEADER_DEBUG') || ! LASSO_LEADER_DEBUG ) return;
        $log_entry = sprintf("[%s] [Lasso Leader] [%s]: %s", date('Y-m-d H:i:s T'), strtoupper($level), is_string($message) ? $message : print_r($message, true));
        if (!empty($context)) { $log_entry .= " | Context: " . print_r($context, true); }
        error_log($log_entry);
    }
}

if ( ! function_exists( 'lasso_leader_sanitize_id_list' ) ) {
    function lasso_leader_sanitize_id_list( $input ) {
        if ( is_string( $input ) ) {
            $paths = explode("\n", $input);
        } elseif ( is_array( $input ) ) {
            $paths = $input;
        } else {
            return array();
        }
        $sanitized_paths = array_map('sanitize_text_field', array_filter(array_map('trim', $paths)));
        return array_values( array_unique($sanitized_paths) );
    }
}

if ( ! function_exists( 'lasso_leader_rgar' ) ) {
    function lasso_leader_rgar( $array, $key ) {
        if ( is_array( $array ) && isset( $array[ $key ] ) ) {
            return $array[ $key ];
        }
        return '';
    }
}
// CORRECTED: Removed the extra closing brace that was causing a fatal parse error.
