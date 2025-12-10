<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Comment_m extends CI_Model
{
    /**
     * 새로운 댓글을 데이터베이스에 저장합니다.
     *
     * 지정된 게시글 ID와 작성자 ID, 댓글 내용을 기반으로 comments 테이블에 새로운 레코드를 추가합니다.
     *
     * @param int $articleId 댓글이 달릴 게시글의 ID
     * @param string $comment 댓글 내용
     * @param int $writerId 댓글 작성자의 사용자 ID
     * @param int|null $parentId 부모 댓글 ID (답글인 경우)
     * @return int 생성된 댓글 ID
     */
    public function create($articleId, $comment, $writerId, $parentId = null)
    {
        $depth = 0;
        $validParentId = null;

        // 부모 댓글이 있으면 depth 계산
        if ($parentId) {
            $parent = $this->get($parentId);
            if ($parent) {
                $depth = min($parent->depth + 1, 2); // 최대 depth 2로 제한
                $validParentId = $parentId;
            }
        }

        $this->db->insert('comments', [
            'article_id' => $articleId,
            'writer_id'  => $writerId,
            'parent_id'  => $validParentId,
            'depth'      => $depth,
            'comment'    => $comment
        ]);

        return $this->db->insert_id();
    }

    /**
     * 지정된 게시글 ID에 해당하는 모든 댓글과 작성자 이름을 계층 구조로 조회합니다.
     *
     * @param int $id 댓글을 조회할 게시글의 ID
     * @return array 댓글 객체 배열 (부모 댓글 아래에 자식 댓글이 정렬됨)
     */
    public function fetchByPost($id)
    {
        // 모든 댓글 조회 (생성일 순 정렬)
        $query = $this->db->select('comments.*, users.name, users.profile_image, parent_user.name as parent_author_name')
            ->from('comments')
            ->join('users', 'users.id = comments.writer_id')
            ->join('comments as parent_comment', 'parent_comment.id = comments.parent_id', 'left')
            ->join('users as parent_user', 'parent_user.id = parent_comment.writer_id', 'left')
            ->where('comments.article_id', $id)
            ->order_by('comments.created_at', 'ASC')
            ->get();

        $allComments = $query->result();

        // 계층 구조 정렬: 부모 댓글 아래에 자식 댓글 배치
        return $this->buildCommentTree($allComments);
    }

    /**
     * 댓글을 계층 구조로 정렬합니다.
     * 부모 댓글 아래에 자식 댓글들이 순서대로 배치됩니다.
     *
     * @param array $comments 모든 댓글 배열
     * @return array 계층 구조로 정렬된 댓글 배열
     */
    private function buildCommentTree($comments)
    {
        $result = [];
        $childrenMap = [];

        // 자식 댓글을 parent_id 기준으로 그룹화
        foreach ($comments as $comment) {
            if ($comment->parent_id) {
                if (!isset($childrenMap[$comment->parent_id])) {
                    $childrenMap[$comment->parent_id] = [];
                }
                $childrenMap[$comment->parent_id][] = $comment;
            }
        }

        // 루트 댓글(depth=0)부터 순서대로 배치
        foreach ($comments as $comment) {
            if ($comment->depth == 0) {
                $result[] = $comment;
                // 이 댓글의 자식들 추가
                $this->addChildren($result, $comment->id, $childrenMap);
            }
        }

        return $result;
    }

    /**
     * 재귀적으로 자식 댓글을 추가합니다.
     *
     * @param array &$result 결과 배열
     * @param int $parentId 부모 댓글 ID
     * @param array $childrenMap 자식 댓글 맵
     */
    private function addChildren(&$result, $parentId, $childrenMap)
    {
        if (isset($childrenMap[$parentId])) {
            foreach ($childrenMap[$parentId] as $child) {
                $result[] = $child;
                // 자식의 자식도 추가 (재귀)
                $this->addChildren($result, $child->id, $childrenMap);
            }
        }
    }

    /**
     * ID로 댓글 조회
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->select('*');
        $this->db->from('comments');
        $this->db->where('id', $id);
        $query = $this->db->get();

        return $query->row();
    }

    /**
     * 댓글 존재 여부 확인
     * @param int $id
     * @return bool
     */
    public function exists($id)
    {
        $this->db->where('id', $id);
        return $this->db->count_all_results('comments') > 0;
    }

    /**
     * 댓글 수정
     * @param int $id 댓글 ID
     * @param string $comment 수정할 댓글 내용
     * @return bool
     */
    public function update($id, $comment)
    {
        try {
            $this->db->where('id', $id);
            $this->db->update('comments', [
                'comment'    => $comment,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->db->affected_rows() > 0;
        } catch (Exception $e) {
            log_message('error', 'Comment update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 댓글 삭제
     * @param int $id 댓글 ID
     * @return bool
     */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id);
            $this->db->delete('comments');

            return $this->db->affected_rows() > 0;
        } catch (Exception $e) {
            log_message('error', 'Comment deletion error: ' . $e->getMessage());
            throw $e;
        }
    }

    /*
     * 작성한 댓글 수 조회
     *
     * @param int $userId
     * @return int
    */
    public function countByUserId($userId)
    {
        $this->db->where('writer_id', $userId);
        return $this->db->count_all_results('comments');

    }

    /**
     * 전체 댓글 수 조회
     *
     * @return int
     */
    public function count()
    {
        return $this->db->count_all('comments');
    }

    /**
     * 모든 댓글을 상세 정보와 함께 조회 (관리자용)
     * 작성자, 게시글, 게시판 정보를 포함하여 페이지네이션과 정렬을 지원합니다.
     *
     * @param int $limit 페이지당 댓글 수
     * @param int $offset 시작 위치
     * @param string $sort 정렬 컬럼 (id, created_at, writer_id, article_id)
     * @param string $order 정렬 순서 (asc, desc)
     * @param string|null $search 검색어 (댓글 내용 또는 작성자명 검색)
     * @return array 댓글 목록과 총 개수를 포함한 배열
     */
    public function get_all_with_details($limit = 10, $offset = 0, $sort = 'id', $order = 'desc', $search = null)
    {
        // 정렬 컬럼 화이트리스트 검증 (SQL Injection 방지)
        $allowedSortColumns = ['id', 'created_at', 'writer_id', 'article_id'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'id';
        }

        // 정렬 순서 검증
        $order = strtolower($order);
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // 총 개수 조회용 쿼리
        $this->db->select('comments.id');
        $this->db->from('comments');
        $this->db->join('users', 'users.id = comments.writer_id', 'left');
        $this->db->join('articles', 'articles.id = comments.article_id', 'left');

        // 검색 조건
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('comments.comment', $search);
            $this->db->or_like('users.name', $search);
            $this->db->or_like('articles.title', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results();

        // 실제 데이터 조회
        $this->db->select('comments.*,
                          users.name as writer_name,
                          users.email as writer_email,
                          articles.id as article_id,
                          articles.title as article_title,
                          articles.board_id,
                          boards.name as board_name');
        $this->db->from('comments');
        $this->db->join('users', 'users.id = comments.writer_id', 'left');
        $this->db->join('articles', 'articles.id = comments.article_id', 'left');
        $this->db->join('boards', 'boards.id = articles.board_id', 'left');

        // 검색 조건 (동일하게 적용)
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('comments.comment', $search);
            $this->db->or_like('users.name', $search);
            $this->db->or_like('articles.title', $search);
            $this->db->group_end();
        }

        // 정렬 및 페이지네이션
        $this->db->order_by("comments.{$sort}", $order);
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        $rows = $query->result();

        return [
            'rows'  => $rows,
            'total' => $total
        ];
    }
}
