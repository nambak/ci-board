<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article_m extends CI_Model
{
    /**
     * 지정된 게시판 ID에 해당하는 모든 게시글을 최신순으로 조회합니다.
     *
     * @param int $boardId 게시판의 고유 ID.
     * @return array 게시글 객체 배열.
     */
    public function fetchByBoardId($boardId)
    {
        $this->db->select('*');
        $this->db->from('articles');
        $this->db->where('board_id', $boardId);
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get();

        return $query->result();
    }

    public function get($id)
    {
        $this->db->select('articles.*, users.name, users.email');
        $this->db->from('articles');
        $this->db->join('users', 'users.id = articles.user_id');
        $this->db->where('articles.id', $id);
        $query = $this->db->get();

        return $query->row();
    }

    public function update($id, $title, $content)
    {
        $this->db->where('id', $id);

        return $this->db->update('articles', ['title' => $title, 'content' => $content]);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);

        return $this->db->delete('articles');
    }

    /**
     * 새로운 게시글을 생성하고 해당 게시글의 ID를 반환합니다.
     *
     * @param int $boardId 게시판의 ID.
     * @param string $title 게시글 제목.
     * @param string $content 게시글 내용.
     * @return int 생성된 게시글의 ID.
     */
    public function store($boardId, $userId, $title, $content)
    {
        $this->db->insert('articles', [
            'board_id' => $boardId,
            'user_id'  => $userId,
            'title'    => $title,
            'content'  => $content
        ]);

        return $this->db->insert_id();
    }

    /**
     * 주어진 게시물 ID보다 작은 ID를 가진 이전 게시물을 반환합니다.
     *
     * @param int $boardId 기준이 되는 게시물의 게시판 ID
     * @param int $id 기준이 되는 게시물의 ID
     * @return object|null 이전 게시물 객체를 반환하며, 없을 경우 null을 반환합니다.
     */
    public function getPrevious($boardId, $id)
    {
        $this->db->select('*');
        $this->db->from('articles');
        $this->db->where('id <', $id);
        $this->db->where('board_id', $boardId);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }


    /**
     * 주어진 게시물 ID보다 큰 다음 게시물을 반환합니다.
     *
     * @param int $boardId 기준이 되는 게시물의 게시판 ID
     * @param int $id 기준이 되는 게시물의 ID입니다.
     * @return object|null 다음 게시물 객체를 반환하며, 없을 경우 null을 반환합니다.
     */
    public function getNext($boardId, $id)
    {
        $this->db->select('*');
        $this->db->from('articles');
        $this->db->where('id >', $id);
        $this->db->where('board_id', $boardId);
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    /**
     * 지정된 게시판의 총 게시글 수를 반환합니다.
     *
     * @param int $boardId 게시판의 고유 ID.
     * @return int 게시글 총 개수.
     */
    public function countByBoardId($boardId)
    {
        $this->db->where('board_id', $boardId);
        return $this->db->count_all_results('articles');
    }

}
