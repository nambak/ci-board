<article class="container my-5" id="register_page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0">회원가입</h4>
                </div>
                <div class="card-body">
                    <form id="registerForm" method="post" action="/register">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">이름 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">이메일 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback" id="email-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">비밀번호 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback" id="password-error"></div>
                            <small class="form-text text-muted">6자 이상, 영문, 숫자, 특수문자 조합</small>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">비밀번호 확인 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   required>
                            <div class="invalid-feedback" id="password_confirm-error"></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark" id="registerBtn">회원가입</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        이미 계정이 있으신가요? <a href="/login" class="text-decoration-none">로그인</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</article>

<script src="/assets/js/auth-register.js" defer></script>
