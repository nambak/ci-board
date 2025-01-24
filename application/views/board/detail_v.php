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
<script defer>
    const pageId = '#board_detail ';

    $(document).ready(() => {
        initPostList();
        $(pageId + '#writePost').on('click', () => {
            location.href = '/post/create?board_id=<?= $id ?>';
        });
    });

    function initPostList() {
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
    }
</script>