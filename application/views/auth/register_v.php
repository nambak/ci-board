<article class="container my-5" id="register_page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0">회원가입</h4>
                </div>
                <div class="card-body">
                    <form id="registerForm" method="post" action="/register">
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

<script defer>
    const pageId = '#register_page ';

    $(document).ready(() => {
        initRegisterForm();
        initValidation();
    });

    function initRegisterForm() {
        $(pageId + '#registerForm').on('submit', function (e) {
            e.preventDefault();

            // 기존 에러 메시지 초기화
            clearErrors();

            // 클라이언트 사이드 유효성 검사
            if (!validateForm()) {
                return;
            }

            const formData = {
                name: $('#name').val(),
                email: $('#email').val(),
                password: $('#password').val(),
                <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>'
            };

            // 회원가입 버튼 비활성화
            $('#registerBtn').prop('disabled', true).text('처리 중...');

            $.ajax({
                url: '/rest/auth/register',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '회원가입 성공',
                            text: response.message || '회원가입이 완료되었습니다. 로그인 페이지로 이동합니다.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.href = '/login';
                        });
                    }
                },
                error: function (xhr, status, error) {
                    let message = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';

                    if (xhr.status === 400 || xhr.status === 422) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            showErrors(xhr.responseJSON.errors);
                            return;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: '오류 발생',
                        text: message
                    });
                },
                complete: function () {
                    // 회원가입 버튼 활성화
                    $('#registerBtn').prop('disabled', false).text('회원가입');
                }
            });
        });
    }

    function initValidation() {
        // 비밀번호 확인 실시간 검증
        $('#password_confirm').on('input', function () {
            const password = $('#password').val();
            const passwordConfirm = $(this).val();

            if (passwordConfirm && password !== passwordConfirm) {
                $(this).addClass('is-invalid');
                $('#password_confirm-error').text('비밀번호가 일치하지 않습니다.');
            } else {
                $(this).removeClass('is-invalid');
                $('#password_confirm-error').text('');
            }
        });

        // 이메일 중복 확인 (디바운스 적용)
        let emailTimeout;
        $('#email').on('input', function () {
            const email = $(this).val();

            clearTimeout(emailTimeout);

            if (email && validateEmail(email)) {
                emailTimeout = setTimeout(() => {
                    checkEmailDuplicate(email);
                }, 500);
            }
        });
    }

    function validateForm() {
        let isValid = true;

        // 필수 필드 검사
        const requiredFields = ['name', 'email', 'password', 'password_confirm'];
        requiredFields.forEach(field => {
            if (!$('#' + field).val().trim()) {
                $('#' + field).addClass('is-invalid');
                $('#' + field + '-error').text('이 필드는 필수입니다.');
                isValid = false;
            }
        });

        // 비밀번호 확인
        if ($('#password').val() !== $('#password_confirm').val()) {
            $('#password_confirm').addClass('is-invalid');
            $('#password_confirm-error').text('비밀번호가 일치하지 않습니다.');
            isValid = false;
        }

        // 비밀번호 강도 검사
        if (!validatePassword($('#password').val())) {
            $('#password').addClass('is-invalid');
            $('#password-error').text('비밀번호는 6자 이상, 영문, 숫자, 특수문자를 포함해야 합니다.');
            isValid = false;
        }

        // 이메일 형식 검사
        if (!validateEmail($('#email').val())) {
            $('#email').addClass('is-invalid');
            $('#email-error').text('올바른 이메일 주소를 입력해주세요.');
            isValid = false;
        }

        // 약관 동의 검사 제거 (필드가 삭제됨)

        return isValid;
    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function validatePassword(password) {
        // 최소 8자, 영문, 숫자, 특수문자 포함
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{6,}$/;
        return passwordRegex.test(password);
    }

    function checkEmailDuplicate(email) {
        $.ajax({
            url: '/rest/auth/check_email',
            type: 'GET',
            data: {
                email: email,
            },
            dataType: 'json',
            success: function (response) {
                if (response.exists) {
                    $('#email').addClass('is-invalid');
                    $('#email-error').text('이미 사용 중인 이메일입니다.');
                } else {
                    $('#email').removeClass('is-invalid');
                    $('#email-error').text('');
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: '오류 발생',
                    text: error
                });
            }
        });
    }

    function clearErrors() {
        $('.form-control').removeClass('is-invalid');
        $('.form-check-input').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showErrors(errors) {
        $.each(errors, function (field, message) {
            $('#' + field).addClass('is-invalid');
            $('#' + field + '-error').text(message);
        });
    }
</script>
