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

<script defer>
    const pageId = '#login_page ';

    $(document).ready(() => {
        initLoginForm();
    });

    function initLoginForm() {
        $(pageId + '#loginForm').on('submit', function (e) {
            e.preventDefault();

            // 기존 에러 메시지 초기화
            clearErrors();

            const formData = {
                email: $('#email').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked')
            };

            // 로그인 버튼 비활성화
            $('#loginBtn').prop('disabled', true).text('로그인 중...');

            $.ajax({
                url: '/rest/auth/login',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '로그인 성공',
                            text: response.message || '환영합니다!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // 이전 페이지로 이동하거나 메인 페이지로 이동
                            location.href = new URLSearchParams(window.location.search).get('redirect') || '/board?id=1';
                        });
                    }
                },
                error: function (xhr, status, error) {
                    let message = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';

                    if (xhr.status === 401 || xhr.status === 422) {
                        message = xhr.responseJSON.message
                    }

                    Swal.fire({
                        icon: 'error',
                        title: '오류 발생',
                        text: message
                    });
                },
                complete: function () {
                    // 로그인 버튼 활성화
                    $('#loginBtn').prop('disabled', false).text('로그인');
                }
            });
        });
    }

    function clearErrors() {
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showErrors(errors) {
        $.each(errors, function(field, message) {
            $('#' + field).addClass('is-invalid');
            $('#' + field + '-error').text(message);
        });
    }
</script>
