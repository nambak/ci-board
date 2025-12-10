-- 프로필 이미지 컬럼 추가 마이그레이션
-- 실행 방법: mysql -u username -p database_name < migrations/004_add_profile_image.sql

-- 사용자 테이블에 프로필 이미지 컬럼 추가
ALTER TABLE `users` ADD COLUMN `profile_image` VARCHAR(255) NULL COMMENT '프로필 이미지 파일명' AFTER `role`;
