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
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '계정 생성일',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정보 수정일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_name` (`name`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_remember_token` (`remember_token`)
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