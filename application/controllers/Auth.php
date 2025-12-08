<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form', 'email']);
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('User_m');
    }

    /**
     * 로그인 페이지
     *
     * @return void
     */
    public function login()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board', 'refresh');
        }

        $this->load->view('auth/login_v');
    }

    /**
     * 회원가입 페이지 표시
     *
     * @return void
     */
    public function register()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board', 'refresh');
        }

        $this->load->view('auth/register_v');
    }


    /**
     * 로그아웃
     *
     * @return void
     */
    public function logout()
    {
         $this->session->sess_destroy();

         redirect('/login', 'refresh');

    }

    /**
     * 비밀번호 찾기 페이지 (이메일 입력)
     *
     * @return void
     */
    public function forgot_password()
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board', 'refresh');
        }

        $this->load->view('auth/forgot_password_v');
    }

    /**
     * 비밀번호 재설정 링크 이메일 발송
     *
     * @return void
     */
    public function send_reset_link()
    {
        $this->load->model('User_m');
        $this->load->model('Password_reset_m');

        // POST 데이터 가져오기
        $email = $this->input->post('email');

        // 이메일 유효성 검증
        $this->form_validation->set_rules('email', '이메일', 'required|valid_email');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', '올바른 이메일 주소를 입력해주세요.');
            redirect('/auth/forgot_password', 'refresh');
            return;
        }

        // 사용자 확인
        $user = $this->User_m->get_by_email($email);

        if (!$user) {
            // 보안상 사용자가 존재하지 않아도 같은 메시지 표시
            $this->session->set_flashdata('success', '이메일로 비밀번호 재설정 링크를 발송했습니다. 이메일을 확인해주세요.');
            redirect('/auth/forgot_password', 'refresh');
            return;
        }

        try {
            // 재설정 토큰 생성
            $token = $this->Password_reset_m->create_token($user->id);

            // 이메일 발송
            $this->load->library('email');

            $reset_url = base_url('auth/reset_password/' . $token);
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // 이메일 템플릿 데이터
            $email_data = [
                'user_name' => $user->name,
                'reset_url' => $reset_url,
                'expires_at' => date('Y년 m월 d일 H시 i분', strtotime($expires_at))
            ];

            $message = $this->load->view('emails/password_reset', $email_data, TRUE);

            $this->email->from('noreply@ci3board.local', 'CI3Board');
            $this->email->to($user->email);
            $this->email->subject('[CI3Board] 비밀번호 재설정 요청');
            $this->email->message($message);

            if ($this->email->send()) {
                $this->session->set_flashdata('success', '이메일로 비밀번호 재설정 링크를 발송했습니다. 이메일을 확인해주세요.');
            } else {
                log_message('error', 'Email send failed: ' . $this->email->print_debugger());
                $this->session->set_flashdata('error', '이메일 발송에 실패했습니다. 잠시 후 다시 시도해주세요.');
            }

        } catch (Exception $e) {
            log_message('error', 'Password reset error: ' . $e->getMessage());
            $this->session->set_flashdata('error', '오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        }

        redirect('/auth/forgot_password', 'refresh');
    }

    /**
     * 비밀번호 재설정 페이지
     *
     * @param string $token 재설정 토큰
     * @return void
     */
    public function reset_password($token = null)
    {
        // 이미 로그인된 사용자는 메인 페이지로 리다이렉트
        if ($this->session->userdata('logged_in')) {
            redirect('/board', 'refresh');
        }

        if (!$token) {
            show_404();
            return;
        }

        $this->load->model('Password_reset_m');

        // 토큰 유효성 검증
        $token_data = $this->Password_reset_m->verify_token($token);

        if (!$token_data) {
            $this->session->set_flashdata('error', '유효하지 않거나 만료된 링크입니다.');
            redirect('/auth/forgot_password', 'refresh');
            return;
        }

        $data['token'] = $token;
        $this->load->view('auth/reset_password_v', $data);
    }

    /**
     * 비밀번호 재설정 처리
     *
     * @return void
     */
    public function update_password()
    {
        $this->load->model('Password_reset_m');
        $this->load->model('User_m');

        $token = $this->input->post('token');
        $password = $this->input->post('password');
        $password_confirm = $this->input->post('password_confirm');

        // 유효성 검증
        $this->form_validation->set_rules('password', '비밀번호', 'required|min_length[8]');
        $this->form_validation->set_rules('password_confirm', '비밀번호 확인', 'required|matches[password]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('/auth/reset_password/' . $token, 'refresh');
            return;
        }

        // 토큰 검증
        $token_data = $this->Password_reset_m->verify_token($token);

        if (!$token_data) {
            $this->session->set_flashdata('error', '유효하지 않거나 만료된 링크입니다.');
            redirect('/auth/forgot_password', 'refresh');
            return;
        }

        try {
            // 비밀번호 업데이트
            $this->User_m->update_password($token_data->user_id, $password);

            // 토큰을 사용됨으로 표시
            $this->Password_reset_m->mark_as_used($token);

            // 해당 사용자의 모든 세션 무효화를 위해 remember_token 삭제
            $this->User_m->save_remember_token($token_data->user_id, null);

            $this->session->set_flashdata('success', '비밀번호가 성공적으로 변경되었습니다. 새 비밀번호로 로그인해주세요.');
            redirect('/login', 'refresh');

        } catch (Exception $e) {
            log_message('error', 'Password update error: ' . $e->getMessage());
            $this->session->set_flashdata('error', '비밀번호 변경에 실패했습니다. 다시 시도해주세요.');
            redirect('/auth/reset_password/' . $token, 'refresh');
        }
    }

    /**
     * 이메일 인증 처리
     *
     * @return void
     */
    public function verify_email()
    {
        $token = $this->input->get('token');

        if (!$token) {
            $this->session->set_flashdata('error', '유효하지 않은 인증 링크입니다.');
            redirect('/login', 'refresh');
            return;
        }

        try {
            // 토큰으로 사용자 조회
            $user = $this->User_m->get_by_verification_token($token);

            if (!$user) {
                $this->session->set_flashdata('error', '유효하지 않거나 만료된 인증 링크입니다.');
                redirect('/login', 'refresh');
                return;
            }

            // 이미 인증된 경우
            if (!empty($user->email_verified_at)) {
                $this->session->set_flashdata('info', '이미 인증이 완료된 계정입니다.');
                redirect('/login', 'refresh');
                return;
            }

            // 이메일 인증 완료
            $this->User_m->verify_email($user->id);

            // 인증 완료 이메일 발송
            send_verification_success_email($user->email, $user->name);

            // 자동 로그인
            $sessionData = [
                'user_id'    => $user->id,
                'user_email' => $user->email,
                'user_name'  => $user->name,
                'role'       => $user->role,
                'logged_in'  => true
            ];

            $this->session->set_userdata($sessionData);

            $this->session->set_flashdata('success', '이메일 인증이 완료되었습니다. 환영합니다!');
            redirect('/board', 'refresh');

        } catch (Exception $e) {
            log_message('error', 'Email verification error: ' . $e->getMessage());
            $this->session->set_flashdata('error', '인증 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
            redirect('/login', 'refresh');
        }
    }

    /**
     * 이메일 인증 안내 페이지
     *
     * @return void
     */
    public function verification_notice()
    {
        $this->load->view('auth/verification_notice_v');
    }
}
