<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이메일 인증 안내 - CI3Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background-color: #667eea;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 30px;
            text-align: center;
        }
        .card-header i {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .card-body {
            padding: 40px;
        }
        .btn-resend {
            background-color: #667eea;
            border: none;
            transition: all 0.3s;
        }
        .btn-resend:hover {
            background-color: #5568d3;
            transform: translateY(-2px);
        }
        .btn-resend:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .check-list {
            list-style: none;
            padding: 0;
        }
        .check-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .check-list li:last-child {
            border-bottom: none;
        }
        .check-list li i {
            color: #667eea;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-envelope-check"></i>
                    <h2 class="mb-0">이메일 인증이 필요합니다</h2>
                </div>
                <div class="card-body">
                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <p class="lead">회원가입이 완료되었습니다!</p>
                        <p>가입하신 이메일 주소로 인증 링크를 발송했습니다. 이메일을 확인하여 인증을 완료해주세요.</p>
                    </div>

                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>인증을 완료하지 않으면</h6>
                        <ul class="check-list mb-0">
                            <li><i class="bi bi-x-circle-fill text-danger"></i>게시글 작성이 제한됩니다</li>
                            <li><i class="bi bi-x-circle-fill text-danger"></i>댓글 작성이 제한됩니다</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i>게시글 읽기는 가능합니다</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h6><i class="bi bi-question-circle me-2"></i>이메일이 도착하지 않았나요?</h6>
                        <ul class="check-list">
                            <li><i class="bi bi-check-circle"></i>스팸 메일함을 확인해주세요</li>
                            <li><i class="bi bi-check-circle"></i>이메일 주소가 정확한지 확인해주세요</li>
                            <li><i class="bi bi-check-circle"></i>아래 버튼을 눌러 인증 메일을 재발송할 수 있습니다 (1분 제한)</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-resend" id="resendBtn" onclick="resendVerificationEmail()">
                            <i class="bi bi-arrow-repeat me-2"></i>인증 메일 재발송
                        </button>
                        <a href="<?= base_url('board') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>메인으로 이동
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cooldownTime = 60;
        let cooldownInterval = null;

        function resendVerificationEmail() {
            const btn = document.getElementById('resendBtn');

            if (btn.disabled) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>발송 중...';

            fetch('<?= base_url('rest/auth/resend-verification') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    startCooldown();
                } else {
                    showAlert('danger', data.message);
                    if (data.retry_after) {
                        cooldownTime = data.retry_after;
                        startCooldown();
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>인증 메일 재발송';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', '오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>인증 메일 재발송';
            });
        }

        function startCooldown() {
            const btn = document.getElementById('resendBtn');
            let timeLeft = cooldownTime;

            cooldownInterval = setInterval(() => {
                timeLeft--;
                btn.innerHTML = `<i class="bi bi-clock me-2"></i>${timeLeft}초 후 재발송 가능`;

                if (timeLeft <= 0) {
                    clearInterval(cooldownInterval);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>인증 메일 재발송';
                }
            }, 1000);
        }

        function showAlert(type, message) {
            const container = document.querySelector('.card-body');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.setAttribute('role', 'alert');

            // 메시지는 텍스트로만 삽입하여 html이 실행되지 않도록 보호
            const textNode = document.createTextNode(message);
            alert.appendChild(textNode);

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');
            closeBtn.setAttribute('aria-label', 'Close');
            alert.appendChild(closeBtn);

            container.insertAdjacentElement('afterbegin', alert);

            setTimeout(() => {
                if (alert && alert.parentNode) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    </script>
</body>
</html>