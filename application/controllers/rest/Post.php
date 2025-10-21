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

    public function index_put($id)
    {
        try {
            $title = $this->put('title', true);
            $content = $this->put('content', true);

            // 게시글 존재 확인
            $post = $this->post_m->get($id);

            if (!$post) {
                $this->response('post not found', 404);
            }

            if (!$title) {
                $this->response('title required', 400);
            }

            if (!$content) {
                $this->response('content required', 400);
            }

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
//        $this->load->library('validation/post_validation');

        try {
//            if ($this->post_validation->validate() === false) {
//                $this->response([
//                    'message' => $this->post_validation->get_errors()
//                ], 400);
//                return;
//            }

            $title = $this->input->post('title', true);
            $content = $this->input->post('content', true);
            $boardId = $this->input->post('board_id', true);
            $userId = $this->input->post('user_id', true);

            $result = $this->post_m->store($boardId, $userId, $title, $content);
            $this->response(['id' => $result], 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage() , 500);
        }
    }

    public function index_get($id)
    {
        $post = $this->post_m->get($id);

        $this->response($post, 200);
    }
}
