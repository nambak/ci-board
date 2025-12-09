-- 대댓글(답글) 기능을 위한 스키마 변경
-- Issue: #116

-- 부모 댓글 ID 컬럼 추가
ALTER TABLE `comments` ADD COLUMN `parent_id` INT UNSIGNED NULL COMMENT '부모 댓글 ID' AFTER `writer_id`;

-- 댓글 깊이 컬럼 추가 (0: 일반 댓글, 1: 1단계 답글, 2: 2단계 답글)
ALTER TABLE `comments` ADD COLUMN `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '댓글 깊이' AFTER `parent_id`;

-- parent_id 인덱스 추가
ALTER TABLE `comments` ADD KEY `idx_comments_parent_id` (`parent_id`)

-- 외래 키 제약조건 추가 (부모 댓글 삭제 시 자식 댓글도 삭제)
ALTER TABLE `comments` ADD CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
