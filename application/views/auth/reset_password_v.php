<article class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">비밀번호 재설정</h4>
                </div>
                <div class="card-body">
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">
                        새로운 비밀번호를 입력해주세요.
                    </p>

                    <form method="post" action="/auth/update_password" id="resetPasswordForm">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        <?= form_hidden('token', $token) ?>

                        <div class="mb-3">
                            <label for="password" class="form-label">새 비밀번호</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="8" required autofocus>
                            <div class="form-text">최소 8자 이상 입력해주세요.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">비밀번호 확인</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   minlength="8" required>
                            <div class="invalid-feedback" id="password-match-error">
                                비밀번호가 일치하지 않습니다.
                            </div>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <small>
                                <strong>비밀번호 요구사항:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>최소 8자 이상</li>
                                    <li>영문, 숫자 조합 권장</li>
                                </ul>
                            </small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">비밀번호 재설정</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <a href="/login" class="text-decoration-none">로그인으로 돌아가기</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</article>

<script>
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const errorDiv = document.getElementById('password-match-error');
    const passwordConfirmInput = document.getElementById('password_confirm');

    if (password !== passwordConfirm) {
        e.preventDefault();
        passwordConfirmInput.classList.add('is-invalid');
        errorDiv.style.display = 'block';
        return false;
    } else {
        passwordConfirmInput.classList.remove('is-invalid');
        errorDiv.style.display = 'none';
    }
});

document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const passwordConfirm = this.value;
    const errorDiv = document.getElementById('password-match-error');

    if (password === passwordConfirm) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        errorDiv.style.display = 'none';
    } else {
        this.classList.remove('is-valid');
    }
});
</script>