<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Activity_log extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('Activity_log_m');
        $this->load->helper('auth');
    }

    /**
     * 활동 로그 목록 조회 (관리자 전용)
     * GET /rest/activity_log
     */
    public function index_get()
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
            // bootstrap-table 페이지네이션 파라미터
            $limit = (int)$this->get('limit') ?: 20;
            $offset = (int)$this->get('offset') ?: 0;

            // 정렬 필드 화이트리스트 검증
            $allowedSortFields = ['id', 'created_at', 'action', 'user_id', 'ip_address'];
            $sort = $this->get('sort') ?: 'created_at';
            if (!in_array($sort, $allowedSortFields)) {
                $sort = 'created_at';
            }

            // 정렬 방향 검증
            $order = strtoupper($this->get('order') ?: 'DESC');
            if (!in_array($order, ['ASC', 'DESC'])) {
                $order = 'DESC';
            }

            // limit 범위 제한
            $limit = max(1, min(100, $limit));

            // 필터 파라미터
            $filters = [];

            if ($this->get('user_id')) {
                $filters['user_id'] = (int)$this->get('user_id');
            }

            if ($this->get('action')) {
                $filters['action'] = $this->get('action', true);
            }

            if ($this->get('target_type')) {
                $filters['target_type'] = $this->get('target_type', true);
            }

            if ($this->get('ip_address')) {
                $filters['ip_address'] = $this->get('ip_address', true);
            }

            if ($this->get('date_from')) {
                $filters['date_from'] = $this->get('date_from', true);
            }

            if ($this->get('date_to')) {
                $filters['date_to'] = $this->get('date_to', true);
            }

            if ($this->get('search')) {
                $filters['search'] = $this->get('search', true);
            }

            // 데이터 조회
            $logs = $this->Activity_log_m->getList($filters, $limit, $offset, $sort, $order);
            $total = $this->Activity_log_m->countFiltered($filters);

            // 응답 데이터 가공
            $rows = array_map(function ($log) {
                return [
                    'id'           => $log->id,
                    'user_id'      => $log->user_id,
                    'user_name'    => $log->user_name ?? '비회원',
                    'user_email'   => $log->user_email ?? '-',
                    'action'       => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'target_type'  => $log->target_type,
                    'target_id'    => $log->target_id,
                    'description'  => $log->description,
                    'ip_address'   => $log->ip_address,
                    'user_agent'   => $log->user_agent,
                    'created_at'   => $log->created_at,
                    'old_data'     => $log->old_data,
                    'new_data'     => $log->new_data,
                ];
            }, $logs);

            // bootstrap-table 형식 응답
            $this->response([
                'rows'  => $rows,
                'total' => $total
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Activity_log::index_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '활동 로그를 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 활동 로그 통계 조회
     * GET /rest/activity_log/stats
     */
    public function stats_get()
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
            $dateFrom = $this->get('date_from', true);
            $dateTo = $this->get('date_to', true);

            $actionStats = $this->Activity_log_m->getActionStats($dateFrom, $dateTo);
            $dailyStats = $this->Activity_log_m->getDailyStats(30);
            $total = $this->Activity_log_m->count();

            // 액션 통계에 라벨 추가
            $actionStatsWithLabel = array_map(function ($stat) {
                return [
                    'action' => $stat->action,
                    'label'  => $this->getActionLabel($stat->action),
                    'count'  => (int)$stat->count
                ];
            }, $actionStats);

            $this->response([
                'success' => true,
                'data'    => [
                    'total'        => $total,
                    'action_stats' => $actionStatsWithLabel,
                    'daily_stats'  => $dailyStats
                ]
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Activity_log::stats_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '통계 정보를 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 특정 사용자의 활동 로그 조회
     * GET /rest/activity_log/user/{user_id}
     */
    public function user_get($userId = null)
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        if (!$userId || (int)$userId <= 0) {
            $this->response([
                'success' => false,
                'message' => '사용자 ID가 필요합니다.'
            ], self::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $limit = (int)$this->get('limit') ?: 50;
            $logs = $this->Activity_log_m->getByUser((int)$userId, $limit);

            $rows = array_map(function ($log) {
                return [
                    'id'           => $log->id,
                    'action'       => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'target_type'  => $log->target_type,
                    'target_id'    => $log->target_id,
                    'description'  => $log->description,
                    'ip_address'   => $log->ip_address,
                    'created_at'   => $log->created_at
                ];
            }, $logs);

            $this->response([
                'success' => true,
                'rows'    => $rows
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Activity_log::user_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '사용자 활동 로그를 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 특정 IP의 활동 로그 조회
     * GET /rest/activity_log/ip/{ip_address}
     */
    public function ip_get($ipAddress = null)
    {
        // 관리자 권한 체크
        if (!is_admin()) {
            $this->response([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ], self::HTTP_FORBIDDEN);
            return;
        }

        if (!$ipAddress) {
            $this->response([
                'success' => false,
                'message' => 'IP 주소가 필요합니다.'
            ], self::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $limit = (int)$this->get('limit') ?: 50;
            $logs = $this->Activity_log_m->getByIp($ipAddress, $limit);

            $rows = array_map(function ($log) {
                return [
                    'id'           => $log->id,
                    'user_id'      => $log->user_id,
                    'user_name'    => $log->user_name ?? '비회원',
                    'action'       => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'target_type'  => $log->target_type,
                    'target_id'    => $log->target_id,
                    'description'  => $log->description,
                    'created_at'   => $log->created_at
                ];
            }, $logs);

            $this->response([
                'success' => true,
                'rows'    => $rows
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Activity_log::ip_get error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => 'IP 활동 로그를 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 액션 타입에 대한 한글 라벨 반환
     *
     * @param string $action
     * @return string
     */
    private function getActionLabel($action)
    {
        $labels = [
            'login'            => '로그인',
            'logout'           => '로그아웃',
            'login_failed'     => '로그인 실패',
            'article_create'   => '게시글 작성',
            'article_update'   => '게시글 수정',
            'article_delete'   => '게시글 삭제',
            'comment_create'   => '댓글 작성',
            'comment_update'   => '댓글 수정',
            'comment_delete'   => '댓글 삭제',
            'password_change'  => '비밀번호 변경',
            'profile_update'   => '프로필 수정',
            'user_delete'      => '사용자 탈퇴',
            'user_role_change' => '권한 변경',
            'board_create'     => '게시판 생성',
            'board_update'     => '게시판 수정',
            'board_delete'     => '게시판 삭제'
        ];

        return $labels[$action] ?? $action;
    }
}
