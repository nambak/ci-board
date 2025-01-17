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
                    <button id="redirectEditPost" class="btn btn-outline-primary me-2">수정</button>
                    <button id="deletePost" class="btn btn-outline-danger">삭제</button>
                </div>
                <div>
                    <button class="btn btn-outline-primary me-2">이전글</button>
                    <button class="btn btn-outline-primary">다음글</button>
                </div>
            </div>
        </div>
    </div>
    <!-- TODO: 댓글 영역 분리하기 -->
    <!-- 댓글 영역 -->
    <div class="row">
        <div class="col">
            <h5 class="mb-3">댓글</h5>

            <!-- 댓글 작성 폼 -->
            <div class="card mb-3">
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <textarea class="form-control" rows="3" placeholder="댓글을 입력하세요"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">댓글 작성</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 댓글 목록 -->
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>댓글작성자</strong>
                            <small class="text-muted ms-2">2024-12-06 14:30</small>
                        </div>
                        <div>
                            <button class="btn btn-sm text-primary">수정</button>
                            <button class="btn btn-sm text-danger">삭제</button>
                        </div>
                    </div>
                    <p class="mt-2 mb-0">댓글 내용이 여기에 표시됩니다.</p>
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

    function initDeletePostButton(postId) {
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
                        url: '/rest/post/delete',
                        type: 'DELETE',
                        dataType: 'json',
                        data: {
                            id: <?= $id ?>
                        },
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

    $(document).ready(() => {
        $.ajax({
            url: '/rest/post/detail',
            type: 'GET',
            dataType: 'json',
            data: {
                id: <?= $id ?>
            },
            success: (data) => {
                if(data) {
                    $(pageId + '#title').text(data.title);
                    $(pageId + '#writer').text(data.name);
                    $(pageId + '#createdAt').text(data.created_at);
                    $(pageId + '#views').text(data.views);
                    $(pageId + '#content').html(data.content);
                }

                initRedirectBoardListButton(data.board_id);
                initRedirectPostEditButton(data.id);
                initDeletePostButton(data.id);
            },
            error: (error) => {
                Swal.fire({
                    title: error.status,
                    text: error.statusText,
                    icon: 'error'
                });
            }
        });
    });
</script>
