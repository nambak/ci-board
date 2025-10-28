<article class="container my-5" id="board_detail" data-board-id="<?=$id?>">
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
<script src="/assets/js/board-detail.js" defer></script>