<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Rate Limiting Configuration
 *
 * API별 요청 제한 설정
 */

// Rate Limiting 활성화 여부
$config['rate_limit_enabled'] = true;

// 기본 설정
$config['rate_limit_default'] = [
    'max_requests' => 100,
    'time_window' => 60 // seconds
];

/**
 * API 엔드포인트별 제한 설정
 *
 * 각 엔드포인트는 다음 형식으로 정의:
 * 'endpoint_pattern' => [
 *     'max_requests' => int,    // 최대 요청 수
 *     'time_window' => int,     // 시간 윈도우 (초)
 *     'description' => string   // 설명
 * ]
 */
$config['rate_limit_rules'] = [
    // 인증 관련 API - 엄격한 제한
    '/rest/auth/login' => [
        'max_requests' => 5,
        'time_window' => 60,  // 5회/분
        'description' => '로그인 API'
    ],
    '/rest/auth/register' => [
        'max_requests' => 3,
        'time_window' => 3600,  // 3회/시간
        'description' => '회원가입 API'
    ],
    '/rest/auth/password' => [
        'max_requests' => 3,
        'time_window' => 3600,  // 3회/시간
        'description' => '비밀번호 재설정 API'
    ],

    // 조회 API - 관대한 제한
    '/rest/board*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '게시판 조회 API'
    ],
    '/rest/article*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '게시글 조회 API'
    ],
    '/rest/comment*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '댓글 조회 API'
    ],
    '/rest/user*' => [
        'max_requests' => 50,
        'time_window' => 60,  // 50회/분
        'description' => '사용자 조회 API'
    ],

    // 작성/수정/삭제 API - 중간 제한
    '/rest/article/create' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '게시글 작성 API'
    ],
    '/rest/article/update' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '게시글 수정 API'
    ],
    '/rest/article/delete' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '게시글 삭제 API'
    ],
    '/rest/comment/create' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '댓글 작성 API'
    ],
    '/rest/comment/update' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '댓글 수정 API'
    ],
    '/rest/comment/delete' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '댓글 삭제 API'
    ],

    // 이메일 중복 체크 등 - 엄격한 제한
    '/rest/auth/check-email' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '이메일 중복 체크 API'
    ]
];

/**
 * 인증된 사용자에 대한 제한 배수
 * 인증된 사용자는 기본 제한의 N배까지 허용
 */
$config['rate_limit_authenticated_multiplier'] = 2;

/**
 * 관리자 역할 (rate limit 면제)
 */
$config['rate_limit_admin_roles'] = ['admin', 'superadmin'];

/**
 * IP 화이트리스트
 * 이 IP들은 rate limiting이 적용되지 않음
 */
$config['rate_limit_whitelist'] = [
    '127.0.0.1',
    '::1',
    // 'xxx.xxx.xxx.xxx' // 추가 화이트리스트 IP
];

/**
 * HTTP 헤더 설정
 */
$config['rate_limit_headers'] = [
    'limit' => 'X-RateLimit-Limit',
    'remaining' => 'X-RateLimit-Remaining',
    'reset' => 'X-RateLimit-Reset'
];

/**
 * 로깅 설정
 */
$config['rate_limit_log_violations'] = true;  // 제한 초과 시 DB 로그 기록
$config['rate_limit_log_level'] = 'warning';   // 로그 레벨 (error, warning, info)
