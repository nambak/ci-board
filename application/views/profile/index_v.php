<article class="container my-5" id="profile_page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">프로필</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div id="profileImageContainer">
                            <div class="avatar-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem;">
                                <span id="userInitial"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">이름</label>
                        <p class="form-control-plaintext border-bottom pb-2" id="userName">로딩 중...</p>
                    </div>

                    <div class="mb-3" id="emailSection" style="display: none;">
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
                        <a href="/profile/edit" class="btn btn-primary" id="editButton" style="display: none;">프로필 수정</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<script defer>
    const pageId = '#profile_page ';
    const userId = <?php echo isset($user_id) ? (int)$user_id : 'null'; ?>;

    $(document).ready(() => {
        if (userId) {
            loadProfile(userId);
        } else {
            showError('사용자 정보를 찾을 수 없습니다.');
        }
    });

    function loadProfile(id) {
        $.ajax({
            url: `/rest/user/${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.success && response.data) {
                    const user = response.data;

                    // 사용자 정보 표시
                    $('#userName').text(user.name || '-');
                    $('#createdAt').text(user.created_at ? formatDate(user.created_at) : '-');

                    // 이메일은 본인 프로필일 때만 표시
                    if (user.email) {
                        $('#userEmail').text(user.email);
                        $('#emailSection').show();
                    }

                    // 프로필 이미지 또는 이니셜 아바타 설정
                    if (user.profile_image_url) {
                        $('#profileImageContainer').html(
                            '<img src="' + user.profile_image_url + '" alt="프로필" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">'
                        );
                    } else if (user.name) {
                        $('#userInitial').text(user.name.charAt(0).toUpperCase());
                    }

                    // 활동 통계
                    $('#postCount').text(user.post_count || 0);
                    $('#commentCount').text(user.comment_count || 0);

                    // 본인 프로필이면 수정 버튼 표시
                    if (user.is_owner) {
                        $('#editButton').show();
                    }
                } else {
                    showError('프로필 정보를 불러올 수 없습니다.');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showError('사용자를 찾을 수 없습니다.');
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
        $('#createdAt').text('-');

        Swal.fire({
            icon: 'error',
            title: '오류',
            text: message
        });
    }
</script>