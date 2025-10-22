<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Board extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('board_m');
    }

    public function index_get()
    {
        $this->response([
            'rows' => $this->board_m->get()
        ], 200);
    }

    public function index_post()
    {
        try {
            $name = $this->post('name', true);
            $description = $this->post('description', true);

            if (!$name) {
                $this->response(['message' => 'name required'], 400);
                return;
            }

            $id = $this->board_m->create($name, $description);
            $this->response(['id' => $id], 201);
        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }

    public function detail_get()
    {
        $this->load->model('post_m');
        $id = $this->get('id', true);
        $posts = $this->post_m->fetchByBoardId($id);
        $board = $this->board_m->get($id);

        $this->response([
            'name' => $board[0]->name,
            'rows' => $posts,
            'id' => $id
        ], 200);
    }
}
