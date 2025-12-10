/**
 * 신고 기능 JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    const reportModal = document.getElementById('reportModal');
    const reportTargetType = document.getElementById('reportTargetType');
    const reportTargetId = document.getElementById('reportTargetId');
    const reportDetailSection = document.getElementById('reportDetailSection');
    const reportDetail = document.getElementById('reportDetail');
    const submitReportBtn = document.getElementById('submitReport');

    if (!reportModal) return;

    // 신고 모달 열 때 대상 정보 설정
    reportModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (button) {
            const targetType = button.getAttribute('data-target-type');
            const targetId = button.getAttribute('data-target-id');

            reportTargetType.value = targetType;
            reportTargetId.value = targetId;

            // 모달 제목 업데이트
            const modalTitle = reportModal.querySelector('.modal-title');
            if (targetType === 'article') {
                modalTitle.textContent = '게시글 신고하기';
            } else if (targetType === 'comment') {
                modalTitle.textContent = '댓글 신고하기';
            }
        }

        // 폼 초기화
        resetReportForm();
    });

    // 기타 사유 선택 시 상세 내용 입력 필드 표시
    const reasonRadios = document.querySelectorAll('input[name="reason"]');
    reasonRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (this.value === 'other') {
                reportDetailSection.style.display = 'block';
            } else {
                reportDetailSection.style.display = 'none';
                reportDetail.value = '';
            }
        });
    });

    // 신고 제출
    submitReportBtn.addEventListener('click', function () {
        const targetType = reportTargetType.value;
        const targetId = reportTargetId.value;
        const reason = document.querySelector('input[name="reason"]:checked');
        const detail = reportDetail.value.trim();

        // 유효성 검사
        if (!reason) {
            Swal.fire({
                icon: 'warning',
                text: '신고 사유를 선택해주세요.'
            });
            return;
        }

        if (reason.value === 'other' && !detail) {
            Swal.fire({
                icon: 'warning',
                text: '기타 사유를 선택한 경우 상세 내용을 입력해주세요.'
            });
            reportDetail.focus();
            return;
        }

        // 버튼 비활성화
        submitReportBtn.disabled = true;
        submitReportBtn.textContent = '처리 중...';

        // CSRF 토큰
        const postDetail = document.getElementById('post_detail');
        const csrfTokenName = postDetail.dataset.csrfTokenName;
        const csrfHash = postDetail.dataset.csrfHash;

        // FormData 생성
        const formData = new FormData();
        formData.append(csrfTokenName, csrfHash);
        formData.append('target_type', targetType);
        formData.append('target_id', targetId);
        formData.append('reason', reason.value);
        if (detail) {
            formData.append('detail', detail);
        }

        const modal = bootstrap.Modal.getInstance(reportModal);

        $.ajax({
            url: '/rest/report',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                Swal.fire({
                    icon: 'success',
                    text: response.message || '신고가 접수되었습니다.'
                }).then(() => modal.hide());
            },
            error: (xhr) => {
                console.error('Report error:', xhr);
                const response = xhr.responseJSON || {};
                if (xhr.status === 409) {
                    Swal.fire({
                        icon: 'error',
                        text: response.message || '이미 신고한 게시물입니다.'
                    }).then(() => modal.hide());
                } else if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'error',
                        text: '로그인이 필요합니다.'
                    }).then(() => window.location.href = '/login');
                } else {
                    Swal.fire({
                        icon: 'error',
                        text: response.message || '신고 처리 중 오류가 발생했습니다.'
                    }).then(() => modal.hide());
                }
            },
            complete: () => {
                submitReportBtn.disabled = false;
                submitReportBtn.textContent = '신고하기';
            }
        });
    });

    /**
     * 신고 폼 초기화
     */
    function resetReportForm() {
        // 라디오 버튼 초기화
        const reasonRadios = document.querySelectorAll('input[name="reason"]');
        reasonRadios.forEach(function (radio) {
            radio.checked = false;
        });

        // 상세 내용 초기화
        reportDetail.value = '';
        reportDetailSection.style.display = 'none';
    }
});

/**
 * 댓글 신고 버튼 클릭 핸들러 (동적 댓글용)
 * @param {HTMLElement} button - 클릭된 버튼 요소
 */
function openReportModal(button) {
    const targetType = button.getAttribute('data-target-type');
    const targetId = button.getAttribute('data-target-id');

    document.getElementById('reportTargetType').value = targetType;
    document.getElementById('reportTargetId').value = targetId;

    // 모달 제목 업데이트
    const reportModal = document.getElementById('reportModal');
    const modalTitle = reportModal.querySelector('.modal-title');
    if (targetType === 'article') {
        modalTitle.textContent = '게시글 신고하기';
    } else if (targetType === 'comment') {
        modalTitle.textContent = '댓글 신고하기';
    }

    // 모달 열기
    const bsModal = new bootstrap.Modal(reportModal);
    bsModal.show();
}
