<article class="container mt-5" id="post_detail"
         data-user-id="<?= $user_id ?>"
         data-csrf-token-name="<?= $this->security->get_csrf_token_name(); ?>"
         data-article-id="<?= $currentArticle->id ?>"
         data-board-id="<?= $currentArticle->board_id ?>"
         data-is-article-author="<?= is_article_author($currentArticle->id) ?>"
         data-is-logged-in="<?= is_logged_in() ?>"
         data-csrf-hash="<?= $this->security->get_csrf_hash(); ?>">
    <!-- 게시글 제목 -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="border-bottom pb-3" id="title"><?= html_escape($currentArticle->title) ?></h2>
        </div>
    </div>
    <!-- 게시글 정보 -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="me-3">작성자: <?= html_escape($currentArticle->name) ?></span>
                    <span class="me-3">작성일: <?= html_escape($currentArticle->created_at) ?></span>
                    <span>조회수: <?= html_escape($currentArticle->views) ?></span>
                </div>
            </div>
        </div>
    </div>
    <!-- 게시글 내용 -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body min-vh-50">
                    <?= nl2br($currentArticle->content); ?>
                </div>
            </div>
        </div>
    </div>
    <!-- 버튼 영역 -->
    <div class="row mb-5">
        <div class="col">
            <div class="d-flex justify-content-between">
                <div>
                    <button id="redirectBoardListButton" class="btn btn-outline-secondary me-2">목록으로</button>
                    <?php if (is_article_author($currentArticle->id)): ?>
                        <button id="redirectEditPost" class="btn btn-outline-primary me-2">수정</button>
                        <button id="deletePost" class="btn btn-outline-danger">삭제</button>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($prevArticleId): ?>
                        <a href="/article/<?= $prevArticleId ?>" class="btn btn-outline-primary me-2">이전글</a>
                    <?php else: ?>
                        <button disabled class="btn btn-outline-primary me-2">이전글</button>
                    <?php endif; ?>

                    <?php if ($nextArticleId): ?>
                        <a href="/article/<?= $nextArticleId ?>" class="btn btn-outline-primary">다음글</a>
                    <?php else: ?>
                        <button disabled class="btn btn-outline-primary">다음글</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 댓글 영역 -->
    <div class="row">
        <div class="col">
            <h5 class="mb-3" id="comment-title">댓글</h5>

            <!-- 댓글 작성 폼 -->
            <?php if (is_logged_in()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <textarea
                                    name="comment"
                                    class="form-control"
                                    rows="3"
                                    placeholder="댓글을 입력하세요"
                            ></textarea>
                        </div>
                        <div class="text-end">
                            <button
                                    id="write_comment"
                                    type="button"
                                    class="btn btn-primary"
                            >댓글 작성
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 댓글 목록 -->
            <div id="comment_list">
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-center">
                            등록된 댓글이 없습니다.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>
<script src="/assets/js/article-detail.js" defer></script>
