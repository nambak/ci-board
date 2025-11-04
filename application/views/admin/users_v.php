<div class="admin-content p-4">
    <div class="container-fluid" id="admin-user">
        <h1 class="mb-4">사용자 관리</h1>

        <div class="row mt-4">
            <div class="col-12">
                <table class="table table-striped" id="user-list"></table>
            </div>
        </div>
    </div>
</div>

<script defer>
    const columns = [{
        field: 'no',
        title: '번호',
        halign: 'center',
        formatter: (value, row, index) => {
            const table = $('#user-list').bootstrapTable('getOptions');
            const pageNumber = table.pageNumber || 1;
            const pageSize = table.pageSize || 10;
            const totalRows = table.totalRows || 0;

            return totalRows - (index + (pageNumber - 1) * pageSize);
        }
    },
    {
        field: 'name',
        title: '사용자명',
        halign: 'center',
    },
    {
        field: 'email',
        title: '메일주소',
        halign: 'center',
    },
    {
        field: 'article_count',
        title: '게시물 수',
        halign: 'center',
        align: 'right'
    },
    {
        field: 'comment_count',
        title: '댓글 수',
        halign: 'center',
        align: 'right'
    },
    {
        field: 'created_at',
        title: '등록일',
        align: 'center',
        halign: 'center'
    }];

    // 테이블 초기화
    $('#user-list').bootstrapTable({
        url: '/rest/user',
        columns: columns,
        pagination: true,
        sidePagination: 'server',
        pageSize: 10,
        pageList: [10, 25, 50, 100],
        queryParams: (params) => {
            return {
                limit: params.limit,
                offset: params.offset,
                sort: params.sort || 'id',
                order: params.order || 'desc'
            };
        },
        headerStyle: (column) => {
            return {
                classes: 'table-dark'
            }
        }
    });
</script>
