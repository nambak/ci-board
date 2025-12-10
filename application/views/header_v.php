<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning CI</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.5/dist/bootstrap-table.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.5/dist/bootstrap-table.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="/assets/css/ci_board.css" rel="stylesheet">
    <?= $debugbarRenderer->renderHead(); ?>
</head>
<body>
<div>
    <header class="p-3 text-bg-dark">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                <div class="bg-white text-black p-2 rounded-3 me-3">CI3Board</div>
                <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                    <li><a href="/board?id=1" class="nav-link px-2 text-white">게시판</a></li>
                </ul>

                <!-- 검색 폼 -->
                <form class="col-12 col-lg-auto mb-3 mb-lg-0 me-lg-3" action="<?= site_url('search') ?>" method="GET" id="headerSearchForm">
                    <div class="input-group">
                        <input type="search"
                               class="form-control form-control-dark"
                               placeholder="게시글 검색..."
                               name="query"
                               id="headerSearchInput"
                               autocomplete="off">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <div class="text-end d-flex align-items-center">
                    <?php if($this->session->userdata('logged_in')): ?>
                        <!-- 로그인된 상태 -->
                        <a href="/profile" class="d-flex align-items-center text-decoration-none me-3">
                            <?= profile_avatar_html(
                                $this->session->userdata('profile_image'),
                                $this->session->userdata('user_name'),
                                32
                            ) ?>
                            <span class="text-white ms-2"><?= html_escape($this->session->userdata('user_name')) ?></span>
                        </a>
                        <a href="/logout" class="btn btn-outline-light">로그아웃</a>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline-light me-2">로그인</a>
                        <a href="/register" class="btn btn-warning">가입</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
