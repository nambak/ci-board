<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.5/dist/bootstrap-table.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="/assets/css/ci_board.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<div class="d-flex flex-column min-vh-100">
    <header class="p-3 text-bg-dark">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                <div class="bg-white text-black p-2 rounded-3 me-3">CI3Board</div>
                <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                    <li><a href="/board?id=1" class="nav-link px-2 text-white">게시판</a></li>
                </ul>
                <div class="text-end">
                    <button type="button" class="btn btn-outline-light me-2">로그인</button>
                    <button type="button" class="btn btn-warning">가입</button>
                </div>
            </div>
        </div>
    </header>
    <div id="container" class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5">
                        <!-- 큰 404 숫자 -->
                        <h1 class="display-1 text-danger fw-bold mb-0">404</h1>

                        <!-- 메인 메시지 -->
                        <h2 class="display-6 fw-normal text-dark mb-4">페이지를 찾을 수 없습니다</h2>

                        <!-- 부가 설명 -->
                        <p class="lead text-muted mb-5">
                            죄송합니다. 요청하신 페이지를 찾을 수 없습니다.<br>
                            페이지가 삭제되었거나 주소가 변경되었을 수 있습니다.
                        </p>

                        <!-- 홈으로 돌아가기 버튼 -->
                        <div class="d-grid gap-2 d-sm-block">
                            <a href="/" class="btn btn-danger btn-lg px-5 py-3 rounded-pill">
                                <i class="bi bi-house-door me-2"></i>메인페이지로 돌아가기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-auto">
        <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 border-top">
            <p class="col-md-4 mb-0 text-body-secondary"></p>
            <p class="col-md-4 mb-0 justify-content-end text-end align-middle">
                <small>Copyright by <em><a href="mailto:nambak80@gmail.com" class="text-secondary text-decoration-none">nambak80@gmail.com</a></em></small>
            </p>
        </footer>
    </div>
</div>
</body>
</html>
