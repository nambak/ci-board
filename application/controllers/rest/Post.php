<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Post extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('post_m');
    }

    // 게시판 게시글 상세 정보 조회
    public function detail_get()
    {
        $id = $this->get('id', true);
        $post = $this->post_m->get($id);

        if (!$post) {
            $this->response('post not found', 404);
        }

        $this->response($post, 200);
    }

    public function update_post()
    {
        try {
            $id = $this->input->get('id', true);
            $title = $this->input->post('title', true);
            $content = $this->input->post('content', true);

            $this->post_m->update($id, $title, $content);

            $this->response('success', 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage() , 500);
        }
    }

    public function index_delete($id)
    {
        try {
            $this->post_m->delete($id);
            $this->response('success', 200);

        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage(), 500);
        }
    }

    public function create_post()
    {
        try {
            $title = $this->input->post('title', true);
            $content = $this->input->post('content', true);
            $boardId = $this->input->post('board_id', true);

            $result = $this->post_m->store($boardId, $title, $content);
            $this->response(['id' => $result], 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage() , 500);
        }
    }
}