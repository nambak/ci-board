



const AuthRegister = {
    pageId: '#register_page ',

    initRegisterForm() {
        $(this.pageId + '#registerForm').on('submit', function (e) {
            e.preventDefault();

            // 기존 에러 메시지 초기화
            this.clearErrors();

            // 클라이언트 사이드 유효성 검사
            if (!this.validateForm()) {
                return;
            }

            const formData = {};
            formData[this.csrfTokenName] = this.csrfToken;
            formData['name'] = $('#name').val();
            formData['email'] = $('#email').val();
            formData['password'] = $('#password').val();

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
                            this.showErrors(xhr.responseJSON.errors);
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
    },

    clearErrors() {
        $('.form-control').removeClass('is-invalid');
        $('.form-check-input').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    },

    showErrors(errors) {
        $.each(errors, function (field, message) {
            $('#' + field).addClass('is-invalid');
            $('#' + field + '-error').text(message);
        });
    },

    initValidation() {
        // 비밀번호 확인 실시간 검증
        $('#password_confirm').on('input', () => {
            const password = $('#password').val();
            const passwordConfirm = $('#password_confirm').val();


            if (passwordConfirm && password !== passwordConfirm) {
                $('#password_confirm').addClass('is-invalid');
                $('#password_confirm-error').text('비밀번호가 일치하지 않습니다.');
            } else {
                $('#password_confirm').removeClass('is-invalid');
                $('#password_confirm-error').text('');
            }
        });

        // 이메일 중복 확인 (디바운스 적용)
        let emailTimeout;
        $('#email').on('input', () => {
            const email = $('#email').val();

            clearTimeout(emailTimeout);

            if (email && this.validateEmail(email)) {
                emailTimeout = setTimeout(() => {
                    this.checkEmailDuplicate(email);
                }, 500);
            }
        });
    },

    validateForm() {
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
        if (!this.validatePassword($('#password').val())) {
            $('#password').addClass('is-invalid');
            $('#password-error').text('비밀번호는 6자 이상, 영문, 숫자, 특수문자를 포함해야 합니다.');
            isValid = false;
        }

        // 이메일 형식 검사
        if (!this.validateEmail($('#email').val())) {
            $('#email').addClass('is-invalid');
            $('#email-error').text('올바른 이메일 주소를 입력해주세요.');
            isValid = false;
        }

        // 약관 동의 검사 제거 (필드가 삭제됨)

        return isValid;
    },

    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    validatePassword(password) {
        // 최소 6자, 영문, 숫자, 특수문자 포함
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{6,}$/;
        return passwordRegex.test(password);
    },

    checkEmailDuplicate(email) {
        $.ajax({
            url: '/rest/auth/check_email',
            type: 'GET',
            data: {
                email: email,
            },
            dataType: 'json',
            timeout: 10000, // 10초 타임아웃 설정
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
                let message = '이메일 중복 확인 중 오류가 발생했습니다.';

                // 네트워크 오류나 타임아웃 처리
                if (status === 'timeout') {
                    message = '요청 시간이 초과되었습니다. 다시 시도해주세요.';
                } else if (status === 'error' && xhr.status === 0) {
                    message = '네트워크 연결을 확인해주세요.';
                } else if (xhr.status === 500) {
                    message = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
                } else if (xhr.status === 400) {
                    message = '올바른 이메일 형식이 아닙니다.';
                }
                Swal.fire({
                    icon: 'error',
                    title: '오류 발생',
                    text: message
                });
            }
        });
    },

    init() {
        this.initRegisterForm();
        this.initValidation();
    }
}

$(document).ready(() => {
    AuthRegister.init()
});
