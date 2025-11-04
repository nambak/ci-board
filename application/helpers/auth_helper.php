<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 현재 사용자가 로그인되어 있는지 확인
 * @return bool
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        $CI =& get_instance();
        return $CI->session->userdata('user_id') !== null;
    }
}
/**
 * 현재 로그인된 사용자 ID 반환
 * @return int|null
 */
if (!function_exists('get_user_id')) {
    function get_user_id()
    {
        $CI =& get_instance();
        return $CI->session->userdata('user_id');
    }
}
/**
 * 현재 로그인된 사용자 정보 반환
 * @return object|null
 */
function get_user_info()
{
    $CI =& get_instance();
    $user_id = get_user_id();

    if (!$user_id) {
        return null;
    }

    return $CI->session->userdata('user_info');
}

/**
 * 사용자가 특정 게시글의 작성자인지 확인
 * @param int $article_id
 * @return bool
 */
function is_article_author($article_id)
{
    $CI =& get_instance();
    $current_user_id = get_user_id();

    if (!$current_user_id) {
        return false;
    }

    // 게시글 작성자 확인
    $CI->load->model('article_m');
    $article = $CI->article_m->get($article_id);

    return $article && $article->user_id == $current_user_id;
}

/**
 * 사용자가 특정 댓글의 작성자인지 확인
 * @param int $comment_id
 *  @return bool
 */
function is_comment_author($comment_id)
{
    $CI =& get_instance();
    $current_user_id = get_user_id();

    if (!$current_user_id) {
        return false;
    }

    // 댓글 작성자 확인
    $CI->load->model('comment_m');
    $comment = $CI->comment_m->get($comment_id);

    return $comment && $comment->writer_id == $current_user_id;
}

/**
 * 로그인 처리
 * @param string $email
 * @param string $password
 * @return bool
 */
function do_login($email, $password)
{
    $CI =& get_instance();
    $CI->load->model('user_m');

    $user = $CI->user_m->get_by_email($email);

    if ($user && $user->password === $password) { // 실제 프로젝트에서는 해시 비교 필요
        $CI->session->set_userdata([
            'user_id'   => $user->id,
            'user_info' => $user
        ]);
        return true;
    }

    return false;
}

/**
 * 로그아웃 처리
 */
function do_logout()
{
    $CI =& get_instance();
    $CI->session->unset_userdata(['user_id', 'user_info']);
}

/**
 * 사용자가 관리자 권한이 있는지 확인
 * @return bool
 */
function is_admin()
{
    $CI =& get_instance();
    $role = $CI->session->userdata('role');
    return isset($role) && $role === 'admin';
}