-- ============================================================
-- JumpHost 完整数据库结构 + 初始数据
-- 导入前请先创建数据库：CREATE DATABASE `jumphost` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 域名跳转表
CREATE TABLE IF NOT EXISTS `domains` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domain`           VARCHAR(191) NOT NULL COMMENT '绑定域名',
  `protocol`         ENUM('http','https') NOT NULL DEFAULT 'https' COMMENT '协议',
  `target_url`       TEXT         NOT NULL COMMENT '目标URL或JSON多链接',
  `template`         VARCHAR(50)  NOT NULL DEFAULT '302' COMMENT '模板名称',
  `status`           ENUM('active','paused') NOT NULL DEFAULT 'active',
  `delay`            TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `img_url`          TEXT DEFAULT NULL,
  `site_title`       VARCHAR(191) DEFAULT NULL,
  `site_description` VARCHAR(500) DEFAULT NULL,
  `owner_id`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属用户ID',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_domain_protocol` (`domain`, `protocol`),
  INDEX `idx_domain`   (`domain`),
  INDEX `idx_status`   (`status`),
  INDEX `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(64)  NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('super','agent','personal') NOT NULL DEFAULT 'personal' COMMENT '角色',
  `status`     ENUM('active','disabled') NOT NULL DEFAULT 'active' COMMENT '状态',
  `owner_id`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建者ID（0=超级管理员创建）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 配置表
CREATE TABLE IF NOT EXISTS `settings` (
  `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 访问日志表
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domain_id`  INT UNSIGNED NOT NULL,
  `ip`         VARCHAR(45)  NOT NULL,
  `location`   VARCHAR(100) DEFAULT '' COMMENT 'IP归属地',
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_domain_id`  (`domain_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_location`   (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 操作日志表（包含扩展字段：location 和 domain）
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id`   INT UNSIGNED NOT NULL DEFAULT 0,
  `username`   VARCHAR(64)  NOT NULL DEFAULT '',
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
  `location`   VARCHAR(100) DEFAULT '' COMMENT 'IP归属地',
  `domain`     VARCHAR(191) DEFAULT '' COMMENT '访问域名',
  `ua`         VARCHAR(500) DEFAULT NULL,
  `action`     VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_location`   (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 媒体资源表
CREATE TABLE IF NOT EXISTS `media` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename`   VARCHAR(255) NOT NULL COMMENT '文件名',
  `url`        VARCHAR(500) NOT NULL COMMENT '访问URL',
  `lib`        ENUM('random','gallery') NOT NULL DEFAULT 'gallery' COMMENT '图库类型',
  `owner_id`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传者ID',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lib`      (`lib`),
  INDEX `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 默认配置
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
  ('site_name',        'JumpHost'),
  ('site_description', '专业的域名跳转管理系统'),
  ('icp',              '');

-- 默认超级管理员（用户名: admin  密码: password）
-- 安装后请立即修改密码！
INSERT IGNORE INTO `admins` (`username`, `password`, `role`, `owner_id`) VALUES
  ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super', 0);

SET FOREIGN_KEY_CHECKS = 1;
