<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Password_reset_m extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 비밀번호 재설정 토큰 생성
     *
     * @param int $user_id 사용자 ID
     * @return string 생성된 토큰
     */
    public function create_token($user_id)
    {
        try {
            // 기존 미사용 토큰 삭제
            $this->delete_user_tokens($user_id);

            // 랜덤 64자 토큰 생성
            $token = bin2hex(random_bytes(32));

            // 24시간 후 만료
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $data = [
                'user_id' => $user_id,
                'token' => $token,
                'expires_at' => $expires_at,
                'used' => 0
            ];

            $this->db->insert('password_resets', $data);

            if ($this->db->affected_rows() > 0) {
                return $token;
            }

            throw new Exception('Failed to create password reset token');

        } catch (Exception $e) {
            log_message('error', 'Password reset token creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 토큰 유효성 검증
     *
     * @param string $token 재설정 토큰
     * @return object|false 토큰 정보 또는 false
     */
    public function verify_token($token)
    {
        try {
            $this->db->where('token', $token);
            $this->db->where('used', 0);
            $this->db->where('expires_at >', date('Y-m-d H:i:s'));
            $query = $this->db->get('password_resets');

            if ($query->num_rows() > 0) {
                return $query->row();
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'Token verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 토큰을 사용됨으로 표시
     *
     * @param string $token 재설정 토큰
     * @return bool 성공 여부
     */
    public function mark_as_used($token)
    {
        try {
            $data = ['used' => 1];
            $this->db->where('token', $token);
            $this->db->update('password_resets', $data);

            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error', 'Token mark as used error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 사용자의 모든 토큰 삭제
     *
     * @param int $user_id 사용자 ID
     * @return bool 성공 여부
     */
    public function delete_user_tokens($user_id)
    {
        try {
            $this->db->where('user_id', $user_id);
            $this->db->delete('password_resets');

            return true;

        } catch (Exception $e) {
            log_message('error', 'Delete user tokens error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 만료된 토큰 정리
     *
     * @return int 삭제된 레코드 수
     */
    public function cleanup_expired()
    {
        try {
            $this->db->where('expires_at <', date('Y-m-d H:i:s'));
            $this->db->or_where('used', 1);
            $this->db->delete('password_resets');

            return $this->db->affected_rows();

        } catch (Exception $e) {
            log_message('error', 'Cleanup expired tokens error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 토큰으로 사용자 ID 가져오기
     *
     * @param string $token 재설정 토큰
     * @return int|false 사용자 ID 또는 false
     */
    public function get_user_id_by_token($token)
    {
        $token_data = $this->verify_token($token);

        if ($token_data) {
            return $token_data->user_id;
        }

        return false;
    }
}