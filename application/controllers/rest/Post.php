<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Post extends RestController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function detail_get()
    {
        $this->load->model('post_m');
        $id = $this->get('id', true);
        $post = $this->post_m->get($id);

        if (!$post) {
            $this->response('post not found', 404);
        }

        $this->response($post, 200);
    }
}