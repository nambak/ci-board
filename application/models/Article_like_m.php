<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article_like_m extends CI_Model
{
    /**
     * 좋아요 추가
     *
     * @param int $articleId
     * @param int $userId
     * @return bool
     */
    public function add($articleId, $userId)
    {
        $this->db->insert('article_likes', [
            'article_id' => $articleId,
            'user_id' => $userId
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * 좋아요 삭제
     *
     * @param int $articleId
     * @param int $userId
     * @return bool
     */
    public function remove($articleId, $userId)
    {
        $this->db->where('article_id', $articleId);
        $this->db->where('user_id', $userId);
        $this->db->delete('article_likes');

        return $this->db->affected_rows() > 0;
    }

    /**
     * 사용자가 해당 게시글에 좋아요를 했는지 확인
     *
     * @param int $articleId
     * @param int $userId
     * @return bool
     */
    public function hasLiked($articleId, $userId)
    {
        $this->db->where('article_id', $articleId);
        $this->db->where('user_id', $userId);

        return $this->db->count_all_results('article_likes') > 0;
    }

    /**
     * 게시글의 좋아요 수 조회
     *
     * @param int $articleId
     * @return int
     */
    public function countByArticleId($articleId)
    {
        $this->db->where('article_id', $articleId);

        return $this->db->count_all_results('article_likes');
    }
}
