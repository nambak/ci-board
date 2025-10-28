/**
 * 게시글 작성 페이지 JavaScript
 */

const ArticleCreate = {
    pageId: '#post_edit ',

    // 클래스 프로퍼티
    boardId: null,
    userId: null,
    csrfTokenName: null,
    csrfHash: null,

    /**
     * 취소 버튼 초기화
     */
    initCancelButton() {
        $(this.pageId + '#cancelButton').on('click', () => {
            location.href = `/board/detail?id=${this.boardId}`;
        });
    },

    /**
     * 저장 버튼 초기화
     */
    initSaveButton() {
        $(this.pageId + '#confirmSave').on('click', () => {
            this.savePost();
        });
    },

    /**
     * 게시글 저장
     */
    savePost() {
        const title = $(this.pageId + 'input[name=title]').val().trim();
        const content = $(this.pageId + 'textarea[name=content]').val().trim();

        if (!title) {
            Swal.fire({
                icon: 'warning',
                text: '제목을 입력해 주세요.'
            });
            return false;
        }

        if (!content) {
            Swal.fire({
                icon: 'warning',
                text: '내용을 입력해 주세요.'
            });
            return false;
        }

        const postData = {
            board_id: this.boardId,
            title: title,
            content: content,
            user_id: this.userId
        };
        postData[this.csrfTokenName] = this.csrfHash;

        $.ajax({
            url: '/rest/article/create',
            type: 'POST',
            data: postData,
            success: (response) => {
                Swal.fire({
                    title: '저장되었습니다.',
                    icon: 'success'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.href = `/article/${response.id}`;
                    }
                });
            },
            error: (error) => {
                Swal.fire({
                    title: '오류',
                    html: error.responseJSON.message,
                    icon: 'error'
                });
            }
        });
    },

    /**
     * 초기화
     */
    init(boardId, userId, csrfTokenName, csrfHash) {
        // 클래스 프로퍼티에 저장
        this.boardId = boardId;
        this.userId = userId;
        this.csrfTokenName = csrfTokenName;
        this.csrfHash = csrfHash;

        // 버튼 초기화
        this.initCancelButton();
        this.initSaveButton();
    }
};

// DOM 로드 완료 후 초기화
$(document).ready(() => {
    const form = $('#post_edit');
    const boardId = form.data('board-id');
    const userId = form.data('user-id');
    const csrfTokenName = form.data('csrf-token-name');
    const csrfHash = form.data('csrf-hash');

    ArticleCreate.init(boardId, userId, csrfTokenName, csrfHash);
});