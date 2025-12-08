<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 파일 업로드 설정
 */

// 업로드 디렉토리 (FCPATH는 index.php가 있는 루트 디렉토리)
$config['upload_path'] = FCPATH . 'uploads/';
$config['upload_path_attachments'] = FCPATH . 'uploads/attachments/';
$config['upload_path_thumbnails'] = FCPATH . 'uploads/thumbnails/';

// 허용된 파일 타입
$config['allowed_types'] = [
    'images' => 'jpg|jpeg|png|gif',
    'documents' => 'pdf|doc|docx|xls|xlsx|ppt|pptx|txt',
    'archives' => 'zip|rar|7z|tar|gz',
    'all' => 'jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|7z|tar|gz'
];

// 이미지 MIME 타입
$config['image_mime_types'] = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif'
];

// 파일 크기 제한 (KB)
$config['max_size_image'] = 5120; // 5MB
$config['max_size_file'] = 10240; // 10MB

// 게시글당 최대 파일 개수
$config['max_files_per_article'] = 5;

// 이미지 리사이징 설정
$config['max_image_width'] = 1920;
$config['max_image_height'] = 1920;

// 썸네일 설정
$config['thumbnail_width'] = 200;
$config['thumbnail_height'] = 200;
$config['thumbnail_maintain_ratio'] = TRUE;

// 파일명 암호화
$config['encrypt_name'] = TRUE;

// 파일 덮어쓰기 방지
$config['overwrite'] = FALSE;

// 허용되지 않은 파일 타입
$config['disallowed_types'] = [
    'php', 'php3', 'php4', 'php5', 'phtml',
    'exe', 'bat', 'cmd', 'com', 'pif',
    'scr', 'vbs', 'js', 'jar', 'sh',
    'asp', 'aspx', 'jsp', 'cgi', 'pl'
];