<div class="container my-5" id="board_list"
     data-is-logged-in="<?= is_logged_in() ?>"
     data-csrf-token="<?= $this->security->get_csrf_hash() ?>"
     data-csrf-name="<?= $this->security->get_csrf_token_name() ?>">
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

<script src="/assets/js/board-list.js" defer></script>
