<article class="container my-5" id="profile_page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">프로필 수정</h4>
                </div>
                <div class="card-body">
                    <!-- 프로필 이미지 섹션 -->
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <div id="profileImageContainer">
                                <div class="avatar-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; border-radius: 50%; font-size: 3rem;">
                                    <span id="userInitial"></span>
                                </div>
                            </div>
                            <label for="profileImageInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; cursor: pointer;">
                                <i class="bi bi-camera"></i>
                            </label>
                            <input type="file" id="profileImageInput" accept="image/jpeg,image/png,image/gif" style="display: none;">
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">JPG, PNG, GIF (최대 2MB)</small>
                        </div>
                        <button type="button" id="deleteProfileImage" class="btn btn-sm btn-outline-danger mt-2" style="display: none;">
                            <i class="bi bi-trash"></i> 이미지 삭제
                        </button>
                    </div>

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

                    <!-- 알림 설정 -->
                    <hr class="my-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">알림 설정</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notificationEnabled" checked>
                            <label class="form-check-label" for="notificationEnabled">
                                알림 받기
                                <small class="text-muted d-block">댓글, 답글, 좋아요 알림을 받습니다.</small>
                            </label>
                        </div>
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
    const csrfTokenName = '<?= $this->security->get_csrf_token_name(); ?>';
    const csrfHash = '<?= $this->security->get_csrf_hash(); ?>';

    $(document).ready(() => {
        loadProfile();
        initUpdateButton();
        initProfileImageUpload();
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

                    // 프로필 이미지 표시
                    if (response.data.profile_image_url) {
                        showProfileImage(response.data.profile_image_url);
                        $('#deleteProfileImage').show();
                    } else if (response.data.name) {
                        $('#userInitial').text(response.data.name.charAt(0).toUpperCase());
                    }

                    // 알림 설정 로드
                    if (response.data.notification_enabled !== undefined) {
                        $('#notificationEnabled').prop('checked', response.data.notification_enabled == 1);
                    }
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

        const password = $('#password').val().trim();
        const newPassword = $('#new-password').val().trim();

        if (password && !newPassword) {
            showError('new-password', '새 비밀번호를 입력해 주세요.');
            isValid = false;
        }

        if (newPassword && !password) {
            showError('password', '현재 비밀번호를 입력해 주세요.');
            isValid = false;
        }

        if (newPassword && newPassword.length < 8) {
            showError('new-password', '새 비밀번호는 8자 이상이어야 합니다.');
            isValid = false;
        }

        if (password && newPassword && password === newPassword) {
            showError('new-password', '새 비밀번호가 현재 비밀번호와 같습니다.');
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
        const password = $('#password').val().trim();
        const newPassword = $('#new-password').val().trim();

        data['name'] = name;
        data['notification_enabled'] = $('#notificationEnabled').is(':checked') ? 1 : 0;

        if (password && newPassword) {
            data['password'] = password;
            data['new_password'] = newPassword;
        }

        $.ajax({
            url: '/rest/user/profile',
            type: 'PUT',
            data: data,
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

    // 프로필 이미지 업로드 초기화
    function initProfileImageUpload() {
        // 파일 선택 이벤트
        $('#profileImageInput').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            // 파일 유효성 검사
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    text: 'JPG, PNG, GIF 형식의 이미지만 업로드할 수 있습니다.'
                });
                this.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    text: '파일 크기는 2MB 이하여야 합니다.'
                });
                this.value = '';
                return;
            }

            uploadProfileImage(file);
        });

        // 삭제 버튼 이벤트
        $('#deleteProfileImage').on('click', function() {
            Swal.fire({
                title: '프로필 이미지 삭제',
                text: '프로필 이미지를 삭제하시겠습니까?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '삭제',
                cancelButtonText: '취소',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteProfileImage();
                }
            });
        });
    }

    function uploadProfileImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append(csrfTokenName, csrfHash);

        Swal.fire({
            title: '업로드 중...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: '/rest/user/profile/image',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                Swal.close();
                if (response.success) {
                    showProfileImage(response.data.profile_image_url);
                    $('#deleteProfileImage').show();
                    Swal.fire({
                        icon: 'success',
                        text: '프로필 이미지가 업로드되었습니다.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        text: response.message || '이미지 업로드에 실패했습니다.'
                    });
                }
            },
            error: (xhr) => {
                Swal.close();
                const response = xhr.responseJSON || {};
                Swal.fire({
                    icon: 'error',
                    text: response.message || '이미지 업로드 중 오류가 발생했습니다.'
                });
            }
        });

        $('#profileImageInput').val('');
    }

    function deleteProfileImage() {
        $.ajax({
            url: '/rest/user/profile/image',
            type: 'DELETE',
            data: { [csrfTokenName]: csrfHash },
            success: (response) => {
                if (response.success) {
                    showInitialAvatar();
                    $('#deleteProfileImage').hide();
                    Swal.fire({
                        icon: 'success',
                        text: '프로필 이미지가 삭제되었습니다.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        text: response.message || '이미지 삭제에 실패했습니다.'
                    });
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON || {};
                Swal.fire({
                    icon: 'error',
                    text: response.message || '이미지 삭제 중 오류가 발생했습니다.'
                });
            }
        });
    }

    function showProfileImage(url) {
        $('#profileImageContainer').html(
            '<img src="' + url + '" alt="프로필" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">'
        );
    }

    function showInitialAvatar() {
        const name = $('#name').val() || '';
        const initial = name.charAt(0).toUpperCase();
        $('#profileImageContainer').html(
            '<div class="avatar-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; border-radius: 50%; font-size: 3rem;">' +
            '<span id="userInitial">' + initial + '</span></div>'
        );
    }
</script>