<div class="admin-content p-4">
    <div class="container-fluid" id="admin-board">
        <h1 class="mb-4">게시판 관리</h1>
        <div class="row mt-4">
            <div class="col-12 mb-3 text-end">
                <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#createBoardModal"
                > 게시판 생성
                </button>
            </div>
            <div class="col-12">
                <table class="table table-striped" id="board-list"></table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Board Modal -->
<div class="modal fade" id="createBoardModal" tabindex="-1" aria-labelledby="createBoardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="createBoardModalLabel">새 게시판 생성</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createBoardForm" class="needs-validation" novalidate>
                    <input type="hidden" id="boardId">
                    <div class="mb-3">
                        <label for="boardName" class="form-label">게시판 이름</label>
                        <input type="text" class="form-control" id="boardName" required>
                        <div class="invalid-feedback">
                            게시판 이름을 입력해주세요.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="boardDescription" class="form-label">게시판 설명</label>
                        <input type="text" class="form-control" id="boardDescription">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="submitCreateBoard">확인</button>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfTokenName = '<?= $this->security->get_csrf_token_name() ?>';
    const csrfHash = '<?= $this->security->get_csrf_hash() ?>';

    const columns = [{
        field: 'no',
        title: 'No.',
        halign: 'center',
        align: 'right',
        formatter: (value, row, index) => {
                const table = $('#board-list').bootstrapTable('getOptions');
                const pageNumber = table.pageNumber || 1;
                const pageSize = table.pageSize || 10;
                const totalRows = table.totalRows || 0;

                return totalRows - (index + (pageNumber - 1) * pageSize);
        }},
        {
            field: 'name',
            title: '제목',
            halign: 'center',
            formatter: (value, row, index) => {
                return `<a href="/board/detail?id=${row.id}">${escapeHtml(row.name)}</a>`;
            }
        },
        {
            field: 'description',
            title: '설명',
            halign: 'center',
        },
        {
            field: 'article_count',
            title: '게시물 수',
            halign: 'center',
            align: 'right'
        },
        {
            field: 'created_at',
            title: '등록일',
            align: 'center',
            halign: 'center'
        },
        {
            title: '관리',
            align: 'center',
            formatter: (value, row, index) => {
                return `
                        <button
                            class="btn btn-sm btn-outline-primary edit-board"
                            data-id="${row.id}"
                            data-name="${escapeHtml(row.name)}"
                            data-description="${escapeHtml(row.description)}"
                        >수정
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger delete-board"
                            data-id="${row.id}"
                            data-name="${escapeHtml(row.name)}"
                        >삭제</button>
                    `;
            }
        }];

    function escapeHtml(str) {
        return String(str === undefined || str === null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // 테이블 초기화
    $('#board-list').bootstrapTable({
        url: '<?= site_url('rest/board') ?>',
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
                text: '게시판 목록을 불러오는 중 오류가 발생했습니다.'
            });
        }
    });

    // 수정 버튼 클릭
    function initEditBoard() {
        $(document).on('click', '.edit-board', (event) => {
            const $target = $(event.currentTarget);
            const boardId = $target.data('id');
            const boardName = $target.data('name');
            const boardDescription = $target.data('description');

            // 모달 제목 변경
            $('#createBoardModalLabel').text('게시판 수정');

            // 폼에 기존 데이터 채우기
            $('#boardId').val(boardId);
            $('#boardName').val(boardName);
            $('#boardDescription').val(boardDescription || '');

            // 모달 열기
            $('#createBoardModal').modal('show');
        });
    }

    // 삭제 버튼 클릭
    function initDeleteBoard() {
        $(document).on('click', '.delete-board', (event) => {
            const $target = $(event.currentTarget);
            const boardId = $target.data('id');
            const boardName = $target.data('name');

            Swal.fire({
                title: '게시판 삭제',
                text: `"${boardName}" 게시판을 삭제하시겠습니까?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '삭제',
                cancelButtonText: '취소',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    const deleteData = {};
                    deleteData[csrfTokenName] = csrfHash;
                    $.ajax({
                        url: `/rest/board/${boardId}`,
                        type: 'DELETE',
                        data: deleteData,
                        success: (response) => {
                            Swal.fire({
                                title: '게시판이 삭제되었습니다.',
                                icon: 'success'
                            });
                            $('#board-list').bootstrapTable('refresh');
                        },
                        error: (error) => {
                            Swal.fire({
                                title: '삭제 실패',
                                text: error.statusText,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
    }

    function initSubmitCreateBoard() {
        $('#submitCreateBoard').on('click', () => {
            const form = document.getElementById('createBoardForm');

            // 부트스트랩 validation 체크
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const boardId = $('#boardId').val();
            const boardName = $('#boardName').val();
            const boardDescription = $('#boardDescription').val();

            // 수정 모드인지 생성 모드인지 확인
            const isEditMode = boardId !== '';
            const url = isEditMode ? `/rest/board/${boardId}` : '/rest/board';
            const method = isEditMode ? 'PUT' : 'POST';
            const successMessage = isEditMode ? '게시판이 수정되었습니다.' : '게시판이 생성되었습니다.';

            const formData = {
                name: boardName,
                description: boardDescription,
            }

            formData[csrfTokenName] = csrfHash;

            // API 요청
            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: (response) => {
                    // 모달 닫기
                    $('#createBoardModal').modal('hide');

                    // 폼 초기화
                    form.reset();
                    form.classList.remove('was-validated');

                    // 테이블 새로고침
                    $('#board-list').bootstrapTable('refresh');

                    // 성공 메시지
                    Swal.fire({
                        title: successMessage,
                        icon: 'success'
                    });
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
    }

    $(document).ready(() => {
        initEditBoard();
        initDeleteBoard();
        initSubmitCreateBoard();
    });
</script>
