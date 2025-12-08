<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Email Configuration
| -------------------------------------------------------------------------
| CI3Board 이메일 설정
|
| 프로토콜 옵션: mail, sendmail, smtp
| 현재 설정: mailtrap
*/

$config = [
    'protocol'       => 'smtp',
    'smtp_host'      => getenv('SMTP_HOST') ?: 'sandbox.smtp.mailtrap.io',
    'smtp_port'      => getenv('SMTP_PORT') ?: 2525,
    'smtp_user'      => getenv('SMTP_USER'),
    'smtp_pass'      => getenv('SMTP_PASS'),
    'smtp_crypto'    => 'tls',
    'crlf'           => "\r\n",
    'newline'        => "\r\n",
    'mailtype'       => 'html',
    'charset'        => 'utf-8',
    'wordwrap'       => TRUE,
    'smtp_from'      => 'noreply@ci3board.test',
    'smtp_from_name' => 'CI3Board'
];