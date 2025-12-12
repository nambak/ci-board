<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Extended REST Controller with Rate Limiting
 *
 * 모든 REST API 요청에 대해 Rate Limiting을 적용하는 확장 컨트롤러
 */
class MY_RestController extends RestController
{
    const HTTP_TOO_MANY_REQUESTS = 429;

    public function __construct($config = 'rest')
    {
        parent::__construct($config);

        // Load Rate Limiting dependencies
        $this->load->config('rate_limit');
        $this->load->library('rate_limiter');
    }

    /**
     * Override early_checks to add Rate Limiting
     */
    protected function early_checks()
    {
        parent::early_checks();

        // Check if rate limiting is enabled
        if (!$this->config->item('rate_limit_enabled')) {
            return;
        }

        // Get client IP
        $ip_address = $this->rate_limiter->get_client_ip();

        // Check IP whitelist
        $whitelist = $this->config->item('rate_limit_whitelist') ?: [];
        if (in_array($ip_address, $whitelist, true)) {
            return;
        }

        // Check if user is admin (exempt from rate limiting)
        if ($this->_is_admin_user()) {
            return;
        }

        // Construct method-aware endpoint string: "METHOD /path"
        $http_method = strtoupper($this->input->method());
        $path = '/' . ltrim($this->uri->uri_string(), '/');
        $endpoint = $http_method . ' ' . $path;

        // Find matching rate limit rule
        $rule = $this->_find_rate_limit_rule($endpoint);

        // Determine base limits (from rule or default)
        if ($rule) {
            $max_requests = $rule['max_requests'];
            $time_window = $rule['time_window'];
        } else {
            // Use default limits
            $default = $this->config->item('rate_limit_default');
            $max_requests = $default['max_requests'];
            $time_window = $default['time_window'];
        }

        // Always apply multiplier for authenticated users
        // (regardless of whether using rule or default)
        if ($this->_is_authenticated_user()) {
            $multiplier = $this->config->item('rate_limit_authenticated_multiplier') ?: 1;
            $max_requests = (int)($max_requests * $multiplier);
        }

        // Get user ID if authenticated
        $user_id = $this->session->userdata('user_id');

        // Check rate limit using method-aware endpoint
        $result = $this->rate_limiter->is_allowed(
            $ip_address,
            $endpoint,
            $max_requests,
            $time_window,
            $user_id
        );

        // Add rate limit headers
        $this->_add_rate_limit_headers($result);

        // If not allowed, return 429 error
        if (!$result['allowed']) {
            $retry_after = max(0, $result['reset_time'] - time());

            $this->output->set_header('Retry-After: ' . max(1, $retry_after));

            $this->response([
                'success' => false,
                'message' => 'Too Many Requests. Please try again later.',
                'error' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retry_after,
                'limit' => $result['limit'],
                'reset_time' => $result['reset_time']
            ], self::HTTP_TOO_MANY_REQUESTS);
            return;
        }
    }

    /**
     * Find matching rate limit rule for METHOD + endpoint
     *
     * Implements two-pass lookup:
     * 1. First pass: Try exact matches - return immediately if found
     * 2. Second pass: Try wildcard matches - return first match
     *
     * Exact matches take precedence over wildcard matches
     *
     * @param string $endpoint Method-aware endpoint (e.g., "POST /rest/article/create")
     * @return array|null Rule array or null if no match
     */
    protected function _find_rate_limit_rule($endpoint)
    {
        $rate_limit_rules = $this->config->item('rate_limit_rules') ?: [];

        // PASS 1: Scan for exact matches
        // Return immediately if pattern exactly equals endpoint
        foreach ($rate_limit_rules as $pattern => $rule) {
            if ($pattern === $endpoint) {
                return $rule;
            }
        }

        // PASS 2: Scan for wildcard matches
        // Only check patterns containing '*'
        foreach ($rate_limit_rules as $pattern => $rule) {
            // Skip non-wildcard patterns
            if (strpos($pattern, '*') === false) {
                continue;
            }

            // Convert wildcard pattern to regex
            // 1. Use preg_quote to escape special regex characters
            // 2. The escaped '\*' becomes '\\\*'
            // 3. Replace '\\\*' with '.*' for regex wildcard matching
            $regex_pattern = preg_quote($pattern, '/');
            $regex_pattern = str_replace('\\*', '.*', $regex_pattern);

            // Case-insensitive match
            if (preg_match('/^' . $regex_pattern . '$/i', $endpoint)) {
                return $rule;
            }
        }

        // No match found
        return null;
    }

    /**
     * Check if current user is admin
     */
    protected function _is_admin_user()
    {
        if (!$this->session->has_userdata('role')) {
            return false;
        }

        $user_role = $this->session->userdata('role');
        $admin_roles = $this->config->item('rate_limit_admin_roles') ?: ['admin'];

        return in_array($user_role, $admin_roles);
    }

    /**
     * Check if user is authenticated
     */
    protected function _is_authenticated_user()
    {
        return $this->session->has_userdata('logged_in') && $this->session->userdata('logged_in') === true;
    }

    /**
     * Add Rate Limit headers to response
     */
    protected function _add_rate_limit_headers($result)
    {
        $headers = $this->config->item('rate_limit_headers') ?: [
            'limit' => 'X-RateLimit-Limit',
            'remaining' => 'X-RateLimit-Remaining',
            'reset' => 'X-RateLimit-Reset'
        ];

        $this->output->set_header($headers['limit'] . ': ' . $result['limit']);
        $this->output->set_header($headers['remaining'] . ': ' . $result['remaining']);
        $this->output->set_header($headers['reset'] . ': ' . $result['reset_time']);
    }
}
