<article class="container mt-5" id="post_edit">
    <form method="post" action="/rest/post/update?id=<?= $id ?>">
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
<script defer>
    let pageId = '#post_edit ';

    function initCancelButton(postId) {
        $(pageId + '#cancelButton').on('click', () => {
            location.href = `/post/detail?id=${postId}`;
        });
    }

    function init(postId) {
        $.ajax({
            url: '/rest/post/detail',
            type: 'GET',
            dataType: 'json',
            data: {
                id: postId
            },
            success: (data) => {
                if (data) {
                    $(pageId + 'input[name=title]').val(data.title);
                    $(pageId + 'textarea[name=content]').val(data.content);
                }
            },
            error: (error) => {
                Swal.fire({
                    title: `${error.status} ${error.statusText}`,
                    icon: 'error'
                });
            }
        });
    }

    function initConfirmButton(postId) {
        $(pageId + '#confirmEdit').on('click', () => updatePost(postId));
    }

    function updatePost(postId) {
        const title = $(pageId + 'input[name=title]').val();
        const content = $(pageId + 'textarea[name=content]').val();

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
            url: '/rest/post/update?id=' + postId,
            type: 'POST',
            data: {
                title: title,
                content: content,
                '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>'
            },
            success: (response) => {
                if (response === 'success') {
                    Swal.fire({
                        title: '수정되었습니다.',
                        icon: 'success'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.href = `/post/detail?id=${postId}`;
                        }
                    });
                }
            },
            error: (error) => {
                Swal.fire({
                    title: `${error.status} ${error.statusText}`,
                    icon: 'error'
                });
            }
        });
    }

    $(document).ready(() => {
        initCancelButton(<?= $id ?>);
        initConfirmButton(<?= $id ?>);
        init(<?= $id ?>);
    });
</script>
