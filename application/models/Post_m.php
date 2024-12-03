<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_m extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function fetchByBoardId($boardId)
    {
        $query = $this->db->get_where('posts', ['board_id' => $boardId]);

        return $query->result();
    }

    public function get($id)
    {
        $query = $this->db->get_where('posts', ['id' => $id]);

        return $query->row();
    }
}