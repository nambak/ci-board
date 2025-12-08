-- 이메일 인증 기능을 위한 컬럼 추가
-- 생성일: 2025-12-08
-- 이슈: #114

ALTER TABLE `users`
  ADD COLUMN `email_verified_at` DATETIME NULL COMMENT '이메일 인증 일시' AFTER `remember_token`,
  ADD COLUMN `verification_token` VARCHAR(64) NULL COMMENT '이메일 인증 토큰' AFTER `email_verified_at`,
  ADD KEY `idx_verification_token` (`verification_token`);
