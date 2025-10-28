<article class="container mt-5" id="post_edit"
    data-board-id="<?= $board_id ?>"
    data-user-id="<?= $user_id ?>"
    data-csrf-token-name="<?= $this->security->get_csrf_token_name(); ?>"
    data-csrf-hash="<?= $this->security->get_csrf_hash(); ?>">
    <form method="post" action="/rest/post/create">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="board_id" value="<?= $board_id ?>">

        <!-- 게시글 제목 -->
        <div class="row mb-2">
            <div class="col">
                <h2 class="border-bottom pb-3">
                    <input type="text" class="form-control" id="title" name="title" placeholder="제목">
                </h2>
            </div>
        </div>
        <!-- 게시글 내용 -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <textarea class="form-control" id="content" rows="10" name="content" placeholder="내용"></textarea>
                </div>
            </div>
        </div>
        <!-- 버튼 영역 -->
        <div class="row mb-5">
            <div class="col">
                <div class="d-flex justify-content-end">
                    <div>
                        <button id="cancelButton" type="button" class="btn btn-outline-secondary me-2">취소</button>
                        <button id="confirmSave" type="button" class="btn btn-primary me-2">저장</button>
                        <noscript>
                            <input type="submit" value="저장" class="btn btn-primary">
                        </noscript>
                    </div>
                </div>
            </div>
        </div>
    </form>
</article>
<script src="/assets/js/article-create.js" defer></script>
