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

    public function detail_get()
    {
        $id = $this->get('id', true);
        $post = $this->post_m->get($id);

        if (!$post) {
            $this->response('post not found', 404);
        }

        $this->response($post, 200);
    }

    public function update_post()
    {
        $id = $this->input->get('id', true);
        $title = $this->input->post('title', true);
        $content = $this->input->post('content', true);

        $result = $this->post_m->update($id, $title, $content);

        if ($result) {
            $this->response('success', 200);
        } else {
            $this->response('database failed: ' . $result , 500);
        }

    }
}