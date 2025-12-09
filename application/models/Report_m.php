<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_m extends CI_Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    const TYPE_ARTICLE = 'article';
    const TYPE_COMMENT = 'comment';

    const REASON_SPAM = 'spam';
    const REASON_ABUSE = 'abuse';
    const REASON_ADULT = 'adult';
    const REASON_COPYRIGHT = 'copyright';
    const REASON_OTHER = 'other';

    /**
     * 허용된 신고 사유 목록
     */
    public static $allowedReasons = [
        self::REASON_SPAM => '스팸/광고',
        self::REASON_ABUSE => '욕설/비방',
        self::REASON_ADULT => '음란물',
        self::REASON_COPYRIGHT => '저작권 침해',
        self::REASON_OTHER => '기타'
    ];

    /**
     * 신고 추가
     *
     * @param int $reporterId
     * @param string $targetType
     * @param int $targetId
     * @param string $reason
     * @param string|null $detail
     * @return int|bool 생성된 ID 또는 실패 시 false
     */
    public function add($reporterId, $targetType, $targetId, $reason, $detail = null)
    {
        $this->db->insert('reports', [
            'reporter_id' => $reporterId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
            'detail' => $detail
        ]);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * 신고 ID로 조회
     *
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        return $this->db->get_where('reports', ['id' => $id])->row();
    }

    /**
     * 사용자가 해당 대상을 이미 신고했는지 확인
     *
     * @param int $reporterId
     * @param string $targetType
     * @param int $targetId
     * @return bool
     */
    public function hasReported($reporterId, $targetType, $targetId)
    {
        $this->db->where('reporter_id', $reporterId);
        $this->db->where('target_type', $targetType);
        $this->db->where('target_id', $targetId);

        return $this->db->count_all_results('reports') > 0;
    }

    /**
     * 신고 목록 조회 (관리자용)
     *
     * @param int $limit
     * @param int $offset
     * @param string|null $status
     * @param string|null $targetType
     * @return array ['rows' => [], 'total' => int]
     */
    public function getAll($limit = 10, $offset = 0, $status = null, $targetType = null)
    {
        // 총 개수 조회
        if ($status) {
            $this->db->where('r.status', $status);
        }
        if ($targetType) {
            $this->db->where('r.target_type', $targetType);
        }

        $this->db->from('reports r');
        $total = $this->db->count_all_results();

        // 데이터 조회
        $this->db->select('r.*, u.name as reporter_name, p.name as processor_name');
        $this->db->from('reports r');
        $this->db->join('users u', 'u.id = r.reporter_id', 'left');
        $this->db->join('users p', 'p.id = r.processed_by', 'left');

        if ($status) {
            $this->db->where('r.status', $status);
        }
        if ($targetType) {
            $this->db->where('r.target_type', $targetType);
        }

        $this->db->order_by('r.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $rows = $this->db->get()->result();

        return [
            'rows' => $rows,
            'total' => $total
        ];
    }

    /**
     * 신고 상태 업데이트
     *
     * @param int $id
     * @param string $status
     * @param int $processedBy
     * @return bool
     */
    public function updateStatus($id, $status, $processedBy)
    {
        $this->db->where('id', $id);
        $this->db->update('reports', [
            'status' => $status,
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $processedBy
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * 특정 대상의 신고 수 조회
     *
     * @param string $targetType
     * @param int $targetId
     * @return int
     */
    public function countByTarget($targetType, $targetId)
    {
        $this->db->where('target_type', $targetType);
        $this->db->where('target_id', $targetId);

        return $this->db->count_all_results('reports');
    }

    /**
     * 상태별 신고 수 조회
     *
     * @return array
     */
    public function countByStatus()
    {
        $this->db->select('status, COUNT(*) as count');
        $this->db->from('reports');
        $this->db->group_by('status');

        $result = $this->db->get()->result();
        $counts = [
            self::STATUS_PENDING => 0,
            self::STATUS_PROCESSING => 0,
            self::STATUS_COMPLETED => 0,
            self::STATUS_REJECTED => 0
        ];

        foreach ($result as $row) {
            $counts[$row->status] = (int)$row->count;
        }

        return $counts;
    }

    /**
     * 신고 상세 정보 조회 (대상 정보 포함)
     *
     * @param int $id
     * @return object|null
     */
    public function getWithDetails($id)
    {
        $this->db->select('r.*, u.name as reporter_name, u.email as reporter_email, p.name as processor_name');
        $this->db->from('reports r');
        $this->db->join('users u', 'u.id = r.reporter_id', 'left');
        $this->db->join('users p', 'p.id = r.processed_by', 'left');
        $this->db->where('r.id', $id);

        return $this->db->get()->row();
    }
}
