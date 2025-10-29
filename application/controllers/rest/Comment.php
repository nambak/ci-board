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

        if (!ctype_digit((string)$articleId) || (int)$articleId <= 0) {
            $this->response(['message' => 'invalid article_id'], 400);
        }

        if (!is_string($comment) || trim($comment) === '') {
            $this->response(['message' => 'comment required'], 400);
        }

        if (!$writerId) {
            $this->response(['message' => 'unauthorized'], 401);
        }

        $article = $this->article_m->get($articleId);

        if (!$article) {
            $this->response('article not found', 404);
        }

        try {
            $this->comment_m->create($articleId, $comment, $writerId);
            $this->response('success', 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 댓글을 수정하는 REST API 엔드포인트
     */
    public function index_put($id)
    {
        $currentUserId = get_user_id();

        if (!$currentUserId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->response(['message' => 'invalid id'], 400);
            return;
        }

        $comment = $this->put('comment', true);

        if (!is_string($comment) || trim($comment) === '') {
            $this->response(['message' => 'comment required'], 400);
            return;
        }

        // 댓글 조회
        $existingComment = $this->comment_m->get($id);

        if (!$existingComment) {
            $this->response(['message' => 'comment not found'], 404);
            return;
        }

        // 작성자 확인
        if ($existingComment->writer_id != $currentUserId) {
            $this->response(['message' => 'forbidden'], 403);
            return;
        }

        try {
            $result = $this->comment_m->update($id, $comment);
            if ($result) {
                $this->response(['message' => 'success'], 200);
            } else {
                $this->response(['message' => 'update failed'], 500);
            }
        } catch (Exception $e) {
            log_message('error', 'Comment update error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 댓글을 삭제하는 REST API 엔드포인트
     */
    public function index_delete($id)
    {
        $currentUserId = get_user_id();

        if (!$currentUserId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->response(['message' => 'invalid id'], 400);
            return;
        }

        // 댓글 조회
        $existingComment = $this->comment_m->get($id);

        if (!$existingComment) {
            $this->response(['message' => 'comment not found'], 404);
            return;
        }

        // 작성자 확인
        if ($existingComment->writer_id != $currentUserId) {
            $this->response(['message' => 'forbidden'], 403);
            return;
        }

        try {
            $result = $this->comment_m->delete($id);
            if ($result) {
                $this->response(['message' => 'success'], 200);
            } else {
                $this->response(['message' => 'delete failed'], 500);
            }

        } catch (Exception $e) {
            log_message('error', 'Comment delete error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }
}
