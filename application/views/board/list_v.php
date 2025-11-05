<div class="container my-5" id="board_list"
     data-is-logged-in="<?= is_logged_in() ?>"
     data-csrf-token="<?= $this->security->get_csrf_hash() ?>"
     data-csrf-name="<?= $this->security->get_csrf_token_name() ?>">
    <h1>게시판</h1>
    <div class="row mb-3">
        <table class="table table-striped" id="board_list_table"></table>
    </div>
</div>

<script src="/assets/js/board-list.js" defer></script>
