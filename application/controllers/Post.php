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

    /**
     * 게시글 생성 화면을 출력합니다.
     *
     * GET 요청에서 'board_id' 파라미터를 받아와 게시글 생성 뷰에 전달합니다.
     */
    public function create()
    {
        $queryParams['board_id'] = $this->input->get('board_id', true);

        $this->load->view('post/create_v', $queryParams);
    }
}
