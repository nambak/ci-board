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
     * 사용자 프로필 조회 (공개)
     * GET /rest/user/{id} - 특정 사용자 조회
     * GET /rest/user - 전체 사용자 목록 조회 (관리자 전용)
     */
    public function index_get($id = null)
    {
        try {
            $current_user_id = (int)$this->session->userdata('user_id');

            // ID가 제공되지 않은 경우: 전체 사용자 목록 조회 (관리자 전용)
            if (!$id) {
                if (!is_admin()) {
                    $this->response([
                        'success' => false,
                        'message' => '접근 권한이 없습니다.'
                    ], self::HTTP_UNAUTHORIZED);
                    return;
                }

                // bootstrap-table 페이지네이션 파라미터
                $limit = (int)$this->get('limit') ?: 10;
                $offset = (int)$this->get('offset') ?: 0;
                $sort = $this->get('sort') ?: 'id';
                $order = strtoupper($this->get('order') ?: 'DESC');

                // limit 범위 제한
                $limit = max(1, min(100, $limit));

                $users = $this->User_m->get_all_with_counts($sort, $order, $limit, $offset);
                $responseData = [];

                foreach ($users as $user) {
                    $responseData[] = [
                        'id'            => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'created_at'    => $user->created_at,
                        'article_count' => (int)$user->article_count,
                        'comment_count' => (int)$user->comment_count,
                        'is_owner'      => $current_user_id && $current_user_id === (int)$user->id
                    ];
                }

                // bootstrap-table 형식 응답 (success 필드 제거)
                $this->response([
                    'rows'  => $responseData,
                    'total' => $this->User_m->count(),
                ], self::HTTP_OK);
                return;
            }

            // ID가 제공된 경우: 특정 사용자 조회
            $id = (int)$id;
            $users = $this->User_m->get($id);

            if (empty($users)) {
                $this->response([
                    'success' => false,
                    'message' => '사용자 정보를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $user = $users[0];
            $responseData = [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'created_at'    => $user->created_at,
                'article_count' => $this->Article_m->countByUserId($user->id),
                'comment_count' => $this->Comment_m->countByUserId($user->id),
                'is_owner'      => $current_user_id && $current_user_id === (int)$user->id
            ];

            $this->response([
                'success' => true,
                'data'    => $responseData,
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
            $password = trim($this->put('password', true));
            $newPassword = trim($this->put('new_password', true));

            // 유효성 검사
            $this->load->library('form_validation');
            $validation_data = ['name' => $name];
            $errors = [];

            // 이름 검증
            $this->form_validation->set_data($validation_data);
            $this->form_validation->set_rules('name', '이름', 'required|trim|min_length[2]|max_length[50]');

            if (!$this->form_validation->run()) {
                if (form_error('name')) {
                    $errors['name'] = strip_tags(form_error('name'));
                }
            }

            // 비밀번호 변경 요청이 있는 경우
            if (!empty($password) || !empty($newPassword)) {
                // 둘 다 입력되어야 함
                if (empty($password)) {
                    $errors['password'] = '현재 비밀번호를 입력해주세요.';
                }
                if (empty($newPassword)) {
                    $errors['new-password'] = '새 비밀번호를 입력해주세요.';
                }

                // 두 필드가 모두 제공된 경우에만 추가 검증 수행
                if (!empty($password) && !empty($newPassword)) {
                    // 비밀번호 길이 검증
                    if (strlen($newPassword) < 8) {
                        $errors['new-password'] = '새 비밀번호는 8자 이상이어야 합니다.';
                    }

                    // 현재 비밀번호와 새 비밀번호가 같은지 검증
                    if ($password === $newPassword) {
                        $errors['new-password'] = '새 비밀번호가 현재 비밀번호와 같습니다.';
                    }

                    // 다른 검증을 모두 통과한 경우에만 DB 조회
                    if (empty($errors)) {
                        // 현재 비밀번호 확인
                        $users = $this->User_m->get($user_id);
                        if (empty($users)) {
                            $this->response([
                                'success' => false,
                                'message' => '사용자 정보를 찾을 수 없습니다.'
                            ], self::HTTP_NOT_FOUND);
                            return;
                        }

                        $user = $users[0];
                        if (!password_verify($password, $user->password)) {
                            $errors['password'] = '현재 비밀번호가 일치하지 않습니다.';
                        }
                    }
                }
            }

            // 에러가 있으면 반환
            if (!empty($errors)) {
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

            // 비밀번호 변경이 있는 경우
            if (!empty($password) && !empty($newPassword)) {
                $update_data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $result = $this->User_m->update($user_id, $update_data);

            if ($result) {
                // 세션 정보도 업데이트
                $this->session->set_userdata('user_name', trim($name));

                $message = '프로필이 수정되었습니다.';
                if (!empty($password) && !empty($newPassword)) {
                    $message = '프로필과 비밀번호가 수정되었습니다.';
                }

                $this->response([
                    'success' => true,
                    'message' => $message,
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