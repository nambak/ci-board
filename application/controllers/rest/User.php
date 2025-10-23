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
        $this->load->model('user_m');
        $this->load->model('post_m');
        $this->load->model('comment_m');
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
            $users = $this->user_m->get($user_id);

            if (empty($users)) {
                $this->response([
                    'success' => false,
                    'message' => '사용자 정보를 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            $user = $users[0];

            // 작성한 글 수 조회
            $this->db->where('user_id', $user_id);
            $post_count = $this->db->count_all_results('posts');

            // 작성한 댓글 수 조회
            $this->db->where('writer_id', $user_id);
            $comment_count = $this->db->count_all_results('comments');

            // 응답 데이터 구성
            $response_data = [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'created_at'    => $user->created_at,
                'post_count'    => $post_count,
                'comment_count' => $comment_count
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
}