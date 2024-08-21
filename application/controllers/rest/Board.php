<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Board extends RestController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_get($id)
    {
        $this->load->model('board_m');

        if (!$this->board_m->exists($id)) {
            $this->response([
                'status'  => false,
                'message' => 'No board  were found'
            ], 404);
        }

        $this->response([
            'board_id' => $id,
            'data'     => $this->board_m->get()
        ], 200);
    }
}
