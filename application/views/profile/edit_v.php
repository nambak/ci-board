<article class="container my-5" id="profile_page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">프로필 수정</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted" for="name">이름 <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted" for="email">이메일</label>
                        <input class="form-control-plaintext" type="email" id="email" readonly>
                        <small class="text-muted">이메일은 변경할 수 없습니다.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted" for="password">비밀번호</label>
                        <input class="form-control" type="password" id="password">
                        <div class="invalid-feedback" id="password-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted" for="new-password">새 비밀번호</label>
                        <input class="form-control" type="password" id="new-password">
                        <div class="invalid-feedback" id="new-password-error"></div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/profile" class="btn btn-secondary">취소</a>
                        <button type="button" class="btn btn-primary" id="updateBtn">확인</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<script defer>
    const pageId = '#profile_page ';

    $(document).ready(() => {
        loadProfile();
        initUpdateButton();
    });

    function loadProfile() {
        $.ajax({
            url: '/rest/user/profile',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success && response.data) {
                    $('#name').val(response.data.name);
                    $('#email').val(response.data.email);
                }
            },
            error: (xhr) => {
                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'warning',
                        title: '로그인 필요',
                        text: '로그인이 필요한 서비스입니다.',
                        confirmButtonText: '로그인'
                    }).then(() => {
                        location.href = '/login?redirect=' + encodeURIComponent(location.pathname);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: '프로필 정보를 불러오는 중 오류가 발생했습니다.'
                    }).then(() => {
                        location.href = '/profile';
                    });
                }
            }
        });
    }

    function initUpdateButton() {
        $(pageId + '#updateBtn').on('click', function() {
            // 유효성 검사
            if (!validateForm()) {
                return;
            }

            // 확인 메시지
            Swal.fire({
                title: '프로필 수정',
                text: '프로필을 수정하시겠습니까?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '확인',
                cancelButtonText: '취소',
                confirmButtonColor: '#0d6efd'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateProfile();
                }
            });
        });
    }

    function validateForm() {
        let isValid = true;
        clearErrors();

        const name = $('#name').val().trim();

        // 이름 검사
        if (!name) {
            showError('name', '이름을 입력해주세요.');
            isValid = false;
        } else if (name.length < 2) {
            showError('name', '이름은 2자 이상이어야 합니다.');
            isValid = false;
        }

        const password = $('#password').val();
        const newPassword = $('#new-password').val();

        if (password && !newPassword) {
            showError('new-password', '새 비밀번호를 입력해 주세요.');
            isValid = false;
        }

        if (newPassword && !password) {
            showError('password', '현재 비빌번호를 입력해 주세요');
            isValid = false;
        }

        if (password && newPassword && password === newPassword) {
            showError('new-password', '새 비밀번호가 현재 비밀번호와 같습니다.');
            showError('password', '현재 비밀번호가 새 비밀번호와 같습니다.');
            isValid = false;
        }

        return isValid;
    }

    function updateProfile() {
        // 버튼 비활성화
        $('#updateBtn').prop('disabled', true).text('처리 중...');

        const data = {};
        data['<?= $this->security->get_csrf_token_name(); ?>'] = '<?= $this->security->get_csrf_hash(); ?>';

        const name = $('#name').val().trim();

        if (name) {
            data['name'] = name;
        }

        $.ajax({
            url: '/rest/user/profile',
            type: 'PUT',
            data: {
                name: name,
                <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '수정 완료',
                        text: '프로필이 수정되었습니다.',
                        confirmButtonText: '확인'
                    }).then(() => {
                        location.href = '/profile';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '수정 실패',
                        text: response.message || '프로필 수정에 실패했습니다.'
                    });
                }
            },
            error: (xhr) => {
                let message = '프로필 수정 중 오류가 발생했습니다.';

                if (xhr.status === 401) {
                    message = '로그인이 필요합니다.';
                } else if (xhr.status === 422 && xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        showServerErrors(xhr.responseJSON.errors);
                        return;
                    }
                    message = xhr.responseJSON.message || message;
                }

                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: message
                });
            },
            complete: () => {
                // 버튼 활성화
                $('#updateBtn').prop('disabled', false).text('확인');
            }
        });
    }

    function showError(field, message) {
        $('#' + field).addClass('is-invalid');
        $('#' + field + '-error').text(message);
    }

    function showServerErrors(errors) {
        $.each(errors, function(field, message) {
            showError(field, message);
        });
    }

    function clearErrors() {
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }
</script>