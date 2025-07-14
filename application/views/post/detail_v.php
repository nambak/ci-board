<article class="container mt-5" id="post_detail">
    <!-- 게시글 제목 -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="border-bottom pb-3" id="title"></h2>
        </div>
    </div>
    <!-- 게시글 정보 -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="me-3">작성자: <span id="writer"></span></span>
                    <span class="me-3">작성일: <span id="createdAt"></span></span>
                    <span>조회수: <span id="views"></span></span>
                </div>
            </div>
        </div>
    </div>
    <!-- 게시글 내용 -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body min-vh-50" id="content"></div>
            </div>
        </div>
    </div>
    <!-- 버튼 영역 -->
    <div class="row mb-5">
        <div class="col">
            <div class="d-flex justify-content-between">
                <div>
                    <button id="redirectBoardListButton" class="btn btn-outline-secondary me-2">목록으로</button>
                    <?php if (is_post_author($id)): ?>
                        <button id="redirectEditPost" class="btn btn-outline-primary me-2">수정</button>
                        <button id="deletePost" class="btn btn-outline-danger">삭제</button>
                    <?php endif; ?>
                </div>
                <div>
                    <button id="prevPostButton" class="btn btn-outline-primary me-2">이전글</button>
                    <button id="nextPostButton" class="btn btn-outline-primary">다음글</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 댓글 영역 -->
    <div class="row">
        <div class="col">
            <h5 class="mb-3">댓글</h5>

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
<script defer>
    let pageId = '#post_detail ';

    function initRedirectBoardListButton(boardId) {
        $(pageId + '#redirectBoardListButton').on('click', () => {
            location.href = `/board/detail?id=${boardId}`;
        });
    }

    function initRedirectPostEditButton(postId) {
        $(pageId + '#redirectEditPost').on('click', () => {
            location.href = `/post/edit?id=${postId}`;
        });
    }

    function initDeletePostButton() {
        $(pageId + '#deletePost').on('click', () => {
            Swal.fire({
                title: '게시물을 삭제하시겠습니까?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '삭제',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/rest/post/<?= $id ?>',
                        type: 'DELETE',
                        dataType: 'json',
                        success: (data) => {
                            $(pageId + '#redirectBoardListButton').click();
                        },
                        error: (error) => {
                            Swal.fire({
                                title: error.status,
                                text: error.statusText,
                                icon: 'error'
                            });
                        }
                    })
                }
            });
        });
    }

    function initPrevNextPostButton(data) {
        if (data.prev_id) {
            $(pageId + '#prevPostButton').on('click', () => {
                getPostData(data.prev_id);
            })
            $(pageId + '#prevPostButton').removeAttr('disabled');
        } else {
            $(pageId + '#prevPostButton').attr('disabled', true);
        }

        if (data.next_id) {
            $(pageId + '#nextPostButton').on('click', () => {
                getPostData(data.next_id);
            })
            $(pageId + '#nextPostButton').removeAttr('disabled');
        } else {
            $(pageId + '#nextPostButton').attr('disabled', true);
        }
    }

    function initCommentPostButton(postId) {
        if (postId) {
            $(pageId + '#write_comment').on('click', (event) => {
                event.preventDefault();
                saveComment(postId);
            });
        }
    }

    function saveComment(postId) {
        const comment = $(pageId + "textarea[name=comment]");

        if (!comment.val().trim()) {
            Swal.fire({
                icon: 'warning',
                text: '댓글 내용이 없습니다.'
            });

            return false;
        }

        $.ajax({
            url: '/rest/comment/save',
            type: 'POST',
            data: {
                post_id: postId,
                comment: comment.val(),
            },
            success: (response) => {
                getComments(postId)

                // 저장 후 textarea 비움
                comment.val('');
            },
            error: (error) => {
                displayError(error)
            }
        });
    }

    function getComments(postId) {
        $.ajax({
            url: `/rest/comment`,
            type: 'GET',
            data: {
                post_id: postId,
            },
            success: (response) => {
                generateCommentList(response.data);
            },
            error: (error) => {
                displayError(error)
            }
        })
    }

    function generateCommentList(data) {
        let html = '';

        if (data.length <= 0) {
            return false;
        }

        data.forEach((comment) => {
            // 댓글 수정/삭제 버튼은 작성자만 표시
            const editDeleteButtons = comment.can_edit ? 
                `<div>
                    <button class="btn btn-sm text-primary">수정</button>
                    <button class="btn btn-sm text-danger">삭제</button>
                </div>` : '';
            
            const template = `<div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${comment.name}</strong>
                            <small class="text-muted ms-2">${comment.created_at}</small>
                        </div>
                        ${editDeleteButtons}
                    </div>
                    <p class="mt-2 mb-0">${comment.comment}</p>
                </div>
            </div>`;

            html += template;
        });

        $('#comment_list').html(html);
    }

    function displayError(error) {
        Swal.fire({
            title: `${error.status} ${error.statusText}`,
            icon: 'error'
        });
    }

    function getPostData(postId) {
        $.ajax({
            url: '/rest/post/detail',
            type: 'GET',
            dataType: 'json',
            data: {
                id: postId
            },
            success: (data) => {
                if (data) {
                    $(pageId + '#title').text(data.title);
                    $(pageId + '#writer').text(data.name);
                    $(pageId + '#createdAt').text(data.created_at);
                    $(pageId + '#views').text(data.views);
                    $(pageId + '#content').html(data.content);
                }

                initRedirectBoardListButton(data.board_id);
                initRedirectPostEditButton(data.id);
                initDeletePostButton(data.id);
                initPrevNextPostButton(data);
            },
            error: (xhr) => {
                if (xhr.status === 404) {
                    window.location.href = '/errors/error_404';
                }
            }
        });
    }

    $(document).ready(() => {
        const postId = <?= $id ?>;

        getPostData(postId);
        initCommentPostButton(postId);
        getComments(postId);
    });
</script>
