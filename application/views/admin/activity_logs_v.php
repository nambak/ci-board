<div class="admin-content p-4">
    <div class="container-fluid" id="admin-activity-logs">
        <h1 class="mb-4">활동 로그</h1>

        <!-- 필터 영역 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">액션 타입</label>
                        <select class="form-select form-select-sm" id="filter-action">
                            <option value="">전체</option>
                            <option value="login">로그인</option>
                            <option value="logout">로그아웃</option>
                            <option value="login_failed">로그인 실패</option>
                            <option value="article_create">게시글 작성</option>
                            <option value="article_update">게시글 수정</option>
                            <option value="article_delete">게시글 삭제</option>
                            <option value="comment_create">댓글 작성</option>
                            <option value="comment_update">댓글 수정</option>
                            <option value="comment_delete">댓글 삭제</option>
                            <option value="password_change">비밀번호 변경</option>
                            <option value="profile_update">프로필 수정</option>
                            <option value="user_role_change">권한 변경</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">대상 타입</label>
                        <select class="form-select form-select-sm" id="filter-target-type">
                            <option value="">전체</option>
                            <option value="user">사용자</option>
                            <option value="article">게시글</option>
                            <option value="comment">댓글</option>
                            <option value="board">게시판</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">시작일</label>
                        <input type="date" class="form-control form-control-sm" id="filter-date-from">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">종료일</label>
                        <input type="date" class="form-control form-control-sm" id="filter-date-to">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IP 주소</label>
                        <input type="text" class="form-control form-control-sm" id="filter-ip" placeholder="IP 주소">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm me-2" id="btn-filter">
                            <i class="bi bi-search"></i> 검색
                        </button>
                        <button class="btn btn-secondary btn-sm" id="btn-reset">
                            <i class="bi bi-arrow-counterclockwise"></i> 초기화
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 로그 목록 테이블 -->
        <div class="row">
            <div class="col-12">
                <table class="table table-striped table-hover" id="activity-log-list"></table>
            </div>
        </div>
    </div>
</div>

