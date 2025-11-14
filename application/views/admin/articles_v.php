<div class="admin-content p-4">
    <div class="container-fluid" id="admin-article">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">게시글 관리</h1>
        </div>

        <!-- 검색 및 필터 영역 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="search-input" placeholder="제목, 내용, 작성자로 검색...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="board-filter">
                            <option value="">전체 게시판</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="search-btn">
                            <i class="bi bi-search"></i> 검색
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 게시글 목록 테이블 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="article-list">
                                <thead class="table-dark">
                                    <tr>
                                        <th data-field="id" data-sortable="true" data-width="80" data-align="center">ID</th>
                                        <th data-field="board_name" data-width="120" data-align="center">게시판</th>
                                        <th data-field="title" data-formatter="titleFormatter">제목</th>
                                        <th data-field="author" data-width="120" data-align="center">작성자</th>
                                        <th data-field="views" data-sortable="true" data-width="80" data-align="center">조회</th>
                                        <th data-field="comment_count" data-width="80" data-align="center">댓글</th>
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

    // 제목 포맷터 - XSS 방지를 위해 이스케이프 처리
    function titleFormatter(value, row) {
        const escapedTitle = $('<div>').text(value || '').html();
        const url = '<?= site_url('article/view') ?>/' + row.board_id + '/' + row.id;
        return `<a href="${url}" target="_blank" class="text-decoration-none">${escapedTitle}</a>`;
    }

    // 액션 버튼 포맷터
    function actionFormatter(value, row) {
        return `
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" data-title="${$('<div>').text(row.title).html()}">
                <i class="bi bi-trash"></i>
            </button>
        `;
    }

    // 게시판 목록 로드
    function loadBoards() {
        $.ajax({
            url: '<?= site_url('rest/board') ?>',
            method: 'GET',
            success: function(response) {
                const select = $('#board-filter');
                if (response.rows && Array.isArray(response.rows)) {
                    response.rows.forEach(board => {
                        const escapedName = $('<div>').text(board.name).html();
                        select.append(`<option value="${board.id}">${escapedName}</option>`);
                    });
                }
            },
            error: function() {
                console.error('게시판 목록 로드 실패');
            }
        });
    }

    // 테이블 초기화
    let currentPage = 1;
    let currentSearch = '';
    let currentBoardId = '';

    function initTable() {
        const table = $('#article-list');

        table.bootstrapTable({
            url: '<?= site_url('rest/article/list') ?>',
            method: 'get',
            pagination: true,
            sidePagination: 'server',
            pageSize: 10,
            pageList: [10, 25, 50, 100],
            search: false,
            queryParams: function(params) {
                currentPage = Math.floor(params.offset / params.limit) + 1;

                return {
                    page: currentPage,
                    per_page: params.limit,
                    search: currentSearch,
                    board_id: currentBoardId
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
                console.error('Error loading articles:', status, res);

                let message = '게시글 목록을 불러오는 중 오류가 발생했습니다.';
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
        currentBoardId = $('#board-filter').val();
        $('#article-list').bootstrapTable('refresh');
    });

    // 엔터키로 검색
    $('#search-input').keypress(function(e) {
        if (e.which === 13) {
            $('#search-btn').click();
        }
    });

    // 게시판 필터 변경 시 자동 검색
    $('#board-filter').change(function() {
        $('#search-btn').click();
    });

    // 삭제 기능
    $(document).on('click', '.delete-btn', function() {
        const articleId = $(this).data('id');
        const articleTitle = $(this).data('title');

        Swal.fire({
            title: '게시글 삭제',
            html: `"<strong>${articleTitle}</strong>"<br>게시글을 삭제하시겠습니까?<br><br><span class="text-danger">이 작업은 되돌릴 수 없습니다.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX로 삭제 요청
                $.ajax({
                    url: `<?= site_url('rest/article') ?>/${articleId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfHash
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: '삭제 완료',
                            text: '게시글이 삭제되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // 테이블 새로고침
                        $('#article-list').bootstrapTable('refresh');
                    },
                    error: function(xhr) {
                        let errorMessage = '게시글 삭제 중 오류가 발생했습니다.';

                        if (xhr.status === 403) {
                            errorMessage = '삭제 권한이 없습니다.';
                        } else if (xhr.status === 404) {
                            errorMessage = '게시글을 찾을 수 없습니다.';
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
        loadBoards();
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

    #article-list {
        margin-bottom: 0;
    }

    .delete-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .delete-btn:hover {
        transform: scale(1.05);
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
    }
</style>