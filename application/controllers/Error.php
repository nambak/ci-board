<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Error extends MY_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper('url');
    }

    public function notFound()
    {
        // HTTP 상태 코드 404 설정
        $this->output->set_status_header('404');

        // 404 페이지 로드
        $this->load->view('errors/html/error_404');
    }
}
