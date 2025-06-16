<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Post_m extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 지정된 게시판 ID에 해당하는 모든 게시글을 최신순으로 조회합니다.
     *
     * @param int $boardId 게시판의 고유 ID.
     * @return array 게시글 객체 배열.
     */
    public function fetchByBoardId($boardId)
    {
        $this->db->select('*');
        $this->db->from('posts');
        $this->db->where('board_id', $boardId);
        $this->db->order_by('id', 'DESC');
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

    /**
     * 새로운 게시글을 생성하고 해당 게시글의 ID를 반환합니다.
     *
     * @param int $boardId 게시판의 ID.
     * @param string $title 게시글 제목.
     * @param string $content 게시글 내용.
     * @return int 생성된 게시글의 ID.
     */
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
     * 주어진 게시물 ID보다 작은 ID를 가진 이전 게시물을 반환합니다.
     *
     * @param int $id 기준이 되는 게시물의 ID
     * @return object|null 이전 게시물 객체를 반환하며, 없을 경우 null을 반환합니다.
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


    /****
     * 주어진 게시물 ID보다 큰 다음 게시물을 반환합니다.
     *
     * @param int $id 기준이 되는 게시물의 ID입니다.
     * @return object|null 다음 게시물 객체를 반환하며, 없을 경우 null을 반환합니다.
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