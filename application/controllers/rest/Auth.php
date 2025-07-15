<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends RestController
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->helper(['security', 'string']);

        $this->load->model('user_m');
    }

    /**
     * 로그인 처리
     */
    public function login_post()
    {
        // 폼 검증 규칙 설정
        $this->form_validation->set_rules('email', '이메일', 'required|valid_email');
        $this->form_validation->set_rules('password', '비밀번호', 'required|min_length[6]');

        if (!$this->form_validation->run()) {
            $errors = [];
            if (form_error('email')) {
                $errors['email'] = strip_tags(form_error('email'));
            }
            if (form_error('password')) {
                $errors['password'] = strip_tags(form_error('password'));
            }

            $this->response([
                'success' => false,
                'message' => '입력값을 확인해주세요.',
                'errors'  => $errors
            ], self::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = $this->post('email', true);
        $password = $this->post('password', true);
        $remember = $this->post('remember', true);

        // 사용자 확인
        $user = $this->user_m->get_user_by_email($email);

        // 사용자가 존재하지 않는 경우
        if (!$user) {
            $this->response([
                'success' => false,
                'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
                'errors'  => [
                    'email' => '등록되지 않은 이메일입니다.'
                ]
            ], self::HTTP_UNAUTHORIZED);
        }

        // 로그인 검증
        if (password_verify($password, $user['password'])) {
            // 로그인 성공 - 세션 설정
            $sessionData = [
                'user_id'    => $user['id'],
                'user_email' => $user['email'],
                'user_name'  => $user['name'],
                'logged_in'  => true
            ];

            $this->session->set_userdata($sessionData);

            // Remember Token
            if ($remember) {
                $token = bin2hex(random_bytes(32));

                $this->input->set_cookie([
                    'name'     => 'remember_token',
                    'value'    => $token,
                    'expire'   => 86400 * 30, // 30일
                    'secure'   => is_https(),
                    'httponly' => true
                ]);
            }

            $this->response([
                'success' => true,
                'message' => '로그인되었습니다.',
                'user'    => [
                    'id'    => $user['id'],
                    'email' => $user['email'],
                    'name'  => $user['name']
                ]
            ], self::HTTP_OK);
        } else {
            // 로그인 실패
            $this->response([
                'success' => false,
                'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
                'errors'  => [
                    'email' => '이메일 또는 비밀번호를 확인해주세요.'
                ]
            ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * 이메일 중복 확인
     */
    public function check_email_get()
    {
        try {
            $email = $this->get('email');

            // 이메일 파라미터 검증
            if (empty($email)) {
                $this->response([
                    'success' => false,
                    'message' => '이메일을 입력해주세요.',
                    'exists'  => false
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 이메일 형식 검증
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->response([
                    'success' => false,
                    'message' => '올바른 이메일 형식이 아닙니다.',
                    'exists'  => false
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 이메일 중복 확인
            $exists = $this->user_m->check_email_exists($email);

            $this->response([
                'success' => true,
                'exists'  => $exists,
                'message' => $exists ? '이미 사용 중인 이메일입니다.' : '사용 가능한 이메일입니다.'
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Email check error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다.',
                'exists'  => false
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 회원가입 처리
     */
    public function register_post()
    {
        try {
            // 입력 데이터 받기
            $inputData = [
                'name'     => $this->post('name', true),
                'email'    => $this->post('email', true),
                'password' => trim($this->post('password', true)),
            ];

            // 유효성 검사 규칙 설정
            $this->form_validation->set_rules('name', '이름', 'required|trim|min_length[2]|max_length[50]');
            $this->form_validation->set_rules('email', '이메일', 'required|trim|valid_email|max_length[100]');
            $this->form_validation->set_rules('password', '비밀번호', 'required|min_length[6]|max_length[255]');

            // 유효성 검사 실행
            if (!$this->form_validation->run()) {
                $errors = [];

                // 각 필드별 에러 메시지 수집
                if (form_error('name')) {
                    $errors['name'] = strip_tags(form_error('name'));
                }
                if (form_error('email')) {
                    $errors['email'] = strip_tags(form_error('email'));
                }
                if (form_error('password')) {
                    $errors['password'] = strip_tags(form_error('password'));
                }

                $this->response([
                    'success' => false,
                    'message' => '입력 정보를 확인해주세요.',
                    'errors'  => $errors
                ], self::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 이메일 중복 확인
            if ($this->user_m->check_email_exists($inputData['email'])) {
                $this->response([
                    'success' => false,
                    'message' => '이미 사용 중인 이메일입니다.',
                    'errors'  => ['email' => '이미 사용 중인 이메일입니다.']
                ], self::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 회원가입 데이터 준비
            $userData = [
                'name'       => trim($inputData['name']),
                'email'      => $inputData['email'],
                'password'   => $inputData['password'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];


            // 사용자 생성
            $result = $this->user_m->create_user($userData);

            if (!$result['success']) {
                $this->response([
                    'success' => false,
                    'message' => $result['message']
                ], self::HTTP_INTERNAL_ERROR);
            }

            // 성공 응답
            $this->response([
                'success' => true,
                'message' => '회원가입이 완료되었습니다.',
                'data'    => [
                    'user_id' => $result['userId'],
                    'name'    => $userData['name'],
                    'email'   => $userData['email']
                ]
            ], self::HTTP_CREATED);

        } catch (Exception $e) {
            log_message('error', 'Register error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }
}