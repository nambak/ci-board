<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CI3Board - Admin</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.25.0/dist/bootstrap-table.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.25.0/dist/bootstrap-table.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/ci_board.css" rel="stylesheet">
    <?= $debugbarRenderer->renderHead(); ?>
</head>
<body>
<div>
    <header class="p-3 text-bg-dark">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-light d-md-none me-3" id="sidebarToggle" type="button">
                        <span class="navbar-toggler-icon"></span>☰
                    </button>
                    <div class="bg-danger text-white p-2 rounded-3 me-3">CI3Board Admin</div>
                    <ul class="nav me-lg-auto mb-0">
                        <li><a href="/board?id=1" class="nav-link px-2 text-white">게시판</a></li>
                    </ul>
                </div>
                <div class="text-end">
                    <?php if($this->session->userdata('logged_in')): ?>
                        <!-- 로그인된 상태 -->
                        <span class="text-white me-3">
                            <a href="/profile" class="text-white text-decoration-none">
                                <?= htmlspecialchars($this->session->userdata('user_name'), ENT_QUOTES, 'UTF-8') ?>
                            </a>님 환영합니다
                        </span>
                        <a href="/logout" class="btn btn-outline-light">로그아웃</a>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline-light me-2">로그인</a>
                        <a href="/register" class="btn btn-warning">가입</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    <div class="admin-wrapper">