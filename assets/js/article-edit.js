const ArticleEdit = {
    pageId: '#post_edit ',
    articleId: null,

    initArticleData() {
        $.ajax({
            url: `/rest/article/${this.articleId}`,
            type: 'GET',
            dataType: 'json',
            success: (data) => {
                if (data) {
                    $(this.pageId + 'input[name=title]').val(data.title);
                    $(this.pageId + 'textarea[name=content]').val(data.content);

                    // 기존 태그 로드
                    if (data.tags && Array.isArray(data.tags)) {
                        TagInput.setTags(data.tags);
                    }
                }
            },
            error: (error) => {
                Swal.fire({
                    title: `${error.status} ${error.statusText}`,
                    icon: 'error'
                });
            }
        });
    },

    initCancelButton() {
        $(this.pageId + '#cancelButton').on('click', () => {
            location.href = `/article/${this.articleId}`;
        });
    },

    initConfirmButton() {
        $(this.pageId + '#confirmEdit').on('click', () => this.updateArticle());
    },

    updateArticle() {
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

        // 태그 가져오기
        const tags = TagInput.getTags();

        $.ajax({
            url: `/rest/article/${this.articleId}`,
            type: 'PUT',
            data: {
                title: title,
                content: content,
                tags: JSON.stringify(tags)
            },
            success: (response) => {
                if (response.message === 'success' || response === 'success') {
                    Swal.fire({
                        title: '수정되었습니다.',
                        icon: 'success'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.href = `/article/${this.articleId}`;
                        }
                    });
                }
            },
            error: (error) => {
                Swal.fire({
                    title: `${error.status} ${error.statusText}`,
                    icon: 'error'
                });
            }
        });
    },

    init(articleId) {
        this.articleId = articleId;

        // 태그 입력 초기화
        TagInput.init();

        this.initCancelButton(articleId);
        this.initConfirmButton(articleId);
        this.initArticleData(articleId);
    }
}

$(document).ready(() => {
    const articleId = $('#post_edit').data('article-id');

    ArticleEdit.init(articleId);
});