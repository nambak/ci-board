<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Article extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_m');
        $this->load->model('article_like_m');
        $this->load->model('user_m');
        $this->load->model('Tag_m');
        $this->load->model('Article_tag_m');
        $this->load->library('session');
        $this->load->library('services/ArticleService', null, 'article_service');
        $this->load->library('activity_logger');
        $this->load->helper('auth');
    }

    public function index_get($id)
    {
        try {
            if ($id === null || (int)$id < 1) {
                $this->response(['message' => 'invalid id'], 400);
            }

            $article = $this->article_m->get($id);

            if (!$article) {
                $this->response(['message' => 'not found'], 404);
            }

            // 세션 기반 조회수 증가 처리
            $viewedArticles = $this->session->userdata('viewed_articles');
            if ($this->article_service->incrementViewCount($id, $viewedArticles)) {
                $this->session->set_userdata('viewed_articles', $viewedArticles);
            }

            // 좋아요 관련 정보 추가
            $userId = get_user_id();
            $article->like_count = (int)($article->like_count ?? 0);
            $article->liked_by_me = $userId ? $this->article_like_m->hasLiked($id, $userId) : false;

            // 태그 정보 추가
            $article->tags = $this->Article_tag_m->getByArticle($id);

            $this->response($article, 200);
        } catch (Exception $e) {
            log_message('error', 'Article::index_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    public function index_put($id)
    {
        if (!$this->session->userdata('user_id')) {
            $this->response(['message' => 'unauthorized'], 401);
        }

        try {
            $title = $this->put('title', true);
            $content = $this->put('content', true);
            $tagsJson = $this->put('tags', true);

            // 게시글 존재 확인
            $post = $this->article_m->get($id);

            if (!$post) {
                $this->response(['message' => 'post not found'], 404);
            }

            if (!$title) {
                $this->response(['message' => 'title required'], 400);
            }

            if (!$content) {
                $this->response(['message' => 'content required'], 400);
            }

            // 변경 전 데이터 저장
            $oldData = ['title' => $post->title, 'content' => $post->content];

            $updated = $this->article_m->update($id, $title, $content);

            // 태그 동기화
            if ($tagsJson) {
                $tagNames = json_decode($tagsJson, true);
                if (is_array($tagNames)) {
                    $tagIds = $this->Tag_m->getOrCreateByNames($tagNames);
                    $this->Article_tag_m->syncTags($id, $tagIds);
                }
            }

            // 게시글 수정 로깅
            if ($updated) {
                $this->activity_logger->logArticleUpdate($id, $oldData, ['title' => $title, 'content' => $content]);
            }

            $this->response(
                $updated ? ['message' => 'success'] : ['message' => 'update failed'],
                $updated ? 200 : 500
            );
        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }

    public function index_delete($id)
    {
        try {
            // 삭제 전 게시글 정보 가져오기
            $article = $this->article_m->get($id);

            if (!$article) {
                $this->response(['message' => 'article not found'], 404);
                return;
            }

            $deleted = $this->article_m->delete($id);

            // 게시글 삭제 로깅
            if  ($deleted) {
                $this->activity_logger->logArticleDelete($id, (array) $article);
            }

            $this->response($deleted ? 'success' : ['message' => 'delete failed'], $deleted ? 200 : 500);

        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }

    public function create_post()
    {
        try {
            $title = $this->input->post('title', true);
            $content = $this->input->post('content', true);
            $boardId = $this->input->post('board_id', true);
            $tagsJson = $this->input->post('tags', true);
            $userId = get_user_id();

            if (!$userId) {
                $this->response(['message' => 'unauthorized'], 401);
            }

            // 이메일 인증 확인
            if (!$this->user_m->is_email_verified($userId)) {
                $this->response([
                    'message' => '이메일 인증이 필요합니다. 이메일을 확인하여 인증을 완료해주세요.',
                    'email_verification_required' => true
                ], 403);
            }

            if ((int)$boardId <= 0) {
                $this->response(['message' => 'invalid board_id'], 400);
            }

            if (!$title) {
                $this->response(['message' => 'title required'], 400);
            }

            if (!$content) {
                $this->response(['message' => 'content required'], 400);
            }

            $result = $this->article_m->store($boardId, $userId, $title, $content);

            // 태그 처리
            if ($result && $tagsJson) {
                $tagNames = json_decode($tagsJson, true);
                if (is_array($tagNames) && !empty($tagNames)) {
                    $tagIds = $this->Tag_m->getOrCreateByNames($tagNames);
                    foreach ($tagIds as $tagId) {
                        $this->Article_tag_m->add($result, $tagId);
                    }
                }
            }

            // 게시글 작성 로깅
            if ($result) {
                $this->activity_logger->logArticleCreate($result, $title, $boardId);
            }

            // 멘션 알림 (게시글 내용에 @사용자명이 있으면)
            $this->load->library('services/NotificationService', null, 'notification_service');
            $this->notification_service->notifyMentionsInArticle($result, $content, $userId);

            $this->response(['id' => $result], 200);
        } catch (Exception $e) {
            $this->response(['message' => 'server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 관리자용 게시글 목록 조회 API
     * 페이지네이션, 검색, 필터링을 지원합니다.
     *
     * @return void
     */
    public function list_get()
    {
        try {
            // 관리자 권한 체크
            if (!is_admin()) {
                $this->response(['message' => 'forbidden'], 403);
                return;
            }

            // 파라미터 받기 (XSS 방지)
            $page = (int)$this->get('page', true) ?: 1;
            $perPage = (int)$this->get('per_page', true) ?: 10;
            $search = $this->get('search', true) ?: null;
            $boardId = $this->get('board_id', true) ? (int)$this->get('board_id', true) : null;

            // 페이지네이션 유효성 검사
            if ($page < 1) {
                $page = 1;
            }

            if ($perPage < 1 || $perPage > 100) {
                $perPage = 10;
            }

            // offset 계산
            $offset = ($page - 1) * $perPage;

            // 데이터 조회
            $result = $this->article_m->get_all_with_details($perPage, $offset, $search, $boardId);

            // 페이지네이션 정보 계산
            $totalPages = ceil($result['total'] / $perPage);

            $response = [
                'rows' => $result['rows'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $result['total'])
                ]
            ];

            $this->response($response, 200);
        } catch (Exception $e) {
            log_message('error', 'Article::list_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 게시글 검색 API
     * GET /rest/article/search
     *
     * @return void
     */
    public function search_get()
    {
        try {
            // 파라미터 받기 (XSS 방지)
            $query = $this->get('query', true);
            $type = $this->get('type', true) ?: 'all';
            $boardId = $this->get('board_id', true) ? (int)$this->get('board_id', true) : null;
            $startDate = $this->get('start_date', true) ?: null;
            $endDate = $this->get('end_date', true) ?: null;
            $page = (int)$this->get('page', true) ?: 1;
            $perPage = (int)$this->get('per_page', true) ?: 10;

            // 검색어가 없으면 에러
            if (empty($query)) {
                $this->response(['message' => 'search query required'], 400);
                return;
            }

            // 페이지네이션 유효성 검사
            if ($page < 1) {
                $page = 1;
            }

            if ($perPage < 1 || $perPage > 100) {
                $perPage = 10;
            }

            // 날짜 형식 검증 (YYYY-MM-DD)
            if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                $this->response(['message' => 'invalid start_date format (YYYY-MM-DD)'], 400);
                return;
            }

            if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $this->response(['message' => 'invalid end_date format (YYYY-MM-DD)'], 400);
                return;
            }

            // offset 계산
            $offset = ($page - 1) * $perPage;

            // 검색 실행
            $result = $this->article_m->search($query, $type, $boardId, $startDate, $endDate, $perPage, $offset);

            // 페이지네이션 정보 계산
            $totalPages = ceil($result['total'] / $perPage);

            $response = [
                'rows' => $result['rows'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $result['total'])
                ],
                'search' => [
                    'query' => $result['query'],
                    'type' => $result['type'],
                    'board_id' => $boardId,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ];

            $this->response($response, 200);
        } catch (Exception $e) {
            log_message('error', 'Article::search_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 게시글 좋아요 추가
     * POST /rest/article/{id}/like
     *
     * @param int $id 게시글 ID
     * @return void
     */
    public function like_post($id)
    {
        try {
            $userId = get_user_id();

            if (!$userId) {
                $this->response(['message' => 'unauthorized'], 401);
                return;
            }

            $id = (int)$id;

            if ($id <= 0) {
                $this->response(['message' => 'invalid id'], 400);
                return;
            }

            // 게시글 존재 확인
            if (!$this->article_m->exists($id)) {
                $this->response(['message' => 'article not found'], 404);
                return;
            }

            // 이미 좋아요 했는지 확인
            if ($this->article_like_m->hasLiked($id, $userId)) {
                $this->response(['message' => 'already liked'], 409);
                return;
            }

            // 좋아요 추가
            if (!$this->article_like_m->add($id, $userId)) {
                $this->response(['message' => 'failed to add like'], 500);
                return;
            }
            $this->article_m->incrementLikeCount($id);

            // 좋아요 알림 생성
            $this->load->library('services/NotificationService', null, 'notification_service');
            $this->notification_service->notifyLike($id, $userId);

            // 업데이트된 좋아요 수 조회
            $likeCount = $this->article_like_m->countByArticleId($id);

            $this->response([
                'message' => 'success',
                'like_count' => $likeCount,
                'liked_by_me' => true
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'Article::like_post error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 게시글 좋아요 취소
     * DELETE /rest/article/{id}/like
     *
     * @param int $id 게시글 ID
     * @return void
     */
    public function like_delete($id)
    {
        try {
            $userId = get_user_id();

            if (!$userId) {
                $this->response(['message' => 'unauthorized'], 401);
                return;
            }

            $id = (int)$id;

            if ($id <= 0) {
                $this->response(['message' => 'invalid id'], 400);
                return;
            }

            // 게시글 존재 확인
            if (!$this->article_m->exists($id)) {
                $this->response(['message' => 'article not found'], 404);
                return;
            }

            // 좋아요 안 했으면 취소 불가
            if (!$this->article_like_m->hasLiked($id, $userId)) {
                $this->response(['message' => 'not liked'], 409);
                return;
            }

            // 좋아요 삭제
            if (!$this->article_like_m->remove($id, $userId)) {
                $this->response(['message' => 'failed to remove like'], 500);
                return;
            }
            $this->article_m->decrementLikeCount($id);

            // 업데이트된 좋아요 수 조회
            $likeCount = $this->article_like_m->countByArticleId($id);

            $this->response([
                'message' => 'success',
                'like_count' => $likeCount,
                'liked_by_me' => false
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'Article::like_delete error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }
}
