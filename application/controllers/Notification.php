<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Notification 웹 컨트롤러
 * 알림 전체 목록 페이지 제공
 */
class Notification extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('auth');

        // 로그인 체크
        if (!is_logged_in()) {
            redirect('login');
        }
    }

    /**
     * 알림 목록 페이지
     */
    public function index()
    {
        $data = [
            'title' => '알림'
        ];

        $this->load->view('notification/index_v', $data);
    }
}
