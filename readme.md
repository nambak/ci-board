# CI Board - 게시판 시스템

<img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white"/>
<img src="https://img.shields.io/badge/CodeIgniter-EF4223?style=flat-square&logo=codeigniter&logoColor=white"/>
<img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white"/>
<img src="https://img.shields.io/badge/Docker-2496ED?style=flat-square&logo=docker&logoColor=white"/>

![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/nambak/ci-board?utm_source=oss&utm_medium=github&utm_campaign=nambak%2Fci-board&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## 개요

CI Board는 CodeIgniter 프레임워크를 기반으로 개발된 웹 게시판 시스템입니다. 사용자가 게시글을 작성하고 관리할 수 있는 기본적인 게시판 기능과 함께 REST API를 제공하여 다양한 클라이언트에서 활용할 수 있도록 설계되었습니다.

## 사용 기술

### 개발 언어
- **PHP 7.4+**: 서버사이드 개발 언어

### 프레임워크 및 라이브러리
- **CodeIgniter 3.x**: 메인 웹 프레임워크
- **CodeIgniter REST Server**: REST API 구현을 위한 라이브러리
- **Bootstrap**: 프론트엔드 UI 프레임워크
- **jQuery**: JavaScript 라이브러리
- **Bootstrap Table**: 테이블 컴포넌트

### 데이터베이스
- **MySQL/MariaDB**: 데이터 저장소

### 기타 도구
- **Docker**: 컨테이너화 및 배포
- **Apache**: 웹 서버
- **Composer**: PHP 의존성 관리
- **Swagger/Redoc**: API 문서화

## 개발 의도

이 프로젝트는 다음과 같은 목적으로 개발되었습니다:

1. **기본 게시판 기능 제공**: 게시글 작성, 조회, 수정, 삭제 등 기본적인 CRUD 기능
2. **REST API 제공**: 웹 인터페이스뿐만 아니라 모바일 앱이나 다른 시스템에서도 활용 가능
3. **확장 가능한 구조**: CodeIgniter의 MVC 패턴을 활용한 유지보수 용이한 코드 구조
4. **Docker 지원**: 쉬운 배포 및 개발 환경 구성
5. **API 문서화**: Swagger를 통한 명확한 API 문서 제공

## 주요 기능

- 게시판 목록 및 상세 조회
- 게시글 작성, 수정, 삭제
- 댓글 시스템
- REST API 엔드포인트
- API 문서 (Swagger/Redoc)
- 반응형 웹 인터페이스

## 설치 및 실행

### Docker를 사용한 실행

```bash
# 저장소 클론
git clone https://github.com/nambak/ci-board.git
cd ci-board

# Docker 컨테이너 빌드 및 실행
docker build -t ci-board .
docker run -p 80:80 ci-board
```

### 로컬 환경에서 실행

```bash
# 의존성 설치
composer install

# 웹 서버 설정 (Apache/Nginx)
# DocumentRoot를 프로젝트 루트 디렉토리로 설정

# 데이터베이스 설정
# application/config/database.php 파일에서 데이터베이스 정보 설정

# 마이그레이션 실행 (필요한 경우)
# migration.sql 파일을 데이터베이스에 적용
```

## API 문서

- **Swagger UI**: `/swagger` 엔드포인트에서 확인 가능
- **Redoc**: `/redoc` 엔드포인트에서 확인 가능

## 프로젝트 구조

```
ci-board/
├── application/          # CodeIgniter 애플리케이션 코드
│   ├── controllers/      # 컨트롤러
│   │   └── rest/        # REST API 컨트롤러
│   ├── models/          # 모델
│   ├── views/           # 뷰 템플릿
│   └── config/          # 설정 파일
├── system/              # CodeIgniter 시스템 파일
├── assets/              # 정적 자원 (CSS, JS, 이미지)
├── Dockerfile           # Docker 설정
├── composer.json        # PHP 의존성 정의
└── migration.sql        # 데이터베이스 마이그레이션
```

## 라이센스

이 프로젝트는 MIT 라이센스 하에 배포됩니다.
