<article class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">비밀번호 찾기</h4>
                </div>
                <div class="card-body">
                    <?php if($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $this->session->flashdata('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $this->session->flashdata('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">
                        가입하신 이메일 주소를 입력하시면, 비밀번호 재설정 링크를 보내드립니다.
                    </p>

                    <form method="post" action="/auth/send_reset_link">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">이메일 주소</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com" required autofocus>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">재설정 링크 발송</button>
                        </div>

                        <div class="text-center">
                            <small>
                                <a href="/login" class="text-decoration-none">로그인으로 돌아가기</a>
                            </small>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        계정이 없으신가요? <a href="/register" class="text-decoration-none">회원가입</a>
                    </small>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">안내사항</h6>
                    <ul class="small text-muted mb-0">
                        <li>재설정 링크는 24시간 동안 유효합니다.</li>
                        <li>링크는 1회만 사용 가능합니다.</li>
                        <li>이메일이 도착하지 않으면 스팸 폴더를 확인해주세요.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</article>