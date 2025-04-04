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

    /**
     * 이전 게시물 가져오기
     *
     * @param $id
     * @return mixed
     */
    public function get_previous($id)
    {
        $this->db->select('*');
        $this->db->from('posts');
        $this->db->where('id <', $id);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }


    /**
     * 다음 게시물 가져오기
     *
     * @param $id
     * @return mixed
     */
    public function get_next($id)
    {
        $this->db->select('*');
        $this->db->from('posts');
        $this->db->where('id >', $id);
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

}