<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Swagger extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->helper('url');
        $this->load->view('swagger');
    }
}