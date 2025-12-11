<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('auth');
        $this->load->helper('url');
        $this->load->library('session');

        // 로그인 체크
        if (!is_logged_in()) {
            // 로그인 페이지로 리다렉트 하기전 현재 URL을 세션에 저장
            $this->session->set_userdata('redirect_url', current_url());

            redirect('auth/login');
        }

        // 관리자 권한 체크
        if (!is_admin()) {
            show_error('접근 권한이 없습니다.', 401, '401 Unauthorized');
        }
    }

    /**
     * 관리자 대시보드
     *
     * @return void
     */
    public function index()
    {
        $this->load->model(['user_m', 'board_m', 'article_m', 'comment_m']);

        $data['total_users'] = $this->user_m->count();
        $data['total_boards'] = $this->board_m->count();
        $data['total_articles'] = $this->article_m->count();
        $data['total_comments'] = $this->comment_m->count();

        $this->load->view('admin/index_v', $data);
    }


    /**
     * 관리자 → 사용자 관리
     *
     * @return void
     */
    public function users()
    {
        $this->load->view('admin/users_v');
    }

    /**
     * 관리자 → 게시판 관리
     *
     * @return void
     */
    public function boards()
    {
        $this->load->view('admin/boards_v');
    }

    /**
     * 관리자 → 게시글 관리
     *
     * @return void
     */
    public function articles()
    {
        $this->load->view('admin/articles_v');
    }

    /**
     * 관리자 → 댓글 관리
     *
     * @return void
     */
    public function comments()
    {
        $this->load->view('admin/comments_v');
    }

    /**
     * 관리자 → 신고 관리
     *
     * @return void
     */
    public function reports()
    {
        $this->load->view('admin/reports_v');
    }

    /**
     * 관리자 → 활동 로그
     *
     * @return void
     */
    public function activity_logs()
    {
        $this->load->view('admin/activity_logs_v');
    }

    /**
     * 관리자 → 태그 관리
     *
     * @return void
     */
    public function tags()
    {
        $this->load->view('admin/tags_v');
    }

    /**
     * Admin 전용 레이아웃 적용
     */
    public function _output($output)
    {
        // Admin 전용 header view에 debugbarRenderer 전달
        $header = $this->load->view('admin/header_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        // Admin 전용 sidebar
        $sidebar = $this->load->view('admin/sidebar_v', [], TRUE);

        // Admin 전용 footer view에 debugbarRenderer 전달
        $footer = $this->load->view('admin/footer_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        echo $header . $sidebar . $output . $footer;
    }
}