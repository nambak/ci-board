<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class User extends RestController
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper(['auth']);
        $this->load->model('User_m');
        $this->load->model('Article_m');
        $this->load->model('Comment_m');
    }

    /**
     * 프로필 조회
     * GET /rest/user/profile
     */
    public function profile_get()
    {
        // 로그인 여부 확인
        if (!$this->session->userdata('logged_in')) {
            $this->response([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ], self::HTTP_UNAUTHORIZED);
            return;
        }

        try {
            $user_id = $this->session->userdata('user_id');

            // 사용자 정보 조회
            $users = $this->User_m->get($user_id);

            if (empty($users)) {
                $this->response([
                    'success' => false,
                    'message' => '사용자 정보를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $user = $users[0];

            // 응답 데이터 구성
            $response_data = [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'created_at'    => $user->created_at,
                'post_count'    => $this->Article_m->countByUserId($user_id),
                'comment_count' => $this->Comment_m->countByUserId($user_id)
            ];

            $this->response([
                'success' => true,
                'data'    => $response_data
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Profile fetch error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '프로필 정보를 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 특정 사용자 프로필 조회 (공개)
     * GET /rest/user/{id}
     */
    public function index_get($id = null)
    {
        if (!$id || !is_numeric($id) || (int)$id <= 0) {
            $this->response([
                'success' => false,
                'message' => '사용자 ID가 필요합니다.'
            ], self::HTTP_BAD_REQUEST);
            return;
        }

        $id = (int)$id;

        try {
            // 사용자 정보 조회
            $users = $this->User_m->get($id);

            if (empty($users)) {
                $this->response([
                    'success' => false,
                    'message' => '사용자 정보를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $user = $users[0];

            // 공개 가능한 정보만 응답
            $response_data = [
                'id'            => $user->id,
                'name'          => $user->name,
                'created_at'    => $user->created_at,
                'post_count'    => $this->Article_m->countByUserId($id),
                'comment_count' => $this->Comment_m->countByUserId($id)
             ];

            // 본인 프로필 여부
            $current_user_id = (int) $this->session->userdata('user_id');

            if ($current_user_id && $current_user_id === (int)$id) {
                $response_data['email'] = $user->email;
                $response_data['is_owner'] = true;
            } else {
                $response_data['is_owner'] = false;
            }

            $this->response([
                'success' => true,
                'data'    => $response_data
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'User profile fetch error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '사용자 정보를 불러오는 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 프로필 수정
     * PUT /rest/user/profile
     */
    public function profile_put()
    {
        // 로그인 여부 확인
        if (!$this->session->userdata('logged_in')) {
            $this->response([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ], self::HTTP_UNAUTHORIZED);
            return;
        }

        try {
            $user_id = $this->session->userdata('user_id');

            // 입력 데이터 받기
            $name = $this->put('name', true);

            // 유효성 검사
            $this->load->library('form_validation');
            $this->form_validation->set_data(['name' => $name]);
            $this->form_validation->set_rules('name', '이름', 'required|trim|min_length[2]|max_length[50]');

            if (!$this->form_validation->run()) {
                $errors = [];
                if (form_error('name')) {
                    $errors['name'] = strip_tags(form_error('name'));
                }

                $this->response([
                    'success' => false,
                    'message' => '입력값을 확인해주세요.',
                    'errors'  => $errors
                ], self::HTTP_UNPROCESSABLE_ENTITY);
                return;
            }

            // 사용자 정보 업데이트
            $update_data = [
                'name' => trim($name)
            ];

            $result = $this->User_m->update($user_id, $update_data);

            if ($result) {
                // 세션 정보도 업데이트
                $this->session->set_userdata('user_name', trim($name));

                $this->response([
                    'success' => true,
                    'message' => '프로필이 수정되었습니다.',
                    'data'    => [
                        'name' => trim($name)
                    ]
                ], self::HTTP_OK);
            } else {
                $this->response([
                    'success' => false,
                    'message' => '프로필 수정에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
            }

        } catch (Exception $e) {
            log_message('error', 'Profile update error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '프로필 수정 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }
}