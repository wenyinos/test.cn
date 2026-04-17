-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: host_78rg_cc
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_logs`
--

DROP TABLE IF EXISTS `access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP归属地',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_location` (`location`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_logs`
--

LOCK TABLES `access_logs` WRITE;
/*!40000 ALTER TABLE `access_logs` DISABLE KEYS */;
INSERT INTO `access_logs` VALUES (3,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 18:50:33'),(5,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 19:02:40'),(6,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 19:03:07'),(7,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 19:03:41'),(9,4,'125.94.144.102','广东 深圳','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-16 19:16:58'),(10,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 20:27:03'),(11,4,'170.106.180.246','加州 圣克拉拉','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-16 20:58:48'),(12,4,'223.104.155.79','江苏 苏州市','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36','2026-04-16 21:14:17'),(14,3,'223.104.155.79','江苏 苏州市','Mozilla/5.0 (Android 15; Mobile; rv:149.0) Gecko/149.0 Firefox/149.0','2026-04-16 21:15:07'),(16,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 21:45:12'),(18,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36','2026-04-16 23:21:23'),(19,4,'109.199.118.129','大東部大區 Lauterbourg','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36 Edg/91.0.864.54','2026-04-17 01:10:17'),(20,4,'43.153.36.110','加州 圣克拉拉','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-17 02:54:29'),(21,4,'58.17.6.9','江西 南昌','Mozilla/5.0 (Linux; Android 14; V2284A Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/86.0.4240.185 Mobile Safari/537.36','2026-04-17 03:24:09'),(22,4,'43.130.91.95','弗吉尼亚州 阿什本','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-17 06:06:07'),(23,4,'182.44.8.254','山东 济南市','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-17 08:02:26'),(24,4,'121.237.36.31','江苏 南京','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.27 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/601.1.27','2026-04-17 08:30:31'),(25,4,'121.237.36.28','江苏 南京','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.27 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/601.1.27','2026-04-17 08:32:20'),(26,4,'121.237.36.29','江苏 南京','Mozilla/5.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1','2026-04-17 08:36:38'),(27,4,'121.237.36.31','江苏 南京','Dalvik/2.1.0 (Linux; U; Android 9.0; ZTE BA520 Build/MRA58K)','2026-04-17 08:38:32'),(28,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-17 08:54:04'),(29,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-17 08:54:15'),(30,4,'43.130.72.40','弗吉尼亚州 阿什本','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-17 09:10:07'),(31,4,'124.156.226.179','东京都 东京','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','2026-04-17 09:38:41'),(32,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:41:42'),(33,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:41:59'),(34,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:44:38'),(35,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:44:47'),(36,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:45:27'),(37,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:46:03'),(38,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:46:11'),(39,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:46:31'),(40,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:48:44'),(41,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:52:07'),(42,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:52:27'),(43,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:54:12'),(44,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 09:57:14'),(45,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:00:43'),(46,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:04:08'),(47,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:04:23'),(48,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:09:49'),(49,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:09:56'),(50,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:10:16'),(51,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:10:34'),(52,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:11:01'),(53,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:11:43'),(54,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:11:54'),(55,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:13:00'),(56,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:13:06'),(57,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:13:39'),(58,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:13:43'),(59,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:14:05'),(60,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:14:40'),(61,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:14:52'),(62,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:16:03'),(63,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:16:15'),(64,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:29:20'),(65,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:29:34'),(66,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:29:39'),(67,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:29:51'),(68,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:30:02'),(69,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:34:39'),(70,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:38:51'),(71,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:39:21'),(72,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:39:50'),(73,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:39:55'),(74,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:42:27'),(75,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:42:35'),(76,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:42:49'),(77,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:42:57'),(78,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:43:15'),(79,3,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:43:39'),(80,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:43:44'),(81,2,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:43:54'),(82,4,'223.66.156.207','江苏 南京','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 10:47:11');
/*!40000 ALTER TABLE `access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','登录成功','2026-04-16 00:14:38'),(2,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','新增域名 domain=a.spcs2.com','2026-04-16 00:15:09'),(3,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','删除域名 id=1','2026-04-16 00:15:22'),(4,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','新增域名 domain=a..78rg.cc','2026-04-16 00:15:33'),(5,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','编辑域名 id=2 domain=a.78rg.cc','2026-04-16 00:15:44'),(6,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','编辑域名 id=2 domain=a.78rg.cc','2026-04-16 00:16:03'),(7,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','新增域名 domain=b.78rg.cc','2026-04-16 00:19:45'),(8,1,'admin','103.196.26.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','登录成功','2026-04-16 00:23:01'),(9,1,'admin','111.19.68.91','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','登录成功','2026-04-16 00:25:35'),(10,1,'admin','103.196.26.211','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','新增域名 domain=234.78rg.cc','2026-04-16 00:25:39'),(11,1,'admin','103.196.26.211','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc','2026-04-16 00:26:06'),(12,1,'admin','113.235.37.126','','host.78rg.cc','Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.71(0x18004722) NetType/WIFI Language/zh_CN','登录成功','2026-04-16 11:08:09'),(13,1,'admin','103.223.122.151','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','登录成功','2026-04-16 12:21:48'),(14,0,'','223.104.3.175','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 NetType/WIFI MicroMessenger/7.0.20.1781(0x6700143B) WindowsWechat(0x63090a13) UnifiedPCWindowsWechat(0xf254173b) XWEB/19027 Flue','登录失败 username=dmw31ova','2026-04-16 12:25:21'),(15,1,'admin','223.104.3.175','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 NetType/WIFI MicroMessenger/7.0.20.1781(0x6700143B) WindowsWechat(0x63090a13) UnifiedPCWindowsWechat(0xf254173b) XWEB/19027 Flue','登录成功','2026-04-16 12:25:37'),(16,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','登录成功','2026-04-16 12:39:17'),(17,1,'admin','185.36.195.87','','112.213.111.107','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','登录成功','2026-04-16 13:19:32'),(18,1,'admin','120.230.198.186','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 QuarkPC/6.6.5.788','登录成功','2026-04-16 13:45:20'),(19,1,'admin','154.222.29.204','','112.213.111.107','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0','登录成功','2026-04-16 14:16:55'),(20,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','登录成功','2026-04-16 16:42:52'),(21,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空了所有访问统计数据','2026-04-16 18:22:42'),(22,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空了所有访问统计数据','2026-04-16 18:23:24'),(23,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=4','2026-04-16 18:28:00'),(24,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=3','2026-04-16 18:28:03'),(25,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=4','2026-04-16 18:28:34'),(26,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空了所有访问统计数据','2026-04-16 18:29:46'),(27,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','新增域名 domain=http://c.78rg.cc','2026-04-16 18:47:39'),(28,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=http://78rg.cc','2026-04-16 18:48:05'),(29,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','删除域名 id=5','2026-04-16 18:48:10'),(30,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=http://b.78rg.cc','2026-04-16 18:48:47'),(31,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc','2026-04-16 18:49:09'),(32,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=http://b.78rg.cc','2026-04-16 18:51:05'),(33,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=http://a.78rg.cc','2026-04-16 18:52:10'),(34,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=4','2026-04-16 18:55:27'),(35,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc','2026-04-16 19:02:38'),(36,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc','2026-04-16 19:02:50'),(37,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc','2026-04-16 19:03:01'),(38,1,'admin','223.66.156.207','','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=2','2026-04-16 20:54:08'),(39,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-16 21:45:10'),(40,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-16 21:45:21'),(41,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-16 21:45:35'),(42,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=2','2026-04-16 22:33:46'),(43,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','登录成功','2026-04-16 22:51:06'),(44,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','清空域名访问数据 id=2','2026-04-16 22:51:27'),(45,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','新增域名 domain=c.78rg.cc protocol=https','2026-04-16 22:51:54'),(46,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','删除域名 id=6','2026-04-16 22:52:18'),(47,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36','登录成功','2026-04-16 23:22:02'),(48,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','登录成功','2026-04-17 08:52:50'),(49,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','登录成功','2026-04-17 09:00:29'),(50,0,'','113.235.37.126','辽宁 大连','host.78rg.cc','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1','登录失败 username=admin','2026-04-17 09:24:47'),(51,0,'','113.235.37.126','辽宁 大连','host.78rg.cc','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1','登录失败 username=admin','2026-04-17 09:25:21'),(52,0,'','113.235.37.126','辽宁 大连','host.78rg.cc','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1','登录失败 username=admin','2026-04-17 09:31:14'),(53,0,'','113.235.37.126','辽宁 大连','host.78rg.cc','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1','登录失败 username=admin','2026-04-17 09:31:28'),(54,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 09:41:34'),(55,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 09:41:57'),(56,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 09:45:23'),(57,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 09:45:58'),(58,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 09:46:28'),(59,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 09:48:39'),(60,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 09:52:04'),(61,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:04:03'),(62,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','新增域名 domain=b.78rg.cc protocol=https','2026-04-17 10:08:46'),(63,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=7 domain=b.78rg.cc protocol=https','2026-04-17 10:09:01'),(64,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','删除域名 id=7','2026-04-17 10:09:31'),(65,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:10:10'),(66,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:10:31'),(67,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','切换域名状态 id=3 status=active','2026-04-17 10:10:46'),(68,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','切换域名状态 id=3 status=paused','2026-04-17 10:10:51'),(69,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:10:57'),(70,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:11:39'),(71,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','切换域名状态 id=3 status=active','2026-04-17 10:11:52'),(72,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','切换域名状态 id=3 status=paused','2026-04-17 10:12:57'),(73,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','切换域名状态 id=3 status=active','2026-04-17 10:13:05'),(74,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:13:33'),(75,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:14:03'),(76,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:14:37'),(77,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:16:00'),(78,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 10:29:32'),(79,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:29:49'),(80,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:30:00'),(81,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:39:16'),(82,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 10:39:40'),(83,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:39:48'),(84,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:40:09'),(85,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 10:40:15'),(86,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=3 domain=b.78rg.cc protocol=http','2026-04-17 10:42:47'),(87,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=2 domain=a.78rg.cc protocol=http','2026-04-17 10:43:12'),(88,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 10:50:48'),(89,1,'admin','223.66.156.207','江苏 南京','host.78rg.cc','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','编辑域名 id=4 domain=78rg.cc protocol=http','2026-04-17 10:51:12');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super','agent','personal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'personal' COMMENT '角色',
  `status` enum('active','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '状态',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID（0=超级管理员创建）',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$MQww//Ujmx8.tvVx3atev.PmC7WYq/QMS5JqgihHmiNhCu5ulnFSK','super','active',0,'2026-04-16 00:14:24');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '绑定域名',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` enum('http','https') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https',
  `target_url` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目标URL或JSON多链接',
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domains`
--

LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
INSERT INTO `domains` VALUES (2,'a.78rg.cc','','http','https://ip138.com','click_delay','active',7,'http://host.78rg.cc/uploads/gallery/20260417094558_eb4aa6e1.jpg','测试','1111',1,NULL,0,1,'2026-04-16 00:15:33','2026-04-17 10:43:12'),(3,'b.78rg.cc','new','http','https://cn.bing.com','delay','active',6,'http://host.78rg.cc/uploads/gallery/20260417094558_eb4aa6e1.jpg','测试','ggg',1,NULL,0,1,'2026-04-16 00:19:45','2026-04-17 10:42:46'),(4,'78rg.cc','main','http','https://www.baidu.com/','img','active',3,'http://host.78rg.cc/uploads/gallery/20260417094558_eb4aa6e1.jpg','ffuck','1111',1,'1',3,1,'2026-04-16 00:25:39','2026-04-17 10:51:11');
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES (1,'20260417094558_eb4aa6e1.jpg','/uploads/gallery/20260417094558_eb4aa6e1.jpg','gallery',1,'2026-04-17 09:45:58');
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('icp','*'),('site_description','****'),('site_name','test');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-17 10:54:21
