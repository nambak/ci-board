<article class="container my-5" id="board_list">
    <h1>게시판</h1>
    <table class="table table-striped" id="board_list_table"></table>
</article>
<script defer>
    $(document).ready(function () {
        $('#board_list_table').bootstrapTable({
            url: '/rest/board',
            columns: [{
                field: 'id',
                title: '번호',
                formatter: function (value, row, index) {
                    return row.id;
                }
            }, {
                field: 'name',
                title: '제목',
                formatter: function (value, row, index) {
                    return `<a href="/board/detail?id=${row.id}">${row.name}</a>`;
                }
            }, {
                field: 'description',
                title: '설명',
                formatter: function (value, row, index) {
                    return row.description;
                }
            }, {
                field: 'created_at',
                title: '등록일',
                formatter: function (value, row, index) {
                    return row.created_at;
                }
            }],
            pagination: true,
            headerStyle: function (column) {
                return {
                    classes: 'table-dark'
                }
            }
        });
    })
</script>
