<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 간단한 로그인 시뮬레이션 (테스트용)
     */
    public function login()
    {
        // 테스트용으로 간단히 사용자 ID 1로 로그인
        $user_data = [
            'id' => 1,
            'name' => '테스트사용자',
            'email' => 'test@example.com'
        ];
        
        $this->set_user_session(1, $user_data);
        
        // 이전 페이지로 돌아가거나 메인으로
        $redirect_url = $this->input->server('HTTP_REFERER') ?: base_url();
        redirect($redirect_url);
    }

    /**
     * 로그아웃
     */
    public function logout()
    {
        $this->clear_user_session();
        
        // 이전 페이지로 돌아가거나 메인으로
        $redirect_url = $this->input->server('HTTP_REFERER') ?: base_url();
        redirect($redirect_url);
    }
}