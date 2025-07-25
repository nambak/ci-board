<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
        $this->load->library('form_validation');
    }

    /**
     * 로그인 페이지
     *
     * @return void
     */
    public function login()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board', 'refresh');
        }

        $this->load->view('auth/login_v');
    }

    /**
     * 회원가입 페이지 표시
     *
     * @return void
     */
    public function register()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board', 'refresh');
        }

        $this->load->view('auth/register_v');
    }


    /**
     * 로그아웃
     *
     * @return void
     */
    public function logout()
    {
         $this->session->sess_destroy();

         redirect('/login', 'refresh');

    }
}
