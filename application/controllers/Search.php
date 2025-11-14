<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('board_m');
    }

    /**
     * 검색 페이지
     */
    public function index()
    {
        // 게시판 목록 조회 (드롭다운용)
        $data['boards'] = $this->board_m->get_all_with_articles_count();

        // 검색 파라미터 받기
        $data['query'] = $this->input->get('query', true) ?: '';
        $data['type'] = $this->input->get('type', true) ?: 'all';
        $data['board_id'] = $this->input->get('board_id', true) ?: '';

        $this->load->view('search/index_v', $data);
    }
}