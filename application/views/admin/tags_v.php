<div class="admin-content p-4">
    <div class="container-fluid" id="admin-tags">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">태그 관리</h1>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm me-2" id="deleteUnusedBtn">
                    <i class="bi bi-trash"></i> 미사용 태그 삭제
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#createTagModal">
                    <i class="bi bi-plus"></i> 태그 추가
                </button>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <table class="table table-striped" id="tag-list"></table>
            </div>
        </div>
    </div>
</div>

<!-- 태그 생성 모달 -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">태그 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newTagName" class="form-label">태그 이름</label>
                    <input type="text" class="form-control" id="newTagName" maxlength="50" placeholder="태그 이름 입력">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="createTagBtn">생성</button>
            </div>
        </div>
    </div>
</div>

<!-- 태그 수정 모달 -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">태그 수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editTagId">
                <div class="mb-3">
                    <label for="editTagName" class="form-label">태그 이름</label>
                    <input type="text" class="form-control" id="editTagName" maxlength="50">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="updateTagBtn">수정</button>
            </div>
        </div>
    </div>
</div>

<!-- 태그 병합 모달 -->
<div class="modal fade" id="mergeTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">태그 병합</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mergeSourceId">
                <p class="mb-3">
                    <strong id="mergeSourceName"></strong> 태그를 다른 태그에 병합합니다.
                    <br><small class="text-muted">병합 후 원본 태그는 삭제됩니다.</small>
                </p>
                <div class="mb-3">
                    <label for="mergeTargetId" class="form-label">병합 대상 태그</label>
                    <select class="form-select" id="mergeTargetId">
                        <option value="">태그 선택...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-warning" id="confirmMergeBtn">병합</button>
            </div>
        </div>
    </div>
</div>

