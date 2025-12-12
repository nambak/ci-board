<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Rate Limiting Library (DB-based)
 *
 * Provides IP-based rate limiting functionality to prevent abuse
 * Uses database for persistence and tracking
 */
class Rate_limiter
{
    protected $CI;
    protected $default_max_requests = 100;
    protected $default_time_window = 60; // seconds
    protected $enabled = true;

    public function __construct($config = [])
    {
        $this->CI =& get_instance();
        $this->CI->load->database();

        // Override defaults with config
        if (isset($config['max_requests'])) {
            $this->default_max_requests = $config['max_requests'];
        }
        if (isset($config['time_window'])) {
            $this->default_time_window = $config['time_window'];
        }
        if (isset($config['enabled'])) {
            $this->enabled = $config['enabled'];
        }
    }

    /**
     * Check if current request is allowed based on rate limits
     *
     * @param string $ip_address IP address
     * @param string $endpoint API endpoint
     * @param int $max_requests Maximum requests allowed
     * @param int $time_window Time window in seconds
     * @param int|null $user_id User ID (for authenticated requests)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => timestamp, 'limit' => int]
     */
    public function is_allowed($ip_address, $endpoint, $max_requests = null, $time_window = null, $user_id = null)
    {
        if (!$this->enabled) {
            return [
                'allowed'    => true,
                'remaining'  => 999,
                'reset_time' => time() + 60,
                'limit'      => 999
            ];
        }

        $max_requests = $max_requests ?: $this->default_max_requests;
        $time_window = $time_window ?: $this->default_time_window;

        $current_time = date('Y-m-d H:i:s');
        $window_start = date('Y-m-d H:i:s', strtotime("-{$time_window} seconds"));

        // Clean up expired entries first
        $this->cleanup_expired_entries();

        // Get current rate limit record
        $rate_limit = $this->CI->db
            ->where('ip_address', $ip_address)
            ->where('endpoint', $endpoint)
            ->where('expires_at >', $current_time)
            ->order_by('id', 'DESC')
            ->get('rate_limits')
            ->row();

        if (!$rate_limit) {
            // First request - create new record
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$time_window} seconds"));
            $this->CI->db->insert('rate_limits', [
                'ip_address'    => $ip_address,
                'endpoint'      => $endpoint,
                'request_count' => 1,
                'window_start'  => $current_time,
                'expires_at'    => $expires_at,
                'created_at'    => $current_time
            ]);

            return [
                'allowed'    => true,
                'remaining'  => $max_requests - 1,
                'reset_time' => strtotime($expires_at),
                'limit'      => $max_requests
            ];
        }

        // Check if within time window
        if (strtotime($rate_limit->window_start) < strtotime($window_start)) {
            // Window expired, reset counter
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$time_window} seconds"));
            $this->CI->db
                ->where('id', $rate_limit->id)
                ->update('rate_limits', [
                    'request_count' => 1,
                    'window_start'  => $current_time,
                    'expires_at'    => $expires_at,
                    'updated_at'    => $current_time
                ]);

