<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 관리자 대시보드
     *
     * @return void
     */
    public function index()
    {
        $data['total_users'] = $this->db->count_all('users');
        $data['total_boards'] = $this->db->count_all('boards');
        $data['total_articles'] = $this->db->count_all('articles');
        $data['total_comments'] = $this->db->count_all('comments');

        $this->load->view('admin/index_v', $data);
    }


    /**
     * 관리자 → 사용자 관리
     *
     * @return void
     */
    public function users()
    {
        $this->load->view('admin/users_v');
    }

    /**
     * Admin 전용 레이아웃 적용
     */
    public function _output($output)
    {
        // Admin 전용 header view에 debugbarRenderer 전달
        $header = $this->load->view('admin/header_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        // Admin 전용 sidebar
        $sidebar = $this->load->view('admin/sidebar_v', [], TRUE);

        // Admin 전용 footer view에 debugbarRenderer 전달
        $footer = $this->load->view('admin/footer_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        echo $header . $sidebar . $output . $footer;
    }
}