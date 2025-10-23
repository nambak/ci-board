<article class="container my-5" id="profile_page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">프로필</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem;">
                            <span id="userInitial"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">이름</label>
                        <p class="form-control-plaintext border-bottom pb-2" id="userName">로딩 중...</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">이메일</label>
                        <p class="form-control-plaintext border-bottom pb-2" id="userEmail">로딩 중...</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">가입일</label>
                        <p class="form-control-plaintext border-bottom pb-2" id="createdAt">로딩 중...</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">활동 통계</label>
                        <div class="row text-center mt-2">
                            <div class="col-6">
                                <div class="p-3 border rounded">
                                    <h5 class="mb-1" id="postCount">-</h5>
                                    <small class="text-muted">작성한 글</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded">
                                    <h5 class="mb-1" id="commentCount">-</h5>
                                    <small class="text-muted">작성한 댓글</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/board" class="btn btn-secondary">목록으로</a>
                        <button type="button" class="btn btn-primary" id="editProfileBtn">프로필 수정</button>
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

        // 프로필 수정 버튼 (향후 구현)
        $(pageId + '#editProfileBtn').on('click', function() {
            Swal.fire({
                icon: 'info',
                title: '준비 중',
                text: '프로필 수정 기능은 준비 중입니다.'
            });
        });
    });

    function loadProfile() {
        $.ajax({
            url: '/rest/user/profile',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const user = response.data;

                    // 사용자 정보 표시
                    $('#userName').text(user.name || '-');
                    $('#userEmail').text(user.email || '-');
                    $('#createdAt').text(user.created_at ? formatDate(user.created_at) : '-');

                    // 아바타 이니셜 설정
                    if (user.name) {
                        $('#userInitial').text(user.name.charAt(0).toUpperCase());
                    }

                    // 활동 통계
                    $('#postCount').text(user.post_count || 0);
                    $('#commentCount').text(user.comment_count || 0);
                } else {
                    showError('프로필 정보를 불러올 수 없습니다.');
                }
            },
            error: function(xhr) {
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
                    showError('프로필 정보를 불러오는 중 오류가 발생했습니다.');
                }
            }
        });
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        // YYYY-MM-DD HH:MM:SS 형식에서 날짜만 추출
        return dateString.split(' ')[0];
    }

    function showError(message) {
        $('#userName').text('-');
        $('#userEmail').text('-');
        $('#createdAt').text('-');

        Swal.fire({
            icon: 'error',
            title: '오류',
            text: message
        });
    }
</script>