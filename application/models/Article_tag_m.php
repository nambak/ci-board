<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Article_tag_m extends CI_Model
{
    const TABLE = 'article_tags';
    const MAX_TAGS_PER_ARTICLE = 5;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Tag_m');
    }

    /**
     * 게시글에 태그 추가
     *
     * @param int $articleId
     * @param int $tagId
     * @return bool
     */
    public function add($articleId, $tagId)
    {
        // 이미 연결되어 있는지 확인
        if ($this->exists($articleId, $tagId)) {
            return true;
        }

        // 최대 태그 수 확인
        if ($this->countByArticle($articleId) >= self::MAX_TAGS_PER_ARTICLE) {
            return false;
        }

        $data = [
            'article_id' => $articleId,
            'tag_id' => $tagId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert(self::TABLE, $data);

        if ($this->db->affected_rows() > 0) {
            // 태그 사용 횟수 증가
            $this->Tag_m->incrementUsageCount($tagId);
            return true;
        }

        return false;
    }

    /**
     * 게시글에서 태그 제거
     *
     * @param int $articleId
     * @param int $tagId
     * @return bool
     */
    public function remove($articleId, $tagId)
    {
        $this->db->where('article_id', $articleId);
        $this->db->where('tag_id', $tagId);
        $this->db->delete(self::TABLE);

        if ($this->db->affected_rows() > 0) {
            // 태그 사용 횟수 감소
            $this->Tag_m->decrementUsageCount($tagId);
            return true;
        }

        return false;
    }

    /**
     * 게시글-태그 연결 존재 여부
     *
     * @param int $articleId
     * @param int $tagId
     * @return bool
     */
    public function exists($articleId, $tagId)
    {
        $this->db->where('article_id', $articleId);
        $this->db->where('tag_id', $tagId);
        return $this->db->count_all_results(self::TABLE) > 0;
    }

    /**
     * 게시글의 태그 수 조회
     *
     * @param int $articleId
     * @return int
     */
    public function countByArticle($articleId)
    {
        $this->db->where('article_id', $articleId);
        return $this->db->count_all_results(self::TABLE);
    }

    /**
     * 게시글의 태그 목록 조회
     *
     * @param int $articleId
     * @return array
     */
    public function getByArticle($articleId)
    {
        $this->db->select('tags.*');
        $this->db->from(self::TABLE);
        $this->db->join('tags', 'tags.id = article_tags.tag_id');
        $this->db->where('article_tags.article_id', $articleId);
        $this->db->order_by('tags.name', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }

    /**
     * 태그가 연결된 게시글 목록 조회
     *
     * @param int $tagId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getArticlesByTag($tagId, $limit = 20, $offset = 0)
    {
        $this->db->select('articles.*, users.name as author_name, boards.name as board_name');
        $this->db->from(self::TABLE);
        $this->db->join('articles', 'articles.id = article_tags.article_id');
        $this->db->join('users', 'users.id = articles.user_id', 'left');
        $this->db->join('boards', 'boards.id = articles.board_id', 'left');
        $this->db->where('article_tags.tag_id', $tagId);
        $this->db->order_by('articles.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result();
    }

    /**
     * 태그가 연결된 게시글 수 조회
     *
     * @param int $tagId
     * @return int
     */
    public function countArticlesByTag($tagId)
    {
        $this->db->where('tag_id', $tagId);
        return $this->db->count_all_results(self::TABLE);
    }

    /**
     * 게시글의 모든 태그 삭제
     *
     * @param int $articleId
     * @return int 삭제된 태그 수
     */
    public function removeAllByArticle($articleId)
    {
        // 현재 연결된 태그 ID들 조회
        $this->db->select('tag_id');
        $this->db->where('article_id', $articleId);
        $query = $this->db->get(self::TABLE);
        $tagIds = array_column($query->result_array(), 'tag_id');

        // 연결 삭제
        $this->db->where('article_id', $articleId);
        $this->db->delete(self::TABLE);

        $deletedCount = $this->db->affected_rows();

        // 각 태그의 사용 횟수 감소
        foreach ($tagIds as $tagId) {
            $this->Tag_m->decrementUsageCount($tagId);
        }

        return $deletedCount;
    }

    /**
     * 게시글의 태그 동기화 (기존 태그 삭제 후 새 태그 추가)
     *
     * @param int $articleId
     * @param array $tagIds 새로운 태그 ID 배열
     * @return bool
     */
    public function syncTags($articleId, $tagIds)
    {
        // 최대 태그 수 제한
        $tagIds = array_slice(array_unique($tagIds), 0, self::MAX_TAGS_PER_ARTICLE);

        // 현재 연결된 태그 ID들 조회
        $this->db->select('tag_id');
        $this->db->where('article_id', $articleId);
        $query = $this->db->get(self::TABLE);
        $currentTagIds = array_column($query->result_array(), 'tag_id');

        // 삭제할 태그 (현재에는 있지만 새 목록에는 없는 것)
        $toRemove = array_diff($currentTagIds, $tagIds);

        // 추가할 태그 (새 목록에는 있지만 현재에는 없는 것)
        $toAdd = array_diff($tagIds, $currentTagIds);

        // 삭제
        foreach ($toRemove as $tagId) {
            $this->remove($articleId, $tagId);
        }

        // 추가
        foreach ($toAdd as $tagId) {
            $this->add($articleId, $tagId);
        }

        return true;
    }

    /**
     * 여러 게시글의 태그 목록을 한 번에 조회 (최적화)
     *
     * @param array $articleIds
     * @return array [article_id => [tags]]
     */
    public function getByArticles($articleIds)
    {
        if (empty($articleIds)) {
            return [];
        }

        $this->db->select('article_tags.article_id, tags.*');
        $this->db->from(self::TABLE);
        $this->db->join('tags', 'tags.id = article_tags.tag_id');
        $this->db->where_in('article_tags.article_id', $articleIds);
        $this->db->order_by('tags.name', 'ASC');

        $query = $this->db->get();
        $results = $query->result();

        // article_id별로 그룹화
        $grouped = [];
        foreach ($results as $row) {
            $articleId = $row->article_id;
            if (!isset($grouped[$articleId])) {
                $grouped[$articleId] = [];
            }

            unset($row->article_id);
            $grouped[$articleId][] = $row;
        }

        return $grouped;
    }
}
