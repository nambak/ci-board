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
 * 'METHOD /endpoint/path' => [
 *     'max_requests' => int,    // 최대 요청 수
 *     'time_window' => int,     // 시간 윈도우 (초)
 *     'description' => string   // 설명
 * ]
 *
 * HTTP 메서드별로 다른 제한을 설정할 수 있습니다.
 * 와일드카드(*) 사용 가능하며, 구체적인 규칙이 우선 적용됩니다.
 */
$config['rate_limit_rules'] = [
    // ============================================
    // 인증 관련 API - 엄격한 제한
    // ============================================
    'POST /rest/auth/login' => [
        'max_requests' => 5,
        'time_window' => 60,  // 5회/분
        'description' => '로그인 API'
    ],
    'POST /rest/auth/register' => [
        'max_requests' => 3,
        'time_window' => 3600,  // 3회/시간
        'description' => '회원가입 API'
    ],
    'POST /rest/auth/password' => [
        'max_requests' => 3,
        'time_window' => 3600,  // 3회/시간
        'description' => '비밀번호 재설정 API'
    ],
    'GET /rest/auth/check-email' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '이메일 중복 체크 API'
    ],
    'POST /rest/auth/logout' => [
        'max_requests' => 30,
        'time_window' => 60,  // 30회/분
        'description' => '로그아웃 API'
    ],

    // ============================================
    // 게시판 API
    // ============================================
    // 조회 - 관대한 제한
    'GET /rest/board*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '게시판 조회 API'
    ],

    // ============================================
    // 게시글 API
    // ============================================
    // 조회 - 관대한 제한
    'GET /rest/article*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '게시글 조회 API'
    ],
    // 작성 - 엄격한 제한
    'POST /rest/article' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '게시글 작성 API'
    ],
    // 수정 - 중간 제한
    'PUT /rest/article/*' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '게시글 수정 API'
    ],
    // 삭제 - 중간 제한
    'DELETE /rest/article/*' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '게시글 삭제 API'
    ],
    // 좋아요
    'POST /rest/article/*/like' => [
        'max_requests' => 30,
        'time_window' => 60,  // 30회/분
        'description' => '게시글 좋아요 API'
    ],

    // ============================================
    // 댓글 API
    // ============================================
    // 조회 - 관대한 제한
    'GET /rest/comment*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '댓글 조회 API'
    ],
    // 작성 - 엄격한 제한
    'POST /rest/comment' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '댓글 작성 API'
    ],
    // 수정 - 중간 제한
    'PUT /rest/comment/*' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '댓글 수정 API'
    ],
    // 삭제 - 중간 제한
    'DELETE /rest/comment/*' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '댓글 삭제 API'
    ],

    // ============================================
    // 사용자 API
    // ============================================
    // 조회 - 중간 제한
    'GET /rest/user*' => [
        'max_requests' => 50,
        'time_window' => 60,  // 50회/분
        'description' => '사용자 조회 API'
    ],
    // 프로필 수정 - 중간 제한
    'PUT /rest/user/*' => [
        'max_requests' => 20,
        'time_window' => 60,  // 20회/분
        'description' => '사용자 정보 수정 API'
    ],

    // ============================================
    // 첨부파일 API
    // ============================================
    // 업로드 - 엄격한 제한
    'POST /rest/attachment' => [
        'max_requests' => 10,
        'time_window' => 60,  // 10회/분
        'description' => '파일 업로드 API'
    ],
    // 다운로드 - 관대한 제한
    'GET /rest/attachment/*' => [
        'max_requests' => 100,
        'time_window' => 60,  // 100회/분
        'description' => '파일 다운로드 API'
    ],

    // ============================================
    // 신고 API
    // ============================================
    'POST /rest/report' => [
        'max_requests' => 5,
        'time_window' => 300,  // 5회/5분
        'description' => '신고 API'
    ],

    // ============================================
    // 알림 API
    // ============================================
    'GET /rest/notification*' => [
        'max_requests' => 50,
        'time_window' => 60,  // 50회/분
        'description' => '알림 조회 API'
    ],
    'PUT /rest/notification/*' => [
        'max_requests' => 30,
        'time_window' => 60,  // 30회/분
        'description' => '알림 읽음 처리 API'
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
