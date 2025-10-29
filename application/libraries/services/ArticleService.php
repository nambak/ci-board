<?php
defined('BASEPATH') or exit('No direct script access allowed');


class ArticleService
{
    private $CI;

    /**
     * 세션에 저장할 조회 기록의 최대 개수
     */
    private const MAX_VIEWED_ARTICLES = 100;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('article_m');
    }

    /**
     * 세션 기반 게시글 조회수 증가
     *
     * @param int $articleId 게시글  ID
     * @param array $viewedArticles 세션에서 가져온 조회한 게시글 ID 배열 (참조)
     * @return bool 조회수가 증가되었으면 true, 이미 조회한 게시글이면 false
     */
    public function incrementViewCount($articleId, &$viewedArticles): bool
    {
        if (!is_array($viewedArticles)) {
            $viewedArticles = [];
        }

        // 해당 게시글을 이번 세션에서 본 적이 없으면 조회수 증가
        if (!in_array($articleId, $viewedArticles, true)) {
            if ($this->CI->article_m->incrementViewCount($articleId)) {
                $viewedArticles[] = $articleId;

                // FIFO 방식: 최대 개수 초과 시 가장 오래된 항목 제거
                if (count($viewedArticles) > self::MAX_VIEWED_ARTICLES) {
                    array_shift($viewedArticles);
                }

                return true;
            }
        }

        return false;
    }
}