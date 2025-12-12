-- Rate Limiting 테이블 마이그레이션
-- 생성일: 2025-12-12
-- API 요청 제한을 위한 테이블 추가

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Rate Limiting 테이블
-- ============================================

-- API 요청 제한 추적 테이블
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP 주소 (IPv6 지원)',
  `endpoint` VARCHAR(100) NOT NULL COMMENT 'API 엔드포인트 (예: /rest/auth/login)',
  `request_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '요청 횟수',
  `window_start` DATETIME NOT NULL COMMENT '제한 윈도우 시작 시간',
  `expires_at` DATETIME NOT NULL COMMENT '만료 시간',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rate_limits_ip_endpoint` (`ip_address`, `endpoint`, `window_start`),
  KEY `idx_rate_limits_ip` (`ip_address`),
  KEY `idx_rate_limits_expires` (`expires_at`),
  KEY `idx_rate_limits_endpoint` (`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API 요청 제한 추적';

-- Rate Limit 초과 로그 테이블
CREATE TABLE IF NOT EXISTS `rate_limit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP 주소',
  `endpoint` VARCHAR(100) NOT NULL COMMENT 'API 엔드포인트',
  `user_id` INT UNSIGNED NULL COMMENT '사용자 ID (인증된 경우)',
  `request_count` INT UNSIGNED NOT NULL COMMENT '초과 시점의 요청 횟수',
  `limit_value` INT UNSIGNED NOT NULL COMMENT '제한 값',
  `user_agent` VARCHAR(500) NULL COMMENT 'User-Agent',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '초과 발생 시간',
  PRIMARY KEY (`id`),
  KEY `idx_rate_limit_logs_ip` (`ip_address`),
  KEY `idx_rate_limit_logs_endpoint` (`endpoint`),
  KEY `idx_rate_limit_logs_user_id` (`user_id`),
  KEY `idx_rate_limit_logs_created_at` (`created_at`),
  CONSTRAINT `fk_rate_limit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rate Limit 초과 로그';

-- ============================================
-- 설정 복원
-- ============================================

SET FOREIGN_KEY_CHECKS = 1;
