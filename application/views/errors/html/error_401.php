<?php
defined('BASEPATH') || exit('No direct script access allowed');
?>

<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning CI</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
<body class="error-404-layout">
<div id="container">
    <div class="row w-100 justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <h1 class="display-1 text-danger fw-bold mb-0">401</h1>
                    <h2 class="display-6 fw-normal text-dark mb-4">접근 권한 오류</h2>
                    <p class="lead text-muted mb-5">
                        죄송합니다. 요청하신 페이지에 접근할 수  없습니다.<br>
                        로그인하거나 관리자에게 접근 권한을 요청하세요.
                    </p>
                    <div class="d-grid gap-2 d-sm-block">
                        <a href="/login" class="btn btn-danger btn-lg px-5 py-3 rounded-pill">
                            로그인 하기
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
