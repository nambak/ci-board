<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends RestController
{
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
            ], 422);
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
            ], 401);
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
            ], 200);
        } else {
            // 로그인 실패
            $this->response([
                'success' => false,
                'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
                'errors'  => [
                    'email' => '이메일 또는 비밀번호를 확인해주세요.'
                ]
            ], 401);
        }
    }
}
