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
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'profile_image'     => $user->profile_image ?? null,
                'profile_image_url' => !empty($user->profile_image) ? '/uploads/profiles/' . $user->profile_image : null,
                'created_at'        => $user->created_at,
                'post_count'        => $this->Article_m->countByUserId($user_id),
                'comment_count'     => $this->Comment_m->countByUserId($user_id)
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
                        'role'          => $user->role ?? 'user',
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
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'profile_image'     => $user->profile_image ?? null,
                'profile_image_url' => !empty($user->profile_image) ? '/uploads/profiles/' . $user->profile_image : null,
                'created_at'        => $user->created_at,
                'article_count'     => $this->Article_m->countByUserId($user->id),
                'comment_count'     => $this->Comment_m->countByUserId($user->id),
                'is_owner'          => $current_user_id && $current_user_id === (int)$user->id
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

    /**
     * 프로필 이미지 업로드
     * POST /rest/user/profile/image
     */
    public function image_post()
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

            // 파일 업로드 확인
            if (empty($_FILES['image']['name'])) {
                $this->response([
                    'success' => false,
                    'message' => '이미지 파일을 선택해주세요.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 업로드 설정
            $config['upload_path'] = FCPATH . 'uploads/profiles/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048; // 2MB
            $config['encrypt_name'] = TRUE;

            // 업로드 디렉토리 생성
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, true);
            }

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('image')) {
                $this->response([
                    'success' => false,
                    'message' => $this->upload->display_errors('', '')
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $upload_data = $this->upload->data();

            // 이미지 유효성 검사 (getimagesize)
            $image_info = @getimagesize($upload_data['full_path']);
            if ($image_info === false) {
                // 잘못된 이미지 파일 삭제
                @unlink($upload_data['full_path']);
                $this->response([
                    'success' => false,
                    'message' => '유효하지 않은 이미지 파일입니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 이미지 리사이징 (200x200)
            $this->load->library('image_lib');

            $resize_config['image_library'] = 'gd2';
            $resize_config['source_image'] = $upload_data['full_path'];
            $resize_config['maintain_ratio'] = FALSE;
            $resize_config['width'] = 200;
            $resize_config['height'] = 200;

            // 정사각형 크롭을 위해 먼저 비율 유지하며 리사이즈
            $original_width = $image_info[0];
            $original_height = $image_info[1];

            if ($original_width != $original_height) {
                // 크롭 설정
                $crop_size = min($original_width, $original_height);
                $x_axis = ($original_width - $crop_size) / 2;
                $y_axis = ($original_height - $crop_size) / 2;

                $crop_config['image_library'] = 'gd2';
                $crop_config['source_image'] = $upload_data['full_path'];
                $crop_config['maintain_ratio'] = FALSE;
                $crop_config['width'] = $crop_size;
                $crop_config['height'] = $crop_size;
                $crop_config['x_axis'] = $x_axis;
                $crop_config['y_axis'] = $y_axis;

                $this->image_lib->initialize($crop_config);
                $this->image_lib->crop();
                $this->image_lib->clear();
            }

            // 200x200으로 리사이즈
            $this->image_lib->initialize($resize_config);
            if (!$this->image_lib->resize()) {
                log_message('error', 'Image resize error: ' . $this->image_lib->display_errors());
            }

            // 기존 프로필 이미지 조회 (삭제용)
            $users = $this->User_m->get($user_id);
            $oldProfileImage = (!empty($users) && !empty($users[0]->profile_image))
                ? $users[0]->profile_image
                : null;


            // DB 업데이트
            $result = $this->User_m->update($user_id, [
                'profile_image' => $upload_data['file_name']
            ]);

            if ($result) {
                // 기존 프로필 이미지 삭제
                $this->deleteProfileImage($oldProfileImage);

                // 세션 업데이트
                $this->session->set_userdata('profile_image', $upload_data['file_name']);

                $this->response([
                    'success' => true,
                    'message' => '프로필 이미지가 업로드되었습니다.',
                    'data'    => [
                        'profile_image'     => $upload_data['file_name'],
                        'profile_image_url' => '/uploads/profiles/' . $upload_data['file_name']
                    ]
                ], self::HTTP_OK);
            } else {
                // 업로드된 파일 삭제
                @unlink($upload_data['full_path']);

                $this->response([
                    'success' => false,
                    'message' => '프로필 이미지 저장에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
            }

        } catch (Exception $e) {
            log_message('error', 'Profile image upload error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '프로필 이미지 업로드 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 프로필 이미지 삭제
     * DELETE /rest/user/profile/image
     */
    public function image_delete()
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

            // 현재 프로필 이미지 확인
            $users = $this->User_m->get($user_id);
            if (empty($users) || empty($users[0]->profile_image)) {
                $this->response([
                    'success' => false,
                    'message' => '삭제할 프로필 이미지가 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            // 파일 삭제
            $image_path = FCPATH . 'uploads/profiles/' . $users[0]->profile_image;
            if (file_exists($image_path)) {
                @unlink($image_path);
            }

            // DB 업데이트
            $result = $this->User_m->update($user_id, [
                'profile_image' => null
            ]);

            if ($result) {
                // 세션 업데이트
                $this->session->unset_userdata('profile_image');

                $this->response([
                    'success' => true,
                    'message' => '프로필 이미지가 삭제되었습니다.'
                ], self::HTTP_OK);
            } else {
                $this->response([
                    'success' => false,
                    'message' => '프로필 이미지 삭제에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
            }

        } catch (Exception $e) {
            log_message('error', 'Profile image delete error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '프로필 이미지 삭제 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 사용자 권한 변경 (관리자 전용)
     * PUT /rest/user/role/{id}
     */
    public function role_put($userId)
    {
        try {
            // 관리자 권한 확인
            if (!is_admin()) {
                $this->response([
                    'success' => false,
                    'message' => '접근 권한이 없습니다.'
                ], self::HTTP_FORBIDDEN);
                return;
            }

            $currentUserId = (int)$this->session->userdata('user_id');
            $userId = (int)$userId;

            // 유효성 검사
            if ($userId <= 0) {
                $this->response([
                    'success' => false,
                    'message' => '잘못된 사용자 ID입니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 본인의 권한은 변경할 수 없음
            if ($currentUserId === $userId) {
                $this->response([
                    'success' => false,
                    'message' => '본인의 권한은 변경할 수 없습니다.'
                ], self::HTTP_FORBIDDEN);
                return;
            }

            // 새로운 권한 값 받기
            $newRole = $this->put('role', true);

            // 권한 값 검증
            if (!in_array($newRole, ['user', 'admin'])) {
                $this->response([
                    'success' => false,
                    'message' => '잘못된 권한 값입니다. (user 또는 admin만 가능)'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 대상 사용자 존재 확인
            $targetUsers = $this->User_m->get($userId);
            if (empty($targetUsers)) {
                $this->response([
                    'success' => false,
                    'message' => '사용자를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }
            $targetUser = $targetUsers[0];

            // 관리자를 일반 사용자로 강등하려는 경우
            if ($targetUser->role === 'admin' && $newRole === 'user') {
                // 최소 1명의 관리자는 유지되어야 함
                $adminCount = $this->User_m->countAdmins();
                if ($adminCount <= 1) {
                    $this->response([
                        'success' => false,
                        'message' => '최소 1명의 관리자가 필요합니다.'
                    ], self::HTTP_FORBIDDEN);
                    return;
                }
            }

            // 권한 변경
            $result = $this->User_m->updateRole($userId, $newRole);

            if ($result) {
                $roleLabel = $newRole === 'admin' ? '관리자' : '일반 사용자';
                $this->response([
                    'success' => true,
                    'message' => "사용자 권한이 '{$roleLabel}'(으)로 변경되었습니다."
                ], self::HTTP_OK);
            } else {
                $this->response([
                    'success' => false,
                    'message' => '권한 변경에 실패했습니다.'
                ], self::HTTP_INTERNAL_ERROR);
            }

        } catch (Exception $e) {
            log_message('error', 'User role update error: ' . $e->getMessage());

            $this->response([
                'success' => false,
                'message' => '권한 변경 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    private function deleteProfileImage($oldProfileImage)
    {
        if (!empty($oldProfileImage)) {
            $oldImagePath = FCPATH . 'uploads/profiles/' . $oldProfileImage;
            if (file_exists($oldImagePath)) {
                @unlink($oldImagePath);
            }
        }
    }
}