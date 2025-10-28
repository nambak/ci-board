/**
 * 게시글 상세 페이지 JavaScript
 */

const ArticleDetail = {
    pageId: '#post_detail ',

    // 클래스 프로퍼티
    articleId: null,
    userId: null,
    csrfTokenName: null,
    csrfHash: null,
    boardId: null,

    /**
     * 게시판 목록으로 이동 버튼 초기화
     */
    initRedirectBoardListButton() {
        $(this.pageId + '#redirectBoardListButton').on('click', () => {
            location.href = `/board/detail?id=${this.boardId}`;
        });
    },

    /**
     * 게시글 수정 페이지로 이동 버튼 초기화
     */
    initRedirectPostEditButton() {
        $(this.pageId + '#redirectEditPost').on('click', () => {
            location.href = `/article/${this.articleId}/edit`;
        });
    },

    /**
     * 게시글 삭제 버튼 초기화
     */
    initDeletePostButton() {
        $(this.pageId + '#deletePost').on('click', () => {
            Swal.fire({
                title: '게시물을 삭제하시겠습니까?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '삭제',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    const deleteData = {};
                    deleteData[this.csrfTokenName] = this.csrfHash;

                    $.ajax({
                        url: `/rest/article/${this.articleId}`,
                        type: 'DELETE',
                        dataType: 'json',
                        data: deleteData,
                        success: (data) => {
                            $(this.pageId + '#redirectBoardListButton').click();
                        },
                        error: (error) => {
                            Swal.fire({
                                title: error.status,
                                text: error.statusText,
                                icon: 'error'
                            });
                        }
                    })
                }
            });
        });
    },

    /**
     * 댓글 작성 버튼 초기화
     */
    initCommentPostButton() {
        if (this.articleId) {
            $(this.pageId + ' #write_comment').on('click', (event) => {
                event.preventDefault();
                this.saveComment();
            });
        }
    },

    /**
     * 댓글 저장
     */
    saveComment() {
        const comment = $(this.pageId + " textarea[name=comment]");

        if (!comment.val().trim()) {
            Swal.fire({
                icon: 'warning',
                text: '댓글 내용이 없습니다.'
            });
            return false;
        }

        const commentData = {};
        commentData[this.csrfTokenName] = this.csrfHash;
        commentData['article_id'] = this.articleId;
        commentData['comment'] = comment.val();
        commentData['writer_id'] = this.userId;

        $.ajax({
            url: '/rest/comment/save',
            type: 'POST',
            data: commentData,
            success: (response) => {
                this.getComments();

                // 저장 후 textarea 비움
                comment.val('');
            },
            error: (error) => {
                this.displayError(error);
            }
        });
    },

    /**
     * 댓글 목록 조회
     */
    getComments() {
        $.ajax({
            url: `/rest/comment`,
            type: 'GET',
            data: {
                article_id: this.articleId,
            },
            success: (response) => {
                this.generateCommentList(response.data);
            },
            error: (error) => {
                this.displayError(error);
            }
        })
    },

    escapeHtml(html) {
        return String(str === undefined || str === null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    /**
     * 댓글 목록 HTML 생성
     */
    generateCommentList(data) {
        let html = '';

        if (data.length <= 0) {
            return false;
        }

        data.forEach((comment) => {
            // 댓글 수정/삭제 버튼은 작성자만 표시
            const editDeleteButtons = comment.can_edit ?
                `<div>
                        <button class="btn btn-sm text-primary">수정</button>
                        <button class="btn btn-sm text-danger">삭제</button>
                    </div>` : '';

            const template = `<div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${this.escapeHtml(comment.name)}</strong>
                                <small class="text-muted ms-2">${comment.created_at}</small>
                            </div>
                            ${editDeleteButtons}
                        </div>
                        <p class="mt-2 mb-0">${this.escapeHtml(comment.comment)}</p>
                    </div>
                </div>`;

            html += template;
        });

        $('#comment_list').html(html);
    },

    /**
     * 에러 표시
     */
    displayError(error) {
        Swal.fire({
            title: `${error.status} ${error.statusText}`,
            icon: 'error'
        });
    },

    /**
     * 초기화
     */
    init(articleId, userId, csrfTokenName, csrfHash, boardId, isArticleAuthor, isLoggedIn) {

        // 클래스 프로퍼티에 저장
        this.articleId = articleId;
        this.userId = userId;
        this.csrfTokenName = csrfTokenName;
        this.csrfHash = csrfHash;
        this.boardId = boardId;

        // 공통 기능 초기화
        this.initRedirectBoardListButton();

        // 작성자 전용 기능
        if (isArticleAuthor) {
            this.initRedirectPostEditButton();
            this.initDeletePostButton();
        }

        // 로그인 사용자 전용 기능
        if (isLoggedIn) {
            this.initCommentPostButton();
        }

        // 댓글 목록 조회
        this.getComments();
    }
};

// DOM 로드 완료 후 초기화
$(document).ready(() => {
    const form = $('#post_detail');
    const userId = form.data('user-id');
    const csrfTokenName = form.data('csrf-token-name');
    const csrfHash = form.data('csrf-hash');
    const articleId = form.data('article-id');
    const boardId = form.data('board-id');
    const isArticleAuthor = form.data('is-article-author');
    const isLoggedIn = form.data('is-logged-in');

    ArticleDetail.init(articleId, userId, csrfTokenName, csrfHash, boardId, isArticleAuthor, isLoggedIn);
});