<article class="container mt-5" id="post_edit">
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
                    <button id="cancelButton" class="btn btn-outline-secondary me-2">취소</button>
                    <button id="confirmSave" class="btn btn-primary me-2">저장</button>
                </div>
            </div>
        </div>
    </div>
</article>
<script defer>
    let pageId = '#post_edit ';

    function initCancelButton(boardId) {
        $(pageId + '#cancelButton').on('click', () => {
            location.href = `/board/detail?id=${boardId}`;
        });
    }

    function initSaveButton(boardId) {
        $(pageId + '#confirmSave').on('click', () => savePost(boardId));
    }

    function savePost(boardId) {
        const title = $(pageId + 'input[name=title]').val().trim();
        const content = $(pageId + 'textarea[name=content]').val().trim();

        if (!title) {
            Swal.fire({
                icon: 'warning',
                text: '제목을 입력해 주세요.'
            });

            return false;
        }

        if (!content) {
            Swal.fire({
                icon: 'warning',
                text: '내용을 입력해 주세요.'
            });

            return false;
        }

        $.ajax({
            url: '/rest/post/create',
            type: 'POST',
            data: {
                board_id: boardId,
                title: title,
                content: content,
                user_id: <?= $user_id ?>,
                <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
            },
            success: (response) => {
                Swal.fire({
                    title: '저장되었습니다.',
                    icon: 'success'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.href = `/post/detail?id=${response.id}`;
                    }
                });
            },
            error: (error) => {
                Swal.fire({
                    title: '오류',
                    html: error.responseJSON.message,
                    icon: 'error'
                });
            }
        });
    }

    $(document).ready(() => {
        initCancelButton(<?= $board_id ?>);
        initSaveButton(<?= $board_id ?>);
    });
</script>