<script>
    // CSRF 토큰
    const csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    const csrfHash = '<?= $this->security->get_csrf_hash() ?>';

    // 사용량 배지 포맷터
    function usageFormatter(value, row) {
        const count = parseInt(value) || 0;
        if (count === 0) {
            return '<span class="badge bg-secondary">미사용</span>';
        }
        return `<span class="badge bg-primary">${count}개 게시글</span>`;
    }

    // 슬러그 포맷터
    function slugFormatter(value, row) {
        return `<a href="/tag/${value}" target="_blank" class="text-decoration-none">
            <code>${value}</code>
            <i class="bi bi-box-arrow-up-right small ms-1"></i>
        </a>`;
    }

    // 액션 버튼 포맷터
    function actionFormatter(value, row) {
        return `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary edit-btn" data-id="${row.id}" data-name="${escapeHtml(row.name)}" title="수정">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-warning merge-btn" data-id="${row.id}" data-name="${escapeHtml(row.name)}" title="병합">
                    <i class="bi bi-arrow-left-right"></i>
                </button>
                <button class="btn btn-outline-danger delete-btn" data-id="${row.id}" data-name="${escapeHtml(row.name)}" title="삭제">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    const columns = [
        {
            field: 'id',
            title: 'ID',
            halign: 'center',
            align: 'center',
            width: 80,
            sortable: true
        },
        {
            field: 'name',
            title: '태그명',
            halign: 'center',
            sortable: true
        },
        {
            field: 'slug',
            title: '슬러그',
            halign: 'center',
            formatter: slugFormatter
        },
        {
            field: 'usage_count',
            title: '사용량',
            halign: 'center',
            align: 'center',
            width: 120,
            sortable: true,
            formatter: usageFormatter
        },
        {
            field: 'created_at',
            title: '생성일',
            halign: 'center',
            align: 'center',
            width: 160,
            sortable: true
        },
        {
            field: 'actions',
            title: '관리',
            halign: 'center',
            align: 'center',
            width: 150,
            formatter: actionFormatter
        }
    ];

    // 테이블 초기화
    $('#tag-list').bootstrapTable({
        url: '/rest/tag',
        columns: columns,
        pagination: true,
        sidePagination: 'server',
        pageSize: 20,
        pageList: [10, 20, 50, 100],
        search: true,
        showRefresh: true,
        queryParams: (params) => {
            return {
                limit: params.limit,
                offset: params.offset,
                sort: params.sort || 'usage_count',
                order: params.order || 'desc',
                search: params.search || ''
            };
        },
        headerStyle: () => ({classes: 'table-dark'}),
        onLoadError: (status, res) => {
            Swal.fire({
                icon: 'error',
                title: '오류',
                text: '태그 목록을 불러오는 중 오류가 발생했습니다.'
            });
        }
    });

    // 태그 생성
    $('#createTagBtn').on('click', function () {
        const name = $('#newTagName').val().trim();

        if (!name) {
            Swal.fire({
                icon: 'warning',
                text: '태그 이름을 입력해주세요.'
            });
            return;
        }

        $.ajax({
            url: '/rest/tag',
            method: 'POST',
            data: {
                name: name,
                [csrfName]: csrfHash
            },
            success: (response) => {
                $('#createTagModal').modal('hide');
                $('#newTagName').val('');
                $('#tag-list').bootstrapTable('refresh');
                Swal.fire({
                    icon: 'success',
                    text: '태그가 생성되었습니다.',
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            error: (xhr) => {
                const message = xhr.responseJSON?.message || '태그 생성에 실패했습니다.';
                Swal.fire({
                    icon: 'error',
                    text: message
                });
            }
        });
    });

    // 수정 모달 열기
    $(document).on('click', '.edit-btn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#editTagId').val(id);
        $('#editTagName').val(name);
        $('#editTagModal').modal('show');
    });

    // 태그 수정
    $('#updateTagBtn').on('click', function () {
        const id = $('#editTagId').val();
        const name = $('#editTagName').val().trim();
        if (!name) {
            Swal.fire({icon: 'warning', text: '태그 이름을 입력해주세요.'});
            return;
        }

        $.ajax({
            url: `/rest/tag/${id}`,
            method: 'PUT',
            data: {name: name},
            success: function (response) {
                $('#editTagModal').modal('hide');
                $('#tag-list').bootstrapTable('refresh');
                Swal.fire({icon: 'success', text: '태그가 수정되었습니다.', timer: 1500, showConfirmButton: false});
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || '태그 수정에 실패했습니다.';
                Swal.fire({icon: 'error', text: message});
            }
        });
    });

    // 태그 삭제
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const html = `
            <strong>${escapeHtml(name)}</strong> 태그를 삭제하시겠습니까?<br>
            <small class="text-muted">이 태그와 연결된 게시글 관계도 삭제됩니다.</small>
        `;

        Swal.fire({
            title: '태그 삭제',
            html: html,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/rest/tag/${id}`,
                    method: 'DELETE',
                    success: (response) => {
                        $('#tag-list').bootstrapTable('refresh');
                        Swal.fire({
                            icon: 'success',
                            text: '태그가 삭제되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: (xhr) => {
                        const message = xhr.responseJSON?.message || '태그 삭제에 실패했습니다.';
                        Swal.fire({
                            icon: 'error',
                            text: message
                        });
                    }
                });
            }
        });
    });

    // 병합 모달 열기
    $(document).on('click', '.merge-btn', () => {
        const sourceId = $(this).data('id');
        const sourceName = $(this).data('name');

        $('#mergeSourceId').val(sourceId);
        $('#mergeSourceName').text(sourceName);

        // 다른 태그 목록 로드
        $.ajax({
            url: '/rest/tag',
            method: 'GET',
            data: {
                limit: 100,
                sort: 'usage_count',
                order: 'desc'
            },
            success: (response) => {
                const $select = $('#mergeTargetId');
                $select.empty().append('<option value="">태그 선택...</option>');

                response.rows.forEach(tag => {
                    if (tag.id != sourceId) {
                        $select.append(`<option value="${tag.id}">${escapeHtml(tag.name)} (${tag.usage_count}개)</option>`);
                    }
                });

                $('#mergeTagModal').modal('show');
            }
        });
    });

    // 태그 병합
    $('#confirmMergeBtn').on('click', function () {
        const sourceId = $('#mergeSourceId').val();
        const targetId = $('#mergeTargetId').val();

        if (!targetId) {
            Swal.fire({icon: 'warning', text: '병합 대상 태그를 선택해주세요.'});
            return;
        }

        $.ajax({
            url: '/rest/tag/merge',
            method: 'POST',
            data: {source_id: sourceId, target_id: targetId},
            success: function (response) {
                $('#mergeTagModal').modal('hide');
                $('#tag-list').bootstrapTable('refresh');
                Swal.fire({icon: 'success', text: '태그가 병합되었습니다.', timer: 1500, showConfirmButton: false});
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || '태그 병합에 실패했습니다.';
                Swal.fire({icon: 'error', text: message});
            }
        });
    });

    // 미사용 태그 삭제
    $('#deleteUnusedBtn').on('click', function () {
        Swal.fire({
            title: '미사용 태그 삭제',
            text: '사용되지 않는 모든 태그를 삭제하시겠습니까?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/rest/tag/unused',
                    method: 'DELETE',
                    success: function (response) {
                        $('#tag-list').bootstrapTable('refresh');
                        Swal.fire({
                            icon: 'success',
                            text: response.message || '미사용 태그가 삭제되었습니다.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || '미사용 태그 삭제에 실패했습니다.';
                        Swal.fire({icon: 'error', text: message});
                    }
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

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }

    @media (max-width: 768px) {
        .admin-content {
            padding: 1rem !important;
        }

        h1 {
            font-size: 1.5rem;
        }
    }
</style>
