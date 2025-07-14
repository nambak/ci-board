<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use DebugBar\StandardDebugBar;

class MY_Controller extends CI_Controller {
    protected $debugbar;
    protected $debugbarRenderer;

    public function __construct() {
        parent::__construct();
        $this->debugbar = new StandardDebugBar();
        $this->debugbarRenderer = $this->debugbar->getJavascriptRenderer();
        
        // Start session for authentication
        $this->load->library('session');
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    protected function is_logged_in() {
        return $this->session->userdata('user_id') !== null;
    }

    /**
     * Get current logged in user ID
     * @return int|null
     */
    protected function get_user_id() {
        return $this->session->userdata('user_id');
    }

    /**
     * Get current logged in user data
     * @return array|null
     */
    protected function get_user_data() {
        return $this->session->userdata('user_data');
    }

    /**
     * Check if current user is the author of a post
     * @param int $post_user_id
     * @return bool
     */
    protected function is_post_author($post_user_id) {
        return $this->is_logged_in() && $this->get_user_id() == $post_user_id;
    }

    /**
     * Set user session for login
     * @param int $user_id
     * @param array $user_data
     */
    protected function set_user_session($user_id, $user_data) {
        $this->session->set_userdata([
            'user_id' => $user_id,
            'user_data' => $user_data
        ]);
    }

    /**
     * Clear user session for logout
     */
    protected function clear_user_session() {
        $this->session->unset_userdata(['user_id', 'user_data']);
    }

    public function _output($output) {
        // header view에 debugbarRenderer와 인증 정보 전달
        $header = $this->load->view('header_v', [
            'debugbarRenderer' => $this->debugbarRenderer,
            'is_logged_in' => $this->is_logged_in(),
            'user_data' => $this->get_user_data()
        ], TRUE);

        // footer view에 debugbarRenderer 전달
        $footer = $this->load->view('footer_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        echo $header . $output . $footer;
    }
}
