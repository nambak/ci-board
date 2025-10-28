<article class="container mt-5" id="post_edit"
    data-article-id="<?= $id ?>">
    <form method="post" action="/rest/article/update?id=<?= $id ?>">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <!-- 게시글 제목 -->
        <div class="row mb-2">
            <div class="col">
                <h2 class="border-bottom pb-3">
                    <input type="text" class="form-control" id="title" name="title">
                </h2>
            </div>
        </div>
        <!-- 게시글 내용 -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <textarea class="form-control" id="content" rows="10" name="content"></textarea>
                </div>
            </div>
        </div>
        <!-- 버튼 영역 -->
        <div class="row mb-5">
            <div class="col">
                <div class="d-flex justify-content-end">
                    <div>
                        <button id="cancelButton" type="button" class="btn btn-outline-secondary me-2">취소</button>
                        <button id="confirmEdit" type="button" class="btn btn-primary me-2">수정</button>
                        <noscript>
                            <input type="submit" value="수정" class="btn btn-primary">
                        </noscript>
                    </div>
                </div>
            </div>
        </div>
    </form>
</article>
<script src="/assets/js/article-edit.js" defer></script>
