<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Article extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_m');
        $this->load->library('session');
    }

    public function index_get($id)
    {
        try {
            if ($id === null || (int)$id < 1) {
                $this->response(['message' => 'invalid id'], 400);
            }

            $article = $this->article_m->get($id);

            if (!$article) {
                $this->response(['message' => 'not found'], 404);
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

            $this->response($article, 200);
        } catch (Exception $e) {
            log_message('error', 'Article::index_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    public function index_put($id)
    {
        if (!$this->session->userdata('user_id')) {
            $this->response(['message' => 'unauthorized'], 401);
        }

        try {
            $title = $this->put('title', true);
            $content = $this->put('content', true);

            // 게시글 존재 확인
            $post = $this->article_m->get($id);

            if (!$post) {
                $this->response(['message' => 'post not found'], 404);
            }

            if (!$title) {
                $this->response(['message' => 'title required'], 400);
            }

            if (!$content) {
                $this->response(['message' => 'content required'], 400);
            }

            $this->article_m->update($id, $title, $content);

            $this->response(['message' => 'success'], 200);
        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }

    public function index_delete($id)
    {
        try {
            $this->article_m->delete($id);
            $this->response('success', 200);

        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }

    public function create_post()
    {
        try {
            $title = $this->input->post('title', true);
            $content = $this->input->post('content', true);
            $boardId = $this->input->post('board_id', true);
            $userId = get_user_id();

            if (!$userId) {
                $this->response(['message' => 'unauthorized'], 401);
            }

            if ((int)$boardId <= 0) {
                $this->response(['message' => 'invalid board_id'], 400);
            }

            if (!$title) {
                $this->response(['message' => 'title required'], 400);
            }

            if (!$content) {
                $this->response(['message' => 'content required'], 400);
            }

            $result = $this->article_m->store($boardId, $userId, $title, $content);
            $this->response(['id' => $result], 200);
        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }
}
