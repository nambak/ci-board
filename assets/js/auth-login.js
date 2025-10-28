const AuthLogin = {
    pageId: '#login_page ',
    csrfTokenName: null,
    csrfToken: null,

    initLoginForm() {
        $(this.pageId + '#loginForm').on('submit', (e) => {
            e.preventDefault();

            // 기존 에러 메시지 초기화
            this.clearErrors();

            const authLoginData = {};
            authLoginData[this.csrfTokenName] = this.csrfToken;
            authLoginData['email'] = $('#email').val();
            authLoginData['password'] = $('#password').val();
            authLoginData['remember'] = $('#remember').is(':checked');

            // 로그인 버튼 비활성화
            $('#loginBtn').prop('disabled', true).text('로그인 중...');

            $.ajax({
                url: '/rest/auth/login',
                type: 'POST',
                data: authLoginData,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '로그인 성공',
                            text: response.message || '환영합니다!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // 이전 페이지로 이동하거나 메인 페이지로 이동
                            location.href = new URLSearchParams(window.location.search).get('redirect') || '/board';
                        });
                    }
                },
                error: (xhr, status, error) => {
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
                complete: () => {
                    // 로그인 버튼 활성화
                    $('#loginBtn').prop('disabled', false).text('로그인');
                }
            });
        });
    },

    clearErrors() {
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    },

    init(csrfTokenName, csrfToken) {
        this.csrfTokenName = csrfTokenName;
        this.csrfToken = csrfToken;

        this.initLoginForm();
    }
}

$(document).ready(() => {
    const form = $('#login_page');
    const csrfTokenName = form.data('csrf-token-name');
    const csrfToken = form.data('csrf-token');

    AuthLogin.init(csrfTokenName, csrfToken)
});