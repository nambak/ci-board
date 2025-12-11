<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tag_m extends CI_Model
{
    const TABLE = 'tags';
    const MAX_TAGS_PER_ARTICLE = 5;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * ID로 태그 조회
     *
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $query = $this->db->get_where(self::TABLE, ['id' => $id]);
        return $query->row();
    }

    /**
     * 슬러그로 태그 조회
     *
     * @param string $slug
     * @return object|null
     */
    public function getBySlug($slug)
    {
        $query = $this->db->get_where(self::TABLE, ['slug' => $slug]);
        return $query->row();
    }

    /**
     * 이름으로 태그 조회
     *
     * @param string $name
     * @return object|null
     */
    public function getByName($name)
    {
        $query = $this->db->get_where(self::TABLE, ['name' => $name]);
        return $query->row();
    }

    /**
     * 태그 생성
     *
     * @param string $name
     * @return int|bool 태그 ID 또는 false
     */
    public function create($name)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }

        // 이미 존재하는 태그인지 확인
        $existing = $this->getByName($name);
        if ($existing) {
            return $existing->id;
        }

        $slug = $this->generateSlug($name);

        $data = [
            'name' => $name,
            'slug' => $slug,
            'usage_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert(self::TABLE, $data);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * 태그 수정
     *
     * @param int $id
     * @param string $name
     * @return bool
     */
    public function update($id, $name)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }

        // 중복 체크 (자기 자신 제외)
        $this->db->where('name', $name);
        $this->db->where('id !=', $id);
        if ($this->db->count_all_results(self::TABLE) > 0) {
            return false;
        }

        $data = [
            'name' => $name,
            'slug' => $this->generateSlug($name)
        ];

        $this->db->where('id', $id);
        $this->db->update(self::TABLE, $data);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * 태그 삭제
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(self::TABLE);

        return $this->db->affected_rows() > 0;
    }

    /**
     * 태그 목록 조회
     *
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @param string $orderDir
     * @param string|null $search
     * @return array
     */
    public function getList($limit = 20, $offset = 0, $orderBy = 'usage_count', $orderDir = 'DESC', $search = null)
    {
        // 허용된 정렬 컬럼
        $allowedColumns = ['id', 'name', 'slug', 'usage_count', 'created_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'usage_count';
        }

        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        if ($search) {
            $this->db->like('name', $search);
        }

        $this->db->order_by($orderBy, $orderDir);
        $this->db->limit($limit, $offset);

        $query = $this->db->get(self::TABLE);
        return $query->result();
    }

    /**
     * 태그 수 조회 (검색 포함)
     *
     * @param string|null $search
     * @return int
     */
    public function count($search = null)
    {
        if ($search) {
            $this->db->like('name', $search);
        }

        return $this->db->count_all_results(self::TABLE);
    }

    /**
     * 전체 태그 수 조회
     *
     * @return int
     */
    public function countAll()
    {
        return $this->db->count_all(self::TABLE);
    }

    /**
     * 인기 태그 조회
     *
     * @param int $limit
     * @return array
     */
    public function getPopular($limit = 10)
    {
        $this->db->where('usage_count >', 0);
        $this->db->order_by('usage_count', 'DESC');
        $this->db->limit($limit);

        $query = $this->db->get(self::TABLE);
        return $query->result();
    }

    /**
     * 태그 자동완성용 검색
     *
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function search($keyword, $limit = 10)
    {
        $this->db->like('name', $keyword, 'after');
        $this->db->order_by('usage_count', 'DESC');
        $this->db->limit($limit);

        $query = $this->db->get(self::TABLE);
        return $query->result();
    }

    /**
     * 사용 횟수 증가
     *
     * @param int $id
     * @return bool
     */
    public function incrementUsageCount($id)
    {
        $this->db->set('usage_count', 'usage_count + 1', false);
        $this->db->where('id', $id);
        $this->db->update(self::TABLE);

        return $this->db->affected_rows() > 0;
    }

    /**
     * 사용 횟수 감소
     *
     * @param int $id
     * @return bool
     */
    public function decrementUsageCount($id)
    {
        $this->db->set('usage_count', 'GREATEST(usage_count - 1, 0)', false);
        $this->db->where('id', $id);
        $this->db->update(self::TABLE);

        return $this->db->affected_rows() > 0;
    }

    /**
     * 미사용 태그 삭제
     *
     * @return int 삭제된 태그 수
     */
    public function deleteUnused()
    {
        $this->db->where('usage_count', 0);
        $this->db->delete(self::TABLE);

        return $this->db->affected_rows();
    }

    /**
     * 태그 병합
     *
     * @param int $sourceId 원본 태그 ID
     * @param int $targetId 대상 태그 ID
     * @return bool
     */
    public function merge($sourceId, $targetId)
    {
        if ($sourceId == $targetId) {
            return false;
        }

        $sourceTag = $this->get($sourceId);
        $targetTag = $this->get($targetId);

        if (!$sourceTag || !$targetTag) {
            return false;
        }

        $this->db->trans_start();

        // article_tags에서 source를 target으로 변경
        // 이미 target이 있는 경우 중복 방지
        $this->db->query("
            UPDATE IGNORE article_tags
            SET tag_id = ?
            WHERE tag_id = ?
        ", [$targetId, $sourceId]);

        // 중복으로 인해 변경되지 않은 source 태그 연결 삭제
        $this->db->where('tag_id', $sourceId);
        $this->db->delete('article_tags');

        // usage_count 다시 계산
        $this->recalculateUsageCount($targetId);

        // 원본 태그 삭제
        $this->delete($sourceId);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * 태그 사용 횟수 재계산
     *
     * @param int $id
     * @return bool
     */
    public function recalculateUsageCount($id)
    {
        $this->db->where('tag_id', $id);
        $count = $this->db->count_all_results('article_tags');

        $this->db->where('id', $id);
        $this->db->update(self::TABLE, ['usage_count' => $count]);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * 슬러그 생성
     *
     * @param string $name
     * @return string
     */
    private function generateSlug($name)
    {
        // 한글 및 영문/숫자 허용, 나머지는 하이픈으로 변환
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $name);
        $slug = trim($slug, '-');
        $slug = mb_strtolower($slug, 'UTF-8');

        // 빈 슬러그인 경우 해시 사용
        if (empty($slug)) {
            $slug = substr(md5($name), 0, 8);
        }

        // 중복 체크 및 고유 슬러그 생성
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * 슬러그 존재 여부 확인
     *
     * @param string $slug
     * @return bool
     */
    private function slugExists($slug)
    {
        $this->db->where('slug', $slug);
        return $this->db->count_all_results(self::TABLE) > 0;
    }

    /**
     * 여러 태그 이름으로 태그 ID 목록 반환 (없으면 생성)
     *
     * @param array $names 태그 이름 배열
     * @return array 태그 ID 배열
     */
    public function getOrCreateByNames($names)
    {
        $tagIds = [];

        foreach ($names as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            $tag = $this->getByName($name);
            if ($tag) {
                $tagIds[] = $tag->id;
            } else {
                $tagId = $this->create($name);
                if ($tagId) {
                    $tagIds[] = $tagId;
                }
            }
        }

        return array_unique($tagIds);
    }
}
