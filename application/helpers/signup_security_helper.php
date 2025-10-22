<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Signup Flow Security Helper
 * 
 * Manages secure signup flow tokens to prevent abuse of email checking outside of registration
 */

if (!function_exists('generate_signup_token')) {
    /**
     * Generate a secure signup flow token
     * 
     * @return string
     */
    function generate_signup_token()
    {
        $CI =& get_instance();
        $CI->load->library('session');
        
        $token = bin2hex(random_bytes(32));
        $token_data = [
            'token' => $token,
            'created_at' => time(),
            'used_for_email_check' => false,
            'email_checks_count' => 0
        ];
        
        $CI->session->set_userdata('signup_flow_token', $token_data);
        
        log_message('info', 'Signup flow token generated: ' . substr($token, 0, 8) . '...');
        
        return $token;
    }
}

if (!function_exists('validate_signup_token')) {
    /**
     * Validate signup flow token
     * 
     * @param string $token
     * @param bool $consume_for_email_check Whether this validation is for email checking
     * @return array ['valid' => bool, 'error' => string|null, 'remaining_checks' => int]
     */
    function validate_signup_token($token, $consume_for_email_check = false)
    {
        $CI =& get_instance();
        $CI->load->library('session');
        
        $token_data = $CI->session->userdata('signup_flow_token');
        
        if (!$token_data) {
            return [
                'valid' => false,
                'error' => 'No active signup session found',
                'remaining_checks' => 0
            ];
        }
        
        // Check token match
        if (!hash_equals($token_data['token'], $token)) {
            log_message('warning', 'Invalid signup token attempted from IP: ' . get_client_ip_address());
            return [
                'valid' => false,
                'error' => 'Invalid signup session token',
                'remaining_checks' => 0
            ];
        }
        
        // Check token age (expires after 30 minutes)
        if (time() - $token_data['created_at'] > 1800) {
            $CI->session->unset_userdata('signup_flow_token');
            return [
                'valid' => false,
                'error' => 'Signup session expired. Please start registration again.',
                'remaining_checks' => 0
            ];
        }
        
        // For email check operations
        if ($consume_for_email_check) {
            // Limit email checks per signup session (max 5)
            if ($token_data['email_checks_count'] >= 5) {
                return [
                    'valid' => false,
                    'error' => 'Email check limit exceeded for this signup session',
                    'remaining_checks' => 0
                ];
            }
            
            // Increment email check counter
            $token_data['email_checks_count']++;
            $token_data['used_for_email_check'] = true;
            $CI->session->set_userdata('signup_flow_token', $token_data);
            
            log_message('info', 'Email check performed with signup token. Count: ' . $token_data['email_checks_count']);
        }
        
        return [
            'valid' => true,
            'error' => null,
            'remaining_checks' => max(0, 5 - $token_data['email_checks_count'])
        ];
    }
}

if (!function_exists('invalidate_signup_token')) {
    /**
     * Invalidate the current signup flow token
     * 
     * @return bool
     */
    function invalidate_signup_token()
    {
        $CI =& get_instance();
        $CI->load->library('session');
        
        $token_data = $CI->session->userdata('signup_flow_token');
        if ($token_data) {
            log_message('info', 'Signup flow token invalidated: ' . substr($token_data['token'], 0, 8) . '...');
        }
        
        $CI->session->unset_userdata('signup_flow_token');
        return true;
    }
}

if (!function_exists('get_signup_token_status')) {
    /**
     * Get current signup token status without consuming it
     * 
     * @return array ['active' => bool, 'remaining_checks' => int, 'created_at' => int|null]
     */
    function get_signup_token_status()
    {
        $CI =& get_instance();
        $CI->load->library('session');
        
        $token_data = $CI->session->userdata('signup_flow_token');
        
        if (!$token_data) {
            return [
                'active' => false,
                'remaining_checks' => 0,
                'created_at' => null
            ];
        }
        
        // Check if expired
        if (time() - $token_data['created_at'] > 1800) {
            $CI->session->unset_userdata('signup_flow_token');
            return [
                'active' => false,
                'remaining_checks' => 0,
                'created_at' => null
            ];
        }
        
        return [
            'active' => true,
            'remaining_checks' => max(0, 5 - $token_data['email_checks_count']),
            'created_at' => $token_data['created_at']
        ];
    }
}

if (!function_exists('get_client_ip_address')) {
    /**
     * Get client IP address with proper header checking
     * 
     * @return string
     */
    function get_client_ip_address()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('is_authenticated_user')) {
    /**
     * Check if current user is authenticated
     * 
     * @return bool
     */
    function is_authenticated_user()
    {
        $CI =& get_instance();
        $CI->load->library('session');
        
        return (bool) $CI->session->userdata('logged_in');
    }
}

if (!function_exists('get_user_id')) {
    /**
     * Get current authenticated user ID
     * 
     * @return int|null
     */
    function get_user_id()
    {
        $CI =& get_instance();
        $CI->load->library('session');
        
        return $CI->session->userdata('user_id');
    }
}