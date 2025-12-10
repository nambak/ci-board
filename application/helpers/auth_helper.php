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
 * 사용자가 관리자 권한이 있는지 확인
 * @return bool
 */
function is_admin()
{
    $CI =& get_instance();
    $role = $CI->session->userdata('role');
    return isset($role) && $role === 'admin';
}

/**
 * 사용자 프로필 이미지 URL 반환
 * @param string|null $profile_image 프로필 이미지 파일명
 * @param string|null $name 사용자 이름 (이니셜 아바타용)
 * @param int $size 이미지 크기 (기본 40)
 * @return array ['type' => 'image'|'initial', 'url' => string|null, 'initial' => string|null]
 */
function get_profile_avatar($profile_image = null, $name = null, $size = 40)
{
    if (!empty($profile_image)) {
        return [
            'type' => 'image',
            'url' => '/uploads/profiles/' . $profile_image,
            'initial' => null
        ];
    }

    // 이니셜 아바타
    $initial = '';
    if (!empty($name)) {
        $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return [
        'type' => 'initial',
        'url' => null,
        'initial' => $initial
    ];
}

/**
 * 프로필 아바타 HTML 생성
 * @param string|null $profile_image 프로필 이미지 파일명
 * @param string|null $name 사용자 이름
 * @param int $size 이미지 크기 (픽셀)
 * @param string $class 추가 CSS 클래스
 * @return string HTML
 */
function profile_avatar_html($profile_image = null, $name = null, $size = 40, $class = '')
{
    $avatar = get_profile_avatar($profile_image, $name, $size);
    $style = "width: {$size}px; height: {$size}px;";

    if ($avatar['type'] === 'image') {
        return '<img src="' . html_escape($avatar['url']) . '" alt="프로필" class="rounded-circle ' . html_escape($class) . '" style="' . $style . ' object-fit: cover;">';
    }

    // 이니셜 아바타
    $font_size = round($size * 0.4);
    return '<div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white ' . html_escape($class) . '" style="' . $style . ' font-size: ' . $font_size . 'px;">' . html_escape($avatar['initial']) . '</div>';
}