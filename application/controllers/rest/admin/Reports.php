<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Reports extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('report_m');
        $this->load->model('article_m');
        $this->load->model('comment_m');
        $this->load->library('session');
        $this->load->helper('auth');

        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response(['message' => 'forbidden'], 403);
            return;
        }
    }

    /**
     * 신고 목록 조회
     * GET /rest/admin/reports
     *
     * @return void
     */
    public function index_get()
    {
        try {
            if (!is_admin()) {
                $this->response(['message' => 'forbidden'], 403);
                return;
            }

            $page = (int)$this->get('page', true) ?: 1;
            $perPage = (int)$this->get('per_page', true) ?: 10;
            $status = $this->get('status', true) ?: null;
            $targetType = $this->get('target_type', true) ?: null;

            if ($page < 1) {
                $page = 1;
            }

            if ($perPage < 1 || $perPage > 100) {
                $perPage = 10;
            }

            $offset = ($page - 1) * $perPage;

            $result = $this->report_m->getAll($perPage, $offset, $status, $targetType);

            // 대상 정보 조회
            foreach ($result['rows'] as &$report) {
                $report->target_info = $this->getTargetInfo($report->target_type, $report->target_id);
                $report->reason_label = Report_m::$allowedReasons[$report->reason] ?? $report->reason;
            }

            $totalPages = ceil($result['total'] / $perPage);

            // 상태별 통계
            $statusCounts = $this->report_m->countByStatus();

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
                'status_counts' => $statusCounts
            ];

            $this->response($response, 200);
        } catch (Exception $e) {
            log_message('error', 'AdminReports::index_get error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 신고 상세 조회
     * GET /rest/admin/reports/{id}
     *
     * @param int $id
     * @return void
     */
    public function index_get_id($id)
    {
        try {
            if (!is_admin()) {
                $this->response(['message' => 'forbidden'], 403);
                return;
            }

            $id = (int)$id;

            if ($id <= 0) {
                $this->response(['message' => 'invalid id'], 400);
                return;
            }

            $report = $this->report_m->getWithDetails($id);

            if (!$report) {
                $this->response(['message' => 'report not found'], 404);
                return;
            }

            $report->target_info = $this->getTargetInfo($report->target_type, $report->target_id);
            $report->reason_label = Report_m::$allowedReasons[$report->reason] ?? $report->reason;

            $this->response($report, 200);
        } catch (Exception $e) {
            log_message('error', 'AdminReports::index_get_id error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 신고 처리 (상태 변경)
     * PUT /rest/admin/reports/{id}
     *
     * @param int $id
     * @return void
     */
    public function index_put($id)
    {
        try {
            if (!is_admin()) {
                $this->response(['message' => 'forbidden'], 403);
                return;
            }

            $id = (int)$id;

            if ($id <= 0) {
                $this->response(['message' => 'invalid id'], 400);
                return;
            }

            $report = $this->report_m->get($id);

            if (!$report) {
                $this->response(['message' => 'report not found'], 404);
                return;
            }

            $status = $this->put('status', true);

            $allowedStatuses = [
                Report_m::STATUS_PENDING,
                Report_m::STATUS_PROCESSING,
                Report_m::STATUS_COMPLETED,
                Report_m::STATUS_REJECTED
            ];

            if (!in_array($status, $allowedStatuses)) {
                $this->response(['message' => 'invalid status'], 400);
                return;
            }

            $adminId = get_user_id();

            if (!$this->report_m->updateStatus($id, $status, $adminId)) {
                $this->response(['message' => '상태 변경에 실패했습니다.'], 500);
                return;
            }

            $this->response([
                'message' => '신고 상태가 변경되었습니다.',
                'status' => $status
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'AdminReports::index_put error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 대상 정보 조회
     *
     * @param string $targetType
     * @param int $targetId
     * @return array|null
     */
    private function getTargetInfo($targetType, $targetId)
    {
        if ($targetType === Report_m::TYPE_ARTICLE) {
            $article = $this->article_m->get($targetId);
            if ($article) {
                return [
                    'type' => 'article',
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => mb_substr($article->content, 0, 200) . (mb_strlen($article->content) > 200 ? '...' : ''),
                    'author_id' => $article->user_id,
                    'created_at' => $article->created_at,
                    'url' => '/article/' . $article->id
                ];
            }
        } elseif ($targetType === Report_m::TYPE_COMMENT) {
            $comment = $this->comment_m->get($targetId);
            if ($comment) {
                return [
                    'type' => 'comment',
                    'id' => $comment->id,
                    'content' => $comment->comment,
                    'author_id' => $comment->writer_id,
                    'article_id' => $comment->article_id,
                    'created_at' => $comment->created_at,
                    'url' => '/article/' . $comment->article_id . '#comment-' . $comment->id
                ];
            }
        }

        return null;
    }
}
