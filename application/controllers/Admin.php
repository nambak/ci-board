<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('admin/index_v');
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