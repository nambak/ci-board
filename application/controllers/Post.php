<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function detail()
    {
        $queryParams['id'] = $this->input->get('id', true);

        $this->load->view('post/detail_v', $queryParams);
    }

    public function edit()
    {
        $queryParams['id'] = $this->input->get('id', true);

        $this->load->view('post/edit_v', $queryParams);
    }

    public function create()
    {
        $queryParams['board_id'] = $this->input->get('board_id', true);

        $this->load->view('post/create_v', $queryParams);
    }
}