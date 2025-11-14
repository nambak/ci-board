<div class="admin-content p-4">
    <div class="container-fluid" id="admin-comment">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">댓글 관리</h1>
        </div>

        <!-- 검색 영역 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="search-input" placeholder="댓글 내용, 작성자, 게시글 제목으로 검색...">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="search-btn">
                            <i class="bi bi-search"></i> 검색
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 댓글 목록 테이블 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="comment-list">
                                <thead class="table-dark">
                                    <tr>
                                        <th data-field="id" data-sortable="true" data-width="80" data-align="center">ID</th>
                                        <th data-field="comment" data-formatter="commentFormatter">댓글 내용</th>
                                        <th data-field="writer_name" data-width="120" data-align="center">작성자</th>
                                        <th data-field="article_title" data-formatter="articleFormatter" data-width="200">게시글</th>
                                        <th data-field="board_name" data-width="120" data-align="center">게시판</th>
                                        <th data-field="created_at" data-sortable="true" data-width="180" data-align="center">작성일</th>
                                        <th data-field="actions" data-formatter="actionFormatter" data-width="100" data-align="center">관리</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // CSRF 토큰 가져오기
    const csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    const csrfHash = '<?= $this->security->get_csrf_hash() ?>';

    // 댓글 내용 포맷터 - XSS 방지를 위해 이스케이프 처리 및 길이 제한
    function commentFormatter(value, row) {
        const escapedComment = $('<div>').text(value || '').html();
        const maxLength = 100;

        if (escapedComment.length > maxLength) {
            return `<span title="${escapedComment}">${escapedComment.substring(0, maxLength)}...</span>`;
        }

        return `<span>${escapedComment}</span>`;
    }

    // 게시글 제목 포맷터 - 클릭시 게시글 상세로 이동
    function articleFormatter(value, row) {
        const escapedTitle = $('<div>').text(value || '').html();
        const url = '<?= site_url('article') ?>/' + row.article_id;
        const maxLength = 30;

        let displayTitle = escapedTitle;
        if (escapedTitle.length > maxLength) {
            displayTitle = escapedTitle.substring(0, maxLength) + '...';
        }

        return `<a href="${url}" target="_blank" class="text-decoration-none" title="${escapedTitle}">${displayTitle}</a>`;
    }

    // 액션 버튼 포맷터
    function actionFormatter(value, row) {
        const escapedComment = $('<div>').text(row.comment || '').html();
        const escapedWriter = $('<div>').text(row.writer_name || '').html();

        return `
            <button class="btn btn-sm btn-danger delete-btn"
                    data-id="${row.id}"
                    data-comment="${escapedComment}"
                    data-writer="${escapedWriter}">
                <i class="bi bi-trash"></i>
            </button>
        `;
    }

    // 테이블 초기화
    let currentPage = 1;
    let currentSearch = '';
    let currentSort = 'id';
    let currentOrder = 'desc';

    function initTable() {
        const table = $('#comment-list');

        table.bootstrapTable({
            url: '<?= site_url('rest/comment') ?>',
            method: 'get',
            pagination: true,
            sidePagination: 'server',
            pageSize: 10,
            pageList: [10, 25, 50, 100],
            search: false,
            sortName: 'id',
            sortOrder: 'desc',
            queryParams: function(params) {
                currentPage = Math.floor(params.offset / params.limit) + 1;
                currentSort = params.sort || 'id';
                currentOrder = params.order || 'desc';

                return {
                    page: currentPage,
                    per_page: params.limit,
                    sort: currentSort,
                    order: currentOrder,
                    search: currentSearch
                };
            },
            responseHandler: function(res) {
                if (res.pagination) {
                    return {
                        total: res.pagination.total,
                        rows: res.rows || []
                    };
                }
                return {
                    total: 0,
                    rows: []
                };
            },
            onLoadError: function(status, res) {
                console.error('Error loading comments:', status, res);

                let message = '댓글 목록을 불러오는 중 오류가 발생했습니다.';
                if (status === 403) {
                    message = '접근 권한이 없습니다.';
                } else if (status === 401) {
                    message = '로그인이 필요합니다.';
                    setTimeout(() => {
                        window.location.href = '<?= site_url('auth/login') ?>';
                    }, 2000);
                }

                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: message
                });
            }
        });
    }

    // 검색 기능
    $('#search-btn').click(function() {
        currentSearch = $('#search-input').val().trim();
        $('#comment-list').bootstrapTable('refresh');
    });

    // 엔터키로 검색
    $('#search-input').keypress(function(e) {
        if (e.which === 13) {
            $('#search-btn').click();
        }
    });

    // 삭제 기능
    $(document).on('click', '.delete-btn', function() {
        const commentId = $(this).data('id');
        const commentText = $(this).data('comment');
        const writerName = $(this).data('writer');

        // HTML 이스케이프
        const escapeHtml = (str) => $('<div>').text(str || '').html();

        // 댓글 내용 미리보기 (최대 100자)
        const rawPreview = commentText.length > 100
            ? commentText.substring(0, 100) + '...'
            : commentText;

        const preview = escapeHtml(rawPreview);

        Swal.fire({
            title: '댓글 삭제',
            html: `
                <div class="text-start">
                    <p><strong>작성자:</strong> ${escapeHtml(writerName)}</p>
                    <p><strong>내용:</strong></p>
                    <div class="border rounded p-2 bg-light mb-3" style="max-height: 150px; overflow-y: auto;">
                        ${preview}
                    </div>
                    <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle"></i> 이 작업은 되돌릴 수 없습니다.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '삭제',
            cancelButtonText: '취소',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX로 삭제 요청
                $.ajax({
                    url: `<?= site_url('rest/comment') ?>/${commentId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfHash
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: '삭제 완료',
                            text: '댓글이 삭제되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // 테이블 새로고침
                        $('#comment-list').bootstrapTable('refresh');
                    },
                    error: function(xhr) {
                        let errorMessage = '댓글 삭제 중 오류가 발생했습니다.';

                        if (xhr.status === 403) {
                            errorMessage = '삭제 권한이 없습니다.';
                        } else if (xhr.status === 404) {
                            errorMessage = '댓글을 찾을 수 없습니다.';
                        } else if (xhr.status === 401) {
                            errorMessage = '로그인이 필요합니다.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: '삭제 실패',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });

    // 페이지 로드 시 초기화
    $(document).ready(function() {
        initTable();
    });
</script>

<style>
    .admin-content {
        min-height: calc(100vh - 56px);
        background-color: #f8f9fa;
    }

    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .table-responsive {
        margin: 0;
    }

    #comment-list {
        margin-bottom: 0;
    }

    .delete-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .delete-btn:hover {
        transform: scale(1.05);
    }

    /* 댓글 내용 말줄임 */
    #comment-list td {
        max-width: 400px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* 반응형 디자인 */
    @media (max-width: 768px) {
        .admin-content {
            padding: 1rem !important;
        }

        h1 {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        .table {
            font-size: 0.875rem;
        }

        #comment-list td {
            max-width: 200px;
        }
    }
</style>