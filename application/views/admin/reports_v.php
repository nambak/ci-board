<div class="admin-content p-4">
    <div class="container-fluid" id="admin-reports">
        <h1 class="mb-4">신고 관리</h1>

        <!-- 상태별 통계 카드 -->
        <div class="row mb-4" id="statusCards">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">대기중</h5>
                        <h2 id="pendingCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">처리중</h5>
                        <h2 id="processingCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">처리완료</h5>
                        <h2 id="completedCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-secondary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">반려</h5>
                        <h2 id="rejectedCount">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- 필터 영역 -->
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <select class="form-select" id="statusFilter">
                    <option value="">전체 상태</option>
                    <option value="pending">대기중</option>
                    <option value="processing">처리중</option>
                    <option value="completed">처리완료</option>
                    <option value="rejected">반려</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <select class="form-select" id="typeFilter">
                    <option value="">전체 유형</option>
                    <option value="article">게시글</option>
                    <option value="comment">댓글</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button class="btn btn-primary w-100" id="applyFilter">
                    <i class="bi bi-search"></i> 검색
                </button>
            </div>
        </div>

        <!-- 신고 목록 테이블 -->
        <div class="row mt-4">
            <div class="col-12">
                <table class="table table-striped" id="reports-list"></table>
            </div>
        </div>
    </div>
</div>

