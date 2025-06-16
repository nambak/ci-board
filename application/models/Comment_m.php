<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Comment_m extends CI_Model
{
    /**
     * 새로운 댓글을 데이터베이스에 저장합니다.
     *
     * 지정된 게시글 ID와 작성자 ID, 댓글 내용을 기반으로 comments 테이블에 새로운 레코드를 추가합니다.
     *
     * @param int $postId 댓글이 달릴 게시글의 ID
     * @param string $comment 댓글 내용
     * @param int $writerId 댓글 작성자의 사용자 ID
     */
    public function create($postId, $comment, $writerId)
    {
        $this->db->insert('comments', [
            'post_id'   => $postId,
            'writer_id' => $writerId,
            'comment'   => $comment
        ]);
    }

    /**
     * 지정된 게시글 ID에 해당하는 모든 댓글과 작성자 이름을 조회합니다.
     *
     * @param int $id 댓글을 조회할 게시글의 ID
     * @return array 댓글 객체 배열로, 각 객체에는 댓글 정보와 작성자 이름이 포함됩니다.
     */
    public function fetchByPost($id)
    {
        $query = $this->db->select('comments.*, users.name')
            ->from('comments')
            ->join('users', 'users.id = comments.writer_id')
            ->where('comments.post_id', $id)
            ->get();

        return $query->result();
    }
}
