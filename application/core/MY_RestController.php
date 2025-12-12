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
        if (in_array($ip_address, $whitelist)) {
            return;
        }

        // Check if user is admin (exempt from rate limiting)
        if ($this->_is_admin_user()) {
            return;
        }

        // Get current endpoint
        $endpoint = '/' . ltrim($this->uri->uri_string(), '/');

        // Find matching rate limit rule
        $rule = $this->_find_rate_limit_rule($endpoint);

        if (!$rule) {
            // Use default limits
            $default = $this->config->item('rate_limit_default');
            $max_requests = $default['max_requests'];
            $time_window = $default['time_window'];
        } else {
            $max_requests = $rule['max_requests'];
            $time_window = $rule['time_window'];

            // Apply multiplier for authenticated users
            if ($this->_is_authenticated_user()) {
                $multiplier = $this->config->item('rate_limit_authenticated_multiplier') ?: 1;
                $max_requests = $max_requests * $multiplier;
            }
        }

        // Get user ID if authenticated
        $user_id = $this->session->userdata('user_id');

        // Check rate limit
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
            $retry_after = $result['reset_time'] - time();

            $this->output->set_header('Retry-After: ' . max(1, $retry_after));

            $this->response([
                'success' => false,
                'message' => 'Too Many Requests. Please try again later.',
                'error' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retry_after,
                'limit' => $result['limit'],
                'reset_time' => $result['reset_time']
            ], self::HTTP_TOO_MANY_REQUESTS);
        }
    }

    /**
     * Find matching rate limit rule for endpoint
     */
    protected function _find_rate_limit_rule($endpoint)
    {
        $rate_limit_rules = $this->config->item('rate_limit_rules') ?: [];

        foreach ($rate_limit_rules as $pattern => $rule) {
            // Exact match
            if ($pattern === $endpoint) {
                return $rule;
            }

            // Wildcard match (e.g., /rest/board*)
            if (strpos($pattern, '*') !== false) {
                $regex_pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
                if (preg_match('/^' . $regex_pattern . '$/i', $endpoint)) {
                    return $rule;
                }
            }
        }

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