<!-- 상세 정보 모달 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">활동 로그 상세</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-content">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script>
    // CSRF 토큰
    const csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    const csrfHash = '<?= $this->security->get_csrf_hash() ?>';

    // 액션 배지 포맷터
    function actionFormatter(value, row) {
        const actionColors = {
            'login': 'success',
            'logout': 'secondary',
            'login_failed': 'danger',
            'article_create': 'primary',
            'article_update': 'info',
            'article_delete': 'warning',
            'comment_create': 'primary',
            'comment_update': 'info',
            'comment_delete': 'warning',
            'password_change': 'dark',
            'profile_update': 'info',
            'user_delete': 'danger',
            'user_role_change': 'danger',
            'board_create': 'primary',
            'board_update': 'info',
            'board_delete': 'warning'
        };

        const color = actionColors[value] || 'secondary';
        return `<span class="badge bg-${color}">${row.action_label}</span>`;
    }

    // 사용자 포맷터
    function userFormatter(value, row) {
        if (row.user_id) {
            return `<a href="javascript:void(0)" class="view-user-logs" data-user-id="${row.user_id}">${value}</a>`;
        }
        return '<span class="text-muted">비회원</span>';
    }

    // IP 주소 포맷터
    function ipFormatter(value, row) {
        return `<a href="javascript:void(0)" class="view-ip-logs" data-ip="${value}">${value}</a>`;
    }

    // 대상 포맷터
    function targetFormatter(value, row) {
        if (!row.target_type) return '-';

        const targetLabels = {
            'user': '사용자',
            'article': '게시글',
            'comment': '댓글',
            'board': '게시판'
        };

        const label = targetLabels[row.target_type] || row.target_type;
        if (row.target_id) {
            return `${label} #${row.target_id}`;
        }
        return label;
    }

    // 상세 보기 버튼 포맷터
    function detailFormatter(value, row) {
        const safeRow = JSON.stringify(row)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        return `
            <button class="btn btn-sm btn-outline-info view-detail" data-row='${safeRow}'>
                <i class="bi bi-eye"></i>
            </button>
        `;
    }

    // 날짜 포맷터
    function dateFormatter(value, row) {
        if (!value) return '-';
        // MySQL datetime은 UTC로 저장되어 있으므로 명시적으로 UTC로 파싱
        const date = new Date(value + ' UTC');
        return date.toLocaleString('ko-KR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    const columns = [
        {
            field: 'id',
            title: 'ID',
            halign: 'center',
            align: 'center',
            width: 70,
            sortable: true
        },
        {
            field: 'user_name',
            title: '사용자',
            halign: 'center',
            align: 'center',
            width: 120,
            formatter: userFormatter
        },
        {
            field: 'action',
            title: '액션',
            halign: 'center',
            align: 'center',
            width: 120,
            sortable: true,
            formatter: actionFormatter
        },
        {
            field: 'target_type',
            title: '대상',
            halign: 'center',
            align: 'center',
            width: 100,
            formatter: targetFormatter
        },
        {
            field: 'description',
            title: '설명',
            halign: 'center',
            align: 'left'
        },
        {
            field: 'ip_address',
            title: 'IP',
            halign: 'center',
            align: 'center',
            width: 130,
            formatter: ipFormatter
        },
        {
            field: 'created_at',
            title: '일시',
            halign: 'center',
            align: 'center',
            width: 170,
            sortable: true,
            formatter: dateFormatter
        },
        {
            field: 'detail',
            title: '상세',
            halign: 'center',
            align: 'center',
            width: 60,
            formatter: detailFormatter
        }
    ];

    // 테이블 초기화
    $('#activity-log-list').bootstrapTable({
        url: '<?= site_url('rest/activity_log') ?>',
        columns: columns,
        pagination: true,
        sidePagination: 'server',
        pageSize: 20,
        pageList: [10, 20, 50, 100],
        sortName: 'created_at',
        sortOrder: 'desc',
        queryParams: (params) => {
            return {
                limit: params.limit,
                offset: params.offset,
                sort: params.sort || 'created_at',
                order: params.order || 'desc',
                action: $('#filter-action').val(),
                target_type: $('#filter-target-type').val(),
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
                ip_address: $('#filter-ip').val()
            };
        },
        headerStyle: (column) => {
            return {
                classes: 'table-dark'
            }
        },
        onLoadError: (status, res) => {
            Swal.fire({
                icon: 'error',
                title: '오류',
                text: '활동 로그를 불러오는 중 오류가 발생했습니다.'
            });
        }
    });

    // 필터 적용
    $('#btn-filter').on('click', function() {
        $('#activity-log-list').bootstrapTable('refresh');
    });

    // 필터 초기화
    $('#btn-reset').on('click', function() {
        $('#filter-action').val('');
        $('#filter-target-type').val('');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-ip').val('');
        $('#activity-log-list').bootstrapTable('refresh');
    });

    // 상세 보기
    $(document).on('click', '.view-detail', function() {
        const row = $(this).data('row');

        let oldData = '-';
        let newData = '-';

        try {
            if (row.old_data) {
                oldData = `<pre class="bg-light p-2 rounded">${JSON.stringify(JSON.parse(row.old_data), null, 2)}</pre>`;
            }
            if (row.new_data) {
                newData = `<pre class="bg-light p-2 rounded">${JSON.stringify(JSON.parse(row.new_data), null, 2)}</pre>`;
            }
        } catch (e) {
            // JSON 파싱 실패 시 원본 표시
            if (row.old_data) oldData = `<pre class="bg-light p-2 rounded">${row.old_data}</pre>`;
            if (row.new_data) newData = `<pre class="bg-light p-2 rounded">${row.new_data}</pre>`;
        }

        const content = `
            <table class="table table-bordered">
                <tr>
                    <th style="width: 120px;">ID</th>
                    <td>${row.id}</td>
                </tr>
                <tr>
                    <th>사용자</th>
                    <td>${row.user_name} (${row.user_email})</td>
                </tr>
                <tr>
                    <th>액션</th>
                    <td>${row.action_label}</td>
                </tr>
                <tr>
                    <th>대상</th>
                    <td>${row.target_type ? `${row.target_type} #${row.target_id}` : '-'}</td>
                </tr>
                <tr>
                    <th>설명</th>
                    <td>${row.description || '-'}</td>
                </tr>
                <tr>
                    <th>IP 주소</th>
                    <td>${row.ip_address}</td>
                </tr>
                <tr>
                    <th>User-Agent</th>
                    <td><small>${row.user_agent || '-'}</small></td>
                </tr>
                <tr>
                    <th>일시</th>
                    <td>${row.created_at}</td>
                </tr>
            </table>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>변경 전 데이터</h6>
                    ${oldData}
                </div>
                <div class="col-md-6">
                    <h6>변경 후 데이터</h6>
                    ${newData}
                </div>
            </div>
        `;

        $('#detail-content').html(content);
        $('#detailModal').modal('show');
    });

    // 사용자별 로그 보기
    $(document).on('click', '.view-user-logs', function() {
        const userId = $(this).data('user-id');
        // 필터 적용 후 새로고침
        $('#filter-action').val('');
        $('#filter-target-type').val('');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-ip').val('');

        // 사용자 ID로 필터링 (URL 파라미터로 처리)
        $('#activity-log-list').bootstrapTable('refresh', {
            url: '<?= site_url('rest/activity_log') ?>?user_id=' + userId
        });

        Swal.fire({
            icon: 'info',
            title: '사용자 활동 조회',
            text: '해당 사용자의 활동 로그를 표시합니다.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // IP별 로그 보기
    $(document).on('click', '.view-ip-logs', function() {
        const ip = $(this).data('ip');
        $('#filter-ip').val(ip);
        $('#activity-log-list').bootstrapTable('refresh');

        Swal.fire({
            icon: 'info',
            title: 'IP 활동 조회',
            text: `IP ${ip}의 활동 로그를 표시합니다.`,
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Enter 키로 검색
    $('#filter-ip').on('keypress', function(e) {
        if (e.which === 13) {
            $('#btn-filter').click();
        }
    });
</script>

<style>
    .admin-content {
        min-height: calc(100vh - 56px);
        background-color: #f8f9fa;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }

    .view-user-logs, .view-ip-logs {
        cursor: pointer;
        text-decoration: none;
    }

    .view-user-logs:hover, .view-ip-logs:hover {
        text-decoration: underline;
    }

    pre {
        font-size: 0.75rem;
        max-height: 200px;
        overflow-y: auto;
    }

    /* 반응형 디자인 */
    @media (max-width: 768px) {
        .admin-content {
            padding: 1rem !important;
        }

        h1 {
            font-size: 1.5rem;
        }

        .table {
            font-size: 0.8rem;
        }

        .badge {
            font-size: 0.65rem;
        }
    }
</style>
