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
