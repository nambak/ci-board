<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Post_m extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function fetchByBoardId($boardId)
    {
        $this->db->select('posts.id as post_id, title, users.name as writer, users.id as writer_id, views, posts.created_at as created_at');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->where('posts.board_id', $boardId);
        $this->db->order_by('posts.created_at', 'DESC');
        $query = $this->db->get();

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

    public function update($id, $title, $content)
    {
        $this->db->where('id', $id);

        return $this->db->update('posts', ['title' => $title, 'content' => $content]);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);

        return $this->db->delete('posts');
    }

    public function store($boardId, $title, $content)
    {
        $this->db->insert('posts', [
            'board_id' => $boardId,
            'user_id'  => 1,
            'title'    => $title,
            'content'  => $content
        ]);

        return $this->db->insert_id();
    }
}