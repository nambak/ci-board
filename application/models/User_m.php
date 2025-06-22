<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_m extends CI_Model
{
    private $table = 'users';


    /**
     * 이메일을 기준으로 사용자를 조회하여 반환합니다.
     *
     * 이메일 입력값은 공백을 제거하고 소문자로 변환하여 검색합니다.
     *
     * @param string $email 조회할 사용자의 이메일 주소.
     * @return array|null 해당 이메일의 사용자 정보(연관 배열) 또는 사용자가 없을 경우 null.
     */
    public function get_user_by_email($email)
    {
        $query = $this->db->get_where($this->table, ['email' => strtolower(trim($email))]);

        return $query->row_array();
    }

    /**
     * 주어진 이메일이 이미 존재하는지 확인합니다.
     *
     * 이메일 입력값은 공백을 제거하고 소문자로 변환하여 비교합니다.
     *
     * @param string $email 확인할 이메일 주소
     * @return bool 이메일이 존재하면 true, 존재하지 않으면 false를 반환합니다.
     */
    public function check_email_exists($email)
    {
        $this->db->where('email', strtolower(trim($email)));
        $query = $this->db->get($this->table);

        return $query->num_rows() > 0;
    }

    /**
     * 새로운 사용자를 데이터베이스에 등록합니다.
     *
     * 필수 정보(이메일, 비밀번호, 이름)를 확인하고, 이메일 중복 여부를 검사한 후 트랜잭션 내에서 사용자 정보를 저장합니다.
     * 성공 시 회원가입 완료 메시지와 생성된 사용자 ID를 반환하며, 실패 시 원인에 따른 메시지와 함께 실패 상태를 반환합니다.
     *
     * @param array $data 사용자 등록에 필요한 정보 배열
     * @return array 성공 여부, 메시지, 생성된 사용자 ID를 포함한 결과 배열
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
