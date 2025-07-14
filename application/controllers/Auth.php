<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 간단한 로그인 처리 (테스트용)
     */
    public function login()
    {
        $email = $this->input->get('email', true);
        $password = $this->input->get('password', true);
        
        if ($email && $password) {
            if (do_login($email, $password)) {
                echo json_encode(['success' => true, 'message' => 'Login successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Email and password required']);
        }
    }

    /**
     * 로그아웃 처리
     */
    public function logout()
    {
        do_logout();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

    /**
     * 현재 로그인 상태 확인
     */
    public function status()
    {
        $user = get_current_user();
        if ($user) {
            echo json_encode([
                'logged_in' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
        } else {
            echo json_encode(['logged_in' => false]);
        }
    }
}