<?php
defined('BASEPATH') || exit('No direct script access allowed');
?>

<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning CI</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/ci_board.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        #container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 0;
        }
    </style>
</head>
<body>
<header class="p-3 text-bg-dark">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <div class="bg-white text-black p-2 rounded-3 me-3">CI3Board</div>
            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <li><a href="/board?id=1" class="nav-link px-2 text-white">게시판</a></li>
            </ul>
            <div class="text-end">
                <?php if(isset($this->session) && $this->session->userdata('logged_in')): ?>
                    <span class="text-white me-3">
                            <?= $this->session->userdata('user_name') ?>님 환영합니다
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

<div id="container">
    <div class="row w-100 justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <h1 class="display-1 text-danger fw-bold mb-0">404</h1>
                    <h2 class="display-6 fw-normal text-dark mb-4">페이지를 찾을 수 없습니다</h2>
                    <p class="lead text-muted mb-5">
                        죄송합니다. 요청하신 페이지를 찾을 수 없습니다.<br>
                        페이지가 삭제되었거나 주소가 변경되었을 수 있습니다.
                    </p>
                    <div class="d-grid gap-2 d-sm-block">
                        <a href="/" class="btn btn-danger btn-lg px-5 py-3 rounded-pill">
                            메인페이지로 돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="py-3 border-top">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <p class="col-md-4 mb-0 text-body-secondary"></p>
            <p class="col-md-4 mb-0 justify-content-end text-end align-middle">
                <small>Copyright by <em><a href="mailto:nambak80@gmail.com" class="text-secondary text-decoration-none">nambak80@gmail.com</a></em></small>
            </p>
        </div>
    </div>
</footer>
</body>
</html>