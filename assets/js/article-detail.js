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
     * @param {number|null} parentId 부모 댓글 ID (답글인 경우)
     * @param {jQuery|null} $textarea 사용할 textarea 요소 (답글 폼인 경우)
     */
    saveComment(parentId = null, $textarea = null) {
        const comment = $textarea || $(this.pageId + " textarea[name=comment]");

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

        if (parentId) {
            commentData['parent_id'] = parentId;
        }

        $.ajax({
            url: '/rest/comment/save',
            type: 'POST',
            data: commentData,
            success: (response) => {
                this.getComments();

                // 저장 후 textarea 비움
                comment.val('');

                // 답글 폼이면 닫기
                if (parentId) {
                    $(`.reply-form[data-parent-id="${parentId}"]`).remove();
                }
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

    /**
     * 첨부파일 목록 조회
     */
    getAttachments() {
        $.ajax({
            url: `/rest/attachment/list/${this.articleId}`,
            type: 'GET',
            success: (response) => {
                if (response.success && response.data.length > 0) {
                    this.displayAttachments(response.data);
                }
            },
            error: (error) => {
                console.error('Attachment load error:', error);
            }
        });
    },

    /**
     * 첨부파일 표시
     */
    displayAttachments(attachments) {
        const images = attachments.filter(file => file.is_image);
        const files = attachments.filter(file => !file.is_image);
        const allFiles = [...images, ...files];

        // 첨부파일 정보 표시 (조회수 옆)
        if (allFiles.length > 0) {
            this.displayAttachmentInfo(allFiles);
        }

        // 이미지 표시 (본문 앞)
        if (images.length > 0) {
            this.displayImages(images);
            $('#attachmentImagesSection').show();
        }
    },

    /**
     * 첨부파일 정보 표시
     */
    displayAttachmentInfo(attachments) {
        const $attachmentInfo = $('#attachmentInfo');
        $attachmentInfo.empty();

        attachments.forEach((file, index) => {
            const fileLink = $(`
                <span class="${index > 0 ? 'ms-3' : ''}">
                    <i class="bi bi-paperclip"></i>
                    <a href="/rest/attachment/download/${file.id}" class="text-muted text-decoration-none">
                        ${this.escapeHtml(file.original_name)}
                    </a>
                    <small class="text-muted">(${this.formatFileSize(file.file_size)}, 다운로드 ${file.download_count}회)</small>
                </span>
            `);

            $attachmentInfo.append(fileLink);
        });

        $attachmentInfo.show();
    },

    /**
     * 모든 파일 다운로드
     */
    downloadAllFiles(attachments) {
        attachments.forEach((file, index) => {
            setTimeout(() => {
                window.location.href = `/rest/attachment/download/${file.id}`;
            }, index * 500); // 500ms 간격으로 다운로드
        });
    },

    /**
     * 이미지 표시
     */
    displayImages(images) {
        const imageContainer = $('#attachmentImages');
        imageContainer.empty();

        images.forEach((image, index) => {
            const imageCard = $(`
                <div class="col-md-2 col-sm-3 col-4">
                    <img src="${this.escapeHtml(image.thumbnail_url)}"
                         class="img-fluid rounded attachment-image"
                         alt="${this.escapeHtml(image.original_name)}"
                         data-image-index="${index}"
                         data-original-name="${this.escapeHtml(image.original_name)}"
                         style="cursor: pointer; width: 100%; height: 150px; object-fit: cover;">
                </div>
            `);

            imageContainer.append(imageCard);
        });

        // 이미지 클릭 이벤트 (라이트박스)
        $('.attachment-image').on('click', (e) => {
            const index = $(e.target).data('image-index');
            this.openLightbox(images, index);
        });
    },

    /**
     * 파일 표시
     */
    displayFiles(files) {
        const fileContainer = $('#attachmentFiles');
        fileContainer.empty();

        files.forEach(file => {
            const fileIcon = this.getFileIcon(file.original_name);
            const fileItem = $(`
                <a href="/rest/attachment/download/${file.id}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi ${fileIcon} me-2"></i>
                        <span>${this.escapeHtml(file.original_name)}</span>
                    </div>
                    <div>
                        <small class="text-muted me-3">${this.formatFileSize(file.file_size)}</small>
                        <small class="text-muted">다운로드: ${file.download_count}회</small>
                    </div>
                </a>
            `);

            fileContainer.append(fileItem);
        });
    },

    /**
     * 파일 아이콘 가져오기
     */
    getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const iconMap = {
            'pdf': 'bi-file-pdf',
            'doc': 'bi-file-word',
            'docx': 'bi-file-word',
            'xls': 'bi-file-excel',
            'xlsx': 'bi-file-excel',
            'ppt': 'bi-file-ppt',
            'pptx': 'bi-file-ppt',
            'zip': 'bi-file-zip',
            'rar': 'bi-file-zip',
            '7z': 'bi-file-zip',
            'txt': 'bi-file-text'
        };

        return iconMap[ext] || 'bi-file-earmark';
    },

    /**
     * 파일 크기 포맷
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    /**
     * 라이트박스 열기
     */
    openLightbox(images, startIndex) {
        const imageUrls = images.map(img => img.image_url || img.thumbnail_url);
        const imageNames = images.map(img => img.original_name);

        let currentIndex = startIndex;

        const lightboxHtml = `
            <div id="imageLightbox" class="modal fade" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content bg-dark">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-white" id="lightboxTitle">${this.escapeHtml(imageNames[currentIndex])}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img id="lightboxImage" src="${imageUrls[currentIndex]}" class="img-fluid" style="max-height: 80vh;">
                        </div>
                        <div class="modal-footer border-0 justify-content-between">
                            <button type="button" class="btn btn-light" id="prevImage" ${currentIndex === 0 ? 'disabled' : ''}>
                                <i class="bi bi-chevron-left"></i> 이전
                            </button>
                            <span class="text-white">${currentIndex + 1} / ${images.length}</span>
                            <button type="button" class="btn btn-light" id="nextImage" ${currentIndex === images.length - 1 ? 'disabled' : ''}>
                                다음 <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 기존 라이트박스 제거
        $('#imageLightbox').remove();

        // 새 라이트박스 추가
        $('body').append(lightboxHtml);

        const $lightbox = $('#imageLightbox');
        const $image = $('#lightboxImage');
        const $title = $('#lightboxTitle');
        const $prevBtn = $('#prevImage');
        const $nextBtn = $('#nextImage');

        // 이미지 변경 함수
        const updateImage = (index) => {
            currentIndex = index;
            $image.attr('src', imageUrls[index]);
            $title.text(imageNames[index]);
            $prevBtn.prop('disabled', index === 0);
            $nextBtn.prop('disabled', index === images.length - 1);
            $lightbox.find('.modal-footer span').text(`${index + 1} / ${images.length}`);
        };

        // 이전/다음 버튼 이벤트
        $prevBtn.on('click', () => {
            if (currentIndex > 0) {
                updateImage(currentIndex - 1);
            }
        });

        $nextBtn.on('click', () => {
            if (currentIndex < images.length - 1) {
                updateImage(currentIndex + 1);
            }
        });

        // 키보드 이벤트 (좌우 화살표)
        $(document).on('keydown.lightbox', (e) => {
            if (e.key === 'ArrowLeft' && currentIndex > 0) {
                updateImage(currentIndex - 1);
            } else if (e.key === 'ArrowRight' && currentIndex < images.length - 1) {
                updateImage(currentIndex + 1);
            }
        });

        // 모달 닫힐 때 키보드 이벤트 제거
        $lightbox.on('hidden.bs.modal', () => {
            $(document).off('keydown.lightbox');
            $lightbox.remove();
        });

        // 모달 표시
        const modal = new bootstrap.Modal($lightbox[0]);
        modal.show();
    },

    escapeHtml(html) {
        return String(html === undefined || html === null ? '' : html)
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

        // 댓글 수 표시
        $("#comment-title").html(`댓글 <span class="comment-count">(${data.length})</span>`);

        // 로그인 여부 확인
        const isLoggedIn = $('#post_detail').data('is-logged-in');

        data.forEach((comment) => {
            // 들여쓰기 스타일 (depth에 따라)
            const indentStyle = comment.depth > 0 ? `margin-left: ${comment.depth * 40}px;` : '';
            const replyIndicator = comment.depth > 0 ? '<i class="bi bi-arrow-return-right text-muted me-2"></i>' : '';

            // 부모 작성자 멘션
            const mentionBadge = comment.parent_author_name
                ? `<span class="badge bg-light text-primary me-2">@${this.escapeHtml(comment.parent_author_name)}</span>`
                : '';

            // 댓글 수정/삭제 버튼은 작성자만 표시
            const editDeleteButtons = comment.can_edit ?
                `<div>
                        <button class="btn btn-sm text-primary edit-comment-btn" data-comment-id="${comment.id}">수정</button>
                        <button class="btn btn-sm text-danger delete-comment-btn" data-comment-id="${comment.id}">삭제</button>
                    </div>` : '';

            // 답글 버튼 (로그인 상태 && depth < 2일 때만 표시)
            const replyButton = isLoggedIn && comment.depth < 2
                ? `<button class="btn btn-sm btn-link text-secondary reply-btn" data-comment-id="${comment.id}" data-author-name="${this.escapeHtml(comment.name).replace(/"/g, '&quot;')}">답글</button>`
                : '';

            const template = `<div class="card mb-2 comment-item" data-comment-id="${comment.id}" data-depth="${comment.depth}" style="${indentStyle}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                ${replyIndicator}
                                <strong>${this.escapeHtml(comment.name)}</strong>
                                <small class="text-muted ms-2">${comment.created_at}</small>
                            </div>
                            ${editDeleteButtons}
                        </div>
                        <div class="comment-content mt-2">
                            <p class="mb-0 comment-text">${mentionBadge}${this.escapeHtml(comment.comment)}</p>
                            <div class="comment-edit-form" style="display: none;">
                                <textarea class="form-control mb-2" rows="3">${this.escapeHtml(comment.comment)}</textarea>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-secondary cancel-edit-btn">취소</button>
                                    <button class="btn btn-sm btn-primary save-edit-btn">저장</button>
                                </div>
                            </div>
                            <div class="mt-2">
                                ${replyButton}
                            </div>
                        </div>
                    </div>
                </div>`;

            html += template;
        });

        $('#comment_list').html(html);

        // 이벤트 핸들러 등록
        this.initCommentEditButtons();
        this.initReplyButtons();
    },

    /**
     * 답글 버튼 이벤트 초기화
     */
    initReplyButtons() {
        $('.reply-btn').on('click', (event) => {
            const $btn = $(event.currentTarget);
            const commentId = $btn.data('comment-id');
            const authorName = $btn.data('author-name');

            // 기존 답글 폼이 있으면 제거
            $('.reply-form').remove();

            // 답글 입력 폼 생성
            const replyForm = `
                <div class="reply-form card mt-2 mb-2" data-parent-id="${commentId}" style="margin-left: 40px;">
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="badge bg-light text-primary">@${this.escapeHtml(authorName)}</span>에게 답글 작성
                        </div>
                        <textarea class="form-control reply-textarea mb-2" rows="2" placeholder="답글을 입력하세요"></textarea>
                        <div class="text-end">
                            <button class="btn btn-sm btn-secondary cancel-reply-btn">취소</button>
                            <button class="btn btn-sm btn-primary save-reply-btn" data-parent-id="${commentId}">답글 작성</button>
                        </div>
                    </div>
                </div>
            `;

            // 해당 댓글 아래에 폼 추가
            $(`.comment-item[data-comment-id="${commentId}"]`).after(replyForm);

            // 답글 폼 이벤트 등록
            this.initReplyFormButtons();

            // 포커스
            $(`.reply-form[data-parent-id="${commentId}"] .reply-textarea`).focus();
        });
    },

    /**
     * 답글 폼 버튼 이벤트 초기화
     */
    initReplyFormButtons() {
        // 취소 버튼
        $('.cancel-reply-btn').off('click').on('click', (event) => {
            $(event.currentTarget).closest('.reply-form').remove();
        });

        // 저장 버튼
        $('.save-reply-btn').off('click').on('click', (event) => {
            const $btn = $(event.currentTarget);
            const parentId = $btn.data('parent-id');
            const $textarea = $btn.closest('.reply-form').find('.reply-textarea');

            this.saveComment(parentId, $textarea);
        });
    },

    /**
     * 댓글 수정 버튼 이벤트 초기화
     */
    initCommentEditButtons() {
        // 수정 버튼 클릭
        $('.edit-comment-btn').on('click', (event) => {
            const commentId = $(event.currentTarget).data('comment-id');
            this.enterEditMode(commentId);
        });

        // 취소 버튼 클릭
        $('.cancel-edit-btn').on('click', (event) => {
            const $commentItem = $(event.currentTarget).closest('.comment-item');
            const commentId = $commentItem.data('comment-id');
            this.exitEditMode(commentId);
        });

        // 저장 버튼 클릭
        $('.save-edit-btn').on('click', (event) => {
            const $commentItem = $(event.currentTarget).closest('.comment-item');
            const commentId = $commentItem.data('comment-id');
            this.updateComment(commentId);
        });

        // 삭제 버튼 클릭
        $('.delete-comment-btn').on('click', (event) => {
            const commentId = $(event.currentTarget).data('comment-id');
            this.deleteComment(commentId);
        });
    },

    /**
     * 댓글 수정 모드 진입
     */
    enterEditMode(commentId) {
        const $commentItem = $(`.comment-item[data-comment-id="${commentId}"]`);
        $commentItem.find('.comment-text').hide();
        $commentItem.find('.comment-edit-form').show();
        $commentItem.find('.edit-comment-btn, .delete-comment-btn').prop('disabled', true);
    },

    /**
     * 댓글 수정 모드 종료
     */
    exitEditMode(commentId) {
        const $commentItem = $(`.comment-item[data-comment-id="${commentId}"]`);
        $commentItem.find('.comment-text').show();
        $commentItem.find('.comment-edit-form').hide();
        $commentItem.find('.edit-comment-btn, .delete-comment-btn').prop('disabled', false);
    },

    /**
     * 댓글 수정 저장
     */
    updateComment(commentId) {
        const $commentItem = $(`.comment-item[data-comment-id="${commentId}"]`);
        const $textarea = $commentItem.find('.comment-edit-form textarea');
        const newComment = $textarea.val().trim();

        if (!newComment) {
            Swal.fire({
                icon: 'warning',
                text: '댓글 내용이 없습니다.'
            });
            return;
        }

        const updateData = {};
        updateData[this.csrfTokenName] = this.csrfHash;
        updateData['comment'] = newComment;

        $.ajax({
            url: `/rest/comment/${commentId}`,
            type: 'PUT',
            data: updateData,
            success: (response) => {
                Swal.fire({
                    icon: 'success',
                    text: '댓글이 수정되었습니다.',
                    timer: 1500,
                    showConfirmButton: false
                });

                // 댓글 목록 새로고침
                this.getComments();
            },
            error: (error) => {
                this.displayError(error);
            }
        });
    },

    /**
     * 댓글 삭제
     */
    deleteComment(commentId) {
        Swal.fire({
            title: '댓글을 삭제하시겠습니까?',
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
                    url: `/rest/comment/${commentId}`,
                    type: 'DELETE',
                    data: deleteData,
                    success: (response) => {
                        Swal.fire({
                            icon: 'success',
                            text: '댓글이 삭제되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // 댓글 목록 새로고침
                        this.getComments();
                    },
                    error: (error) => {
                        this.displayError(error);
                    }
                });
            }
        });
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

        // 첨부파일 목록 조회
        this.getAttachments();
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