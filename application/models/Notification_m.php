<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Notification_m 모델
 * 사용자 알림 관리를 위한 데이터베이스 작업 처리
 */
class Notification_m extends CI_Model
{
    /**
     * 알림 생성
     *
     * @param int $userId 수신자 ID
     * @param string $type 알림 유형 (comment, reply, like, mention)
     * @param string $title 알림 제목
     * @param string $message 알림 메시지
     * @param string|null $referenceType 참조 타입 (article, comment)
     * @param int|null $referenceId 참조 ID
     * @param int|null $actorId 발생시킨 사용자 ID
     * @return int 생성된 알림 ID
     */
    public function create($userId, $type, $title, $message, $referenceType = null, $referenceId = null, $actorId = null)
    {
        $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'actor_id' => $actorId
        ]);

        return $this->db->insert_id();
    }

    /**
     * ID로 알림 조회
     *
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        return $this->db->get_where('notifications', ['id' => $id])->row();
    }

    /**
     * 알림 존재 여부 확인
     *
     * @param int $id
     * @return bool
     */
    public function exists($id)
    {
        $this->db->where('id', $id);
        return $this->db->count_all_results('notifications') > 0;
    }

    /**
     * 사용자별 알림 목록 조회 (페이지네이션)
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array ['rows' => [...], 'total' => int]
     */
    public function fetchByUserId($userId, $limit = 20, $offset = 0)
    {
        // 총 개수 조회
        $this->db->where('user_id', $userId);
        $total = $this->db->count_all_results('notifications');

        // 데이터 조회 (actor 정보 JOIN)
        $this->db->select('notifications.*, users.name as actor_name, users.profile_image as actor_profile_image');
        $this->db->from('notifications');
        $this->db->join('users', 'users.id = notifications.actor_id', 'left');
        $this->db->where('notifications.user_id', $userId);
        $this->db->order_by('notifications.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $rows = $this->db->get()->result();

        return [
            'rows' => $rows,
            'total' => $total
        ];
    }

    /**
     * 사용자별 안읽은 알림 수 조회
     *
     * @param int $userId
     * @return int
     */
    public function countUnreadByUserId($userId)
    {
        $this->db->where('user_id', $userId);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results('notifications');
    }

    /**
     * 최근 알림 조회 (드롭다운용)
     *
     * @param int $userId
     * @param int $limit (기본 5개)
     * @return array
     */
    public function getRecentByUserId($userId, $limit = 5)
    {
        $this->db->select('notifications.*, users.name as actor_name, users.profile_image as actor_profile_image');
        $this->db->from('notifications');
        $this->db->join('users', 'users.id = notifications.actor_id', 'left');
        $this->db->where('notifications.user_id', $userId);
        $this->db->order_by('notifications.created_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    /**
     * 알림 읽음 처리
     *
     * @param int $id
     * @return bool
     */
    public function markAsRead($id)
    {
        try {
            $this->db->where('id', $id);
            $this->db->update('notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
            return $this->db->affected_rows() > 0;
        } catch (Exception $e) {
            log_message('error', 'Notification markAsRead error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 사용자의 모든 알림 읽음 처리
     *
     * @param int $userId
     * @return int 업데이트된 행 수
     */
    public function markAllAsRead($userId)
    {
        try {
            $this->db->where('user_id', $userId);
            $this->db->where('is_read', 0);
            $this->db->update('notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
            return $this->db->affected_rows();
        } catch (Exception $e) {
            log_message('error', 'Notification markAllAsRead error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 알림 삭제
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id);
            $this->db->delete('notifications');
            return $this->db->affected_rows() > 0;
        } catch (Exception $e) {
            log_message('error', 'Notification delete error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 오래된 알림 삭제 (기본 30일 이상)
     *
     * @param int $days
     * @return int 삭제된 행 수
     */
    public function deleteOldNotifications($days = 30)
    {
        $this->db->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        $this->db->delete('notifications');
        return $this->db->affected_rows();
    }
}
