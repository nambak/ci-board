<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Board extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
         $this->load->view('board/list_v');
    }
}
