<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Comment extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('comment_m');
        $this->load->model('post_m');
    }

    /**
     * 지정된 게시글의 댓글 목록을 조회하여 반환합니다.
     */
    public function index_get()
    {
        $result = $this->comment_m->fetchByPost($this->get('post_id', true));

        $data = array_map(function ($comment) {
            return [
                'name'       => $this->security->xss_clean($comment->name),
                'comment'    => $this->security->xss_clean($comment->comment),
                'created_at' => $this->security->xss_clean($comment->created_at)
            ];
        }, $result);

        $this->response(compact('data'), 200);
    }

    /**
     * 새로운 댓글을 저장하는 REST API 엔드포인트
     */
    public function save_post()
    {
        $postId = $this->post('post_id', true);
        $comment = $this->post('comment', true);
        $writerId = $this->post('writer_id', true);

        if (!$postId || !trim($comment) || !$writerId) {
            $this->response('invalid request', 400);
        }

        $post = $this->post_m->get($postId);

        if (!$post) {
            $this->response('post not found', 404);
        }

        try {
            $this->comment_m->create($postId, $comment, $writerId);
            $this->response('success', 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage(), 500);
        }
    }
}
