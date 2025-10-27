<article class="container my-5" id="board_detail">
    <div class="row mb-4">
        <h1 id="title"></h1>
    </div>
    <div class="row mb-3">
        <table class="table table-striped" id="board_detail_table"></table>
    </div>
    <div class="row">
        <div class="col">
            <?php if (is_logged_in()): ?>
                <button id="writePost" class="btn btn-primary">글 작성</button>
            <?php endif; ?>
        </div>
    </div>
</article>
<script defer>
    const pageId = '#board_detail ';

    $(document).ready(() => {
        initPostList();
        $(pageId + '#writePost').on('click', () => {
            location.href = '/article/create?board_id=<?= $id ?>';
        });
    });

    function initPostList() {
        $(pageId + '#board_detail_table').bootstrapTable({
            url: '/rest/board/detail',
            columns: [{
                field: 'id',
                title: '번호',
                halign: 'center',
                align: 'right',
                formatter: (value, row, index) => {
                    const table = $(pageId + '#board_detail_table').bootstrapTable('getOptions');
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
                formatter: (value, row, index) => {
                    return `<a href="/article/${row.id}">${row.title}</a>`;
                }
            }, {
                field: 'views',
                title: '조회수',
                halign: 'center',
                align: 'right',
                formater: (value, row, index) => {
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
            headerStyle: () => {
                return {
                    classes: 'table-dark'
                }
            },
            queryParams: (params) => {
                return {
                    id: <?= $id; ?>
                }
            },
            onLoadSuccess: (data) => {
                let title = '게시판';

                if (data && data.name) {
                    title = data.name;
                }

                $(pageId + '#title').text(title)
            }
        });
    }
</script>