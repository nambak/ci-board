<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tag extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tag_m');
        $this->load->model('Article_tag_m');
        $this->load->library('pagination');
    }

    /**
     * 태그별 게시글 목록 페이지
     * /tag/{slug}
     *
     * @param string $slug 태그 슬러그
     */
    public function index($slug = null)
    {
        if (!$slug) {
            show_404();
            return;
        }

        $tag = $this->Tag_m->getBySlug($slug);

        if (!$tag) {
            show_404();
            return;
        }

        // 페이지네이션 설정
        $perPage = 20;
        $page = max(1, (int)$this->input->get('page'));
        $offset = ($page - 1) * $perPage;

        // 게시글 목록 조회
        $articles = $this->Article_tag_m->getArticlesByTag($tag->id, $perPage, $offset);
        $total = $this->Article_tag_m->countArticlesByTag($tag->id);

        // 각 게시글에 태그 정보 추가
        if (!empty($articles)) {
            $articleIds = array_map(function($a) { return $a->id; }, $articles);
            $tagsByArticle = $this->Article_tag_m->getByArticles($articleIds);

            foreach ($articles as &$article) {
                $article->tags = isset($tagsByArticle[$article->id]) ? $tagsByArticle[$article->id] : [];
            }
        }

        // 인기 태그 조회
        $popularTags = $this->Tag_m->getPopular(20);

        $data = [
            'tag' => $tag,
            'articles' => $articles,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'perPage' => $perPage,
            'popularTags' => $popularTags,
        ];

        $this->load->view('tag/index_v', $data);
    }

    /**
     * 전체 태그 목록 페이지
     * /tags
     */
    public function list()
    {
        // 인기 태그 조회
        $popularTags = $this->Tag_m->getPopular(50);

        $data = [
            'popularTags' => $popularTags,
        ];

        $this->load->view('tag/list_v', $data);
    }
}
