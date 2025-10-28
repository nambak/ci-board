const BoardList = {
    isLoggedIn: null,
    csrfHash: null,
    csrfTokenName: null,

    initBoardList() {
        // 기본 컬럼 구성
        const columns = [{
            field: 'name',
            title: '제목',
            halign: 'center',
            formatter: (value, row, index) => {
                return `<a href="/board/detail?id=${row.id}">${this.escapeHtml(row.name)}</a>`;
            }
        }, {
            field: 'description',
            title: '설명',
            halign: 'center',
            formatter: (value, row, index) => {
                return this.escapeHtml(row.description || '-');
            }
        }, {
            field: 'created_at',
            title: '등록일',
            align: 'center',
            halign: 'center',
            formatter: (value, row, index) => {
                if (!row.created_at) return '-';
                // 날짜만 추출 (YYYY-MM-DD)
                return row.created_at.split(' ')[0];
            }
        }];

        // 로그인한 경우에만 관리 컬럼 추가
        if (this.isLoggedIn) {
            columns.push({
                field: 'actions',
                title: '관리',
                align: 'center',
                halign: 'center',
                formatter: (value, row, index) => {
                    return `
                        <button
                            class="btn btn-sm btn-outline-primary edit-board"
                            data-id="${row.id}"
                            data-name="${this.escapeHtml(row.name)}"
                            data-description="${this.escapeHtml(row.description)}"
                        >수정
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger delete-board"
                            data-id="${row.id}"
                            data-name="${this.escapeHtml(row.name)}"
                        >삭제</button>
                    `;
                }
            });
        }

        // 테이블 초기화
        $('#board_list_table').bootstrapTable({
            url: '/rest/board',
            columns: columns,
            pagination: true,
            headerStyle: (column) => {
                return {
                    classes: 'table-dark'
                }
            }
        });
    },

    initSubmitCreateBoard() {
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

            formData[this.csrfTokenName] = this.csrfHash;

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
                    $('#board_list_table').bootstrapTable('refresh');

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
    },

    // 모달이 닫힐 때 폼 초기화
    initCreateBoardModal() {
        $('#createBoardModal').on('hidden.bs.modal', () => {
            const form = document.getElementById('createBoardForm');
            form.reset();
            form.classList.remove('was-validated');
            // 모달 제목을 기본값으로 복원
            $('#createBoardModalLabel').text('새 게시판 생성');
        });
    },

    // 수정 버튼 클릭
    initEditBoard() {
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
    },

    // 삭제 버튼 클릭
    initDeleteBoard() {
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
                    deleteData[this.csrfTokenName] = this.csrfHash;
                    $.ajax({
                        url: `/rest/board/${boardId}`,
                        type: 'DELETE',
                        data: deleteData,
                        success: (response) => {
                            Swal.fire({
                                title: '게시판이 삭제되었습니다.',
                                icon: 'success'
                            });
                            $('#board_list_table').bootstrapTable('refresh');
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
    },

    escapeHtml(str) {
        return String(str === undefined || str === null ? '' : str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;');
    },

    init(isLoggedIn, csrfHash, csrfTokenName) {
        this.isLoggedIn = isLoggedIn;
        this.csrfHash = csrfHash;
        this.csrfTokenName = csrfTokenName;

        this.initBoardList();
        this.initSubmitCreateBoard();
        this.initCreateBoardModal();
        this.initEditBoard();
        this.initDeleteBoard();
    }
}

$(document).ready(() => {
    const form = $('#board_list');
    const isLoggedIn = form.data('is-logged-in');
    const csrfHash = form.data('csrf-hash');
    const csrfTokenName = form.data('csrf-token-name');

    BoardList.init(isLoggedIn, csrfHash, csrfTokenName);
});


