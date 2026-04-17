-- JumpHost Database Schema
-- MySQL 5.7+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: access_logs
-- ----------------------------
DROP TABLE IF EXISTS `access_logs`;
CREATE TABLE `access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP归属地',
  `isp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '运营商',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: admin_logs
-- ----------------------------
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP归属地',
  `domain` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '访问域名',
  `ua` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: admins
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super','agent','personal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'personal' COMMENT '角色',
  `status` enum('active','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '状态',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 默认管理员 (密码: password)
INSERT INTO `admins` (`id`, `username`, `password`, `role`, `status`, `owner_id`) VALUES
(1, 'admin', '$2y$10$MQww//Ujmx8.tvVx3atev.PmC7WYq/QMS5JqgihHmiNhCu5ulnFSK', 'super', 'active', 0);

-- ----------------------------
-- Table: domains
-- ----------------------------
DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '绑定域名',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` enum('http','https') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https',
  `target_url` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目标URL',
  `template` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '302' COMMENT '模板名称',
  `status` enum('active','paused') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `delay` tinyint(3) unsigned NOT NULL DEFAULT '3',
  `img_url` text COLLATE utf8mb4_unicode_ci,
  `site_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_show_link` tinyint(1) DEFAULT '1',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int(11) DEFAULT '0',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属用户ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain_protocol` (`domain`,`protocol`),
  KEY `idx_domain` (`domain`),
  KEY `idx_status` (`status`),
  KEY `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table: ip_cache
-- ----------------------------
DROP TABLE IF EXISTS `ip_cache`;
CREATE TABLE `ip_cache` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `isp` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: media
-- ----------------------------
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '访问URL',
  `lib` enum('random','gallery') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gallery' COMMENT '图库类型',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传者ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lib` (`lib`),
  KEY `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 默认设置
INSERT INTO `settings` (`key`, `value`) VALUES
('site_name', 'JumpHost'),
('site_description', ''),
('icp', '');

SET FOREIGN_KEY_CHECKS = 1;
