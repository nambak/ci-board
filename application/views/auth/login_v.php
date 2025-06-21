<article class="container my-5" id="login_page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0">로그인</h4>
                </div>
                <div class="card-body">
                    <form id="loginForm" method="post" action="/login">
                        <div class="mb-3">
                            <label for="email" class="form-label">이메일</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback" id="email-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">비밀번호</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback" id="password-error"></div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                로그인 상태 유지
                            </label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark" id="loginBtn">로그인</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        계정이 없으신가요? <a href="/register" class="text-decoration-none">회원가입</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</article>

