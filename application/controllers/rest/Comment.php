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
        $comments = [
            'data' => $this->comment_m->fetchByPost($this->get('post_id', true)),
        ];

        $this->response($comments, 200);
    }

    /**
     * 새로운 댓글을 저장하는 REST API 엔드포인트
     */
    public function save_post()
    {
        $comment = $this->post('comment', true);
        $postId = $this->post('post_id', true);
        $writerId = $this->post('writer_id', true);

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
