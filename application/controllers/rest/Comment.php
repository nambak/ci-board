<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Comment extends RestController
{
    /****
     * Comment 컨트롤러를 초기화하고 댓글 및 게시글 모델을 로드합니다.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('comment_m');
        $this->load->model('post_m');
    }

    /**
     * 지정된 게시글의 댓글 목록을 조회하여 반환합니다.
     *
     * GET 요청의 'post_id' 파라미터에 해당하는 게시글의 모든 댓글을 JSON 형식으로 응답합니다.
     */
    public function index_get()
    {
        $comments = [
            'data' => $this->comment_m->fetchByPost($this->get('post_id', true)),
        ];

        $this->response($comments, 200);
    }

    /**
     * 새로운 댓글을 저장하는 REST API 엔드포인트입니다.
     *
     * POST 데이터에서 댓글 내용, 게시글 ID, 작성자 ID를 받아 해당 게시글이 존재하는지 확인한 후, 댓글을 생성합니다.
     * 게시글이 존재하지 않으면 404 상태 코드와 함께 'post not found' 메시지를 반환합니다.
     * 댓글 생성에 성공하면 200 상태 코드와 'success' 메시지를 반환하며, 오류 발생 시 500 상태 코드와 에러 메시지를 반환합니다.
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
