<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 이메일 인증 메일 발송
 *
 * @param string $to 수신자 이메일
 * @param string $name 수신자 이름
 * @param string $token 인증 토큰
 * @return bool 발송 성공 여부
 */
function send_verification_email($to, $name, $token)
{
    // 필수 파라미터 검증
    if (empty($to) || empty($token)) {
        log_message('error', 'send_verification_mail: Missing required parameters');
        return false;
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        log_message('error', 'send_verification_email: Invalid email address:');
        return false;
    }

    $name = $name ?: 'User';

    $CI =& get_instance();
    $CI->load->library('email');

    $verification_url = base_url('auth/verify-email?token=' . $token);

    $subject = '[CI3Board] 이메일 인증을 완료해주세요';

    $message = '
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>CI3Board 이메일 인증</h1>
            </div>
            <div class="content">
                <h2>안녕하세요, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '님!</h2>
                <p>CI3Board에 가입해 주셔서 감사합니다.</p>
                <p>아래 버튼을 클릭하여 이메일 인증을 완료해주세요:</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($verification_url, ENT_QUOTES, 'UTF-8') . '" class="button">이메일 인증하기</a>
                </p>
                
                <p>또는 아래 링크를 복사하여 브라우저에 붙여넣으세요:</p>
                <p style="word-break: break-all; background-color: #fff; padding: 10px; border: 1px solid #ddd;">
                    ' . htmlspecialchars($verification_url, ENT_QUOTES, 'UTF-8') . '
                </p>
                <p style="color: #666; font-size: 14px;">
                    <strong>참고:</strong> 이메일 인증을 완료하지 않으면 게시글 및 댓글 작성이 제한됩니다.
                </p>
            </div>
            <div class="footer">
                <p>본 메일은 발신전용입니다. 문의사항이 있으시면 관리자에게 문의해주세요.</p>
                <p>&copy; 2025 CI3Board. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $CI->email->from('noreply@ci3board.test', 'CI3Board');
    $CI->email->to($to);
    $CI->email->subject($subject);
    $CI->email->message($message);

    $result = $CI->email->send();

    if (!$result) {
        // 필요 시 개발 환경에서만 print_debugger()를 남기도록 조건 분기
        log_message('error', 'Verification email sending failed');
        return false;
    }

    log_message('info', 'Verification email sent.');

    return true;
}

/**
 * 이메일 인증 완료 알림 메일 발송
 *
 * @param string $to 수신자 이메일
 * @param string $name 수신자 이름
 * @return bool 발송 성공 여부
 */
function send_verification_success_email($to, $name)
{
    $CI =& get_instance();
    $CI->load->library('email');

    $subject = '[CI3Board] 이메일 인증이 완료되었습니다';

    $message = '
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✓ 이메일 인증 완료</h1>
            </div>
            <div class="content">
                <h2>안녕하세요, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '님!</h2>
                <p>이메일 인증이 성공적으로 완료되었습니다.</p>
                <p>이제 CI3Board의 모든 기능을 자유롭게 이용하실 수 있습니다:</p>
                <ul>
                    <li>게시글 작성 및 수정</li>
                    <li>댓글 작성</li>
                    <li>프로필 관리</li>
                </ul>
                <p>즐거운 시간 되세요!</p>
            </div>
            <div class="footer">
                <p>&copy; 2025 CI3Board. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $CI->email->from('noreply@ci3board.test', 'CI3Board');
    $CI->email->to($to);
    $CI->email->subject($subject);
    $CI->email->message($message);

    $result = $CI->email->send();

    if (!$result) {
        log_message('error', 'Verification success email sending failed');
        return false;
    }

    return true;
}
