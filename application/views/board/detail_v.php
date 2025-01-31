<article class="container my-5" id="board_detail">
    <div class="row mb-4">
        <h1 id="title"></h1>
    </div>
    <div class="row mb-3">
        <table class="table table-striped" id="board_detail_table"></table>
    </div>
    <div class="row">
        <div class="col">
            <button id="writePost" class="btn btn-primary">글 작성</button>
        </div>
    </div>
</article>
<style>
    #board_detail_table td a {
        color: #000;
        text-decoration: none;
    }
</style>
<script defer>
    const pageId = '#board_detail ';

    $(document).ready(() => {
        initPostList();
        $(pageId + '#writePost').on('click', () => {
            location.href = '/post/create?board_id=<?= $id ?>';
        });
    });

    function initPostList() {
        const $table = $(pageId + '#board_detail_table');
        $table.bootstrapTable({
            url: '/rest/board/detail',
            columns: [{
                field: 'id',
                title: '번호',
                formatter: (value, row, index) => {
                    let dataLength = $table.bootstrapTable('getData').length;
                    return dataLength - index;
                }
            }, {
                field: 'title',
                title: '제목',
                formatter: (value, row) => {
                    return `<a href="/post/detail?id=${row.post_id}">${row.title}</a>`;
                }
            }, {
                field: 'writer',
                title: '작성자',
                formatter: (value, row) => {
                    return `<a href="/user?id=${row.writer_id}">${row.writer}</a>`;
                }
            }, {
                field: 'views',
                title: '조회수',
                formater: (value, row) => {
                    return row.views;
                }
            }, {
                field: 'created_at',
                title: '등록일',
                formatter: (value, row) => {
                    return row.created_at
                }
            }],
            pagination: true,
            headerStyle: () => {
                return {
                    classes: 'table-dark'
                }
            },
            queryParams: () => {
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