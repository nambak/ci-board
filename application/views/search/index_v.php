<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-search"></i> 게시글 검색</h1>

    <!-- 검색 폼 -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" method="GET" action="<?= site_url('search') ?>">
                <div class="row g-3">
                    <!-- 검색어 -->
                    <div class="col-md-6">
                        <label for="searchQuery" class="form-label">검색어</label>
                        <input type="text"
                               class="form-control"
                               id="searchQuery"
                               name="query"
                               placeholder="검색어를 입력하세요"
                               value="<?= htmlspecialchars($query) ?>"
                               required>
                    </div>

                    <!-- 검색 타입 -->
                    <div class="col-md-3">
                        <label for="searchType" class="form-label">검색 대상</label>
                        <select class="form-select" id="searchType" name="type">
                            <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>제목+내용</option>
                            <option value="title" <?= $type === 'title' ? 'selected' : '' ?>>제목만</option>
                            <option value="content" <?= $type === 'content' ? 'selected' : '' ?>>내용만</option>
                            <option value="author" <?= $type === 'author' ? 'selected' : '' ?>>작성자</option>
                        </select>
                    </div>

                    <!-- 게시판 필터 -->
                    <div class="col-md-3">
                        <label for="boardFilter" class="form-label">게시판</label>
                        <select class="form-select" id="boardFilter" name="board_id">
                            <option value="">전체 게시판</option>
                            <?php foreach ($boards as $board): ?>
                                <option value="<?= $board->id ?>" <?= $board_id == $board->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($board->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 고급 검색 옵션 -->
                <div class="row g-3 mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSearch">
                            <i class="bi bi-chevron-down"></i> 고급 검색 옵션
                        </button>
                    </div>
                </div>

                <div class="collapse" id="advancedSearch">
                    <div class="row g-3 mt-2">
                        <!-- 시작 날짜 -->
                        <div class="col-md-6">
                            <label for="startDate" class="form-label">시작 날짜</label>
                            <input type="date" class="form-control" id="startDate" name="start_date">
                        </div>

                        <!-- 종료 날짜 -->
                        <div class="col-md-6">
                            <label for="endDate" class="form-label">종료 날짜</label>
                            <input type="date" class="form-control" id="endDate" name="end_date">
                        </div>
                    </div>
                </div>

                <!-- 검색 버튼 -->
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> 검색
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetBtn">
                            <i class="bi bi-arrow-clockwise"></i> 초기화
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 검색 결과 영역 -->
    <div id="searchResults">
        <?php if (!empty($query)): ?>
            <!-- 검색 결과 정보 -->
            <div class="alert alert-info d-flex align-items-center mb-3" id="searchInfo">
                <i class="bi bi-info-circle me-2"></i>
                <div>
                    '<strong id="searchQueryText"><?= htmlspecialchars($query) ?></strong>' 검색 결과:
                    <span id="totalResults" class="badge bg-primary">검색 중...</span>
                </div>
            </div>

            <!-- 검색 결과 테이블 -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="searchResultTable">
                            <thead class="table-dark">
                                <tr>
                                    <th data-field="board_name" data-width="120" data-align="center">게시판</th>
                                    <th data-field="title" data-formatter="titleFormatter">제목</th>
                                    <th data-field="author" data-width="120" data-align="center">작성자</th>
                                    <th data-field="comment_count" data-width="80" data-align="center">댓글</th>
                                    <th data-field="views" data-width="80" data-align="center">조회</th>
                                    <th data-field="created_at" data-width="180" data-align="center" data-formatter="dateFormatter">작성일</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary text-center">
                <i class="bi bi-search" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">검색어를 입력하고 검색 버튼을 클릭하세요.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // 제목 포맷터 - 검색어 하이라이팅
    function titleFormatter(value, row) {
        const escapedTitle = $('<div>').text(value || '').html();
        const url = '<?= site_url('article') ?>/' + row.id;

        // 검색어 하이라이팅
        const query = $('#searchQuery').val();
        let highlightedTitle = escapedTitle;

        if (query) {
            const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            highlightedTitle = escapedTitle.replace(regex, '<mark>$1</mark>');
        }

        return `<a href="${url}" class="text-decoration-none">${highlightedTitle}</a>`;
    }

    // 날짜 포맷터
    function dateFormatter(value) {
        if (!value) return '-';
        return value.split(' ')[0];
    }

    // 검색 실행
    function executeSearch() {
        const query = $('#searchQuery').val().trim();

        if (!query) {
            return;
        }

        const params = {
            query: query,
            type: $('#searchType').val(),
            board_id: $('#boardFilter').val() || undefined,
            start_date: $('#startDate').val() || undefined,
            end_date: $('#endDate').val() || undefined,
            page: 1,
            per_page: 20
        };

        // 테이블 초기화
        $('#searchResultTable').bootstrapTable('destroy');
        $('#searchResultTable').bootstrapTable({
            url: '<?= site_url('rest/article/search') ?>',
            method: 'get',
            queryParams: function(tableParams) {
                return {
                    ...params,
                    page: Math.floor(tableParams.offset / tableParams.limit) + 1,
                    per_page: tableParams.limit
                };
            },
            pagination: true,
            sidePagination: 'server',
            pageSize: 20,
            pageList: [10, 20, 50, 100],
            responseHandler: function(res) {
                // 결과 개수 업데이트
                $('#totalResults').text(res.pagination.total + '건');

                return {
                    total: res.pagination.total,
                    rows: res.rows || []
                };
            },
            onLoadError: function(status, res) {
                console.error('검색 오류:', status, res);
                Swal.fire({
                    icon: 'error',
                    title: '검색 오류',
                    text: '검색 중 오류가 발생했습니다.'
                });
            },
            onLoadSuccess: function(data) {
                if (data.rows.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: '검색 결과 없음',
                        text: '검색 조건에 맞는 게시글이 없습니다.'
                    });
                }
            }
        });
    }

    // 초기화 버튼
    $('#resetBtn').click(function() {
        $('#searchForm')[0].reset();
        window.location.href = '<?= site_url('search') ?>';
    });

    // 페이지 로드 시 검색 실행
    $(document).ready(function() {
        const query = $('#searchQuery').val().trim();

        if (query) {
            executeSearch();
        }
    });
</script>

<style>
    mark {
        background-color: #fff3cd;
        padding: 0.1em 0.2em;
        border-radius: 0.2em;
    }

    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    #advancedSearch {
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        margin-top: 1rem;
    }

    .alert-info {
        background-color: #e7f3ff;
        border-color: #b3d9ff;
        color: #004085;
    }

    /* 반응형 디자인 */
    @media (max-width: 768px) {
        .table {
            font-size: 0.875rem;
        }

        h1 {
            font-size: 1.5rem;
        }
    }
</style>