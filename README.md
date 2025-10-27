# CI3Board

CodeIgniter 3.x 기반의 한국어 웹 게시판 시스템입니다. 웹 인터페이스와 REST API를 모두 제공하여 사용자 인증, 게시판 관리, 게시글 작성, 댓글 기능을 지원합니다.

## 주요 기능

- **사용자 관리**: 회원가입, 로그인, 프로필 관리
- **게시판 시스템**: 다중 게시판 지원, 게시글 작성/수정/삭제
- **댓글 기능**: 게시글에 대한 댓글 작성 및 관리
- **REST API**: 모든 주요 기능에 대한 RESTful API 제공
- **보안**: Rate Limiting, CAPTCHA, CSRF 보호, SQL Injection 방지
- **API 문서화**: Swagger UI 및 Redoc 지원

## 기술 스택

- **프레임워크**: CodeIgniter 3.x (MVC 패턴)
- **데이터베이스**: MySQL/MariaDB
- **REST API**: chriskacerguis/codeigniter-restserver
- **디버깅**: PHP DebugBar (개발 환경)
- **API 문서**: OpenAPI 3.0 (Swagger/Redoc)

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

- **Swagger UI**: `http://localhost/swagger`
- **Redoc**: `http://localhost/redoc`
- **OpenAPI 스펙**: `assets/api.json`

### 주요 API 엔드포인트

#### 인증
```bash
# 회원가입
POST /rest/auth/register

# 로그인
POST /rest/auth/login

# 로그아웃
POST /rest/auth/logout
```

#### 게시판
```bash
# 게시판 목록 조회
GET /rest/board

# 게시판 상세 조회
GET /rest/board/{id}
```

#### 게시글
```bash
# 게시글 목록 조회
GET /rest/article?board_id={board_id}

# 게시글 상세 조회
GET /rest/article/{id}

# 게시글 작성
POST /rest/article

# 게시글 수정
PUT /rest/article/{id}

# 게시글 삭제
DELETE /rest/article/{id}
```

#### 댓글
```bash
# 댓글 목록 조회
GET /rest/comment?article_id={article_id}

# 댓글 작성
POST /rest/comment

# 댓글 삭제
DELETE /rest/comment/{id}
```

## 보안 기능

### Rate Limiting
IP별 요청 빈도 제한으로 무차별 대입 공격 방지
- 이메일 체크: 5분간 5회
- 로그인 시도: 15분간 5회

### CAPTCHA
수학 문제 기반 자동화 방지 시스템

### CSRF 보호
모든 폼 제출에 대한 CSRF 토큰 검증

### SQL Injection 방지
CodeIgniter 쿼리 빌더를 통한 안전한 데이터베이스 쿼리

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