<!-- 신고 상세 모달 -->
<div class="modal fade" id="reportDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">신고 상세</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportDetailBody">
                <!-- 내용이 동적으로 로드됩니다 -->
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

    // 상태 배지 포맷터
    function statusFormatter(value, row) {
        const statusMap = {
            'pending': '<span class="badge bg-warning">대기중</span>',
            'processing': '<span class="badge bg-info">처리중</span>',
            'completed': '<span class="badge bg-success">처리완료</span>',
            'rejected': '<span class="badge bg-secondary">반려</span>'
        };
        return statusMap[value] || value;
    }

    // 유형 배지 포맷터
    function typeFormatter(value, row) {
        if (value === 'article') {
            return '<span class="badge bg-primary">게시글</span>';
        }
        return '<span class="badge bg-info">댓글</span>';
    }

    // 대상 정보 포맷터
    function targetFormatter(value, row) {
        if (!row.target_info) {
            return '<span class="text-muted">삭제됨</span>';
        }

        const info = row.target_info;
        const title = info.title || info.content;
        const truncated = title.length > 30 ? title.substring(0, 30) + '...' : title;
        const escaped = $('<div>').text(truncated).html();

        return `<a href="${info.url}" target="_blank" class="text-decoration-none">${escaped}</a>`;
    }

    // 액션 버튼 포맷터
    function actionFormatter(value, row) {
        let buttons = `<button class="btn btn-sm btn-outline-primary view-detail-btn" data-id="${row.id}">
            <i class="bi bi-eye"></i> 상세
        </button>`;

        if (row.status === 'pending') {
            buttons += `
                <button class="btn btn-sm btn-info change-status-btn" data-id="${row.id}" data-status="processing">
                    <i class="bi bi-play"></i> 처리중
                </button>
                <button class="btn btn-sm btn-success change-status-btn" data-id="${row.id}" data-status="completed">
                    <i class="bi bi-check"></i> 처리완료
                </button>
                <button class="btn btn-sm btn-secondary change-status-btn" data-id="${row.id}" data-status="rejected">
                    <i class="bi bi-x"></i> 반려
                </button>
            `;
        } else if (row.status === 'processing') {
            buttons += `
                <button class="btn btn-sm btn-success change-status-btn" data-id="${row.id}" data-status="completed">
                    <i class="bi bi-check"></i> 처리완료
                </button>
                <button class="btn btn-sm btn-secondary change-status-btn" data-id="${row.id}" data-status="rejected">
                    <i class="bi bi-x"></i> 반려
                </button>
            `;
        }

        return buttons;
    }

    const columns = [
        {
            field: 'id',
            title: 'ID',
            halign: 'center',
            align: 'center',
            width: 60
        },
        {
            field: 'target_type',
            title: '유형',
            halign: 'center',
            align: 'center',
            width: 80,
            formatter: typeFormatter
        },
        {
            field: 'target_info',
            title: '대상',
            halign: 'center',
            align: 'left',
            formatter: targetFormatter
        },
        {
            field: 'reason_label',
            title: '신고 사유',
            halign: 'center',
            align: 'center',
            width: 100
        },
        {
            field: 'reporter_name',
            title: '신고자',
            halign: 'center',
            align: 'center',
            width: 100
        },
        {
            field: 'status',
            title: '상태',
            halign: 'center',
            align: 'center',
            width: 90,
            formatter: statusFormatter
        },
        {
            field: 'created_at',
            title: '신고일',
            halign: 'center',
            align: 'center',
            width: 150
        },
        {
            field: 'actions',
            title: '관리',
            halign: 'center',
            width: 330,
            formatter: actionFormatter
        }
    ];

    // 테이블 초기화
    $('#reports-list').bootstrapTable({
        url: '<?= site_url('rest/admin/reports') ?>',
        columns: columns,
        pagination: true,
        sidePagination: 'server',
        pageSize: 10,
        pageList: [10, 25, 50, 100],
        queryParams: (params) => {
            return {
                per_page: params.limit,
                page: Math.floor(params.offset / params.limit) + 1,
                status: $('#statusFilter').val(),
                target_type: $('#typeFilter').val()
            };
        },
        responseHandler: (res) => {
            // 상태별 통계 업데이트
            if (res.status_counts) {
                $('#pendingCount').text(res.status_counts.pending || 0);
                $('#processingCount').text(res.status_counts.processing || 0);
                $('#completedCount').text(res.status_counts.completed || 0);
                $('#rejectedCount').text(res.status_counts.rejected || 0);
            }

            return {
                total: res.pagination ? res.pagination.total : 0,
                rows: res.rows || []
            };
        },
        headerStyle: (column) => {
            return {
                classes: 'table-dark'
            }
        },
        onLoadError: (status, res) => {
            if (status === 403) {
                Swal.fire({
                    icon: 'error',
                    title: '접근 거부',
                    text: '관리자 권한이 필요합니다.'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '신고 목록을 불러오는 중 오류가 발생했습니다.'
                });
            }
        }
    });

    // 필터 적용
    $('#applyFilter').on('click', function() {
        $('#reports-list').bootstrapTable('refresh');
    });

    // 상태 변경
    $(document).on('click', '.change-status-btn', function() {
        const reportId = $(this).data('id');
        const newStatus = $(this).data('status');

        const statusText = {
            'processing': '처리중으로',
            'completed': '처리완료로',
            'rejected': '반려로'
        };

        Swal.fire({
            title: '상태 변경',
            text: `이 신고를 ${statusText[newStatus]} 변경하시겠습니까?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '변경',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
                const updateData = {};
                updateData[csrfName] = csrfHash;
                updateData['status'] = newStatus;

                $.ajax({
                    url: `<?= site_url('rest/admin/reports') ?>/${reportId}`,
                    method: 'PUT',
                    data: updateData,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: '변경 완료',
                            text: response.message || '상태가 변경되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#reports-list').bootstrapTable('refresh');
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '변경 실패',
                            text: '상태 변경 중 오류가 발생했습니다.'
                        });
                    }
                });
            }
        });
    });

    // 상세 보기
    $(document).on('click', '.view-detail-btn', function() {
        const reportId = $(this).data('id');

        $.ajax({
            url: `<?= site_url('rest/admin/reports') ?>/${reportId}`,
            method: 'GET',
            success: function(report) {
                let targetHtml = '<p class="text-muted">삭제된 게시물</p>';

                if (report.target_info) {
                    const info = report.target_info;
                    targetHtml = `
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>${info.type === 'article' ? '게시글' : '댓글'}</strong>
                                <a href="${info.url}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                    <i class="bi bi-box-arrow-up-right"></i> 바로가기
                                </a>
                            </div>
                            <div class="card-body">
                                ${info.title ? `<h6>${$('<div>').text(info.title).html()}</h6>` : ''}
                                <p class="text-muted small mb-0">${$('<div>').text(info.content).html()}</p>
                            </div>
                        </div>
                    `;
                }

                const statusBadge = {
                    'pending': '<span class="badge bg-warning">대기중</span>',
                    'processing': '<span class="badge bg-info">처리중</span>',
                    'completed': '<span class="badge bg-success">처리완료</span>',
                    'rejected': '<span class="badge bg-secondary">반려</span>'
                };

                const html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>신고 정보</h6>
                            <table class="table table-sm">
                                <tr><th>신고 ID</th><td>${report.id}</td></tr>
                                <tr><th>신고자</th><td>${$('<div>').text(report.reporter_name).html()} (${$('<div>').text(report.reporter_email).html()})</td></tr>
                                <tr><th>신고 사유</th><td>${report.reason_label}</td></tr>
                                <tr><th>상태</th><td>${statusBadge[report.status]}</td></tr>
                                <tr><th>신고일시</th><td>${report.created_at}</td></tr>
                                ${report.processed_at ? `<tr><th>처리일시</th><td>${report.processed_at}</td></tr>` : ''}
                                ${report.processor_name ? `<tr><th>처리자</th><td>${$('<div>').text(report.processor_name).html()}</td></tr>` : ''}
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>상세 내용</h6>
                            <p class="border p-2 rounded bg-light">${report.detail ? $('<div>').text(report.detail).html() : '<em class="text-muted">상세 내용 없음</em>'}</p>
                        </div>
                    </div>
                    <hr>
                    <h6>신고 대상</h6>
                    ${targetHtml}
                `;

                $('#reportDetailBody').html(html);
                new bootstrap.Modal(document.getElementById('reportDetailModal')).show();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '상세 정보를 불러오는 중 오류가 발생했습니다.'
                });
            }
        });
    });
</script>

<style>
    .admin-content {
        min-height: calc(100vh - 56px);
        background-color: #f8f9fa;
    }

    .card h2 {
        font-size: 2rem;
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        .admin-content {
            padding: 1rem !important;
        }

        h1 {
            font-size: 1.5rem;
        }

        .card h2 {
            font-size: 1.5rem;
        }

        .table {
            font-size: 0.875rem;
        }
    }
</style>
