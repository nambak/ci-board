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
        $this->output->set_status_header(404);
        $this->load->view('errors/html/error_404');
    }
}
