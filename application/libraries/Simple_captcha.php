<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Simple CAPTCHA Library
 * 
 * Provides basic math-based CAPTCHA functionality
 */
class Simple_captcha
{
    protected $CI;
    protected $session_key = 'captcha_answer';
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('session');
    }
    
    /**
     * Generate a new CAPTCHA challenge
     * 
     * @return array ['question' => string, 'challenge_id' => string]
     */
    public function generate()
    {
        // Generate random math problem
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operations = ['+', '-', '*'];
        $operation = $operations[array_rand($operations)];
        
        // Calculate answer
        switch ($operation) {
            case '+':
                $answer = $num1 + $num2;
                break;
            case '-':
                // Ensure positive result
                if ($num1 < $num2) {
                    list($num1, $num2) = [$num2, $num1];
                }
                $answer = $num1 - $num2;
                break;
            case '*':
                $answer = $num1 * $num2;
                break;
        }
        
        $question = "{$num1} {$operation} {$num2} = ?";
        $challenge_id = bin2hex(random_bytes(16));
        
        // Store answer in session with challenge ID
        $captcha_data = $this->CI->session->userdata('captcha_data') ?: [];
        $captcha_data[$challenge_id] = [
            'answer' => $answer,
            'created_at' => time(),
            'attempts' => 0
        ];
        
        // Clean old CAPTCHAs (older than 10 minutes)
        $current_time = time();
        foreach ($captcha_data as $id => $data) {
            if ($current_time - $data['created_at'] > 600) {
                unset($captcha_data[$id]);
            }
        }
        
        $this->CI->session->set_userdata('captcha_data', $captcha_data);
        
        return [
            'question' => $question,
            'challenge_id' => $challenge_id
        ];
    }
    
    /**
     * Verify CAPTCHA answer
     * 
     * @param string $challenge_id
     * @param mixed $user_answer
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function verify($challenge_id, $user_answer)
    {
        if (empty($challenge_id) || !is_string($challenge_id)) {
            return [
                'valid' => false,
                'error' => 'Invalid challenge ID'
            ];
        }
        
        $captcha_data = $this->CI->session->userdata('captcha_data') ?: [];
        
        if (!isset($captcha_data[$challenge_id])) {
            return [
                'valid' => false,
                'error' => 'CAPTCHA expired or invalid'
            ];
        }
        
        $challenge = $captcha_data[$challenge_id];
        
        // Check if expired (10 minutes)
        if (time() - $challenge['created_at'] > 600) {
            unset($captcha_data[$challenge_id]);
            $this->CI->session->set_userdata('captcha_data', $captcha_data);
            
            return [
                'valid' => false,
                'error' => 'CAPTCHA expired'
            ];
        }
        
        // Increment attempt count
        $captcha_data[$challenge_id]['attempts']++;
        
        // Limit attempts to prevent brute force
        if ($captcha_data[$challenge_id]['attempts'] > 3) {
            unset($captcha_data[$challenge_id]);
            $this->CI->session->set_userdata('captcha_data', $captcha_data);
            
            log_message('warning', 'CAPTCHA brute force attempt detected from IP: ' . $this->get_client_ip());
            
            return [
                'valid' => false,
                'error' => 'Too many attempts. Please refresh and try again.'
            ];
        }
        
        // Verify answer
        if ((int)$user_answer === (int)$challenge['answer']) {
            // Success - remove the used CAPTCHA
            unset($captcha_data[$challenge_id]);
            $this->CI->session->set_userdata('captcha_data', $captcha_data);
            
            return [
                'valid' => true,
                'error' => null
            ];
        } else {
            // Wrong answer - update session with incremented attempts
            $this->CI->session->set_userdata('captcha_data', $captcha_data);
            
            return [
                'valid' => false,
                'error' => 'Incorrect answer'
            ];
        }
    }
    
    /**
     * Check if CAPTCHA is required based on security settings
     * 
     * @param string $identifier Usually IP address
     * @param string $action Action being performed
     * @return bool
     */
    public function is_required($identifier, $action = 'default')
    {
        // CAPTCHA is required for email checking to prevent enumeration
        if ($action === 'email_check') {
            return true;
        }
        
        // Check if there have been multiple failed attempts
        $cache_key = 'captcha_required:' . $action . ':' . $identifier;
        $this->CI->load->driver('cache', ['adapter' => 'file', 'backup' => 'file']);
        
        $failed_attempts = $this->CI->cache->get($cache_key) ?: 0;
        
        // Require CAPTCHA after 2 failed attempts
        return $failed_attempts >= 2;
    }
    
    /**
     * Mark a failed attempt for CAPTCHA requirement tracking
     */
    public function mark_failed_attempt($identifier, $action = 'default')
    {
        $cache_key = 'captcha_required:' . $action . ':' . $identifier;
        $this->CI->load->driver('cache', ['adapter' => 'file', 'backup' => 'file']);
        
        $failed_attempts = $this->CI->cache->get($cache_key) ?: 0;
        $failed_attempts++;
        
        // Store for 1 hour
        $this->CI->cache->save($cache_key, $failed_attempts, 3600);
        
        log_message('info', "Failed attempt #{$failed_attempts} for {$action} from {$identifier}");
    }
    
    /**
     * Reset failed attempts counter
     */
    public function reset_failed_attempts($identifier, $action = 'default')
    {
        $cache_key = 'captcha_required:' . $action . ':' . $identifier;
        $this->CI->load->driver('cache', ['adapter' => 'file', 'backup' => 'file']);
        
        return $this->CI->cache->delete($cache_key);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip()
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