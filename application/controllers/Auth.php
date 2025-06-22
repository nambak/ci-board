<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->library('form_validation');
    }

    /**
     * 로그인 페이지를 표시한다.
     *
     * 사용자가 이미 로그인된 경우 메인 게시판 페이지로 리다이렉트한다. 로그인되어 있지 않으면 로그인 뷰를 로드한다.
     */
    public function login()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board?id=1');
            return;
        }

        $data = [];
        $this->load->view('auth/login_v', $data);
    }

    /**
     * 회원가입 페이지를 표시한다.
     *
     * 이미 로그인된 사용자는 메인 게시판 페이지로 리다이렉트된다.
     */
    public function register()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board?id=1');
            return;
        }

        $this->load->view('auth/register_v');
    }
}
