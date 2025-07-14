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
    <?php if($debugbarRenderer): ?>
        <?= $debugbarRenderer->renderHead(); ?>
    <?php endif; ?>
    
    <!-- 로그인 모달 -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">로그인</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">이메일</label>
                        <input type="email" class="form-control" id="loginEmail" placeholder="test@example.com">
                        <small class="form-text text-muted">테스트용: test@example.com / password123</small>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">비밀번호</label>
                        <input type="password" class="form-control" id="loginPassword" placeholder="password123">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="doLogin()">로그인</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showLoginForm() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        }
        
        function doLogin() {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            fetch(`/auth/login?email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('로그인 중 오류가 발생했습니다.');
                });
        }
        
        function logout() {
            fetch('/auth/logout')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    alert('로그아웃 중 오류가 발생했습니다.');
                });
        }
    </script>
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
                <div class="text-end">
                    <?php if (is_logged_in()): ?>
                        <?php $user = get_user_info(); ?>
                        <span class="text-white me-3">안녕하세요, <?= htmlspecialchars($user->name) ?>님</span>
                        <button type="button" class="btn btn-outline-light" onclick="logout()">로그아웃</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-light me-2" onclick="showLoginForm()">로그인</button>
                        <button type="button" class="btn btn-warning">가입</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
