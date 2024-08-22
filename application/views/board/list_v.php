<article class="container my-5">
    <h1>게시판</h1>
    <table class="table table-striped">
        <thead class="table-dark">
        <tr>
            <th scope="col">번호</th>
            <th scope="col">제목</th>
            <th scope="col">작성자</th>
            <th scope="col">조회수</th>
            <th scope="col">등록일</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
    <nav>
        <ul class="pagination">
            <li class="page-item"><a href="#" class="page-link">1</a></li>
        </ul>
    </nav>
</article>
<script defer>
  $(document).ready(function () {
    $.ajax({
      url: '/rest/board/<?= $this->input->get('id', TRUE) ?: 1; ?>',
      type: 'GET',
      data: {
        page: 1,
      },
      error: function (error) {
        Swal.fire({
          title: 'Error ' + error.status ,
          text: error.statusText,
          icon: 'error',
        });
      },
      success: function (response) {
        console.log(response);
      }
    });
  })
</script>
