<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Comment extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('comment_m');
        $this->load->model('post_m');
    }

    public function index_get()
    {
        $comments = [
            'data' => []
        ];

        $this->response($comments, 200);
    }

    public function save_post()
    {
        $comment = $this->post('comment', true);
        $postId = $this->post('post_id', true);
        $post = $this->post_m->get($postId);

        if (!$post) {
            $this->response('post not found', 404);
        }

        try {
            $this->comment_m->create($postId, $comment);
            $this->response('success', 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage() , 500);
        }
    }
}