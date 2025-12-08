-- CI3Board 데이터베이스 스키마
-- CodeIgniter 3.x 기반 게시판 시스템
-- 생성일: 2025-10-27

-- 데이터베이스 설정
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 테이블 생성
-- ============================================

-- 사용자 테이블
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT '사용자 이름',
  `email` VARCHAR(100) NOT NULL COMMENT '이메일 주소',
  `password` VARCHAR(255) NOT NULL COMMENT '암호화된 비밀번호',
  `role` VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '사용자 권한 (admin, user)',
  `remember_token` VARCHAR(64) NULL COMMENT 'Remember Me 토큰',
  `email_verified_at` DATETIME NULL COMMENT '이메일 인증 일시',
  `verification_token` VARCHAR(64) NULL COMMENT '이메일 인증 토큰',
  `last_verification_sent_at` DATETIME NULL COMMENT '인증 이메일 마지막 발송 시간',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '계정 생성일',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정보 수정일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_name` (`name`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_remember_token` (`remember_token`),
  KEY `idx_verification_token` (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 정보';

-- 게시판 테이블
CREATE TABLE IF NOT EXISTS `boards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '게시판 이름',
  `description` TEXT NULL COMMENT '게시판 설명',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_boards_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 정보';

-- 게시글 테이블
CREATE TABLE IF NOT EXISTS `articles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id` INT UNSIGNED NOT NULL COMMENT '게시판 ID',
  `user_id` INT UNSIGNED NOT NULL COMMENT '작성자 ID',
  `title` VARCHAR(200) NOT NULL COMMENT '게시글 제목',
  `content` TEXT NOT NULL COMMENT '게시글 내용',
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '조회수',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  PRIMARY KEY (`id`),
  KEY `idx_articles_board_id` (`board_id`),
  KEY `idx_articles_user_id` (`user_id`),
  KEY `idx_articles_created_at` (`created_at`),
  CONSTRAINT `fk_articles_board` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_articles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시글 정보';

-- 댓글 테이블
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL COMMENT '게시글 ID',
  `writer_id` INT UNSIGNED NOT NULL COMMENT '작성자 ID',
  `comment` TEXT NOT NULL COMMENT '댓글 내용',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  PRIMARY KEY (`id`),
  KEY `idx_comments_article_id` (`article_id`),
  KEY `idx_comments_writer_id` (`writer_id`),
  KEY `idx_comments_created_at` (`created_at`),
  CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_writer` FOREIGN KEY (`writer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='댓글 정보';

-- 비밀번호 재설정 테이블
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '사용자 ID',
  `token` VARCHAR(64) NOT NULL COMMENT '재설정 토큰',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  `expires_at` DATETIME NOT NULL COMMENT '만료일',
  `used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '사용 여부 (0: 미사용, 1: 사용됨)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_password_resets_token` (`token`),
  KEY `idx_password_resets_user_id` (`user_id`),
  KEY `idx_password_resets_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='비밀번호 재설정 토큰';

-- 첨부파일 테이블
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL COMMENT '게시글 ID',
  `original_name` VARCHAR(255) NOT NULL COMMENT '원본 파일명',
  `stored_name` VARCHAR(255) NOT NULL COMMENT '저장된 파일명 (암호화)',
  `file_path` VARCHAR(500) NOT NULL COMMENT '파일 저장 경로',
  `file_size` INT UNSIGNED NOT NULL COMMENT '파일 크기 (bytes)',
  `mime_type` VARCHAR(100) NOT NULL COMMENT 'MIME 타입',
  `is_image` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '이미지 여부 (0: 아니오, 1: 예)',
  `thumbnail_path` VARCHAR(500) NULL COMMENT '썸네일 경로 (이미지인 경우)',
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '다운로드 횟수',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '업로드 일시',
  PRIMARY KEY (`id`),
  KEY `idx_attachments_article_id` (`article_id`),
  KEY `idx_attachments_created_at` (`created_at`),
  CONSTRAINT `fk_attachments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='첨부파일 정보';

-- ============================================
-- 초기 데이터 삽입 (선택사항)
-- ============================================

-- 기본 게시판 생성
INSERT INTO `boards` (`name`, `description`) VALUES
('공지사항', '사이트 운영 관련 공지사항을 게시하는 게시판입니다.'),
('자유게시판', '자유롭게 의견을 나누는 공간입니다.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================
-- 설정 복원
-- ============================================

SET FOREIGN_KEY_CHECKS = 1;