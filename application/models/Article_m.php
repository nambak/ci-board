<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article_m extends CI_Model
{
    /**
     * 지정된 게시판 ID에 해당하는 모든 게시글을 최신순으로 조회합니다.
     * 각 게시글의 댓글 수도 함께 조회합니다.
     *
     * @param int $boardId 게시판의 고유 ID.
     * @return array 게시글 객체 배열 (comment_count 포함).
     */
    public function fetchByBoardId($boardId)
    {
        $this->db->select('articles.*, users.name as author, COUNT(comments.id) as comment_count');
        $this->db->from('articles');
        $this->db->join('comments', 'comments.article_id = articles.id', 'left');
        $this->db->join('users', 'users.id = articles.user_id', 'left');
        $this->db->where('articles.board_id', $boardId);
        $this->db->group_by('articles.id');
        $this->db->order_by('articles.id', 'DESC');
        $query = $this->db->get();

        $result = $query->result();

        // comment_count를 정수값으로 변환
        foreach ($result as &$row) {
            $row->comment_count = (int)$row->comment_count;
        }

        return $result;
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

    /**
     * 게시글의 조회수를 1 증가시킵니다.
     *
     * @param int $id 게시글 ID.
     * @return bool 업데이트 성공 여부.
     */
    public function incrementViewCount($id)
    {
        $this->db->where('id', $id);
        $this->db->set('views', 'views + 1', false);
        return $this->db->update('articles');
    }

    /**
     * 사용자가 작성한 글 수 조회
     *
     * @param int $userId
     * @return int
     */
    public function countByUserId($userId)
    {
        $this->db->where('user_id', $userId);

        return $this->db->count_all_results('articles');
    }

    /**
     * 전체 게시글 수 조회
     *
     * @return int
     */
    public function count()
    {
        return $this->db->count_all('articles');
    }

    /**
     * 모든 게시글을 상세 정보와 함께 조회 (관리자용)
     * 게시판명, 작성자, 댓글 수를 포함하여 페이지네이션과 검색을 지원합니다.
     *
     * @param int $limit 페이지당 게시글 수
     * @param int $offset 시작 위치
     * @param string|null $search 검색어 (제목 또는 내용 검색)
     * @param int|null $boardId 게시판 ID 필터
     * @return array 게시글 목록과 총 개수를 포함한 배열
     */
    public function get_all_with_details($limit = 10, $offset = 0, $search = null, $boardId = null)
    {
        // 총 개수 조회용 쿼리
        $this->db->select('articles.id');
        $this->db->from('articles');
        $this->db->join('boards', 'boards.id = articles.board_id', 'left');
        $this->db->join('users', 'users.id = articles.user_id', 'left');

        // 검색 조건
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('articles.title', $search);
            $this->db->or_like('articles.content', $search);
            $this->db->or_like('users.name', $search);
            $this->db->group_end();
        }

        // 게시판 필터
        if (!empty($boardId)) {
            $this->db->where('articles.board_id', $boardId);
        }

        $total = $this->db->count_all_results();

        // 실제 데이터 조회
        $this->db->select('articles.*,
                          boards.name as board_name,
                          users.name as author,
                          users.email as author_email,
                          COUNT(comments.id) as comment_count');
        $this->db->from('articles');
        $this->db->join('boards', 'boards.id = articles.board_id', 'left');
        $this->db->join('users', 'users.id = articles.user_id', 'left');
        $this->db->join('comments', 'comments.article_id = articles.id', 'left');

        // 검색 조건 (동일하게 적용)
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('articles.title', $search);
            $this->db->or_like('articles.content', $search);
            $this->db->or_like('users.name', $search);
            $this->db->group_end();
        }

        // 게시판 필터 (동일하게 적용)
        if (!empty($boardId)) {
            $this->db->where('articles.board_id', $boardId);
        }

        $this->db->group_by('articles.id');
        $this->db->order_by('articles.id', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        $rows = $query->result();

        // comment_count를 정수값으로 변환
        foreach ($rows as &$row) {
            $row->comment_count = (int)$row->comment_count;
        }

        return [
            'rows' => $rows,
            'total' => $total
        ];
    }

}
