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
        $this->db->select('*');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->where('posts.id', $id);
        $query = $this->db->get();

        return $query->row();
    }
}