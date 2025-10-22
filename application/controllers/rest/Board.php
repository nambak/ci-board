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
        if (!$this->session->userdata('user_id')) {
            $this->response(['message' => 'unauthorized'], 401);
        }

        try {
            $name = $this->post('name', true);
            $description = $this->post('description', true);

            if (!$name) {
                $this->response(['message' => 'name required'], 400);
            }

            $id = $this->board_m->create($name, $description);
            $this->response(['id' => $id], 201);
        } catch (Exception $e) {
            log_message('error', 'board.index_post: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    public function index_put($id)
    {
        if (!$this->session->userdata('user_id')) {
            $this->response(['message' => 'unauthorized'], 401);
        }

        try {
            $name = $this->put('name', true);
            $description = $this->put('description', true);

            if (!$name) {
                $this->response(['message' => 'name required'], 400);
                return;
            }

            // 게시판 존재 확인
            if (!$this->board_m->exists($id)) {
                $this->response(['message' => 'board not found'], 404);
                return;
            }

            $this->board_m->update($id, $name, $description);
            $this->response(['message' => 'success'], 200);
        } catch (Exception $e) {
            log_message('error', 'board.index_post: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    public function index_delete($id)
    {
        if (!$this->session->userdata('user_id')) {
            $this->response(['message' => 'unauthorized'], 401);
        }

        try {
            // 게시판 존재 확인
            if (!$this->board_m->exists($id)) {
                $this->response(['message' => 'board not found'], 404);
                return;
            }

            $this->board_m->delete($id);
            $this->response(['message' => 'success'], 200);
        } catch (Exception $e) {
            log_message('error', 'board.index_post: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
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
            'id'   => $id
        ], 200);
    }
}
