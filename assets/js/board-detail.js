const BoardDetail = {
    pageId: '#board_detail ',
    boardId: null,

    initPostList() {
        $(this.pageId + '#board_detail_table').bootstrapTable({
            url: `/rest/board/${this.boardId}`,
            columns: [{
                field: 'id',
                title: '번호',
                halign: 'center',
                align: 'right',
                formatter: (value, row, index) => {
                    const table = $(this.pageId + '#board_detail_table').bootstrapTable('getOptions');
                    const pageNumber = table.pageNumber || 1;
                    const pageSize = table.pageSize || 10;
                    const totalRows = table.totalRows || 0;

                    return totalRows - (index + (pageNumber - 1) * pageSize);
                }
            }, {
                field: 'title',
                title: '제목',
                halign: 'center',
                align: 'left',
                width: 60,
                widthUnit: '%',
                formatter: (value, row, index) => {
                    const escapedTitle = $('<div>').text(row.title).html();
                    let title = `<a href="/article/${row.id}">${escapedTitle}</a>`;

                    // 첨부파일 아이콘 표시
                    if (row.attachment_count > 0) {
                        title += ` <i class="bi bi-paperclip text-muted"></i>`;
                    }

                    // 댓글 수 표시
                    if (row.comment_count > 0) {
                        title += ` <span class="text-muted" style="font-size: 0.8rem;">(${row.comment_count})</span>`;
                    }

                    return title;
                }
            }, {
                field: 'author',
                title: '작성자',
                align: 'center',
                formatter: (value, row, index) => {
                    const escapedAuthor = $('<div>').text(row.author || '알 수 없음').html();
                    const userId = parseInt(row.user_id, 10);
                    if (userId > 0) {
                        return `<a href="/user/${userId}">${escapedAuthor}</a>`;
                    }
                    return escapedAuthor;
                }
            }, {
                field: 'views',
                title: '조회수',
                halign: 'center',
                align: 'right',
                formatter: (value, row, index) => {
                    return row.views;
                }
            }, {
                field: 'created_at',
                title: '등록일',
                align: 'center',
                formatter: (value, row, index) => {
                    return row.created_at
                }
            }],
            pagination: true,
            pageSize: 25,
            headerStyle: () => {
                return {
                    classes: 'table-dark'
                }
            },
            onLoadSuccess: (data) => {
                let title = '게시판';

                if (data && data.name) {
                    title = data.name;
                }

                $(this.pageId + '#title').text(title)
            }
        });
    },

    init(boardId) {
        this.boardId = boardId;

        this.initPostList();

        $(this.pageId + '#writePost').on('click', () => {
            location.href = `/article/create?board_id=${this.boardId}`;
        });
    }

}

$(document).ready(() => {
    BoardDetail.init($('#board_detail').data('board-id'));
});
