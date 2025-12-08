-- 첨부파일 기능을 위한 테이블 추가
-- 생성일: 2025-12-08
-- 이슈: #115

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