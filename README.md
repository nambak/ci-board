# CI3Board

CodeIgniter 3.x 기반의 게시판 서비스입니다. 웹 인터페이스와 REST API를 모두 제공하여 사용자 인증, 게시판 관리, 게시글 작성, 댓글 기능을 지원합니다.

## 주요 기능

- **사용자 관리**: 회원가입, 로그인, 프로필 관리
- **게시판 시스템**: 다중 게시판 지원, 게시글 작성/수정/삭제
- **댓글 기능**: 게시글에 대한 댓글 작성/수정/삭제 (인라인 편집 지원)
- **REST API**: 모든 주요 기능에 대한 RESTful API 제공
- **API Rate Limiting**: IP 기반 요청 제한으로 DDoS 공격 방어 및 서버 보호
- **API 문서화**: Redoc 지원

## 기술 스택

- **프레임워크**: CodeIgniter 3.x (MVC 패턴)
- **데이터베이스**: MySQL/MariaDB
- **REST API**: chriskacerguis/codeigniter-restserver
- **디버깅**: PHP DebugBar (개발 환경)
- **API 문서**: OpenAPI 3.0 (Redoc)

## 시작하기

### 필수 요구사항

- PHP 7.4 이상
- MySQL 5.7 이상 또는 MariaDB 10.2 이상
- Composer
- Apache 또는 Nginx

### 설치

1. **저장소 클론**
```bash
git clone <repository-url>
cd CI3Board
```

2. **의존성 설치**
```bash
composer install
```

3. **데이터베이스 설정**
```bash
# 데이터베이스 생성
mysql -u root -p -e "CREATE DATABASE ci3board CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 스키마 생성
mysql -u username -p ci3board < schema.sql

# Rate Limiting 테이블 마이그레이션
mysql -u username -p ci3board < migrations/001_add_rate_limiting_tables.sql
```

4. **설정 파일 수정**
```bash
# application/config/database.php
# 데이터베이스 접속 정보 설정

# application/config/config.php
# base_url 설정
```

### Docker 사용 (선택사항)

```bash
# 이미지 빌드
docker build -t ci3board .

# 컨테이너 실행
docker run -p 80:80 ci3board

# 개발 모드 (볼륨 마운트)
docker run -p 80:80 -v $(pwd):/var/www/html ci3board
```

## 프로젝트 구조

```
application/
├── controllers/          # 웹 컨트롤러
│   ├── Auth.php         # 사용자 인증
│   ├── Board.php        # 게시판 목록
│   ├── Article.php      # 게시글 관리
│   ├── Profile.php      # 프로필 관리
│   └── rest/            # REST API 컨트롤러
│       ├── Auth.php
│       ├── Board.php
│       ├── Article.php
│       ├── Comment.php
│       └── User.php
├── models/              # 데이터 모델
│   ├── User_m.php
│   ├── Board_m.php
│   ├── Article_m.php
│   └── Comment_m.php
├── views/               # 뷰 템플릿
├── libraries/           # 커스텀 라이브러리
│   ├── Rate_limiter.php
│   └── Simple_captcha.php
└── helpers/             # 헬퍼 함수
    └── auth_helper.php
```

## API 사용법

### API 문서 확인

- **Redoc**: `http://localhost/redoc`
- **OpenAPI 스펙**: `assets/api.json`

### API Rate Limiting

모든 REST API 요청은 Rate Limiting이 적용됩니다. DDoS 공격 방어 및 서버 보호를 위해 IP 주소 기반으로 요청 수를 제한합니다.

#### 제한 정책

| API 유형   | 제한   | 시간 윈도우 |
|----------|------|------------|
| 로그인      | 5회   | 1분 |
| 회원가입     | 3회   | 1시간 |
| 비밀번호 재설정 | 3회   | 1시간 |
| 조회 API   | 100회 | 1분 |
| 작성 API   | 10회  | 1분 |
| 수정 API   | 20회  | 1분 |
| 삭제 API   | 20회  | 1분 |

#### 사용자 등급별 제한

- **일반 사용자**: 기본 제한 적용
- **인증된 사용자**: 기본 제한의 2배
- **관리자**: 제한 없음
- **화이트리스트 IP**: 제한 없음 (127.0.0.1, ::1)

#### Rate Limit 헤더

정상 응답에는 다음 헤더가 포함됩니다:

```http
X-RateLimit-Limit: 100           # 최대 요청 수
X-RateLimit-Remaining: 95        # 남은 요청 수
X-RateLimit-Reset: 1702345678    # 리셋 시간 (Unix timestamp)
```

#### 제한 초과 시

제한을 초과하면 HTTP 429 응답을 받게 됩니다:

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 45

{
  "success": false,
  "message": "Too Many Requests. Please try again later.",
  "error": "RATE_LIMIT_EXCEEDED",
  "retry_after": 45,
  "limit": 100,
  "reset_time": 1702345678
}
```

#### 설정

Rate Limiting 설정은 `application/config/rate_limit.php`에서 관리합니다:

```php
// Rate Limiting 활성화/비활성화
$config['rate_limit_enabled'] = true;

// IP 화이트리스트 추가
$config['rate_limit_whitelist'] = [
    '127.0.0.1',
    '::1',
    '192.168.1.100'  // 신뢰된 IP 추가
];

// 인증된 사용자 배수 조정
$config['rate_limit_authenticated_multiplier'] = 2;
```

#### 모니터링

Rate Limit 초과 로그는 `rate_limit_logs` 테이블에 자동으로 기록됩니다:

```sql
SELECT
    ip_address,
    endpoint,
    request_count,
    limit_value,
    created_at
FROM rate_limit_logs
ORDER BY created_at DESC
LIMIT 10;
```


## 개발

### 디버그 모드 활성화

`application/config/config.php`:
```php
$config['debug_mode'] = TRUE;
```

### 로그 확인

```bash
tail -f application/logs/log-$(date +%Y-%m-%d).php
```

### 테스트 데이터 생성

```bash
# UserSeeder 실행
php index.php seeder/UserSeeder
```

## 라이선스

MIT License - 자세한 내용은 [LICENSE](license.txt) 파일을 참조하세요.