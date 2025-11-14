<div class="admin-content p-4">
    <div class="container-fluid" id="admin-user">
        <h1 class="mb-4">사용자 관리</h1>

        <div class="row mt-4">
            <div class="col-12">
                <table class="table table-striped" id="user-list"></table>
            </div>
        </div>
    </div>
</div>

<script>
    // CSRF 토큰
    const csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    const csrfHash = '<?= $this->security->get_csrf_hash() ?>';

    // 권한 배지 포맷터
    function roleFormatter(value, row) {
        const role = value || 'user';
        if (role === 'admin') {
            return '<span class="badge bg-danger">관리자</span>';
        }
        return '<span class="badge bg-secondary">일반 사용자</span>';
    }

    // 권한 변경 버튼 포맷터
    function actionFormatter(value, row) {
        // 본인은 권한 변경 불가
        if (row.is_owner) {
            return '<span class="text-muted small">본인</span>';
        }

        const currentRole = row.role || 'user';
        const newRole = currentRole === 'admin' ? 'user' : 'admin';
        const buttonClass = newRole === 'admin' ? 'btn-danger' : 'btn-secondary';
        const buttonText = newRole === 'admin' ? '관리자로 변경' : '일반으로 변경';
        const escapedName = $('<div>').text(row.name).html();

        return `
            <button class="btn btn-sm ${buttonClass} change-role-btn"
                    data-id="${row.id}"
                    data-name="${escapedName}"
                    data-current-role="${currentRole}"
                    data-new-role="${newRole}">
                <i class="bi bi-arrow-repeat"></i> ${buttonText}
            </button>
        `;
    }

    const columns = [{
        field: 'no',
        title: 'No.',
        halign: 'center',
        align: 'center',
        width: 80,
        formatter: (value, row, index) => {
            const table = $('#user-list').bootstrapTable('getOptions');
            const pageNumber = table.pageNumber || 1;
            const pageSize = table.pageSize || 10;
            const totalRows = table.totalRows || 0;

            return totalRows - (index + (pageNumber - 1) * pageSize);
        }
    },
    {
        field: 'name',
        title: '사용자명',
        halign: 'center',
        align: 'center',
        width: 150
    },
    {
        field: 'email',
        title: '메일주소',
        halign: 'center',
        align: 'center'
    },
    {
        field: 'role',
        title: '권한',
        halign: 'center',
        align: 'center',
        width: 120,
        formatter: roleFormatter
    },
    {
        field: 'article_count',
        title: '게시물 수',
        halign: 'center',
        align: 'right',
        width: 100
    },
    {
        field: 'comment_count',
        title: '댓글 수',
        halign: 'center',
        align: 'right',
        width: 100
    },
    {
        field: 'created_at',
        title: '등록일',
        align: 'center',
        halign: 'center',
        width: 180
    },
    {
        field: 'actions',
        title: '관리',
        halign: 'center',
        align: 'center',
        width: 150,
        formatter: actionFormatter
    }];

    // 테이블 초기화
    $('#user-list').bootstrapTable({
        url: '<?= site_url('rest/user') ?>',
        columns: columns,
        pagination: true,
        sidePagination: 'server',
        pageSize: 10,
        pageList: [10, 25, 50, 100],
        queryParams: (params) => {
            return {
                limit: params.limit,
                offset: params.offset,
                sort: params.sort || 'id',
                order: params.order || 'desc'
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
                text: '사용자 목록을 불러오는 중 오류가 발생했습니다.'
            });
        }
    });

    // 권한 변경 버튼 클릭 이벤트
    $(document).on('click', '.change-role-btn', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        const currentRole = $(this).data('current-role');
        const newRole = $(this).data('new-role');

        const currentRoleText = currentRole === 'admin' ? '관리자' : '일반 사용자';
        const newRoleText = newRole === 'admin' ? '관리자' : '일반 사용자';

        Swal.fire({
            title: '권한 변경 확인',
            html: `
                <div class="text-start">
                    <p><strong>사용자:</strong> ${userName}</p>
                    <p><strong>현재 권한:</strong> <span class="badge bg-${currentRole === 'admin' ? 'danger' : 'secondary'}">${currentRoleText}</span></p>
                    <p><strong>변경 권한:</strong> <span class="badge bg-${newRole === 'admin' ? 'danger' : 'secondary'}">${newRoleText}</span></p>
                    <hr>
                    <p class="text-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        ${newRole === 'admin' ?
                            '관리자 권한으로 변경하면 모든 관리 기능에 접근할 수 있습니다.' :
                            '일반 사용자로 변경하면 관리자 기능에 접근할 수 없게 됩니다.'}
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: newRole === 'admin' ? '#dc3545' : '#6c757d',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '변경',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX로 권한 변경 요청
                $.ajax({
                    url: `<?= site_url('rest/user/role') ?>/${userId}`,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        role: newRole
                    }),
                    headers: {
                        'X-CSRF-TOKEN': csrfHash
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: '변경 완료',
                            text: response.message || '권한이 변경되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // 테이블 새로고침
                        $('#user-list').bootstrapTable('refresh');
                    },
                    error: function(xhr) {
                        let errorMessage = '권한 변경 중 오류가 발생했습니다.';

                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            // JSON 파싱 실패시 기본 메시지 사용
                        }

                        if (xhr.status === 403) {
                            errorMessage = '권한 변경 권한이 없습니다.';
                        } else if (xhr.status === 404) {
                            errorMessage = '사용자를 찾을 수 없습니다.';
                        } else if (xhr.status === 401) {
                            errorMessage = '로그인이 필요합니다.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: '변경 실패',
                            text: errorMessage
                        });
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

    .change-role-btn {
        white-space: nowrap;
    }

    .badge {
        font-size: 0.875rem;
        padding: 0.35em 0.65em;
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
            font-size: 0.875rem;
        }

        .change-role-btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    }
</style>