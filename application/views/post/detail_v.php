<article class="container my-5" id="post_detail">
    <h1 id="title"></h1>
    <div class="container text-center">
        <div class="row align-items-start">
            <div class="col text-start">작성자: <span id="writer"></span></div>
            <div class="col text-start">작성일: <span id="createdAt"></span></div>
            <div class="col text-start">조회수: <span id="views"></span></div>
        </div>
    <div id="content" class="text-start mt-4"></div>
</article>
<script defer>
    let pageId = '#post_detail ';
    $(document).ready(() => {
        $.ajax({
            url: '/rest/post/detail',
            type: 'GET',
            dataType: 'json',
            data: {
                id: <?= $id ?>
            },
            success: (data) => {
                if(data) {
                    console.log(data.title);
                    $(pageId + '#title').text(data.title);
                    $(pageId + '#writer').text(data.user_id);
                    $(pageId + '#createdAt').text(data.created_at);
                    $(pageId + '#views').text(data.views);
                    $(pageId + '#content').html(data.content);
                }
            },
            error: (error) => {
                Swal.fire({
                    title: `${error.status} ${error.statusText}`,
                    icon: 'error'
                });
            }
        });
    });
</script>
