<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Comment extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('comment_m');
        $this->load->model('article_m');
    }

    /**
     * 지정된 게시글의 댓글 목록을 조회하여 반환합니다.
     */
    public function index_get()
    {
        $result = $this->comment_m->fetchByPost($this->get('article_id', true));
        $current_user_id = get_user_id();

        $data = array_map(function ($comment) use ($current_user_id) {
            return [
                'id'         => $comment->id,
                'name'       => $this->security->xss_clean($comment->name),
                'comment'    => $this->security->xss_clean($comment->comment),
                'created_at' => $this->security->xss_clean($comment->created_at),
                'can_edit'   => $current_user_id && $comment->writer_id == $current_user_id
            ];
        }, $result);

        $this->response(compact('data'), 200);
    }

    /**
     * 새로운 댓글을 저장하는 REST API 엔드포인트
     */
    public function save_post()
    {
        $articleId = $this->post('article_id', true);
        $comment = $this->post('comment', true);
        $writerId = get_user_id(); // 현재 로그인된 사용자 ID 사용

        if (!$articleId || !trim($comment) || !$writerId) {
            $this->response('invalid request', 400);
        }

        $post = $this->article_m->get($articleId);

        if (!$post) {
            $this->response('post not found', 404);
        }

        try {
            $this->comment_m->create($articleId, $comment, $writerId);
            $this->response('success', 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage(), 500);
        }
    }
}
