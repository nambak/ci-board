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
                <div class="d-flex align-items-center">
                    <span class="me-3 d-flex align-items-center">작성자:
                        <a href="/user/<?= $currentArticle->user_id ?>" class="d-flex align-items-center text-decoration-none ms-2">
                            <?= profile_avatar_html(
                                $currentArticle->profile_image ?? null,
                                $currentArticle->name,
                                28
                            ) ?>
                            <span class="ms-1"><?= html_escape($currentArticle->name) ?></span>
                        </a>
                    </span>
                    <span class="me-3">작성일: <?= html_escape($currentArticle->created_at) ?></span>
                    <span>조회수: <?= html_escape($currentArticle->views) ?></span>
                    <span class="ms-3">
                        <i class="bi bi-heart-fill text-danger"></i>
                        <span id="likeCount"><?= (int)($currentArticle->like_count ?? 0) ?></span>
                    </span>
                </div>
                <div id="attachmentInfo" class="text-muted" style="display: none;">
                    <!-- 첨부파일 정보가 여기에 표시됩니다 -->
                </div>
            </div>
        </div>
    </div>
    <!-- 태그 영역 -->
    <?php if (!empty($tags)): ?>
    <div class="row mb-3">
        <div class="col">
            <div class="article-tags">
                <i class="bi bi-tags me-2 text-muted"></i>
                <?php foreach ($tags as $tag): ?>
                    <a href="/tag/<?= html_escape($tag->slug) ?>" class="tag-link">
                        <?= html_escape($tag->name) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- 게시글 내용 -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body min-vh-50">
                    <!-- 첨부 이미지 -->
                    <div class="row mb-3" id="attachmentImagesSection" style="display: none;">
                        <div class="col">
                            <div id="attachmentImages" class="row g-2">
                                <!-- 이미지 썸네일 표시 -->
                            </div>
                        </div>
                    </div>

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

                    <?php if (is_logged_in()): ?>
                        <button id="likeButton" class="btn btn-outline-danger me-2">
                            <i class="bi bi-heart" id="likeIcon"></i>
                            <span id="likeButtonText">좋아요</span>
                        </button>
                        <button id="reportArticleButton"
                                class="btn btn-outline-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#reportModal"
                                data-target-type="article"
                                data-target-id="<?= $currentArticle->id ?>"
                        >
                            <i class="bi bi-flag"></i> 신고
                        </button>
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

    <!-- 신고 모달 -->
    <?php if (is_logged_in()): ?>
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">신고하기</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm">
                        <input type="hidden" id="reportTargetType" name="target_type" value="">
                        <input type="hidden" id="reportTargetId" name="target_id" value="">

                        <div class="mb-3">
                            <label class="form-label">신고 사유 선택 <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reason" id="reasonSpam" value="spam">
                                <label class="form-check-label" for="reasonSpam">스팸/광고</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reason" id="reasonAbuse" value="abuse">
                                <label class="form-check-label" for="reasonAbuse">욕설/비방</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reason" id="reasonAdult" value="adult">
                                <label class="form-check-label" for="reasonAdult">음란물</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reason" id="reasonCopyright" value="copyright">
                                <label class="form-check-label" for="reasonCopyright">저작권 침해</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reason" id="reasonOther" value="other">
                                <label class="form-check-label" for="reasonOther">기타</label>
                            </div>
                        </div>

                        <div class="mb-3" id="reportDetailSection" style="display: none;">
                            <label for="reportDetail" class="form-label">상세 내용 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reportDetail" name="detail" rows="3" placeholder="신고 사유를 상세히 입력해주세요."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-warning" id="submitReport">신고하기</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</article>
<script src="/assets/js/article-detail.js" defer></script>
<script src="/assets/js/report.js" defer></script>
