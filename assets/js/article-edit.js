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

        $.ajax({
            url: `/rest/article/${this.articleId}`,
            type: 'PUT',
            data: {
                title: title,
                content: content,
            },
            success: (response) => {
                if (response === 'success') {
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

        this.initCancelButton(articleId);
        this.initConfirmButton(articleId);
        this.initArticleData(articleId);
    }
}

$(document).ready(() => {
    const articleId = $('#post_edit').data('article-id');

    ArticleEdit.init(articleId);
});