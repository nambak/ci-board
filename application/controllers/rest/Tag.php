<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Tag extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('Tag_m');
        $this->load->model('Article_tag_m');
        $this->load->model('Article_m');
        $this->load->helper('auth');
    }

    /**
     * 태그 목록 조회
     * GET /rest/tag
     */
    public function index_get()
    {
        try {
            // bootstrap-table 페이지네이션 파라미터
            $limit = (int)$this->get('limit') ?: 20;
            $offset = (int)$this->get('offset') ?: 0;
            $sort = $this->get('sort') ?: 'usage_count';
            $order = strtoupper($this->get('order') ?: 'DESC');
            $search = $this->get('search', true) ?: null;

            // limit 범위 제한
            $limit = max(1, min(100, $limit));

            $tags = $this->Tag_m->getList($limit, $offset, $sort, $order, $search);
            $total = $this->Tag_m->count($search);

            // bootstrap-table 형식 응답
            $this->response([
                'rows' => $tags,
                'total' => $total
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::index_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 목록을 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그 상세 조회
     * GET /rest/tag/{id}
     */
    public function show_get($id = null)
    {
        try {
            if (!$id) {
                $this->response([
                    'success' => false,
                    'message' => '태그 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $tag = $this->Tag_m->get($id);

            if (!$tag) {
                $this->response([
                    'success' => false,
                    'message' => '태그를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $this->response([
                'success' => true,
                'data' => $tag
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::show_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 조회 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 인기 태그 조회
     * GET /rest/tag/popular
     */
    public function popular_get()
    {
        try {
            $limit = (int)$this->get('limit') ?: 10;
            $limit = max(1, min(50, $limit));

            $tags = $this->Tag_m->getPopular($limit);

            $this->response([
                'success' => true,
                'data' => $tags
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::popular_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '인기 태그 조회 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그 자동완성 검색
     * GET /rest/tag/search
     */
    public function search_get()
    {
        try {
            $keyword = $this->get('q', true);

            if (empty($keyword)) {
                $this->response([
                    'success' => true,
                    'data' => []
                ], self::HTTP_OK);
                return;
            }

            $limit = (int)$this->get('limit') ?: 10;
            $limit = max(1, min(20, $limit));

            $tags = $this->Tag_m->search($keyword, $limit);

            $this->response([
                'success' => true,
                'data' => $tags
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::search_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 검색 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그 생성 (관리자 전용)
     * POST /rest/tag
     */
    public function index_post()
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        try {
            $name = trim($this->post('name', true));

            if (empty($name)) {
                $this->response([
                    'success' => false,
                    'message' => '태그 이름을 입력해주세요.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            if (mb_strlen($name) > 50) {
                $this->response([
                    'success' => false,
                    'message' => '태그 이름은 50자 이내로 입력해주세요.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 중복 체크
            if ($this->Tag_m->getByName($name)) {
                $this->response([
                    'success' => false,
                    'message' => '이미 존재하는 태그입니다.'
                ], self::HTTP_CONFLICT);
                return;
            }

            $tagId = $this->Tag_m->create($name);

            if (!$tagId) {
                $this->response([
                    'success' => false,
                    'message' => '태그 생성에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
                return;
            }

            $tag = $this->Tag_m->get($tagId);

            $this->response([
                'success' => true,
                'message' => '태그가 생성되었습니다.',
                'data' => $tag
            ], self::HTTP_CREATED);

        } catch (Exception $e) {
            log_message('error', 'Tag::index_post error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 생성 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그 수정 (관리자 전용)
     * PUT /rest/tag/{id}
     */
    public function index_put($id = null)
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        try {
            if (!$id) {
                $this->response([
                    'success' => false,
                    'message' => '태그 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $tag = $this->Tag_m->get($id);

            if (!$tag) {
                $this->response([
                    'success' => false,
                    'message' => '태그를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $name = trim($this->put('name', true));

            if (empty($name)) {
                $this->response([
                    'success' => false,
                    'message' => '태그 이름을 입력해주세요.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            if (mb_strlen($name) > 50) {
                $this->response([
                    'success' => false,
                    'message' => '태그 이름은 50자 이내로 입력해주세요.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $result = $this->Tag_m->update($id, $name);

            if (!$result) {
                $this->response([
                    'success' => false,
                    'message' => '태그 수정에 실패했습니다. 중복된 이름일 수 있습니다.'
                ], self::HTTP_CONFLICT);
                return;
            }

            $updatedTag = $this->Tag_m->get($id);

            $this->response([
                'success' => true,
                'message' => '태그가 수정되었습니다.',
                'data' => $updatedTag
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::index_put error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 수정 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그 삭제 (관리자 전용)
     * DELETE /rest/tag/{id}
     */
    public function index_delete($id = null)
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        try {
            if (!$id) {
                $this->response([
                    'success' => false,
                    'message' => '태그 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $tag = $this->Tag_m->get($id);

            if (!$tag) {
                $this->response([
                    'success' => false,
                    'message' => '태그를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $result = $this->Tag_m->delete($id);

            if (!$result) {
                $this->response([
                    'success' => false,
                    'message' => '태그 삭제에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
                return;
            }

            $this->response([
                'success' => true,
                'message' => '태그가 삭제되었습니다.'
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::index_delete error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 삭제 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그 병합 (관리자 전용)
     * POST /rest/tag/merge
     */
    public function merge_post()
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        try {
            $sourceId = (int)$this->post('source_id');
            $targetId = (int)$this->post('target_id');

            if (!$sourceId || !$targetId) {
                $this->response([
                    'success' => false,
                    'message' => '원본 태그와 대상 태그 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            if ($sourceId === $targetId) {
                $this->response([
                    'success' => false,
                    'message' => '같은 태그를 병합할 수 없습니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $result = $this->Tag_m->merge($sourceId, $targetId);

            if (!$result) {
                $this->response([
                    'success' => false,
                    'message' => '태그 병합에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
                return;
            }

            $targetTag = $this->Tag_m->get($targetId);

            $this->response([
                'success' => true,
                'message' => '태그가 병합되었습니다.',
                'data' => $targetTag
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::merge_post error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '태그 병합 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 미사용 태그 삭제 (관리자 전용)
     * DELETE /rest/tag/unused
     */
    public function unused_delete()
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        try {
            $deletedCount = $this->Tag_m->deleteUnused();

            $this->response([
                'success' => true,
                'message' => "{$deletedCount}개의 미사용 태그가 삭제되었습니다.",
                'deleted_count' => $deletedCount
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::unused_delete error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '미사용 태그 삭제 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 태그별 게시글 목록 조회
     * GET /rest/tag/{slug}/articles
     */
    public function articles_get($slug = null)
    {
        try {
            if (!$slug) {
                $this->response([
                    'success' => false,
                    'message' => '태그 슬러그가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $tag = $this->Tag_m->getBySlug($slug);

            if (!$tag) {
                $this->response([
                    'success' => false,
                    'message' => '태그를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            // 페이지네이션 파라미터
            $limit = (int)$this->get('limit') ?: 20;
            $offset = (int)$this->get('offset') ?: 0;
            $limit = max(1, min(100, $limit));

            $articles = $this->Article_tag_m->getArticlesByTag($tag->id, $limit, $offset);
            $total = $this->Article_tag_m->countArticlesByTag($tag->id);

            $this->response([
                'success' => true,
                'tag' => $tag,
                'rows' => $articles,
                'total' => $total
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Tag::articles_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '게시글 조회 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }
}
