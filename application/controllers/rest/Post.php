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

    /**
     * 게시글의 상세 정보와 이전/다음 게시글 ID를 조회하여 반환합니다.
     *
     * GET 요청에서 전달된 게시글 ID로 해당 게시글의 상세 정보를 조회하며,
     * 이전 및 다음 게시글이 존재할 경우 각각의 ID를 함께 반환합니다.
     * 게시글이 존재하지 않으면 404 에러를 반환합니다.
     *
     * @return void
     */
    public function detail_get()
    {
        $id = $this->get('id', true);
        $post = $this->post_m->get($id);

        if (!$post) {
            $this->response('post not found', 404);
        }

        $prevPost = $this->post_m->get_previous($id);
        $nextPost = $this->post_m->get_next($id);

        $post->prev_id = $prevPost ? $prevPost->id : null;
        $post->next_id = $nextPost ? $nextPost->id : null;

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