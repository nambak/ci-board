<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_m');
        $this->load->library('session');
    }

    /**
     * 게시글 상세 정보를 표시합니다.
     *
     * URI 세그먼트에서 게시글 ID를 받아옵니다 (예: /article/1)
     *
     * @param int $id 게시글 ID
     */
    public function index($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            show_404();
            return;
        }

        $currentArticle = $this->article_m->get($id);

        if (!$currentArticle) {
            show_404();
            return;
        }

        // 세션 기반 조회수 증가 처리
        $viewedArticles = $this->session->userdata('viewed_articles');
        if (!is_array($viewedArticles)) {
            $viewedArticles = [];
        }

        // 해당 게시글을 이번 세션에서 본 적이 없으면 조회수 증가
        if (!in_array($id, $viewedArticles)) {
            $this->article_m->incrementViewCount($id);
            $viewedArticles[] = $id;
            $this->session->set_userdata('viewed_articles', $viewedArticles);
        }

        $currentBoardId = $currentArticle->board_id;
        $prevArticle = $this->article_m->getPrevious($currentBoardId, $id);
        $nextArticle = $this->article_m->getNext($currentBoardId, $id);

        $data = [
            'currentArticle' => $currentArticle,
            'prevArticleId'  => $prevArticle ? $prevArticle->id : null,
            'nextArticleId'  => $nextArticle ? $nextArticle->id : null,
            'user_id'     => $this->session->userdata('user_id'),
        ];

        $this->load->view('article/detail_v', $data);
    }

    public function edit($id = null)
    {
        if ($id === null) {
            show_404();
            return;
        }

        $currentArticle = $this->article_m->get($id);

        if (!$currentArticle) {
            show_404();
            return;
        }

        $this->load->view('article/edit_v', compact('id'));
    }

    /**
     * 게시글 생성 화면을 출력합니다.
     *
     * GET 요청에서 'board_id' 파라미터를 받아와 게시글 생성 뷰에 전달합니다.
     */
    public function create()
    {
        $queryParams = [
            'board_id' => $this->input->get('board_id', true),
            'user_id'  => $this->session->userdata('user_id'),
        ];

        $this->load->view('article/create_v', $queryParams);
    }
}
