<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Article extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_m');;
    }

    public function index_get($id)
    {
        try {
            $article = $this->article_m->get($id);

            if (!$article) {
                $this->response('not found', 404);
            } else {
                $this->response($article, 200);
            }
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage(), 500);
        }
    }

    public function update_post()
    {
        try {
            $id = $this->input->get('id', true);
            $title = $this->input->post('title', true);
            $content = $this->input->post('content', true);

            $this->article_m->update($id, $title, $content);

            $this->response('success', 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage() , 500);
        }
    }

    public function index_delete($id)
    {
        try {
            $this->article_m->delete($id);
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

            $result = $this->article_m->store($boardId, $title, $content);
            $this->response(['id' => $result], 200);
        } catch (Exception $e) {
            $this->response('server error: ' . $e->getMessage() , 500);
        }
    }
}
