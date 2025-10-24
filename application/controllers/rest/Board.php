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

    public function detail_get()
    {
        $this->load->model('article_m');
        $id = $this->get('id', true);
        $posts = $this->article_m->fetchByBoardId($id);
        $board = $this->board_m->get($id);

        $this->response([
            'name' => $board[0]->name,
            'rows' => $posts,
            'id' => $id
        ], 200);
    }
}
