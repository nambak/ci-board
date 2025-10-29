<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_m');
        $this->load->library('session');
        $this->load->library('ArticleService', null, 'article_service');
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
        $viewArticles = $this->session->userdata('viewed_articles');
        if ($this->article_service->incrementViewCountIfNotViewd($id, $viewArticles)) {
            $this->session->set_userdata('viewed_articles', $viewArticles);
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
