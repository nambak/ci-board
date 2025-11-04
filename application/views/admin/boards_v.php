<div class="admin-content p-4">
    <div class="container-fluid" id="admin-user">
        <h1 class="mb-4">게시판 관리</h1>
        <div class="row mt-4">
            <div class="col-12 mb-3 text-end">
                <button class="btn btn-primary">게시판 생성</button>
            </div>
            <div class="col-12">
                <table class="table table-striped" id="board-list"></table>
            </div>
        </div>
    </div>
</div>
<script>
    const columns = [{
        field: 'no',
        title: 'No.',
        halign: 'center',
        formatter: (value, row, index) => {
            const table = $('#board-list').bootstrapTable('getOptions');
            const pageNumber = table.pageNumber || 1;
            const pageSize = table.pageSize || 10;
            const totalRows = table.totalRows || 0;

            return totalRows - (index + (pageNumber - 1) * pageSize);
        }},
        {
            field: 'name',
            title: '제목',
            halign: 'center',
        },
        {
            field: 'description',
            title: '설명',
            halign: 'center',
        },
        {
            field: 'article_count',
            title: '게시물 수',
            halign: 'center',
            align: 'right'
        },
        {
            field: 'created_at',
            title: '등록일',
            align: 'center',
            halign: 'center'
        },
        {
            title: '관리',
            align: 'center',
            formatter: (value, row, index) => {
                return `
                        <button
                            class="btn btn-sm btn-outline-primary edit-board"
                            data-id="${row.id}"
                            data-name="${escapeHtml(row.name)}"
                            data-description="${escapeHtml(row.description)}"
                        >수정
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger delete-board"
                            data-id="${row.id}"
                            data-name="${escapeHtml(row.name)}"
                        >삭제</button>
                    `;
            }
        }];

    function escapeHtml(str) {
        return String(str === undefined || str === null ? '' : str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;');
    }

    // 테이블 초기화
    $('#board-list').bootstrapTable({
        url: '<?= site_url('rest/board') ?>',
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
        },
        onLoadError: (status, res) => {
            Swal.fire({
                icon: 'error',
                title: '오류',
                text: '게시판 목록을 불러오는 중 오류가 발생했습니다.'
            });
        }
    });
</script>