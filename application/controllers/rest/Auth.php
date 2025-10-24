<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends RestController
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_TOO_MANY_REQUESTS = 429;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->library('rate_limiter');
        $this->load->library('simple_captcha');
        $this->load->helper(['security', 'string', 'signup_security']);

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
        $user = $this->user_m->get_by_email($email);

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
        if (password_verify($password, $user->password)) {
            // 로그인 성공 - 세션 설정
            $sessionData = [
                'user_id'    => $user->id,
                'user_email' => $user->email,
                'user_name'  => $user->name,
                'logged_in'  => true
            ];

            $this->session->set_userdata($sessionData);

            // Remember Token
            if ($remember) {
                $token = bin2hex(random_bytes(32));

                // 토큰을 데이터베이스에 저장
                $this->user_m->save_remember_token($user->id, $token);

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
                    'id'    => $user->id,
                    'email' => $user->email,
                    'name'  => $user->name,
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
     * 이메일 중복 확인 (보안 강화)
     *
     * 보안 조치:
     * 1. Rate limiting (IP별 5분간 5회 제한)
     * 2. CAPTCHA 검증 (자동화 방지)
     * 3. 인증된 사용자 또는 유효한 회원가입 플로우에서만 접근
     * 4. 보안 로깅
     */
    public function check_email_get()
    {
        try {
            $client_ip = $this->rate_limiter->get_client_ip();
            $email = $this->get('email');
            $captcha_challenge_id = $this->get('captcha_id');
            $captcha_answer = $this->get('captcha_answer');
            $signup_token = $this->get('signup_token');

            // 보안 로깅 시작
            log_message('info', "Email check attempt from IP {$client_ip} for email: " . ($email ? 'provided' : 'missing'));

            // 1. Rate Limiting 검사
            $rate_check = $this->rate_limiter->is_allowed($client_ip, 'email_check', 5, 300);
            if (!$rate_check['allowed']) {
                log_message('warning', "Rate limit exceeded for email check from IP {$client_ip}");

                $this->response([
                    'success'    => false,
                    'message'    => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',
                    'exists'     => false,
                    'rate_limit' => [
                        'reset_time' => $rate_check['reset_time'],
                        'remaining'  => $rate_check['remaining']
                    ]
                ], self::HTTP_TOO_MANY_REQUESTS);
                return;
            }

            // 2. 접근 권한 검사 (인증된 사용자 또는 유효한 회원가입 플로우)
            $is_authenticated = is_authenticated_user();
            $signup_token_valid = false;

            if (!$is_authenticated) {
                // 비인증 사용자는 유효한 회원가입 토큰이 필요
                if (!$signup_token) {
                    log_message('warning', "Email check attempted without authentication or signup token from IP {$client_ip}");

                    $this->response([
                        'success'    => false,
                        'message'    => '이 기능은 회원가입 과정에서만 사용할 수 있습니다.',
                        'exists'     => false,
                        'error_code' => 'AUTH_REQUIRED'
                    ], self::HTTP_UNAUTHORIZED);
                    return;
                }

                // 회원가입 토큰 검증
                $token_validation = validate_signup_token($signup_token, true);
                if (!$token_validation['valid']) {
                    log_message('warning', "Invalid signup token for email check from IP {$client_ip}: " . $token_validation['error']);

                    $this->response([
                        'success'    => false,
                        'message'    => $token_validation['error'],
                        'exists'     => false,
                        'error_code' => 'INVALID_TOKEN'
                    ], self::HTTP_UNAUTHORIZED);
                    return;
                }

                $signup_token_valid = true;
            }

            // 3. CAPTCHA 검증 (비인증 사용자는 필수, 인증 사용자도 의심스러운 활동 시 필요)
            $captcha_required = $this->simple_captcha->is_required($client_ip, 'email_check');

            if (!$is_authenticated || $captcha_required) {
                if (!$captcha_challenge_id || !$captcha_answer) {
                    // CAPTCHA 생성 및 요구
                    $captcha = $this->simple_captcha->generate();

                    $this->response([
                        'success'          => false,
                        'message'          => '보안을 위해 인증이 필요합니다.',
                        'exists'           => false,
                        'captcha_required' => true,
                        'captcha'          => $captcha,
                        'error_code'       => 'CAPTCHA_REQUIRED'
                    ], self::HTTP_UNPROCESSABLE_ENTITY);
                    return;
                }

                // CAPTCHA 검증
                $captcha_result = $this->simple_captcha->verify($captcha_challenge_id, $captcha_answer);
                if (!$captcha_result['valid']) {
                    log_message('warning', "CAPTCHA verification failed for email check from IP {$client_ip}: " . $captcha_result['error']);

                    // 실패 시 새로운 CAPTCHA 생성
                    $new_captcha = $this->simple_captcha->generate();

                    $this->response([
                        'success'          => false,
                        'message'          => $captcha_result['error'],
                        'exists'           => false,
                        'captcha_required' => true,
                        'captcha'          => $new_captcha,
                        'error_code'       => 'CAPTCHA_FAILED'
                    ], self::HTTP_UNPROCESSABLE_ENTITY);
                    return;
                }
            }

            // 4. 이메일 파라미터 검증
            if (empty($email)) {
                $this->response([
                    'success' => false,
                    'message' => '이메일을 입력해주세요.',
                    'exists'  => false
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 5. 이메일 형식 검증
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->response([
                    'success' => false,
                    'message' => '올바른 이메일 형식이 아닙니다.',
                    'exists'  => false
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 6. 이메일 중복 확인 수행
            $exists = $this->user_m->check_email_exists($email);

            // 7. 성공 응답 (보안 로깅 포함)
            $user_info = $is_authenticated ? 'user_id:' . get_user_id() : 'signup_session';
            log_message('info', "Email check completed by {$user_info} from IP {$client_ip}. Email " . ($exists ? 'exists' : 'available'));

            // 실패한 시도 카운터 리셋 (성공 시)
            $this->simple_captcha->reset_failed_attempts($client_ip, 'email_check');

            $response_data = [
                'success'    => true,
                'exists'     => $exists,
                'message'    => $exists ? '이미 사용 중인 이메일입니다.' : '사용 가능한 이메일입니다.',
                'rate_limit' => [
                    'remaining'  => $rate_check['remaining'] - 1,
                    'reset_time' => $rate_check['reset_time']
                ]
            ];

            // 회원가입 플로우 정보 추가
            if ($signup_token_valid) {
                $token_status = get_signup_token_status();
                $response_data['signup_flow'] = [
                    'remaining_checks' => $token_status['remaining_checks']
                ];
            }

            $this->response($response_data, self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Email check error from IP ' . $client_ip . ': ' . $e->getMessage());

            // 실패한 시도로 기록
            $this->simple_captcha->mark_failed_attempt($client_ip, 'email_check');

            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다.',
                'exists'  => false
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 새로운 사용자 계정을 생성하고 클라이언트에 적절한 HTTP 응답을 전송합니다.
     *
     * 이름, 이메일, 비밀번호를 검증하고 이메일 중복 여부를 확인한 후 사용자 레코드를 생성합니다.
     * 유효성 검사 실패 또는 이메일 중복 시 422 응답, 생성 성공 시 201 응답, 서버 처리 오류 발생 시 500 응답을 반환합니다.
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