            return [
                'allowed'    => true,
                'remaining'  => $max_requests - 1,
                'reset_time' => strtotime($expires_at),
                'limit'      => $max_requests
            ];
        }

        // Check if limit exceeded
        if ($rate_limit->request_count >= $max_requests) {
            // Log the violation
            $this->log_violation($ip_address, $endpoint, $rate_limit->request_count, $max_requests, $user_id);
            $message = "Rate limit exceeded for {$endpoint} by {$ip_address}. Count: {$rate_limit->request_count}/{$max_requests}";
            $level = $this->CI->config->item('rate_limit_log_level') ?: 'error';
            log_message($level, $message);

            return [
                'allowed'    => false,
                'remaining'  => 0,
                'reset_time' => strtotime($rate_limit->expires_at),
                'limit'      => $max_requests
            ];
        }

        // Increment counter atomically (avoid race)
        $this->CI->db
            ->where('id', $rate_limit->id)
            ->where('request_count <', $max_requests)
            ->set('request_count', 'request_count + 1', FALSE)
            ->set('updated_at', $current_time)
            ->update('rate_limits');

        if ($this->CI->db->affected_rows() === 0) {
            $this->log_violation($ip_address, $endpoint, $rate_limit->request_count, $max_requests, $user_id);
            return [
                'allowed'    => false,
                'remaining'  => 0,
                'reset_time' => strtotime($rate_limit->expires_at),
                'limit'      => $max_requests
            ];
        }

        $new_count = $rate_limit->request_count + 1;

        return [
            'allowed'    => true,
            'remaining'  => $max_requests - $new_count,
            'reset_time' => strtotime($rate_limit->expires_at),
            'limit'      => $max_requests
        ];
    }

    /**
     * Get current rate limit status without incrementing
     *
     * @param string $ip_address IP address
     * @param string $endpoint API endpoint
     * @param int $max_requests Maximum requests allowed
     * @param int $time_window Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => timestamp, 'limit' => int, 'current' => int]
     */
    public function get_status($ip_address, $endpoint, $max_requests = null, $time_window = null)
    {
        if (!$this->enabled) {
            return [
                'allowed'    => true,
                'remaining'  => 999,
                'reset_time' => time() + 60,
                'limit'      => 999,
                'current'    => 0
            ];
        }

        $max_requests = $max_requests ?: $this->default_max_requests;
        $time_window = $time_window ?: $this->default_time_window;

        $current_time = date('Y-m-d H:i:s');
        $window_start = date('Y-m-d H:i:s', strtotime("-{$time_window} seconds"));

        $rate_limit = $this->CI->db
            ->where('ip_address', $ip_address)
            ->where('endpoint', $endpoint)
            ->where('expires_at >', $current_time)
            ->order_by('id', 'DESC')
            ->get('rate_limits')
            ->row();

        if (!$rate_limit) {
            return [
                'allowed'    => true,
                'remaining'  => $max_requests,
                'reset_time' => strtotime("+{$time_window} seconds"),
                'limit'      => $max_requests,
                'current'    => 0
            ];
        }

        // Check if window expired
        if (strtotime($rate_limit->window_start) < strtotime($window_start)) {
            return [
                'allowed'    => true,
                'remaining'  => $max_requests,
                'reset_time' => strtotime("+{$time_window} seconds"),
                'limit'      => $max_requests,
                'current'    => 0
            ];
        }

        $allowed = $rate_limit->request_count < $max_requests;
        $remaining = max(0, $max_requests - $rate_limit->request_count);

        return [
            'allowed'    => $allowed,
            'remaining'  => $remaining,
            'reset_time' => strtotime($rate_limit->expires_at),
            'limit'      => $max_requests,
            'current'    => (int)$rate_limit->request_count
        ];
    }

    /**
     * Reset rate limit for a specific IP and endpoint
     */
    public function reset($ip_address, $endpoint = null)
    {
        $this->CI->db->where('ip_address', $ip_address);

        if ($endpoint !== null) {
            $this->CI->db->where('endpoint', $endpoint);
        }

        return $this->CI->db->delete('rate_limits');
    }

    /**
     * Clean up expired rate limit entries
     */
    protected function cleanup_expired_entries()
    {
        // Only run cleanup occasionally (10% chance) to reduce DB load
        if (rand(1, 10) !== 1) {
            return;
        }

        $cutoff_time = date('Y-m-d H:i:s', strtotime('-1 hour'));

        // Delete old expired entries
        $this->CI->db
            ->where('expires_at <', $cutoff_time)
            ->delete('rate_limits');
    }

    /**
     * Log rate limit violation to database
     */
    protected function log_violation($ip_address, $endpoint, $request_count, $limit_value, $user_id = null)
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $this->CI->db->insert('rate_limit_logs', [
            'ip_address'    => $ip_address,
            'endpoint'      => $endpoint,
            'user_id'       => $user_id,
            'request_count' => $request_count,
            'limit_value'   => $limit_value,
            'user_agent'    => $user_agent,
            'created_at'    => date('Y-m-d H:i:s')
        ]);
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