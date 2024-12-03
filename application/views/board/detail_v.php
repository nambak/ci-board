<article class="container my-5" id="board_detail">
    <h1 id="title"></h1>
    <table class="table table-striped" id="board_detail_table"></table>
</article>
<script defer>
    const pageId = '#board_detail ';

    $(document).ready(() => {
        $(pageId + '#board_detail_table').bootstrapTable({
            url: '/rest/board/detail',
            columns: [{
                field: 'id',
                title: '번호',
                formatter: (value, row, index) => {
                    return row.id;
                }
            }, {
                field: 'title',
                title: '제목',
                formatter: (value, row, index) => {
                    return `<a href="/post/detail?id=${row.id}">${row.title}</a>`;
                }
            }, {
                field: 'views',
                title: '조회수',
                formater: (value, row, index) => {
                    return row.views;
                }
            }, {
                field: 'created_at',
                title: '등록일',
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
    });
</script>