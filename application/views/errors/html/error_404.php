<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<?php include_once(APPPATH . 'views/header_v.php'); ?>
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
