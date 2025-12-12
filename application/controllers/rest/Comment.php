<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Comment extends MY_RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('comment_m');
        $this->load->model('article_m');
        $this->load->model('user_m');
        $this->load->library('activity_logger');
        $this->load->helper('auth');
    }

    /**
     * 댓글 목록 조회 API
     * - article_id가 있으면: 해당 게시글의 댓글 목록 반환 (계층 구조)
     * - article_id가 없고 관리자면: 전체 댓글 목록 반환 (페이지네이션)
     */
    public function index_get()
    {
        $articleId = $this->get('article_id', true);

        // article_id가 있으면 게시글별 댓글 조회
        if ($articleId) {
            $result = $this->comment_m->fetchByPost($articleId);
            $current_user_id = get_user_id();

            $data = array_map(function ($comment) use ($current_user_id) {
                $item = [
                    'id'                 => $comment->id,
                    'name'               => $this->security->xss_clean($comment->name),
                    'profile_image'      => $comment->profile_image ?? null,
                    'profile_image_url'  => !empty($comment->profile_image) ? '/uploads/profiles/' . $comment->profile_image : null,
                    'comment'            => $this->security->xss_clean($comment->comment),
                    'created_at'         => $this->security->xss_clean($comment->created_at),
                    'can_edit'           => $current_user_id && $comment->writer_id == $current_user_id,
                    'parent_id'          => $comment->parent_id,
                    'depth'              => (int)$comment->depth,
                    'parent_author_name' => $comment->parent_author_name ? $this->security->xss_clean($comment->parent_author_name) : null
                ];
                return $item;
            }, $result);

            $this->response(compact('data'), 200);
            return;
        }

        // article_id가 없으면 관리자용 전체 목록 조회
        if (!is_admin()) {
            $this->response(['message' => 'forbidden'], 403);
            return;
        }

        // 관리자용 전체 댓글 목록 조회
        try {
            // 파라미터 받기 (XSS 방지)
            $page = (int)$this->get('page', true) ?: 1;
            $perPage = (int)$this->get('per_page', true) ?: 10;
            $sort = $this->get('sort', true) ?: 'id';
            $order = $this->get('order', true) ?: 'desc';
            $search = $this->get('search', true) ?: null;

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
            $result = $this->comment_m->get_all_with_details($perPage, $offset, $sort, $order, $search);

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
            log_message('error', 'Comment::index_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 새로운 댓글을 저장하는 REST API 엔드포인트
     * parent_id가 있으면 답글로 저장됩니다.
     */
    public function save_post()
    {
        $articleId = $this->post('article_id', true);
        $comment = $this->post('comment', true);
        $parentId = $this->post('parent_id', true);
        $writerId = get_user_id(); // 현재 로그인된 사용자 ID 사용

        if (!ctype_digit((string)$articleId) || (int)$articleId <= 0) {
            $this->response(['message' => 'invalid article_id'], 400);
            return;
        }

        if (!is_string($comment) || trim($comment) === '') {
            $this->response(['message' => 'comment required'], 400);
            return;
        }

        if (!$writerId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        // 이메일 인증 확인
        if (!$this->user_m->is_email_verified($writerId)) {
            $this->response([
                'message' => '이메일 인증이 필요합니다. 이메일을 확인하여 인증을 완료해주세요.',
                'email_verification_required' => true
            ], 403);
            return;
        }

        $article = $this->article_m->get($articleId);

        if (!$article) {
            $this->response(['message' => 'article not found'], 404);
            return;
        }

        // parent_id 유효성 검사
        if ($parentId !== null && $parentId !== '') {
            if (!ctype_digit((string)$parentId) || (int)$parentId <= 0) {
                $this->response(['message' => 'invalid parent_id'], 400);
                return;
            }

            $parentId = (int)$parentId;
            $parentComment = $this->comment_m->get($parentId);

            if (!$parentComment) {
                $this->response(['message' => 'parent comment not found'], 404);
                return;
            }

            // 부모 댓글이 같은 게시글에 속하는지 확인
            if ($parentComment->article_id != $articleId) {
                $this->response(['message' => 'parent comment does not belong to this article'], 400);
                return;
            }

            // 최대 depth 검사 (depth 2까지만 허용)
            if ($parentComment->depth >= 2) {
                $this->response(['message' => 'maximum reply depth reached'], 400);
                return;
            }
        } else {
            // 루트 댓글의 경우 명시적으로 null로 정규화
            $parentId = null;
        }

        try {
            $commentId = $this->comment_m->create($articleId, $comment, $writerId, $parentId);

            // 댓글 작성 로깅
            $this->activity_logger->logCommentCreate($commentId, $articleId, $comment);

            // 알림 생성
            $this->load->library('services/NotificationService', null, 'notification_service');
            if ($parentId) {
                // 답글 알림
                $this->notification_service->notifyNewReply($parentId, $commentId, $writerId);
            } else {
                // 일반 댓글 알림
                $this->notification_service->notifyNewComment($articleId, $commentId, $writerId);
            }

            // 멘션 알림 (댓글 내용에 @사용자명이 있으면)
            $this->notification_service->notifyMentionsInComment($articleId, $commentId, $comment, $writerId);

            $this->response(['message' => 'success', 'id' => $commentId], 200);
        } catch (Exception $e) {
            log_message('error', 'Comment save error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 댓글을 수정하는 REST API 엔드포인트
     */
    public function index_put($id)
    {
        $currentUserId = get_user_id();

        if (!$currentUserId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->response(['message' => 'invalid id'], 400);
            return;
        }

        $comment = $this->put('comment', true);

        if (!is_string($comment) || trim($comment) === '') {
            $this->response(['message' => 'comment required'], 400);
            return;
        }

        // 댓글 조회
        $existingComment = $this->comment_m->get($id);

        if (!$existingComment) {
            $this->response(['message' => 'comment not found'], 404);
            return;
        }

        // 작성자 확인
        if ($existingComment->writer_id != $currentUserId) {
            $this->response(['message' => 'forbidden'], 403);
            return;
        }

        try {
            $oldContent = $existingComment->comment;
            $result = $this->comment_m->update($id, $comment);
            if ($result) {
                // 댓글 수정 로깅
                $this->activity_logger->logCommentUpdate($id, $oldContent, $comment);
                $this->response(['message' => 'success'], 200);
            } else {
                $this->response(['message' => 'update failed'], 500);
            }
        } catch (Exception $e) {
            log_message('error', 'Comment update error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 댓글을 삭제하는 REST API 엔드포인트
     * 작성자 또는 관리자만 삭제 가능
     */
    public function index_delete($id)
    {
        $currentUserId = get_user_id();

        if (!$currentUserId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->response(['message' => 'invalid id'], 400);
            return;
        }

        // 댓글 조회
        $existingComment = $this->comment_m->get($id);

        if (!$existingComment) {
            $this->response(['message' => 'comment not found'], 404);
            return;
        }

        // 권한 확인: 작성자 또는 관리자
        $isWriter = $existingComment->writer_id == $currentUserId;
        $isAdmin = is_admin();

        if (!$isWriter && !$isAdmin) {
            $this->response(['message' => 'forbidden'], 403);
            return;
        }

        try {
            $articleId = $existingComment->article_id;
            $result = $this->comment_m->delete($id);
            if ($result) {
                // 댓글 삭제 로깅
                $this->activity_logger->logCommentDelete($id, $articleId);
                $this->response(['message' => 'success'], 200);
            } else {
                $this->response(['message' => 'delete failed'], 500);
            }

        } catch (Exception $e) {
            log_message('error', 'Comment delete error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }
}
