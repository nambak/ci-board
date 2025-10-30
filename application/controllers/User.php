<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
    }

    /**
     * 본인 프로필 페이지
     * /profile
     */
    public function profile()
    {
        // 로그인 확인
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        $user_id = $this->session->userdata('user_id');

        $data = [
            'user_id' => $user_id
        ];

        $this->load->view('profile/index_v', $data);
    }

    /**
     * 다른 사용자 프로필 페이지
     * /user/{id}
     */
    public function index($id)
    {
        // ID 유효성 검사
        if (!$id || !ctype_digit((string)$id) || (int)$id <= 0) {
            show_404();
            return;
        }

        $data = [
            'user_id' => $id
        ];

        $this->load->view('profile/index_v', $data);
    }
}