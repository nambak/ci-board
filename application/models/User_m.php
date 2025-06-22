<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_m extends CI_Model
{
    private $table = 'users';


    /**
     * 사용자 조회
     *
     * @param $email
     * @return mixed
     */
    public function get_user_by_email($email)
    {
        $query = $this->db->get_where($this->table, ['email' => strtolower(trim($email))]);

        return $query->row_array();
    }

    /**
     * 이메일 중복 확인
     *
     * @param string $email
     * @return bool
     */
    public function check_email_exists($email)
    {
        $this->db->where('email', strtolower(trim($email)));
        $query = $this->db->get($this->table);

        return $query->num_rows() > 0;
    }

    /**
     * 사용자 생성 (트랜잭션)
     *
     * @param array $data
     * @return array
     */
    public function create_user($data)
    {
        try {
            // 데이터 유효성 검사
            if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
                return [
                    'success' => false,
                    'message' => '필수 정보가 누락되었습니다',
                    'userId'  => null
                ];
            }

            // 이메일 중복 확인
            if ($this->check_email_exists($data['email'])) {
                return [
                    'success' => false,
                    'message' => '이미 사용 중인 이메일입니다',
                    'userId'  => null
                ];
            }

            // 트랜잭션 시작
            $this->db->trans_start();

            // 사용자 데이터 준비
            $userData = [
                'name'       => trim($data['name']),
                'email'      => strtolower(trim($data['email'])),
                'password'   => password_hash(trim($data['password']), PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // 사용자 데이터 삽입
            $this->db->insert($this->table, $userData);

            $user_id = $this->db->insert_id();

            // 트랜잭션 완료
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                return [
                    'success' => false,
                    'message' => '사용자 정보 저장에 실패했습니다',
                    'userId'  => null
                ];
            }

            return [
                'success' => true,
                'message' => '회원가입이 완료되었습니다.',
                'userId'  => $user_id
            ];
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $this->db->trans_rollback();

            log_message('error', 'User registration error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => '회원가입 처리 중 시스템 오류가 발생했습니다.',
                'userId' => null
            ];
        }
    }
}
