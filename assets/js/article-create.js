/**
 * 게시글 작성 페이지 JavaScript
 */

const ArticleCreate = {
    pageId: '#post_edit',

    // 클래스 프로퍼티
    boardId: null,
    userId: null,
    csrfTokenName: null,
    csrfHash: null,
    selectedFiles: [],
    maxFiles: 5,
    maxSizeImage: 5 * 1024 * 1024, // 5MB
    maxSizeFile: 10 * 1024 * 1024, // 10MB

    /**
     * 취소 버튼 초기화
     */
    initCancelButton() {
        $(this.pageId + ' #cancelButton').on('click', () => {
            location.href = `/board/detail?id=${this.boardId}`;
        });
    },

    /**
     * 저장 버튼 초기화
     */
    initSaveButton() {
        $(this.pageId + ' #confirmSave').on('click', () => {
            this.savePost();
        });
    },

    /**
     * 파일 입력 초기화
     */
    initFileInput() {
        $('#fileInput').on('change', (e) => {
            const files = Array.from(e.target.files);
            this.handleFileSelect(files);
        });
    },

    /**
     * 파일 선택 처리
     */
    handleFileSelect(files) {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', '7z'];
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        for (const file of files) {
            // 파일 개수 체크
            if (this.selectedFiles.length >= this.maxFiles) {
                Swal.fire({
                    icon: 'warning',
                    text: `최대 ${this.maxFiles}개의 파일만 첨부할 수 있습니다.`
                });
                break;
            }

            // 확장자 체크
            const ext = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(ext)) {
                Swal.fire({
                    icon: 'warning',
                    text: `${file.name}: 허용되지 않는 파일 형식입니다.`
                });
                continue;
            }

            // 파일 크기 체크
            const isImage = imageExtensions.includes(ext);
            const maxSize = isImage ? this.maxSizeImage : this.maxSizeFile;
            if (file.size > maxSize) {
                const maxSizeMB = Math.round(maxSize / 1024 / 1024);
                Swal.fire({
                    icon: 'warning',
                    text: `${file.name}: 파일 크기는 최대 ${maxSizeMB}MB까지 허용됩니다.`
                });
                continue;
            }

            this.selectedFiles.push(file);
        }

        this.renderFileList();
        // 파일 input 초기화
        $('#fileInput').val('');
    },

    /**
     * 파일 목록 렌더링
     */
    renderFileList() {
        const fileList = $('#fileList');
        fileList.empty();

        if (this.selectedFiles.length === 0) {
            return;
        }

        this.selectedFiles.forEach((file, index) => {
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(file.name.split('.').pop().toLowerCase());
            const icon = isImage ? 'bi-image' : 'bi-file-earmark';

            const fileItem = $(`
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi ${icon} me-2"></i>
                        <span>${this.escapeHtml(file.name)}</span>
                        <small class="text-muted ms-2">(${fileSizeMB} MB)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" data-index="${index}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `);

            fileItem.find('button').on('click', () => {
                this.removeFile(index);
            });

            fileList.append(fileItem);
        });
    },

    /**
     * 파일 제거
     */
    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        this.renderFileList();
    },

    /**
     * HTML 이스케이프
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * 게시글 저장
     */
    async savePost() {
        const title = $(this.pageId + ' input[name=title]').val().trim();
        const content = $(this.pageId + ' textarea[name=content]').val().trim();

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

        // 로딩 표시
        Swal.fire({
            title: '저장 중...',
            text: '잠시만 기다려주세요.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const postData = {
            board_id: this.boardId,
            title: title,
            content: content,
            user_id: this.userId
        };
        postData[this.csrfTokenName] = this.csrfHash;

        try {
            // 게시글 저장
            const articleResponse = await $.ajax({
                url: '/rest/article/create',
                type: 'POST',
                data: postData
            });

            const articleId = articleResponse.id;

            // 파일 업로드
            if (this.selectedFiles.length > 0) {
                await this.uploadFiles(articleId);
            }

            Swal.fire({
                title: '저장되었습니다.',
                icon: 'success'
            }).then(() => {
                location.href = `/article/${articleId}`;
            });

        } catch (error) {
            Swal.fire({
                title: '오류',
                html: error.responseJSON?.message || '저장 중 오류가 발생했습니다.',
                icon: 'error'
            });
        }
    },

    /**
     * 파일 업로드
     */
    async uploadFiles(articleId) {
        console.log('uploadFiles called with articleId:', articleId);
        console.log('selectedFiles:', this.selectedFiles);
        console.log('CSRF token:', this.csrfTokenName, '=', this.csrfHash);

        const uploadPromises = this.selectedFiles.map((file, index) => {
            console.log(`Preparing upload for file ${index + 1}:`, file.name);

            const formData = new FormData();
            formData.append('file', file);
            formData.append('article_id', articleId);
            formData.append(this.csrfTokenName, this.csrfHash);

            console.log('FormData prepared for:', file.name);

            return $.ajax({
                url: '/rest/attachment/upload',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function(response) {
                console.log('Upload success for', file.name, ':', response);
            }).fail(function(xhr, status, error) {
                console.error('Upload failed for', file.name, ':', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
            });
        });

        try {
            console.log('Starting Promise.all for', uploadPromises.length, 'files');
            const results = await Promise.all(uploadPromises);
            console.log('All uploads completed:', results);
            return results;
        } catch (error) {
            console.error('File upload error:', error);
            throw error;
        }
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
        this.initFileInput();
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