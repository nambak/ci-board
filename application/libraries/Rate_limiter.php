<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Rate Limiting Library
 * 
 * Provides IP-based rate limiting functionality to prevent abuse
 */
class Rate_limiter
{
    protected $CI;
    protected $cache_key_prefix = 'rate_limit:';
    protected $default_max_requests = 5;
    protected $default_time_window = 300; // 5 minutes
    
    public function __construct($config = [])
    {
        $CI = get_instance();
        $this->CI = $CI;
        $this->CI->load->driver('cache', ['adapter' => 'file', 'backup' => 'file']);
        
        // Override defaults with config
        if (isset($config['max_requests'])) {
            $this->default_max_requests = $config['max_requests'];
        }
        if (isset($config['time_window'])) {
            $this->default_time_window = $config['time_window'];
        }
    }
    
    /**
     * Check if current request is allowed based on rate limits
     * 
     * @param string $identifier Usually IP address or user ID
     * @param string $action Action being rate limited (e.g., 'email_check')
     * @param int $max_requests Maximum requests allowed
     * @param int $time_window Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public function is_allowed($identifier, $action = 'default', $max_requests = null, $time_window = null)
    {
        $max_requests = $max_requests ?: $this->default_max_requests;
        $time_window = $time_window ?: $this->default_time_window;
        
        $cache_key = $this->cache_key_prefix . $action . ':' . $identifier;
        $current_time = time();
        
        // Get current request data
        $request_data = $this->CI->cache->get($cache_key);
        
        if (!$request_data) {
            // First request
            $request_data = [
                'count' => 1,
                'window_start' => $current_time,
                'requests' => [$current_time]
            ];
            
            $this->CI->cache->save($cache_key, $request_data, $time_window);
            
            return [
                'allowed' => true,
                'remaining' => $max_requests - 1,
                'reset_time' => $current_time + $time_window
            ];
        }
        
        // Clean old requests (sliding window)
        $request_data['requests'] = array_filter($request_data['requests'], function($timestamp) use ($current_time, $time_window) {
            return ($current_time - $timestamp) < $time_window;
        });
        
        $current_count = count($request_data['requests']);
        
        if ($current_count >= $max_requests) {
            // Rate limit exceeded
            $oldest_request = min($request_data['requests']);
            $reset_time = $oldest_request + $time_window;
            
            // Log rate limit violation
            log_message('warning', "Rate limit exceeded for {$action} by {$identifier}. Count: {$current_count}/{$max_requests}");
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $reset_time
            ];
        }
        
        // Add current request
        $request_data['requests'][] = $current_time;
        $request_data['count'] = count($request_data['requests']);
        
        // Update cache
        $this->CI->cache->save($cache_key, $request_data, $time_window);
        
        return [
            'allowed' => true,
            'remaining' => $max_requests - $request_data['count'],
            'reset_time' => $current_time + $time_window
        ];
    }
    
    /**
     * Get current rate limit status without incrementing
     */
    public function get_status($identifier, $action = 'default', $max_requests = null, $time_window = null)
    {
        $max_requests = $max_requests ?: $this->default_max_requests;
        $time_window = $time_window ?: $this->default_time_window;
        
        $cache_key = $this->cache_key_prefix . $action . ':' . $identifier;
        $current_time = time();
        
        $request_data = $this->CI->cache->get($cache_key);
        
        if (!$request_data) {
            return [
                'allowed' => true,
                'remaining' => $max_requests,
                'reset_time' => $current_time + $time_window
            ];
        }
        
        // Clean old requests
        $request_data['requests'] = array_filter($request_data['requests'], function($timestamp) use ($current_time, $time_window) {
            return ($current_time - $timestamp) < $time_window;
        });
        
        $current_count = count($request_data['requests']);
        
        if ($current_count >= $max_requests) {
            $oldest_request = min($request_data['requests']);
            $reset_time = $oldest_request + $time_window;
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $reset_time
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $max_requests - $current_count,
            'reset_time' => $current_time + $time_window
        ];
    }
    
    /**
     * Reset rate limit for a specific identifier and action
     */
    public function reset($identifier, $action = 'default')
    {
        $cache_key = $this->cache_key_prefix . $action . ':' . $identifier;
        return $this->CI->cache->delete($cache_key);
    }
    
    /**
     * Get client IP address
     */
    public function get_client_ip()
    {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancers/proxies
            'HTTP_X_FORWARDED',          // Proxies
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxies
            'HTTP_FORWARDED',            // Proxies
            'HTTP_CLIENT_IP',            // Proxies
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}