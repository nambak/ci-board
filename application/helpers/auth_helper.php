<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 사용자 로그인 여부 확인
 *
 * @return bool 로그인되어 있으면 true, 아니면 false
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        $CI =& get_instance();
        return (bool) $CI->session->userdata('logged_in');
    }
}

/**
 * 현재 로그인한 사용자 ID 반환
 *
 * @return int|null 사용자 ID, 로그인하지 않았으면 null
 */
if (!function_exists('get_user_id')) {
    function get_user_id()
    {
        $CI =& get_instance();
        return $CI->session->userdata('user_id');
    }
}

/**
 * 현재 로그인한 사용자 이름 반환
 *
 * @return string|null 사용자 이름, 로그인하지 않았으면 null
 */
if (!function_exists('get_user_name')) {
    function get_user_name()
    {
        $CI =& get_instance();
        return $CI->session->userdata('user_name');
    }
}

/**
 * 현재 로그인한 사용자 이메일 반환
 *
 * @return string|null 사용자 이메일, 로그인하지 않았으면 null
 */
if (!function_exists('get_user_email')) {
    function get_user_email()
    {
        $CI =& get_instance();
        return $CI->session->userdata('user_email');
    }
}

/**
 * 현재 로그인한 사용자가 게시글 작성자인지 확인
 *
 * @param int $article_id 게시글 ID
 * @return bool 작성자이면 true, 아니면 false
 */
if (!function_exists('is_post_author')) {
    function is_post_author($article_id)
    {
        if (!is_logged_in()) {
            return false;
        }

        $CI =& get_instance();
        $CI->load->model('article_m');

        $article = $CI->article_m->get($article_id);

        if (!$article) {
            return false;
        }

        return (int) $article->user_id === (int) get_user_id();
    }
}
