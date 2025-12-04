<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 재설정</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0d6efd;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #0d6efd;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0b5ed7;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 0.9em;
            color: #6c757d;
            text-align: center;
        }
        .token-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 CI3Board</h1>
            <h2>비밀번호 재설정 요청</h2>
        </div>

        <div class="content">
            <p>안녕하세요, <strong><?php echo htmlspecialchars($user_name); ?></strong>님</p>

            <p>회원님의 계정에서 비밀번호 재설정 요청이 접수되었습니다.</p>

            <p>아래 버튼을 클릭하여 새로운 비밀번호를 설정하실 수 있습니다:</p>

            <div style="text-align: center;">
                <a href="<?php echo $reset_url; ?>" class="button">비밀번호 재설정하기</a>
            </div>

            <p>또는 아래 링크를 복사하여 브라우저에 붙여넣기 하세요:</p>
            <div class="token-info">
                <?php echo $reset_url; ?>
            </div>

            <div class="warning">
                <strong>⚠️ 보안 안내</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>이 링크는 <strong><?php echo $expires_at; ?></strong>까지 유효합니다.</li>
                    <li>링크는 1회만 사용 가능합니다.</li>
                    <li>비밀번호 재설정을 요청하지 않으셨다면, 이 이메일을 무시하셔도 됩니다.</li>
                    <li>계정 보안을 위해 이 이메일을 타인과 공유하지 마세요.</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>본 메일은 발신 전용입니다. 문의사항이 있으시면 고객센터를 이용해 주세요.</p>
            <p>&copy; <?php echo date('Y'); ?> CI3Board. All rights reserved.</p>
        </div>
    </div>
</body>
</html>