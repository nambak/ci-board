<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_RestController
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->library('simple_captcha');
        $this->load->library('activity_logger');
        $this->load->helper(['security', 'string', 'signup_security', 'email']);

        $this->load->model('user_m');
        $this->load->model('password_reset_m');
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
            // 로그인 실패 로깅
            $this->activity_logger->logLoginFailed($email);

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
                'role'       => $user->role,
                'logged_in'  => true
            ];

            $this->session->set_userdata($sessionData);

            // 로그인 성공 로깅
            $this->activity_logger->logLogin($user->id, $user->name);

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
                'success'      => true,
                'message'      => '로그인되었습니다.',
                'user'         => [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'name'  => $user->name,
                ],
                'redirect_url' => $this->session->userdata('redirect_url') ?? base_url(),
            ], self::HTTP_OK);
        } else {
            // 로그인 실패 로깅
            $this->activity_logger->logLoginFailed($email);

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

            // 이메일 중복 확인 수행
            $exists = $this->user_m->check_email_exists($email);

            $response_data = [
                'success' => true,
                'exists'  => $exists,
                'message' => $exists ? '이미 사용 중인 이메일입니다.' : '사용 가능한 이메일입니다.',
            ];

            $this->response($response_data, self::HTTP_OK);

        } catch (Exception $e) {
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
            $user_id = $this->user_m->create($userData);

            if (!$user_id) {
                throw new Exception('Failed to create user');
            }

            // 이메일 인증 토큰 생성
            $verification_token = $this->user_m->generate_verification_token($user_id);

            if (!$verification_token) {
                log_message('error', 'Failed to generate verification token for user: ' . $user_id);
                throw new Exception('Failed to generate verification token');
            }

            // 이메일 인증 메일 발송
            $email_sent = send_verification_email(
                $inputData['email'],
                trim($inputData['name']),
                $verification_token
            );

            if (!$email_sent) {
                log_message('warning', 'Verification email failed to send for user: ' . $user_id);
            }

            // 이메일 발송 여부에 때라 다른 메시지 제공
            $message = $email_sent
                ? '회원가입이 완료되었습니다. 이메일로 발송된 인증 링크를 확인해주세요.'
                : '회원가입이 완료되었습니다. 이메일 발송에 실패했습니다. 로그인 후 인증 메일을 재발송해주세요.';

            // 성공 응답
            $this->response([
                'success'    => true,
                'message'    => $message,
                'email_sent' => $email_sent
            ], self::HTTP_CREATED);

        } catch (Exception $e) {
            log_message('error', 'Register error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 비밀번호 재설정 링크 요청
     * POST /rest/auth/forgot_password
     */
    public function forgot_password_post()
    {
        try {
            // 유효성 검사
            $this->form_validation->set_rules('email', '이메일', 'required|valid_email');

            if (!$this->form_validation->run()) {
                $errors = [];
                if (form_error('email')) {
                    $errors['email'] = strip_tags(form_error('email'));
                }

                $this->response([
                    'success' => false,
                    'message' => '올바른 이메일 주소를 입력해주세요.',
                    'errors'  => $errors
                ], self::HTTP_UNPROCESSABLE_ENTITY);
                return;
            }

            $email = $this->post('email', true);

            // 사용자 확인
            $user = $this->user_m->get_by_email($email);

            // 보안상 사용자 존재 여부와 관계없이 같은 응답 반환
            if (!$user) {
                $this->response([
                    'success' => true,
                    'message' => '이메일로 비밀번호 재설정 링크를 발송했습니다. 이메일을 확인해주세요.'
                ], self::HTTP_OK);
                return;
            }

            // 재설정 토큰 생성
            $token = $this->password_reset_m->create_token($user->id);

            // 이메일 발송
            $this->load->library('email');

            $reset_url = base_url('auth/reset_password/' . $token);
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // 이메일 템플릿 데이터
            $email_data = [
                'user_name'  => $user->name,
                'reset_url'  => $reset_url,
                'expires_at' => date('Y년 m월 d일 H시 i분', strtotime($expires_at))
            ];

            $message = $this->load->view('emails/password_reset', $email_data, TRUE);

            $this->email->from('noreply@ci3board.local', 'CI3Board');
            $this->email->to($user->email);
            $this->email->subject('[CI3Board] 비밀번호 재설정 요청');
            $this->email->message($message);

            if ($this->email->send()) {
                $this->response([
                    'success' => true,
                    'message' => '이메일로 비밀번호 재설정 링크를 발송했습니다. 이메일을 확인해주세요.'
                ], self::HTTP_OK);
            } else {
                log_message('error', '비밀번호 재설정 메일 발송 실패.');
                $this->response([
                    'success' => false,
                    'message' => '이메일 발송에 실패했습니다. 잠시 후 다시 시도해주세요.'
                ], self::HTTP_INTERNAL_ERROR);
            }

        } catch (Exception $e) {
            log_message('error', 'Password reset request error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 토큰 유효성 검증
     * GET /rest/auth/verify_token/{token}
     */
    public function verify_token_get($token = null)
    {
        try {
            if (!$token) {
                $this->response([
                    'success' => false,
                    'message' => '토큰이 제공되지 않았습니다.',
                    'valid'   => false
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $token_data = $this->password_reset_m->verify_token($token);

            if ($token_data) {
                $this->response([
                    'success'    => true,
                    'message'    => '유효한 토큰입니다.',
                    'valid'      => true,
                    'expires_at' => $token_data->expires_at
                ], self::HTTP_OK);
            } else {
                $this->response([
                    'success' => false,
                    'message' => '유효하지 않거나 만료된 토큰입니다.',
                    'valid'   => false
                ], self::HTTP_BAD_REQUEST);
            }

        } catch (Exception $e) {
            log_message('error', 'Token verification error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다.',
                'valid'   => false
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 비밀번호 재설정 처리
     * POST /rest/auth/reset_password
     */
    public function reset_password_post()
    {
        try {
            // 유효성 검사
            $this->form_validation->set_rules('token', '토큰', 'required');
            $this->form_validation->set_rules('password', '비밀번호', 'required|min_length[8]');
            $this->form_validation->set_rules('password_confirm', '비밀번호 확인', 'required|matches[password]');

            if (!$this->form_validation->run()) {
                $errors = [];
                if (form_error('token')) {
                    $errors['token'] = strip_tags(form_error('token'));
                }
                if (form_error('password')) {
                    $errors['password'] = strip_tags(form_error('password'));
                }
                if (form_error('password_confirm')) {
                    $errors['password_confirm'] = strip_tags(form_error('password_confirm'));
                }

                $this->response([
                    'success' => false,
                    'message' => '입력값을 확인해주세요.',
                    'errors'  => $errors
                ], self::HTTP_UNPROCESSABLE_ENTITY);
                return;
            }

            $token = $this->post('token', true);
            $password = $this->post('password', true);

            // 토큰 검증
            $token_data = $this->password_reset_m->verify_token($token);

            if (!$token_data) {
                $this->response([
                    'success' => false,
                    'message' => '유효하지 않거나 만료된 토큰입니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 비밀번호 업데이트
            $this->user_m->update_password($token_data->user_id, $password);

            // 토큰을 사용됨으로 표시
            $this->password_reset_m->mark_as_used($token);

            // 해당 사용자의 모든 세션 무효화를 위해 remember_token 삭제
            $this->user_m->save_remember_token($token_data->user_id, null);

            $this->response([
                'success' => true,
                'message' => '비밀번호가 성공적으로 변경되었습니다. 새 비밀번호로 로그인해주세요.'
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Password reset error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '비밀번호 변경에 실패했습니다. 다시 시도해주세요.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 이메일 인증 메일 재발송
     * POST /rest/auth/resend-verification
     */
    public function resend_verification_post()
    {
        try {
            // 세션에서 사용자 ID 가져오기
            $user_id = $this->session->userdata('user_id');

            if (!$user_id) {
                $this->response([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], self::HTTP_UNAUTHORIZED);
                return;
            }

            // 트랜잭션 시작
            $this->db->trans_start();
            $this->db->query('SELECT * FROM users WHERE id = ? FOR UPDATE', [$user_id]);

            // 사용자 정보 조회
            $users = $this->user_m->get($user_id);
            if (empty($users)) {
                $this->response([
                    'success' => false,
                    'message' => '사용자를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $user = $users[0];

            // 이미 인증된 경우
            if (!empty($user->email_verified_at)) {
                $this->response([
                    'success' => false,
                    'message' => '이미 인증이 완료된 계정입니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // DB에서 마지막 발송 시간 확인
            if ($user->last_verification_sent_at) {
                $last_sent_time  = strtotime($user->last_verification_sent_at);
                $elapsed = time() - $last_sent_time;
                if ($elapsed < 60) {
                    $remaining = 60 - $elapsed;
                    $this->response([
                        'success'     => false,
                        'message'     => "잠시 후 다시 시도해주세요. ({$remaining}초 후 재발송 가능)",
                        'retry_after' => $remaining
                    ], self::HTTP_TOO_MANY_REQUESTS);
                    return;
                }
            }

            // 새로운 인증 토큰 생성
            $verification_token = $this->user_m->generate_verification_token($user_id);

            if (!$verification_token) {
                log_message('error', 'Failed to generate verification token for user: ' . $user_id);
                $this->response([
                    'success' => false,
                    'message' => '인증 토큰 생성에 실패했습니다. 잠시 후 다시 시도해주세요.'
                ], self::HTTP_INTERNAL_ERROR);
                return;
            }

            // 인증 메일 발송
            $email_sent = send_verification_email(
                $user->email,
                $user->name,
                $verification_token
            );

            if (!$email_sent) {
                log_message('error', 'Resend verification email failed for user: ' . $user_id);
                $this->response([
                    'success' => false,
                    'message' => '이메일 발송에 실패했습니다. 잠시 후 다시 시도해주세요.'
                ], self::HTTP_INTERNAL_ERROR);
                return;
            }

            // DB에 재발송 시간 기록
            $this->user_m->update_last_verification_sent_at($user_id, date('Y-m-d H:i:s'));

            // 트랜잭션 종료
            $this->db->trans_complete();

            $this->response([
                'success' => true,
                'message' => '인증 메일이 재발송되었습니다. 이메일을 확인해주세요.'
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Resend verification error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }
}