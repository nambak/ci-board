<div class="container my-5" id="board_list">
    <h1>게시판</h1>
    <div class="row mb-3">
        <table class="table table-striped" id="board_list_table"></table>
    </div>
    <div class="row">
        <div class="col">
            <?php if (is_logged_in()) : ?>
                <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#createBoardModal"
                > 새 게시판
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Board Modal -->
<div class="modal fade" id="createBoardModal" tabindex="-1" aria-labelledby="createBoardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="createBoardModalLabel">새 게시판 생성</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createBoardForm" class="needs-validation" novalidate>
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

<script defer>
    $(document).ready(function () {
        $('#board_list_table').bootstrapTable({
            url: '/rest/board',
            columns: [{
                field: 'id',
                title: '번호',
                formatter: function (value, row, index) {
                    return row.id;
                }
            }, {
                field: 'name',
                title: '제목',
                formatter: function (value, row, index) {
                    return `<a href="/board/detail?id=${row.id}">${row.name}</a>`;
                }
            }, {
                field: 'description',
                title: '설명',
                formatter: function (value, row, index) {
                    return row.description;
                }
            }, {
                field: 'created_at',
                title: '등록일',
                formatter: function (value, row, index) {
                    return row.created_at;
                }
            }],
            pagination: true,
            headerStyle: function (column) {
                return {
                    classes: 'table-dark'
                }
            }
        });

        $('#submitCreateBoard').on('click', function () {
            const form = document.getElementById('createBoardForm');

            // 부트스트랩 validation 체크
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const boardName = $('#boardName').val();
            const boardDescription = $('#boardDescription').val();

            // API 요청
            $.ajax({
                url: '/rest/board',
                type: 'POST',
                data: {
                    name: boardName,
                    description: boardDescription,
                    <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>'
                },
                success: function (response) {
                    // 모달 닫기
                    $('#createBoardModal').modal('hide');

                    // 폼 초기화
                    form.reset();
                    form.classList.remove('was-validated');

                    // 테이블 새로고침
                    $('#board_list_table').bootstrapTable('refresh');

                    // 성공 메시지
                    Swal.fire({
                        title: '게시판이 생성되었습니다.',
                        icon: 'success'
                    });
                },
                error: function (error) {
                    Swal.fire({
                        title: error.status,
                        text: error.statusText,
                        icon: 'error'
                    });
                }
            });
        });

        // 모달이 닫힐 때 폼 초기화
        $('#createBoardModal').on('hidden.bs.modal', function () {
            const form = document.getElementById('createBoardForm');
            form.reset();
            form.classList.remove('was-validated');
        });
    });
</script>
