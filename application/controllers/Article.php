<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_m');

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
        if ($id === null) {
            show_404();
            return;
        }

        $currentPost = $this->article_m->get($id);

        if (!$currentPost) {
            show_404();
            return;
        }

        $currentBoardId = $currentPost->board_id;
        $prevPost = $this->article_m->getPrevious($currentBoardId, $id);
        $nextPost = $this->article_m->getNext($currentBoardId, $id);

        $data = [
            'currentPost' => $currentPost,
            'prevPostId'  => $prevPost ? $prevPost->id : null,
            'nextPostId'  => $nextPost ? $nextPost->id : null,
        ];

        $this->load->view('article/detail_v', $data);
    }


    public function edit()
    {
        $queryParams['id'] = $this->input->get('id', true);

        $this->load->view('article/edit_v', $queryParams);
    }

    /**
     * 게시글 생성 화면을 출력합니다.
     *
     * GET 요청에서 'board_id' 파라미터를 받아와 게시글 생성 뷰에 전달합니다.
     */
    public function create()
    {
        $queryParams['board_id'] = $this->input->get('board_id', true);

        $this->load->view('article/create_v', $queryParams);
    }
}
