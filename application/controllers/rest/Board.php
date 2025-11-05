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

    public function index_get($id = null)
    {
        if ($id === null) {
            $boards = $this->board_m->get_all_with_articles_count();
            $rows = [];
            foreach ($boards as $board) {
                $rows[] = [
                    'id'            => $board->id,
                    'name'          => $board->name,
                    'description'   => $board->description,
                    'article_count' => $board->article_count,
                    'created_at'    => $board->created_at,
                ];
            }
            $this->response([
                'rows'  => $rows,
                'total' => $this->board_m->count(),
            ], 200);
        } else {
            $this->load->model('article_m');
            $articles = $this->article_m->fetchByBoardId($id);
            $board = $this->board_m->get($id);
            $total = $this->article_m->countByBoardId($id);

            $this->response([
                'total' => $total,
                'name'  => $board->name,
                'rows'  => $articles,
                'id'    => $id
            ], 200);
        }
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
            $id = (int)$id;

            if ($id <= 0) {
                $this->response(['message' => 'invalid id'], 400);
            }

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
            $id = (int)$id;

            if ($id <= 0) {
                $this->response(['message' => 'invalid id'], 400);
            }

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
}
