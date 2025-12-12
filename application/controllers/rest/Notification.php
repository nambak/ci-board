<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Notification REST API 컨트롤러
 * 알림 관련 REST API 엔드포인트 제공
 */
class Notification extends MY_RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('notification_m');
        $this->load->helper('auth');
    }

    /**
     * 알림 목록 조회 API
     * GET /rest/notification
     * - recent=1 : 최근 5개 (드롭다운용)
     * - page, per_page : 페이지네이션 (전체 목록용)
     */
    public function index_get()
    {
        $userId = get_user_id();

        if (!$userId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        try {
            $recent = $this->get('recent', true);

            if ($recent) {
                // 드롭다운용 최근 알림
                $rows = $this->notification_m->getRecentByUserId($userId, 5);
                $unreadCount = $this->notification_m->countUnreadByUserId($userId);

                $this->response([
                    'rows' => $rows,
                    'unread_count' => $unreadCount
                ], 200);
                return;
            }

            // 전체 목록 페이지네이션
            $page = (int)$this->get('page', true) ?: 1;
            $perPage = (int)$this->get('per_page', true) ?: 20;

            if ($page < 1) {
                $page = 1;
            }
            if ($perPage < 1 || $perPage > 100) {
                $perPage = 20;
            }

            $offset = ($page - 1) * $perPage;

            $result = $this->notification_m->fetchByUserId($userId, $perPage, $offset);
            $totalPages = $result['total'] > 0 ? ceil($result['total'] / $perPage) : 0;

            $this->response([
                'rows' => $result['rows'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'from' => $result['total'] > 0 ? $offset + 1 : 0,
                    'to' => min($offset + $perPage, $result['total'])
                ]
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Notification::index_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 안읽은 알림 수 조회 API
     * GET /rest/notification/unread-count
     */
    public function unread_count_get()
    {
        $userId = get_user_id();

        if (!$userId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        try {
            $count = $this->notification_m->countUnreadByUserId($userId);
            $this->response(['count' => $count], 200);
        } catch (Exception $e) {
            log_message('error', 'Notification::unread_count_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 개별 알림 읽음 처리 API
     * PUT /rest/notification/{id}/read
     */
    public function read_put($id)
    {
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

        try {
            $notification = $this->notification_m->get($id);

            if (!$notification) {
                $this->response(['message' => 'notification not found'], 404);
                return;
            }

            // 본인 알림만 읽음 처리 가능
            if ($notification->user_id != $userId) {
                $this->response(['message' => 'forbidden'], 403);
                return;
            }

            $this->notification_m->markAsRead($id);
            $this->response(['message' => 'success'], 200);

        } catch (Exception $e) {
            log_message('error', 'Notification::read_put error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 모든 알림 읽음 처리 API
     * PUT /rest/notification/read-all
     */
    public function read_all_put()
    {
        $userId = get_user_id();

        if (!$userId) {
            $this->response(['message' => 'unauthorized'], 401);
            return;
        }

        try {
            $count = $this->notification_m->markAllAsRead($userId);
            $this->response([
                'message' => 'success',
                'updated_count' => $count
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Notification::read_all_put error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 알림 삭제 API
     * DELETE /rest/notification/{id}
     */
    public function index_delete($id)
    {
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

        try {
            $notification = $this->notification_m->get($id);

            if (!$notification) {
                $this->response(['message' => 'notification not found'], 404);
                return;
            }

            if ($notification->user_id != $userId) {
                $this->response(['message' => 'forbidden'], 403);
                return;
            }

            $this->notification_m->delete($id);
            $this->response(['message' => 'success'], 200);

        } catch (Exception $e) {
            log_message('error', 'Notification::index_delete error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }
}
