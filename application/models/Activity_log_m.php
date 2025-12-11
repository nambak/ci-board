<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity_log_m extends CI_Model
{
    const TABLE = 'activity_logs';

    // 액션 타입 상수
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_ARTICLE_CREATE = 'article_create';
    const ACTION_ARTICLE_UPDATE = 'article_update';
    const ACTION_ARTICLE_DELETE = 'article_delete';
    const ACTION_COMMENT_CREATE = 'comment_create';
    const ACTION_COMMENT_UPDATE = 'comment_update';
    const ACTION_COMMENT_DELETE = 'comment_delete';
    const ACTION_PASSWORD_CHANGE = 'password_change';
    const ACTION_PROFILE_UPDATE = 'profile_update';
    const ACTION_USER_DELETE = 'user_delete';
    const ACTION_USER_ROLE_CHANGE = 'user_role_change';
    const ACTION_BOARD_CREATE = 'board_create';
    const ACTION_BOARD_UPDATE = 'board_update';
    const ACTION_BOARD_DELETE = 'board_delete';

    // 대상 타입 상수
    const TARGET_USER = 'user';
    const TARGET_ARTICLE = 'article';
    const TARGET_COMMENT = 'comment';
    const TARGET_BOARD = 'board';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 활동 로그 기록
     *
     * @param string $action 액션 타입
     * @param string|null $targetType 대상 타입
     * @param int|null $targetId 대상 ID
     * @param string|null $description 설명
     * @param array|null $oldData 변경 전 데이터
     * @param array|null $newData 변경 후 데이터
     * @param int|null $userId 사용자 ID (null이면 세션에서 가져옴)
     * @return int|bool 삽입된 ID 또는 false
     */
    public function log($action, $targetType = null, $targetId = null, $description = null, $oldData = null, $newData = null, $userId = null)
    {
        try {
            // 사용자 ID가 지정되지 않으면 세션에서 가져옴
            if ($userId === null) {
                $userId = $this->session->userdata('user_id');
            }

            $data = [
                'user_id' => $userId ?: null,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'description' => $description,
                'old_data' => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                'new_data' => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert(self::TABLE, $data);

            if ($this->db->affected_rows() > 0) {
                return $this->db->insert_id();
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'Activity log error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ID로 로그 조회
     *
     * @param int $id 로그 ID
     * @return object|null
     */
    public function get($id)
    {
        $query = $this->db->get_where(self::TABLE, ['id' => $id]);
        return $query->row();
    }

    /**
     * 로그 목록 조회 (필터링 및 페이징 지원)
     *
     * @param array $filters 필터 조건
     * @param int $limit 조회 개수
     * @param int $offset 시작 위치
     * @param string $orderBy 정렬 기준
     * @param string $orderDir 정렬 방향
     * @return array
     */
    public function getList($filters = [], $limit = 20, $offset = 0, $orderBy = 'created_at', $orderDir = 'DESC')
    {
        $this->db->select('activity_logs.*, users.name as user_name, users.email as user_email');
        $this->db->from(self::TABLE);
        $this->db->join('users', 'users.id = activity_logs.user_id', 'left');

        // 필터 적용
        $this->applyFilters($filters);

        // 정렬
        $allowedColumns = ['id', 'user_id', 'action', 'target_type', 'ip_address', 'created_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->order_by('activity_logs.' . $orderBy, $orderDir);

        // 페이징
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result();
    }

    /**
     * 필터 조건에 맞는 로그 개수 조회
     *
     * @param array $filters 필터 조건
     * @return int
     */
    public function countFiltered($filters = [])
    {
        $this->db->from(self::TABLE);
        $this->applyFilters($filters);
        return $this->db->count_all_results();
    }

    /**
     * 필터 조건 적용
     *
     * @param array $filters
     */
    private function applyFilters($filters)
    {
        if (!empty($filters['user_id'])) {
            $this->db->where('activity_logs.user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $this->db->where('activity_logs.action', $filters['action']);
        }

        if (!empty($filters['target_type'])) {
            $this->db->where('activity_logs.target_type', $filters['target_type']);
        }

        if (!empty($filters['target_id'])) {
            $this->db->where('activity_logs.target_id', $filters['target_id']);
        }

        if (!empty($filters['ip_address'])) {
            $this->db->where('activity_logs.ip_address', $filters['ip_address']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where('activity_logs.created_at >=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $this->db->where('activity_logs.created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('activity_logs.description', $filters['search']);
            $this->db->or_like('activity_logs.ip_address', $filters['search']);
            $this->db->group_end();
        }
    }

    /**
     * 사용자별 활동 내역 조회
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getByUser($userId, $limit = 50)
    {
        return $this->getList(['user_id' => $userId], $limit);
    }

    /**
     * IP별 활동 내역 조회
     *
     * @param string $ipAddress
     * @param int $limit
     * @return array
     */
    public function getByIp($ipAddress, $limit = 50)
    {
        return $this->getList(['ip_address' => $ipAddress], $limit);
    }

    /**
     * 특정 대상의 활동 내역 조회
     *
     * @param string $targetType
     * @param int $targetId
     * @param int $limit
     * @return array
     */
    public function getByTarget($targetType, $targetId, $limit = 50)
    {
        return $this->getList([
            'target_type' => $targetType,
            'target_id' => $targetId
        ], $limit);
    }

    /**
     * 오래된 로그 삭제 (보관 정책)
     *
     * @param int $days 보관 일수 (기본값: 90일)
     * @return int 삭제된 행 수
     */
    public function deleteOldLogs($days = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->db->where('created_at <', $cutoffDate);
        $this->db->delete(self::TABLE);

        return $this->db->affected_rows();
    }

    /**
     * 의심스러운 활동 감지 (같은 IP에서 로그인 실패 횟수)
     *
     * @param string $ipAddress IP 주소
     * @param int $minutes 시간 범위 (분)
     * @param int $threshold 임계값
     * @return bool
     */
    public function detectSuspiciousActivity($ipAddress, $minutes = 30, $threshold = 5)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $this->db->where('ip_address', $ipAddress);
        $this->db->where('action', self::ACTION_LOGIN_FAILED);
        $this->db->where('created_at >=', $since);
        $count = $this->db->count_all_results(self::TABLE);

        return $count >= $threshold;
    }

    /**
     * 로그인 실패 횟수 조회
     *
     * @param string $ipAddress IP 주소
     * @param int $minutes 시간 범위 (분)
     * @return int
     */
    public function getLoginFailureCount($ipAddress, $minutes = 30)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $this->db->where('ip_address', $ipAddress);
        $this->db->where('action', self::ACTION_LOGIN_FAILED);
        $this->db->where('created_at >=', $since);

        return $this->db->count_all_results(self::TABLE);
    }

    /**
     * 액션별 통계 조회
     *
     * @param string|null $dateFrom 시작일
     * @param string|null $dateTo 종료일
     * @return array
     */
    public function getActionStats($dateFrom = null, $dateTo = null)
    {
        $this->db->select('action, COUNT(*) as count');
        $this->db->from(self::TABLE);

        if ($dateFrom) {
            $this->db->where('created_at >=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $this->db->where('created_at <=', $dateTo . ' 23:59:59');
        }

        $this->db->group_by('action');
        $this->db->order_by('count', 'DESC');

        $query = $this->db->get();
        return $query->result();
    }

    /**
     * 일별 활동 통계 조회
     *
     * @param int $days 최근 일수 (기본값: 30일)
     * @return array
     */
    public function getDailyStats($days = 30)
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));

        $this->db->select('DATE(created_at) as date, COUNT(*) as count');
        $this->db->from(self::TABLE);
        $this->db->where('created_at >=', $since . ' 00:00:00');
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }

    /**
     * 전체 로그 수 조회
     *
     * @return int
     */
    public function count()
    {
        return $this->db->count_all(self::TABLE);
    }
}
