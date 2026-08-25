-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: custodian_db
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=544 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'Database Backup Created','2026-03-22 18:44:03',NULL),(2,'Database Backup Created','2026-03-22 19:49:16',208),(3,'System Backup Created (Tar): system_2026-03-28_07-28-44.zip','2026-03-28 14:28:46',208),(4,'System Backup Created: system_2026-03-28_07-30-54.zip','2026-03-28 14:30:55',208),(5,'System Backup Created: system_2026-03-28_07-34-04.zip','2026-03-28 14:34:05',208),(6,'System Backup Created: system_2026-03-28_07-38-12.zip','2026-03-28 14:38:18',208),(7,'System Backup Created: system_2026-03-28_07-41-29.zip','2026-03-28 14:41:41',208),(8,'System Backup Created: system_2026-03-28_07-44-54.zip','2026-03-28 14:45:05',208),(9,'System Backup Created: system_2026-03-28_07-47-28.zip','2026-03-28 14:47:38',208),(10,'System Zip Backup Created: system_2026-03-28_08-59-13.zip','2026-03-28 15:59:26',208),(11,'Restored Maintenance Report: MC-2026-0001','2026-03-28 16:19:23',208),(12,'User Logged Out','2026-03-28 16:19:44',208),(13,'User Logged In','2026-03-28 16:19:57',208),(14,'Added New Office: College President','2026-03-28 16:30:29',208),(15,'Admin Logged Out','2026-03-28 16:30:45',208),(16,'Admin Logged In','2026-03-28 16:31:05',208),(17,'Admin Logged In','2026-03-28 16:39:46',208),(18,'Added New Property: com (            )','2026-03-28 18:42:50',208),(19,'Added New Product: bondpaper','2026-03-28 18:51:12',208),(20,'Updated Office Details: College President','2026-03-28 19:25:07',208),(21,'Updated Office Details: College President','2026-03-28 19:25:32',208),(22,'Deleted Property: com (            )','2026-03-28 20:12:48',208),(23,'Admin Logged Out','2026-03-28 20:56:59',208),(24,'Admin Logged In','2026-03-29 10:07:47',208),(25,'Database SQL Backup Created: db_backup_2026-03-29_04-22-14.sql','2026-03-29 10:22:15',208),(26,'System Zip Backup Created: system_backup_2026-03-29_04-26-28.zip','2026-03-29 10:27:03',208),(27,'System Zip Backup Created: system_backup_2026-03-29_05-17-14.zip','2026-03-29 11:18:25',208),(28,'Admin Logged In','2026-03-29 11:52:53',208),(29,'System Zip Backup Created: system_backup_2026-03-29_05-57-18.zip','2026-03-29 11:57:50',208),(30,'Database SQL Backup Created: db_backup_2026-03-29_05-58-07.sql','2026-03-29 11:58:08',208),(31,'System Zip Backup Created: system_backup_2026-03-29_06-13-38.zip','2026-03-29 12:14:15',208),(32,'System Zip Backup Created: system_backup_2026-03-29_06-39-38.zip','2026-03-29 12:40:23',208),(33,'System Zip Backup Created: system_backup_2026-03-29_06-40-12.zip','2026-03-29 12:40:48',208),(34,'System Zip Backup Created: system_backup_2026-03-29_06-44-49.zip','2026-03-29 12:45:35',208),(35,'System Zip Backup Created: system_backup_2026-03-29_06-45-20.zip','2026-03-29 12:45:56',208),(36,'System Zip Backup Created: system_backup_2026-03-29_06-47-31.zip','2026-03-29 12:48:11',208),(37,'System Zip Backup Created: system_backup_2026-03-29_06-47-58.zip','2026-03-29 12:48:29',208),(38,'System Zip Backup Created: system_backup_2026-03-29_06-49-27.zip','2026-03-29 12:50:03',208),(39,'System Zip Backup Created: system_backup_2026-03-29_06-49-51.zip','2026-03-29 12:50:22',208),(40,'Database SQL Backup Created: db_backup_2026-03-29_06-51-29.sql','2026-03-29 12:51:29',208),(41,'Database SQL Backup Created: db_backup_2026-03-29_06-53-44.sql','2026-03-29 12:53:45',208),(42,'Database SQL Backup Created: db_backup_2026-03-29_06-57-23.sql','2026-03-29 12:57:23',208),(43,'Database SQL Backup Created: db_backup_2026-03-29_07-02-28.sql','2026-03-29 13:02:28',208),(44,'System Zip Backup Created: system_backup_2026-03-29_07-02-39.zip','2026-03-29 13:03:23',208),(45,'System Zip Backup Created: system_backup_2026-03-29_07-03-09.zip','2026-03-29 13:03:41',208),(46,'Database SQL Backup Created: db_backup_2026-03-29_07-07-07.sql','2026-03-29 13:07:08',208),(47,'Database SQL Backup Created: db_backup_2026-03-29_07-07-08.sql','2026-03-29 13:07:09',208),(48,'Database SQL Backup Created: db_backup_2026-03-29_07-11-57.sql','2026-03-29 13:11:58',208),(49,'Database SQL Backup Created: db_backup_2026-03-29_07-11-58.sql','2026-03-29 13:11:58',208),(50,'System Zip Backup Created: system_backup_2026-03-29_07-12-03.zip','2026-03-29 13:12:15',208),(51,'System Zip Backup Created: system_backup_2026-03-29_07-12-15.zip','2026-03-29 13:12:26',208),(52,'System Zip Backup Created: system_backup_2026-03-29_07-14-38.zip','2026-03-29 13:14:50',208),(53,'System Zip Backup Created: system_backup_2026-03-29_07-14-50.zip','2026-03-29 13:15:02',208),(54,'System Zip Backup Created: system_backup_2026-03-29_07-16-04.zip','2026-03-29 13:16:16',208),(55,'System Zip Backup Created: system_backup_2026-03-29_07-16-16.zip','2026-03-29 13:16:28',208),(56,'Database SQL Backup Created: db_backup_2026-03-29_07-22-22.sql','2026-03-29 13:22:23',208),(57,'Database SQL Backup Created: db_backup_2026-03-29_07-22-23.sql','2026-03-29 13:22:23',208),(58,'System Zip Backup Created: system_backup_2026-03-29_07-22-27.zip','2026-03-29 13:22:39',208),(59,'System Zip Backup Created: system_backup_2026-03-29_07-22-39.zip','2026-03-29 13:22:51',208),(60,'Database SQL Backup Created: db_backup_2026-03-29_07-22-59.sql','2026-03-29 13:22:59',208),(61,'Database SQL Backup Created: db_backup_2026-03-29_07-23-00.sql','2026-03-29 13:23:00',208),(62,'System Zip Backup Created: system_backup_2026-03-29_07-23-42.zip','2026-03-29 13:23:54',208),(63,'System Zip Backup Created: system_backup_2026-03-29_07-23-55.zip','2026-03-29 13:24:07',208),(64,'Full System Archive Created: full_archive_2026-03-29_07-28-33.zip','2026-03-29 13:28:47',208),(65,'Full System Archive Created: full_archive_2026-03-29_07-31-25.zip','2026-03-29 13:31:39',208),(66,'Registered new user: Rose Anne Somintac (Intern)','2026-03-29 13:38:05',208),(67,'Deleted user: Rose Anne Somintac (ID: 212)','2026-03-29 13:38:20',208),(68,'Admin Logged Out','2026-03-29 13:38:24',208),(69,'Intern Logged In','2026-03-29 13:38:33',211),(70,'Intern Logged Out','2026-03-29 13:38:54',211),(71,'Admin Logged In','2026-03-29 13:39:01',208),(72,'Updated user details: Rose Anne Somintac (211)','2026-03-29 13:39:14',208),(73,'Admin Logged Out','2026-03-29 13:39:17',208),(74,'Admin Logged In','2026-03-29 13:39:23',211),(75,'Admin Logged Out','2026-03-29 13:39:35',211),(76,'Admin Logged In','2026-03-29 13:39:46',208),(77,'Admin Logged Out','2026-03-29 13:50:51',208),(78,'Admin Logged In','2026-03-29 13:51:00',208),(79,'Admin Logged Out','2026-03-29 13:52:36',208),(80,'Admin Logged In','2026-03-29 13:52:59',208),(81,'Updated user details: Lea H (200)','2026-03-29 13:53:18',208),(82,'Updated user details: Lea H (200)','2026-03-29 13:53:24',208),(83,'Updated user details: Lea H (200)','2026-03-29 13:56:52',208),(84,'Updated user details: Lea H (200)','2026-03-29 13:58:32',208),(85,'Updated user details: Rose Anne Somintac (211)','2026-03-29 13:58:45',208),(86,'Updated user details: Rose Anne Somintac (211)','2026-03-29 13:58:49',208),(87,'Updated user details: Rose Anne Somintac (211)','2026-03-29 13:59:15',208),(88,'Updated user details: Kian Hilario (210)','2026-03-29 13:59:31',208),(89,'Updated user details: Kian Hilario (210)','2026-03-29 14:00:14',208),(90,'Updated Office Details: Admins','2026-03-29 14:00:27',208),(91,'Updated Office Details: Admin','2026-03-29 14:00:36',208),(92,'Updated user details: Lea Hilario (208)','2026-03-29 14:03:19',208),(93,'Updated user details: Lea Hilario (208)','2026-03-29 14:05:13',208),(94,'Updated user details: Lea Hilario (208)','2026-03-29 14:07:08',208),(95,'Admin Logged Out','2026-03-29 15:36:55',208),(96,'Admin Logged In','2026-03-29 20:11:59',208),(97,'Admin Logged Out','2026-03-29 20:12:04',208),(98,'Admin Logged In','2026-03-29 20:12:12',208),(99,'Updated Office Details: Admins','2026-03-29 21:42:17',208),(100,'Updated Office Details: Admin','2026-03-29 21:42:21',208),(101,'Updated Personnel: Jomar Gonzaga','2026-03-29 21:50:07',208),(102,'Updated Personnel: Jomar Gonzaga','2026-03-29 21:51:55',208),(103,'Updated Personnel: Jomar Gonzaga','2026-03-29 21:52:08',208),(104,'Updated Personnel: Jomar Gonzaga','2026-03-29 21:52:20',208),(105,'Updated Personnel: Jomar Gonzaga','2026-03-29 21:56:38',208),(106,'Database SQL Backup Created: db_backup_2026-03-29_16-13-48.sql','2026-03-29 22:13:49',208),(107,'Database SQL Backup Created: db_backup_2026-03-29_16-13-49.sql','2026-03-29 22:13:50',208),(108,'Admin Logged Out','2026-03-29 22:56:03',208),(109,'Student Assistant Logged In','2026-03-29 22:56:23',210),(110,'Student Assistant Logged Out','2026-03-29 22:56:48',210),(111,'Admin Logged In','2026-03-30 07:47:39',208),(112,'Admin Logged In','2026-03-30 15:52:17',208),(113,'Database SQL Backup Created: db_backup_2026-03-30_10-14-50.sql','2026-03-30 16:14:51',208),(114,'Database SQL Backup Created: db_backup_2026-03-30_10-14-51.sql','2026-03-30 16:14:51',208),(115,'Admin Logged In','2026-03-30 16:42:51',208),(116,'Updated user details: Lea H (200)','2026-03-30 16:56:22',208),(117,'Updated user details: Lea H (200)','2026-03-30 16:57:20',208),(118,'Updated user details: Lea H (200)','2026-03-30 16:59:05',208),(119,'Updated user details: Lea H (200)','2026-03-30 17:01:12',208),(120,'Updated user details: Lea H (200)','2026-03-30 17:01:59',208),(121,'Updated user details: Lea H (200)','2026-03-30 17:09:38',208),(122,'Updated user details: Lea H (200)','2026-03-30 17:11:43',208),(123,'Updated user details: Lea H (200)','2026-03-30 17:14:35',208),(124,'Updated user details: Lea H (200)','2026-03-30 17:19:15',208),(125,'Updated user details: Lea H (200)','2026-03-30 17:21:07',208),(126,'Updated user details: Lea H (200)','2026-03-30 17:27:08',208),(127,'Updated user details: Lea H (200)','2026-03-30 17:27:15',208),(128,'Updated user details: Lea H (200)','2026-03-30 17:51:54',208),(129,'Updated user details: Lea H (200)','2026-03-30 17:52:01',208),(130,'Admin Logged In','2026-03-30 19:04:06',208),(131,'Added New Product: ballpen','2026-03-30 19:07:22',208),(132,'Added New Product: ballpen','2026-03-30 19:08:18',208),(133,'Added New Property: Aircon (SN-20260330-0001)','2026-03-30 19:11:15',208),(134,'Deleted Property: Aircon (SN-20260330-0001)','2026-03-30 19:11:27',208),(135,'Added New Product: bondpaper','2026-03-30 19:14:54',208),(136,'Added New Product: bondpaper','2026-03-30 19:22:28',208),(137,'Deleted Item: Aircon','2026-03-30 19:25:15',208),(138,'Database SQL Backup Created: db_backup_2026-03-30_13-35-39.sql','2026-03-30 19:35:40',208),(139,'Database SQL Backup Created: db_backup_2026-03-30_13-35-40.sql','2026-03-30 19:35:41',208),(140,'System Zip Backup Created: system_backup_2026-03-30_13-35-45.zip','2026-03-30 19:36:14',208),(141,'System Zip Backup Created: system_backup_2026-03-30_13-36-15.zip','2026-03-30 19:36:27',208),(142,'Admin Logged Out','2026-03-30 19:37:02',208),(143,'Admin Logged In','2026-03-30 19:38:41',208),(144,'Admin Logged Out','2026-03-30 19:38:48',208),(145,'Admin Logged In','2026-03-30 19:52:36',208),(146,'Admin Logged Out','2026-03-30 19:52:53',208),(147,'Admin Logged In','2026-03-31 18:22:37',208),(148,'Deleted Property: table (SN-20260325-0007)','2026-03-31 18:52:55',208),(149,'Deleted Property: Aircon (SN-20260310-0001)','2026-03-31 18:53:02',208),(150,'Deleted Property: laptop (SN-20260311-0001)','2026-03-31 18:53:09',208),(151,'Deleted Property: PC ()','2026-03-31 18:53:16',208),(152,'Added New Product: bondpaper','2026-03-31 18:54:54',208),(153,'Archived Property: laptop (SN-20260310-0002)','2026-03-31 18:57:59',208),(154,'Permanently Deleted Property: laptop (SN-20260310-0002)','2026-03-31 18:58:07',208),(155,'Database SQL Backup Created: db_backup_2026-03-31_12-59-15.sql','2026-03-31 18:59:16',208),(156,'Database SQL Backup Created: db_backup_2026-03-31_12-59-16.sql','2026-03-31 18:59:17',208),(157,'System Zip Backup Created: system_backup_2026-03-31_12-59-23.zip','2026-03-31 19:00:49',208),(158,'Archived Property: laptop ()','2026-03-31 19:14:32',208),(159,'Archived Property: table (SN-20260310-0003)','2026-03-31 19:14:39',208),(160,'Archived Property: chair (SN-20260310-0004)','2026-03-31 19:14:44',208),(161,'Restored Property: chair','2026-03-31 19:14:57',208),(162,'Archived Product: bondpaper','2026-03-31 19:16:54',208),(163,'Archived Product: bondpaper','2026-03-31 19:22:10',208),(164,'System Zip Backup Created: system_backup_2026-03-31_13-22-15.zip','2026-03-31 19:22:47',208),(165,'System Zip Backup Created: system_backup_2026-03-31_13-22-47.zip','2026-03-31 19:23:18',208),(166,'Added New Property: Aircon (SN-20260331-0001)','2026-03-31 19:43:09',208),(167,'Updated Property: Aircons (SN-20260331-0001)','2026-03-31 19:44:29',208),(168,'Archived Property: chair (SN-20260310-0004)','2026-03-31 19:46:16',208),(169,'Admin Logged Out','2026-03-31 20:07:31',208),(170,'Admin Logged In','2026-03-31 20:09:03',208),(171,'Updated user details: Rose Anne Somintac (211)','2026-03-31 20:18:24',208),(172,'Updated user details: Rose Anne Somintac (211)','2026-03-31 20:18:29',208),(173,'Updated user details: Rose Anne Somintac (211)','2026-03-31 20:18:34',208),(174,'Admin Logged Out','2026-03-31 20:19:02',208),(175,'Admin Logged In','2026-03-31 20:21:32',208),(176,'Database SQL Backup Created: db_backup_2026-03-31_14-21-39.sql','2026-03-31 20:21:40',208),(177,'Database SQL Backup Created: db_backup_2026-03-31_14-21-40.sql','2026-03-31 20:21:41',208),(178,'System Zip Backup Created: system_backup_2026-03-31_14-21-46.zip','2026-03-31 20:22:01',208),(179,'System Zip Backup Created: system_backup_2026-03-31_14-22-01.zip','2026-03-31 20:22:16',208),(180,'Admin Logged Out','2026-03-31 20:23:03',208),(181,'Admin Logged In','2026-04-01 09:56:25',208),(182,'Registered new user: Charl Bilson Flores (Admin)','2026-04-01 10:20:58',208),(183,'Admin Logged Out','2026-04-01 10:21:06',208),(184,'Admin Logged In','2026-04-01 10:21:15',213),(185,'Admin Logged Out','2026-04-01 10:21:37',213),(186,'Admin Logged In','2026-04-01 10:23:59',208),(187,'Admin Logged Out','2026-04-01 10:29:48',208),(188,'Admin Logged In','2026-04-01 10:30:17',208),(189,'Registered new user: Amore Flores (Admin)','2026-04-01 10:35:29',208),(190,'Admin Logged Out','2026-04-01 10:35:34',208),(191,'Admin Logged In via Recovery Question','2026-04-01 10:35:55',214),(192,'Admin Logged Out','2026-04-01 10:36:16',214),(193,'Admin Logged In','2026-04-01 10:36:23',214),(194,'Admin Logged Out','2026-04-01 10:36:30',214),(195,'Student Assistant Logged In','2026-04-01 10:36:39',210),(196,'Student Assistant Logged Out','2026-04-01 10:36:44',210),(197,'Admin Logged In','2026-04-01 10:39:28',208),(198,'Admin Logged Out','2026-04-01 10:42:46',208),(199,'Admin Logged In','2026-04-01 10:43:43',208),(200,'Approved password reset for User ID: 210. New password: g$F*QhXN','2026-04-01 10:44:04',208),(201,'Admin Logged Out','2026-04-01 10:44:15',208),(202,'Admin Logged In','2026-04-01 10:44:45',208),(203,'Approved password reset for User ID: 210. New password: $mynO8NK','2026-04-01 10:46:06',208),(204,'Admin Logged Out','2026-04-01 10:46:10',208),(205,'Student Assistant Logged In','2026-04-01 10:46:16',210),(206,'Student Assistant Logged Out','2026-04-01 10:50:03',210),(207,'Admin Logged In','2026-04-01 10:50:12',208),(208,'Approved password reset for User ID: 210. New password: 5EF5fUgK','2026-04-01 10:53:52',208),(209,'Student Assistant Logged In','2026-04-01 10:54:06',210),(210,'Student Assistant Logged Out','2026-04-01 10:54:41',210),(211,'Student Assistant Logged In','2026-04-01 10:54:46',210),(212,'Admin Logged Out','2026-04-01 10:56:35',208),(213,'Admin Logged In via Recovery Question','2026-04-01 10:57:46',214),(214,'Admin Logged Out','2026-04-01 10:57:55',214),(215,'Student Assistant Logged Out','2026-04-01 10:58:22',210),(216,'Admin Logged In','2026-04-01 10:59:43',208),(217,'Admin Logged Out','2026-04-01 10:59:55',208),(218,'Admin Logged In','2026-04-01 11:00:21',208),(219,'Approved password reset for User ID: 210. New password: 3A*DMPo^','2026-04-01 11:00:52',208),(220,'Approved password reset for User ID: 210. New password: 3A*DMPo^','2026-04-01 11:00:56',208),(221,'Admin Logged Out','2026-04-01 11:00:57',208),(222,'Student Assistant Logged In','2026-04-01 11:01:05',210),(223,'Student Assistant Logged Out','2026-04-01 11:01:20',210),(224,'Admin Logged In via Recovery Question','2026-04-01 11:01:46',214),(225,'Admin Logged Out','2026-04-01 11:05:27',214),(226,'Admin Reset Password via Recovery Question','2026-04-01 11:05:52',214),(227,'Admin Reset Password via Recovery Question','2026-04-01 11:07:34',214),(228,'Admin Reset Password via Recovery Question','2026-04-01 11:08:38',214),(229,'Admin Reset Password via Recovery Question','2026-04-01 11:10:49',214),(230,'Admin Reset Password via Recovery Question','2026-04-01 11:11:11',214),(231,'Admin Reset Password via Recovery Question','2026-04-01 11:11:29',214),(232,'Admin Reset Password via Recovery Question','2026-04-01 11:14:47',214),(233,'Admin Logged In','2026-04-01 11:15:21',214),(234,'Admin Logged Out','2026-04-01 11:16:13',214),(235,'Admin Reset Password via Recovery Question','2026-04-01 11:18:26',214),(236,'Admin Logged In','2026-04-01 11:18:39',214),(237,'Admin Logged Out','2026-04-01 11:19:11',214),(238,'Admin Reset Password via Recovery Question','2026-04-01 11:19:24',214),(239,'Admin Reset Password via Recovery Question','2026-04-01 11:23:42',214),(240,'Admin Reset Password via Recovery Question','2026-04-01 11:24:17',214),(241,'Admin Reset Password via Recovery Question','2026-04-01 11:26:12',214),(242,'Admin Reset Password via Recovery Question','2026-04-01 11:28:33',214),(243,'Admin Logged In','2026-04-01 11:28:44',214),(244,'Admin Logged Out','2026-04-01 11:29:17',214),(245,'Admin Logged In','2026-04-01 11:33:22',214),(246,'Admin Logged Out','2026-04-01 11:33:40',214),(247,'Admin Logged In','2026-04-01 11:33:45',208),(248,'Added New Product: bondpaper','2026-04-01 11:39:02',208),(249,'Archived Property: Aircons (SN-20260331-0001)','2026-04-01 11:39:39',208),(250,'Added New Category: ITs','2026-04-01 11:42:01',208),(251,'Deleted Item: com','2026-04-01 11:42:42',208),(252,'Deleted Category: ITs','2026-04-01 11:42:55',208),(253,'Admin Logged Out','2026-04-01 11:57:21',208),(254,'Admin Logged In','2026-04-04 08:38:39',208),(255,'Approved password reset for User ID: 210. New password: WJi#$aBv','2026-04-04 08:40:06',208),(256,'Registered new user: Hachiko Hilario (Intern)','2026-04-04 08:43:08',208),(257,'Updated user details: Hachiko Hilario (215)','2026-04-04 08:44:06',208),(258,'Updated user details: Amore Flores (214)','2026-04-04 08:44:51',208),(259,'Added New Organization: Blue Eagle','2026-04-04 08:47:15',208),(260,'Added New Office: VTB11','2026-04-04 08:49:17',208),(261,'Added New Item: alcohol','2026-04-04 08:50:31',208),(262,'Deleted Item: alcohol','2026-04-04 08:50:38',208),(263,'Updated Item: aircon','2026-04-04 08:51:40',208),(264,'Updated Personnel: Jomar Gonzaga','2026-04-04 08:56:00',208),(265,'Added New Category: Cleaning Materials','2026-04-04 08:57:54',208),(266,'Added New Category: eme','2026-04-04 08:58:10',208),(267,'Deleted Category: eme','2026-04-04 08:58:20',208),(268,'Updated Category: Maintenance Supplies','2026-04-04 09:00:09',208),(269,'Added New Item: powder cleanser','2026-04-04 09:02:28',208),(270,'Added New Product: powder cleanser','2026-04-04 09:04:53',208),(271,'Added New Property: aircon (SN-20260404-0001)','2026-04-04 09:09:42',208),(272,'Updated Property: aircon (SN-20260404-0001)','2026-04-04 09:11:01',208),(273,'Updated Property: aircon (SN-20260404-0001)','2026-04-04 09:11:11',208),(274,'Archived Property: aircon (SN-20260404-0001)','2026-04-04 09:11:54',208),(275,'Created Requisition & Issue Slip (RIS): RIS-20260404-0031','2026-04-04 09:28:25',208),(276,'Created Property Transfer Receipt (PTR): PTR-20260404033157','2026-04-04 09:31:57',208),(277,'Added New Item: laptop','2026-04-04 09:40:39',208),(278,'Added New Item: computer','2026-04-04 09:40:55',208),(279,'Added New Property: laptop (SN-20260404-0002)','2026-04-04 09:42:04',208),(280,'Added New Property: laptop (SN-20260404-0003)','2026-04-04 09:44:56',208),(281,'Created Property Transfer Receipt (PTR): PTR-20260404034613','2026-04-04 09:46:13',208),(282,'Added New Item: chair','2026-04-04 09:48:23',208),(283,'Added New Property: chair (SN-20260404-0004)','2026-04-04 09:50:19',208),(284,'Updated Office Details: VTB2','2026-04-04 09:51:07',208),(285,'Updated Office Details: JFK1','2026-04-04 09:51:19',208),(286,'Updated Office Details: JFK2','2026-04-04 09:51:39',208),(287,'Updated Office Details: EB1','2026-04-04 09:51:49',208),(288,'Added New Property: table (SN-20260404-0005)','2026-04-04 09:53:00',208),(289,'Created Requisition & Issue Slip (RIS): RIS-20260404-0029','2026-04-04 09:55:27',208),(290,'Created Incident Report: IR-0003','2026-04-04 09:58:09',208),(291,'Disposed Item: table (Qty: 1)','2026-04-04 10:05:22',208),(292,'Restored Property: aircon','2026-04-04 10:06:24',208),(293,'Deleted user: Hachiko Hilario (ID: 215)','2026-04-04 13:41:29',208),(294,'Admin Logged In','2026-04-04 14:00:36',208),(295,'Deleted user: Amore Flores (ID: 214)','2026-04-04 14:33:18',208),(296,'Updated user details: Charl Bilson Flores (213)','2026-04-04 14:35:16',208),(297,'Deleted user: Charl Bilson Flores (ID: 213)','2026-04-04 14:55:03',208),(298,'Registered new user: lyka salazar (Intern)','2026-04-04 14:59:34',208),(299,'Deleted user: lyka salazar (ID: 216)','2026-04-04 14:59:43',208),(300,'Admin Logged Out','2026-04-04 14:59:50',208),(301,'Admin Logged In','2026-04-04 15:00:53',208),(302,'Registered new user: lyka salazar (Admin)','2026-04-04 15:14:45',208),(303,'Admin Logged Out','2026-04-04 15:14:55',208),(304,'Admin Reset Password via Recovery Question','2026-04-04 15:15:17',217),(305,'Admin Logged In','2026-04-04 15:15:33',217),(306,'Registered new user: kiray celis (Intern)','2026-04-04 17:19:21',217),(307,'Deleted user: kiray celis (ID: 218)','2026-04-04 17:19:27',217),(308,'Updated Office Details: VTB12','2026-04-04 17:19:44',217),(309,'Admin Logged In','2026-04-04 17:24:00',208),(310,'Deleted Office: VTB12','2026-04-04 17:40:09',217),(311,'Added New Organization: Blue Eagle','2026-04-04 17:45:53',217),(312,'Added New Personnel: Jamel Gonzaga','2026-04-04 17:51:01',217),(313,'Added New Personnel: Jamel Gonzaga','2026-04-04 17:55:57',217),(314,'Deleted Personnel: Jamel Gonzaga','2026-04-04 17:56:07',217),(315,'Updated Personnel: Jamel Gonzaga','2026-04-04 18:02:33',217),(316,'Updated Personnel: Jomar Gonzaga','2026-04-04 18:02:47',217),(317,'Deleted Personnel: Jamel Gonzaga','2026-04-04 18:03:20',217),(318,'Added New Personnel: Jam','2026-04-04 18:03:41',217),(319,'Updated Personnel: Jamel Gonzaga','2026-04-04 18:04:01',217),(320,'Updated Personnel: Jamel Gonzaga','2026-04-04 18:05:10',217),(321,'Deleted Personnel: Jamel Gonzaga','2026-04-04 18:05:45',217),(322,'Updated Office Details: TED Faculty','2026-04-04 18:06:42',217),(323,'Added New Personnel: Pauline Gacillos','2026-04-04 18:08:12',217),(324,'Added New Personnel: Jamel Gonzaga','2026-04-04 18:09:03',217),(325,'Added New Personnel: Sam Coching','2026-04-04 18:12:35',217),(326,'Updated Product: powder cleanser','2026-04-04 18:22:01',217),(327,'Updated Property: laptop (SN-20260404-0003)','2026-04-04 18:33:58',217),(328,'Updated Property: table (SN-20260325-0001)','2026-04-04 18:34:41',217),(329,'Updated Property: table (SN-20260325-0006)','2026-04-04 18:35:09',217),(330,'Updated Property: com (SN-20260325-0002)','2026-04-04 18:35:45',217),(331,'Updated Property: Aircon (SN-20260319-0001)','2026-04-04 18:36:06',217),(332,'Updated Property: laptop (SN-20260404-0003)','2026-04-04 18:46:00',217),(333,'Disposed Item: chair (Incident #11)','2026-04-04 19:56:24',217),(334,'Cleared/Returned Item: laptop to Office (Incident #10)','2026-04-04 20:06:53',217),(335,'Created Incident Report: IR-0001','2026-04-04 20:17:45',217),(336,'Disposed Item: table (Incident #12)','2026-04-04 20:18:16',217),(337,'Created Incident Report: IR-0001','2026-04-04 20:21:24',217),(338,'Cleared/Returned Item: chair (Qty: 1) to Office (Incident #13)','2026-04-04 20:21:42',217),(339,'Registered new user: kiray celis (Intern)','2026-04-04 20:59:53',217),(340,'Deleted user: kiray celis (ID: 219)','2026-04-04 20:59:58',217),(341,'Added New Office: Library','2026-04-04 21:00:30',217),(342,'Deleted Office: Library','2026-04-04 21:00:34',217),(343,'Created Incident Report: IR-0001','2026-04-04 21:01:33',217),(344,'Created Incident Report: IR-0001','2026-04-04 21:03:34',217),(345,'Cleared/Returned Item: chair (Qty: 1) to Office (Incident #15)','2026-04-04 21:04:23',217),(346,'Disposed Item: chair (Incident #14)','2026-04-04 21:04:51',217),(347,'Updated Property: laptop ()','2026-04-04 21:07:39',217),(348,'Created Incident Report: IR-0001','2026-04-04 22:11:39',217),(349,'Disposed Item: table (Incident #16)','2026-04-04 22:12:04',217),(350,'Created Incident Report: IR-0001','2026-04-04 22:14:38',217),(351,'Disposed Item: table (Incident #17)','2026-04-04 22:15:03',217),(352,'Created Incident Report: IR-0001','2026-04-04 22:56:46',217),(353,'Disposed Item: table (Incident #18)','2026-04-04 22:56:57',217),(354,'Created Incident Report: IR-0001','2026-04-04 22:58:56',217),(355,'Disposed Item: table (Incident #19)','2026-04-04 22:59:05',217),(356,'Archived Property: table (SN-20260325-0001)','2026-04-04 22:59:42',217),(357,'Created Incident Report: IR-0001','2026-04-04 23:01:09',217),(358,'Disposed Item: chair (Incident #20)','2026-04-04 23:01:14',217),(359,'Added New Property: aircon (SN-20260404-0006)','2026-04-04 23:12:42',217),(360,'Admin Logged Out','2026-04-04 23:53:26',217),(361,'Admin Logged In','2026-04-04 23:53:46',208),(362,'Registered new user: kiray celis (Intern)','2026-04-05 00:00:44',208),(363,'Archived user: kiray celis (ID: 220)','2026-04-05 00:00:51',208),(364,'Restored archived user (ID: 220)','2026-04-05 00:01:09',208),(365,'Added New Office: VTB11','2026-04-05 00:01:47',208),(366,'Archived Office: VTB11','2026-04-05 00:01:52',208),(367,'Restored archived office (ID: 32)','2026-04-05 00:02:11',208),(368,'Added New Personnel: kiko c kineme','2026-04-05 00:02:56',208),(369,'Archived Personnel: kiko c kineme','2026-04-05 00:03:04',208),(370,'Created Incident Report: IR-0001','2026-04-05 00:08:00',208),(371,'Cleared/Returned Item: com (Qty: 1) to Office (Incident #21)','2026-04-05 00:08:06',208),(372,'Restored archived rse (ID: 4)','2026-04-05 00:10:14',208),(373,'Restored archived ris (ID: 49)','2026-04-05 00:11:18',208),(374,'Restored archived facility (ID: 2)','2026-04-05 00:11:56',208),(375,'Admin Logged Out','2026-04-05 00:15:33',208),(376,'Intern Logged In','2026-04-05 00:16:12',220),(377,'Added New Property: aircon (SN-20260404-0007)','2026-04-05 00:20:12',220),(378,'Intern Logged Out','2026-04-05 00:20:49',220),(379,'Admin Logged In','2026-04-05 00:21:06',208),(380,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:14',208),(381,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:17',208),(382,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:19',208),(383,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:21',208),(384,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:22',208),(385,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:23',208),(386,'Updated PTR Details: PTR-20260310221500','2026-04-05 00:58:26',208),(387,'Archived PTR Record (ID: 4)','2026-04-05 01:05:24',208),(388,'Restored archived ptr (ID: 4)','2026-04-05 01:06:03',208),(389,'Updated PTR Details: PTR-20260310221500','2026-04-05 01:06:54',208),(390,'Database SQL Backup Created: db_backup_2026-04-04_19-08-52.sql','2026-04-05 01:08:53',208),(391,'Database SQL Backup Created: db_backup_2026-04-04_19-08-53.sql','2026-04-05 01:08:54',208),(392,'Admin Logged In','2026-04-05 22:01:00',208),(393,'Admin Logged In','2026-04-06 07:42:27',208),(394,'Admin Logged Out','2026-04-06 07:54:18',208),(395,'Intern Logged In','2026-04-06 07:54:34',220),(396,'Intern Logged Out','2026-04-06 07:55:38',220),(397,'Admin Logged In','2026-04-06 07:55:52',208),(398,'Admin Logged In','2026-04-06 12:33:24',208),(399,'Registered new user: kiko awit (Intern)','2026-04-06 12:35:33',208),(400,'Archived user: kiko awit (ID: 221)','2026-04-06 12:35:41',208),(401,'Restored archived user (ID: 221)','2026-04-06 12:35:54',208),(402,'Admin Logged Out','2026-04-06 16:15:20',208),(403,'Admin Logged In','2026-04-06 16:15:43',208),(404,'Admin Logged In','2026-04-06 17:08:26',208),(405,'Archived user: kiko awit (ID: 221)','2026-04-06 17:11:49',208),(406,'Restored archived user (ID: 221)','2026-04-06 17:12:01',208),(407,'Updated Personnel: Sam John Coching','2026-04-06 17:13:39',208),(408,'Archived Personnel: Sam John Coching','2026-04-06 17:13:51',208),(409,'Restored archived personnel (ID: 8)','2026-04-06 17:14:10',208),(410,'Created Incident Report: IR-0002','2026-04-06 17:20:21',208),(411,'Disposed Item: table (Incident #22)','2026-04-06 17:20:35',208),(412,'Admin Logged Out','2026-04-06 17:22:15',208),(413,'Admin Logged In','2026-04-06 17:39:26',200),(414,'Restored archived personnel (ID: 9)','2026-04-06 17:42:05',200),(415,'Updated user details: Lea baho (200)','2026-04-06 17:43:26',200),(416,'Admin Logged Out','2026-04-06 17:45:09',200),(417,'Intern Logged In','2026-04-06 23:37:20',220),(418,'Admin Logged In','2026-04-07 00:15:55',208),(419,'Admin Logged In','2026-04-07 11:30:32',208),(420,'Updated user details: Lea Hilario (200)','2026-04-07 11:31:25',208),(421,'Archived user: Lea Hilario (ID: 200)','2026-04-07 11:31:43',208),(422,'Restored archived user (ID: 200)','2026-04-07 11:32:11',208),(423,'Added New Property: table (SN-20260407-0001)','2026-04-07 11:40:01',208),(424,'Database SQL Backup Created: db_backup_2026-04-07_05-53-31.sql','2026-04-07 11:53:32',208),(425,'Database Restored','2026-04-07 11:53:44',208),(426,'Admin Logged Out','2026-04-07 11:54:20',208),(427,'Admin Logged In','2026-04-07 11:55:17',208),(428,'Approved password reset for User ID: 220. New password: SnwwLaoZ','2026-04-07 11:55:45',208),(429,'Admin Logged Out','2026-04-07 11:55:57',208),(430,'Intern Logged In','2026-04-07 11:56:11',220),(431,'Intern Logged Out','2026-04-07 11:57:28',220),(432,'Admin Logged In','2026-04-07 11:57:36',208),(433,'Admin Logged In','2026-04-09 11:50:18',208),(434,'System Zip Backup Created: system_backup_2026-04-09_05-50-43.zip','2026-04-09 11:52:14',208),(435,'System Zip Backup Created: system_backup_2026-04-09_05-52-14.zip','2026-04-09 11:52:29',208),(436,'System Files Restored from ZIP','2026-04-09 11:54:37',208),(437,'Admin Logged Out','2026-04-09 11:55:41',208),(438,'Admin Logged In','2026-04-09 12:08:45',208),(439,'Admin Logged Out','2026-04-09 12:09:07',208),(440,'Admin Reset Password via Recovery Question','2026-04-09 12:48:59',200),(441,'Admin Reset Password via Recovery Question','2026-04-09 12:49:28',200),(442,'Admin Logged Out','2026-04-09 12:49:51',200),(443,'Changed password after reset','2026-04-09 12:50:20',200),(444,'Admin Logged Out','2026-04-09 14:27:45',200),(445,'Admin Logged In','2026-04-09 14:29:27',200),(446,'Approved password reset for User ID: 220. New password: 9nBNBXkM','2026-04-09 14:31:20',200),(447,'Admin Logged Out','2026-04-09 14:31:29',200),(448,'Changed password after reset','2026-04-09 14:31:53',220),(449,'Intern Logged Out','2026-04-09 14:32:01',220),(450,'Admin Reset Password via Recovery Question','2026-04-09 14:32:27',200),(451,'Admin Logged Out','2026-04-09 14:34:09',200),(452,'Changed password after reset','2026-04-09 14:34:32',200),(453,'Admin Logged Out','2026-04-09 14:34:38',200),(454,'Admin Logged In','2026-04-09 14:35:00',200),(455,'Admin Logged Out','2026-04-09 14:35:45',200),(456,'Admin Logged In','2026-04-09 14:36:10',208),(457,'Admin Logged Out','2026-04-09 14:50:16',208),(458,'Admin Logged In','2026-04-09 14:50:22',200),(459,'Admin Logged Out','2026-04-09 14:50:28',200),(460,'Default Admin Logged In','2026-04-09 14:50:40',0),(461,'Admin Logged Out','2026-04-09 14:50:49',0),(462,'Admin Logged In','2026-04-09 14:53:25',208),(463,'Admin Logged Out','2026-04-09 14:53:33',208),(464,'Admin Logged In','2026-04-09 14:55:17',208),(465,'Default Admin Logged In','2026-04-09 14:56:39',0),(466,'Registered new user: kiko awit (Intern)','2026-04-09 14:58:04',0),(467,'Admin Logged Out','2026-04-09 14:58:20',0),(468,'Admin Logged Out','2026-04-09 15:02:26',208),(469,'Intern Logged In','2026-04-09 15:02:50',222),(470,'Intern Logged Out','2026-04-09 15:02:57',222),(471,'Admin Logged In','2026-04-09 15:03:37',208),(472,'Deleted user permanently: lyka salazar (ID: 217)','2026-04-09 15:03:50',208),(473,'Admin Logged Out','2026-04-09 15:04:02',208),(474,'Default Admin Logged In','2026-04-09 15:10:50',0),(475,'Admin Logged Out','2026-04-09 15:10:56',0),(476,'Default Admin Logged In','2026-04-09 15:11:19',0),(477,'Admin Logged Out','2026-04-09 15:11:34',0),(478,'Default Admin Logged In','2026-04-09 15:12:00',0),(479,'Registered new user: Lea Hilario (Admin)','2026-04-09 15:13:29',0),(480,'Admin Logged Out','2026-04-09 15:13:34',0),(481,'Admin Logged In','2026-04-09 15:14:02',223),(482,'Registered new user: Ismael Amver (Student Assistant)','2026-04-09 15:17:25',223),(483,'Admin Logged Out','2026-04-09 15:17:32',223),(484,'Student Assistant Logged In','2026-04-09 15:17:43',224),(485,'Student Assistant Logged Out','2026-04-09 15:18:03',224),(486,'Admin Logged In','2026-04-09 15:18:21',223),(487,'Admin Logged Out','2026-04-09 15:20:56',223),(488,'Admin Logged In','2026-04-09 15:22:44',223),(489,'System Zip Backup Created: system_backup_2026-04-09_09-22-56.zip','2026-04-09 15:24:17',223),(490,'System Zip Backup Created: system_backup_2026-04-09_09-24-18.zip','2026-04-09 15:24:33',223),(491,'Admin Logged Out','2026-04-09 15:24:44',223),(492,'Admin Logged In','2026-04-09 15:41:22',223),(493,'Updated user details: Ismael Amver (224)','2026-04-09 15:43:50',223),(494,'Updated user details: Lea Hilario (223)','2026-04-09 16:02:50',223),(495,'Updated user details: Lea Hilario (223)','2026-04-09 16:08:49',223),(496,'Updated user details: Lea Hilario (223)','2026-04-09 16:09:24',223),(497,'Updated user details: Ismael Amver (224)','2026-04-09 16:09:40',223),(498,'Admin Logged Out','2026-04-09 16:09:52',223),(499,'Admin Logged In','2026-04-09 16:32:53',223),(500,'Added New Product: bondpaper','2026-04-09 16:36:40',223),(501,'Created Requisition & Issue Slip (RIS): RIS-20260409-0030','2026-04-09 17:16:44',223),(502,'Created Requisition & Issue Slip (RIS): RIS-20260409-0031','2026-04-09 17:27:57',223),(503,'Created Property Transfer Receipt (PTR): PTR-20260409114012','2026-04-09 17:40:12',223),(504,'Created Incident Report: IR-0003','2026-04-09 17:48:44',223),(505,'Created Incident Report: IR-0004','2026-04-09 18:04:55',223),(506,'Updated Office Details: VTB1','2026-04-09 18:32:31',223),(507,'Admin Logged In','2026-04-09 23:51:54',223),(508,'Admin Logged Out','2026-04-10 03:12:26',223),(509,'Admin Logged In','2026-04-10 09:20:18',223),(510,'System Zip Backup Created: system_backup_2026-04-10_03-20-55.zip','2026-04-10 09:23:32',223),(511,'System Zip Backup Created: system_backup_2026-04-10_03-23-33.zip','2026-04-10 09:24:04',223),(512,'System Files Restored from ZIP','2026-04-10 09:25:26',223),(513,'Admin Logged Out','2026-04-10 09:32:29',223),(514,'Admin Logged In','2026-04-10 09:33:47',223),(515,'Approved password reset for User ID: 224. New password: kdtOhcOL','2026-04-10 09:33:59',223),(516,'Admin Logged Out','2026-04-10 09:34:09',223),(517,'Changed password after reset','2026-04-10 09:35:16',224),(518,'Student Assistant Logged Out','2026-04-10 09:40:33',224),(519,'Admin Logged In','2026-04-10 09:40:52',223),(520,'System Files Restored from ZIP','2026-04-10 09:42:47',223),(521,'System Zip Backup Created: system_backup_2026-04-10_03-42-06.zip','2026-04-10 09:42:47',223),(522,'Admin Logged Out','2026-04-10 09:42:54',223),(523,'Admin Logged In','2026-04-10 09:43:30',223),(524,'Approved password reset for User ID: 224. New password: JyvoArV*','2026-04-10 09:43:41',223),(525,'Admin Logged Out','2026-04-10 09:43:51',223),(526,'Changed password after reset','2026-04-10 09:44:36',224),(527,'Student Assistant Logged Out','2026-04-10 09:45:04',224),(528,'Default Admin Logged In','2026-04-10 09:45:53',0),(529,'Default Admin Logged In','2026-04-13 11:09:14',0),(530,'Registered new user: ishmael amver f. furton (Student Assistant)','2026-04-13 11:13:22',0),(531,'Admin Logged Out','2026-04-13 11:13:38',0),(532,'Student Assistant Logged In','2026-04-13 11:14:17',225),(533,'Student Assistant Logged Out','2026-04-13 11:18:09',225),(534,'Default Admin Logged In','2026-04-13 11:19:30',0),(535,'Registered new user: lea (Admin)','2026-04-13 11:20:25',0),(536,'Added New Property: laptop (SN-20260413-0001)','2026-04-13 11:24:15',0),(537,'Added New Property: laptop (SN-20260413-0002)','2026-04-13 11:25:12',0),(538,'Admin Logged Out','2026-04-13 11:26:23',0),(539,'Admin Logged In','2026-04-13 11:26:37',226),(540,'System Files Restored from ZIP','2026-04-13 11:33:53',226),(541,'Deleted Item: computer','2026-04-13 11:42:50',226),(542,'System Zip Backup Created: system_backup_2026-04-13_05-43-10.zip','2026-04-13 11:43:45',226),(543,'System Zip Backup Created: system_backup_2026-04-13_05-43-46.zip','2026-04-13 11:44:18',226);
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archive_maintenance`
--

DROP TABLE IF EXISTS `archive_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archive_maintenance` (
  `id` int(11) NOT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `office` varchar(150) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `maintenance_code` varchar(50) DEFAULT NULL,
  `maintenance_task` text DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `previous_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `days_before_due` int(11) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archive_maintenance`
--

LOCK TABLES `archive_maintenance` WRITE;
/*!40000 ALTER TABLE `archive_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `archive_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_header`
--

DROP TABLE IF EXISTS `facility_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facility_header` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_no` varchar(20) NOT NULL,
  `requesting_office` varchar(255) NOT NULL,
  `contact_no` varchar(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `date_of_filing` date NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `num_participants` int(10) unsigned NOT NULL,
  `remarks` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` datetime NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_header`
--

LOCK TABLES `facility_header` WRITE;
/*!40000 ALTER TABLE `facility_header` DISABLE KEYS */;
INSERT INTO `facility_header` VALUES (2,'20260310192259','JPCS','09304871699','Wawandue Subic, Zambales','2026-03-11','Seminar',50,'Standing','2026-03-11 02:24:00','2026-03-12 02:24:00','Completed','2026-03-10 19:40:32',0),(3,'20260310192259','JPCS','09304871699','Wawandue Subic, Zambales','2026-03-11','Seminar',50,'Standing','2026-03-11 02:24:00','2026-03-12 02:24:00','Completed','2026-03-10 19:42:47',0),(4,'','The Navigators','09223344598','WAWANDUE SUBIC ZAMBALES','2026-03-16','Navigators Day',30,'solem','2026-03-22 21:29:00','2026-03-23 17:30:00','Pending','2026-03-22 14:32:06',0),(5,'','The Navigators','09223344598','WAWANDUE SUBIC ZAMBALES','2026-03-16','Navigators Day',30,'solem','2026-03-22 21:29:00','2026-03-23 17:30:00','Completed','2026-03-22 14:36:57',0),(6,'FAC-20260322-0005','Indak Silab','09223344598','Wawandue Subic, Zambales','2026-03-16','Dance Tryout',30,'lively','2026-03-23 10:10:00','2026-03-24 10:11:00','Cancelled','2026-03-22 15:11:38',0),(7,'FAC-20260404-0006','JPCS','09304871699','Wawandue Subic, Zambales','2026-04-04','JPCS Day',150,'standing ','2026-04-06 09:28:00','2026-04-06 21:29:00','Cancelled','2026-04-04 03:30:00',0),(8,'FAC-20260404-0007','','09223344598','Wawandue Subic, Zambales','2026-04-04','CSD Day',200,'standing','2026-04-07 10:03:00','2026-04-07 22:03:00','In Progress','2026-04-04 04:03:22',0),(9,'FAC-20260404-0008','The Navigators','09223344598','Wawandue Subic, Zambales','2026-04-04','Party',150,'standing','2026-04-06 10:07:00','2026-04-06 22:08:00','Completed','2026-04-04 16:08:19',0),(10,'FAC-20260409-0009','','09897966466','Wawandue Subic, Zambales','2026-04-09','Party',50,'','2026-04-13 17:35:00','2026-04-14 17:35:00','Pending','2026-04-09 11:37:27',0);
/*!40000 ALTER TABLE `facility_header` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_items`
--

DROP TABLE IF EXISTS `facility_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facility_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` int(10) unsigned NOT NULL,
  `office_id` int(10) unsigned DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  CONSTRAINT `facility_items_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facility_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_items`
--

LOCK TABLES `facility_items` WRITE;
/*!40000 ALTER TABLE `facility_items` DISABLE KEYS */;
INSERT INTO `facility_items` VALUES (1,2,NULL,0),(3,5,20,0),(4,6,21,0),(5,7,28,0),(6,8,28,0),(7,9,28,0),(8,10,28,0);
/*!40000 ALTER TABLE `facility_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incident_items`
--

DROP TABLE IF EXISTS `incident_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incident_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `last_borrower` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `incident_items_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incident_items`
--

LOCK TABLES `incident_items` WRITE;
/*!40000 ALTER TABLE `incident_items` DISABLE KEYS */;
INSERT INTO `incident_items` VALUES (14,21,39,1,'SN-20260325-0002','CSD Faculty','lyka a. salazar',1),(15,22,38,1,'SN-20260325-0001','CSD Faculty','lyka a. salazar',1),(16,23,42,1,'SN-20260325-0005','CSD Faculty','carl b Flores',0),(17,24,42,1,'SN-20260325-0005','CSD Faculty','eme C kineme',0);
/*!40000 ALTER TABLE `incident_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incident_reports`
--

DROP TABLE IF EXISTS `incident_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_number` varchar(50) NOT NULL,
  `reported_by` varchar(150) NOT NULL,
  `office` varchar(150) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time NOT NULL,
  `description` text NOT NULL,
  `extent_of_damage` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ris_id` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incident_reports`
--

LOCK TABLES `incident_reports` WRITE;
/*!40000 ALTER TABLE `incident_reports` DISABLE KEYS */;
INSERT INTO `incident_reports` VALUES (21,'IR-0001','Lea Hilario','4','2026-04-04','12:07:00','basag','wasak','2026-04-05 00:08:00',NULL,1),(22,'IR-0002','Lea Hilario','4','2026-04-04','17:19:00','wasak','super wasak','2026-04-06 17:20:21',NULL,1),(23,'IR-0003','Lea Hilario','4','2026-04-09','18:48:00','nabasag','basag na basag','2026-04-09 17:48:44',NULL,0),(24,'IR-0004','Lea Hilario','4','2026-04-09','18:04:00','nabasag','basag basag\r\n','2026-04-09 18:04:55',NULL,0);
/*!40000 ALTER TABLE `incident_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reports`
--

DROP TABLE IF EXISTS `maintenance_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(150) NOT NULL,
  `office` varchar(150) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `maintenance_code` varchar(50) NOT NULL,
  `maintenance_task` text NOT NULL,
  `frequency_days` int(11) NOT NULL,
  `previous_maintenance_date` date NOT NULL,
  `next_maintenance_date` date NOT NULL,
  `days_before_due` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reports`
--

LOCK TABLES `maintenance_reports` WRITE;
/*!40000 ALTER TABLE `maintenance_reports` DISABLE KEYS */;
INSERT INTO `maintenance_reports` VALUES (1,'Aircon','4','LG','SN-20260310-0001','MC-2026-0001','Cleaning',180,'2026-03-10','2026-09-06',179,'2026-03-28 08:19:23'),(2,'Aircon','4','LG','SN-20260319-0001','MC-2026-0002','Cleaning',180,'2026-03-19','2026-09-15',179,'2026-03-28 08:14:08'),(3,'Aircon','4','LG','SN-20260310-0001','MC-2026-0003','Cleaning',180,'2026-03-19','2026-09-15',-1,'2026-03-19 07:07:37'),(4,'Aircon','4','LG','SN-20260325-0005','MC-2026-0004','Cleaning',180,'2026-04-04','2026-10-01',179,'2026-04-04 02:00:28'),(5,'aircon','7','Midea','SN-20260404-0006','MC-2026-0005','Cleaning',180,'2026-04-04','2026-10-01',179,'2026-04-04 15:16:57'),(6,'com','4','Lenovo','SN-20260325-0002','MC-2026-0006','Cleaning',180,'2026-04-09','2026-10-06',179,'2026-04-09 10:06:17'),(7,'laptop','4','Acer','','MC-2026-0006','Cleaning',180,'2026-04-09','2026-10-06',179,'2026-04-09 10:09:47'),(8,'laptop','5','acer','SN-20260404-0003','MC-2026-0007','Cleaning',180,'2026-04-09','2026-10-06',179,'2026-04-09 10:25:34');
/*!40000 ALTER TABLE `maintenance_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_requests`
--

DROP TABLE IF EXISTS `password_reset_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','completed') DEFAULT 'pending',
  `new_password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`userid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_requests`
--

LOCK TABLES `password_reset_requests` WRITE;
/*!40000 ALTER TABLE `password_reset_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptr_header`
--

DROP TABLE IF EXISTS `ptr_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ptr_header` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ptr_no` varchar(50) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `from_office` int(11) DEFAULT NULL,
  `to_office` int(11) DEFAULT NULL,
  `transferred_by` varchar(100) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptr_header`
--

LOCK TABLES `ptr_header` WRITE;
/*!40000 ALTER TABLE `ptr_header` DISABLE KEYS */;
INSERT INTO `ptr_header` VALUES (4,'PTR-20260310221500','2026-03-11',5,6,NULL,NULL,'for new instructor',0),(5,'PTR-20260404033157','2026-04-04',4,5,NULL,NULL,'long table',0),(6,'PTR-20260404034613','2026-04-04',4,5,NULL,NULL,'for encoding',0),(7,'PTR-20260409114012','2026-04-09',6,4,NULL,NULL,'',0);
/*!40000 ALTER TABLE `ptr_header` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptr_items`
--

DROP TABLE IF EXISTS `ptr_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ptr_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ptr_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `inventory_no` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptr_items`
--

LOCK TABLES `ptr_items` WRITE;
/*!40000 ALTER TABLE `ptr_items` DISABLE KEYS */;
INSERT INTO `ptr_items` VALUES (3,4,32,'KNS26-0011','table',1,0),(4,5,38,'KNS26-0016','table',3,0),(5,6,50,'KNS26-0025','laptop',1,0),(6,7,32,'KNS26-0011','table',6,0);
/*!40000 ALTER TABLE `ptr_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ris_header`
--

DROP TABLE IF EXISTS `ris_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ris_header` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_no` varchar(50) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(225) DEFAULT NULL,
  `mi_name` varchar(11) DEFAULT NULL,
  `cp_number` varchar(11) NOT NULL,
  `position` varchar(225) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed` tinyint(1) DEFAULT 0,
  `is_returned` tinyint(1) NOT NULL DEFAULT 0,
  `return_date` datetime DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ris_header`
--

LOCK TABLES `ris_header` WRITE;
/*!40000 ALTER TABLE `ris_header` DISABLE KEYS */;
INSERT INTO `ris_header` VALUES (17,'RIS-OLD-17','Flores','Rose Anne','S.','2147483647','criminal','happy birthday party','2026-03-07','2026-03-07 16:37:00','2026-04-09 16:37:00','2026-03-07 08:38:14',0,1,'2026-03-09 11:17:06',0,'2026-04-04 15:58:29'),(20,'RIS-OLD-20','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-10 13:01:00','2026-03-04 13:01:00','2026-03-10 05:02:27',0,1,'2026-03-10 13:02:59',0,'2026-04-04 15:58:29'),(21,'RIS-OLD-21','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:11',0,1,'2026-04-04 09:35:01',0,'2026-04-04 15:58:29'),(22,'RIS-OLD-22','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:13',0,1,'2026-04-04 09:34:58',0,'2026-04-04 15:58:29'),(23,'RIS-OLD-23','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:15',0,1,'2026-04-04 09:34:54',0,'2026-04-04 15:58:29'),(24,'RIS-OLD-24','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:17',0,1,'2026-04-04 09:34:48',0,'2026-04-04 15:58:29'),(25,'RIS-OLD-25','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:19',0,1,'2026-03-19 17:30:45',0,'2026-04-04 15:58:29'),(26,'RIS-OLD-26','Flores','Linux','B.','2147483647','OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:21',0,1,'2026-03-11 09:41:59',0,'2026-04-04 15:58:29'),(28,'RIS-OLD-28','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 17:10:00','2026-03-26 17:10:00','2026-03-25 09:10:42',0,1,'2026-03-25 20:21:42',0,'2026-04-04 15:58:29'),(31,'RIS-OLD-31','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-26 17:38:00','2026-03-30 17:38:00','2026-03-25 09:39:15',0,1,'2026-03-25 20:21:04',0,'2026-04-04 15:58:29'),(32,'RIS-OLD-32','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-26 17:38:00','2026-03-30 17:38:00','2026-03-25 09:39:17',0,1,'2026-04-04 09:34:04',0,'2026-04-04 15:58:29'),(33,'RIS-OLD-33','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-26 17:38:00','2026-03-30 17:38:00','2026-03-25 09:39:22',0,1,'2026-03-25 20:14:10',0,'2026-04-04 15:58:29'),(34,'RIS-OLD-34','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 17:44:00','2026-03-30 17:44:00','2026-03-25 09:44:49',0,1,'2026-03-25 17:49:56',0,'2026-04-04 15:58:29'),(35,'RIS-OLD-35','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 17:44:00','2026-03-30 17:44:00','2026-03-25 09:46:57',0,1,'2026-03-25 17:49:54',0,'2026-04-04 15:58:29'),(36,'RIS-OLD-36','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 17:51:00','2026-03-30 17:52:00','2026-03-25 09:52:20',0,1,'2026-03-25 19:39:15',0,'2026-04-04 15:58:29'),(37,'RIS-OLD-37','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 19:32:00','2026-03-26 19:32:00','2026-03-25 11:33:28',0,1,'2026-03-25 19:35:22',0,'2026-04-04 15:58:29'),(38,'RIS-OLD-38','kineme','kiko','C','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 19:45:00','2026-03-30 19:46:00','2026-03-25 11:46:18',0,1,'2026-03-25 19:47:19',0,'2026-04-04 15:58:29'),(39,'RIS-OLD-39','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 19:48:00','2026-03-28 19:48:00','2026-03-25 11:49:10',0,1,'2026-03-25 19:49:58',0,'2026-04-04 15:58:29'),(40,'RIS-OLD-40','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 19:54:00','2026-03-27 19:54:00','2026-03-25 11:54:38',0,1,'2026-03-25 20:07:31',0,'2026-04-04 15:58:29'),(42,'RIS-OLD-42','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:07:00','2026-03-27 20:08:00','2026-03-25 12:08:25',0,1,'2026-03-25 20:10:04',0,'2026-04-04 15:58:29'),(43,'RIS-OLD-43','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:08:00','2026-03-26 20:08:00','2026-03-25 12:09:02',0,1,'2026-03-25 20:09:41',0,'2026-04-04 15:58:29'),(44,'RIS-OLD-44','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:12:00','2026-03-26 20:12:00','2026-03-25 12:12:46',0,1,'2026-03-25 20:13:31',0,'2026-04-04 15:58:29'),(45,'RIS-OLD-45','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:14:00','2026-03-27 20:14:00','2026-03-25 12:14:53',0,1,'2026-03-25 20:15:40',0,'2026-04-04 15:58:29'),(46,'RIS-OLD-46','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:22:00','2026-03-26 20:22:00','2026-03-25 12:22:31',0,1,'2026-03-25 20:25:05',0,'2026-04-04 15:58:29'),(47,'RIS-OLD-47','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:22:00','2026-03-26 20:22:00','2026-03-25 12:23:12',0,1,'2026-03-25 20:25:03',0,'2026-04-04 15:58:29'),(48,'RIS-OLD-48','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:23:00','2026-03-26 20:23:00','2026-03-25 12:23:52',0,1,'2026-03-25 20:24:42',0,'2026-04-04 15:58:29'),(49,'RIS-OLD-49','Flores','carl','b','2147483647','Dean','kiko c kineme','2026-03-25','2026-03-25 20:26:00','2026-03-26 20:26:00','2026-03-25 12:26:42',0,1,'2026-03-25 20:27:36',0,'2026-04-04 15:58:29'),(50,'RIS-OLD-50','salazar','lyka','a.','2147483647','Guard','party','2026-04-04','2026-04-06 09:27:00','2026-04-06 21:27:00','2026-04-04 01:28:25',0,1,'2026-04-04 09:33:55',0,'2026-04-04 15:58:29'),(51,'RIS-OLD-51','salazar','lyka','a.','2147483647','Guard','party','2026-04-04','2026-04-06 09:54:00','2026-04-07 21:54:00','2026-04-04 01:55:27',0,1,'2026-04-04 09:56:05',0,'2026-04-04 15:58:29'),(52,'RIS-20260409-0030','kineme','eme','C','2147483647','Dean','Party','2026-04-09','2026-04-10 17:16:00','2026-04-13 17:16:00','2026-04-09 09:16:44',0,0,NULL,0,'2026-04-09 09:16:44'),(53,'RIS-20260409-0031','Flores','Charl','P.','09465728391','Dean','party','2026-04-09','2026-04-09 17:27:00','2026-04-13 17:27:00','2026-04-09 09:27:57',0,0,NULL,0,'2026-04-09 09:27:57');
/*!40000 ALTER TABLE `ris_header` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ris_items`
--

DROP TABLE IF EXISTS `ris_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ris_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ris_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `borrowed_from` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ris_id` (`ris_id`),
  KEY `property_id` (`property_id`),
  KEY `borrowed_from` (`borrowed_from`),
  CONSTRAINT `fk_ris` FOREIGN KEY (`ris_id`) REFERENCES `ris_header` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ris_items_office_new` FOREIGN KEY (`borrowed_from`) REFERENCES `tbl_office` (`id`),
  CONSTRAINT `fk_ris_items_property_new` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`),
  CONSTRAINT `fk_ris_items_ris_new` FOREIGN KEY (`ris_id`) REFERENCES `ris_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ris_items`
--

LOCK TABLES `ris_items` WRITE;
/*!40000 ALTER TABLE `ris_items` DISABLE KEYS */;
INSERT INTO `ris_items` VALUES (14,20,29,1,4,0),(15,21,33,1,24,0),(16,22,33,1,24,0),(17,23,33,1,24,0),(18,24,33,1,24,0),(19,25,33,1,24,0),(20,26,33,1,24,0),(31,37,39,1,4,0),(33,39,40,1,4,0),(34,40,41,1,4,0),(36,42,38,1,4,0),(37,43,41,1,4,0),(38,44,43,1,4,0),(40,46,42,1,4,0),(41,47,37,1,4,0),(44,50,39,1,4,0),(45,50,38,1,4,0),(46,51,51,3,20,0),(47,52,42,1,4,0),(48,53,37,1,4,0);
/*!40000 ALTER TABLE `ris_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rse_header`
--

DROP TABLE IF EXISTS `rse_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rse_header` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `request_no` varchar(200) NOT NULL,
  `requesting_office` varchar(100) NOT NULL,
  `contact_no` varchar(11) NOT NULL,
  `address` varchar(100) NOT NULL,
  `date_of_filing` date NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_returned` tinyint(1) DEFAULT 0,
  `return_date` datetime DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rse_header`
--

LOCK TABLES `rse_header` WRITE;
/*!40000 ALTER TABLE `rse_header` DISABLE KEYS */;
INSERT INTO `rse_header` VALUES (4,'20260310175118','JPCS','2147483647','Wawandue Subic, Zambales','2026-03-11','kasal','2026-03-13 13:00:00','2026-03-14 13:00:00','2026-03-10 18:00:52',1,'2026-04-04 22:10:42',0),(5,'20260311023928','CSD Faculty','2147483647','Wawandue Subic, Zambales','2026-03-11','kasal','2026-03-12 09:39:00','2026-03-17 09:40:00','2026-03-11 02:40:27',1,'2026-04-04 22:10:35',0),(7,'20260404032042','JPCS','2147483647','Wawandue Subic, Zambales','2026-04-04','JPCS Day','2026-04-06 09:22:00','2026-04-06 21:22:00','2026-04-04 03:25:22',0,NULL,0),(8,'20260404130121','Indak Silab','2147483647','Wawandue Subic, Zambales','2026-04-04','tryout','2026-04-06 07:02:00','2026-04-06 19:02:00','2026-04-04 13:03:16',1,'2026-04-04 19:28:37',0),(9,'20260409104458','CSD Faculty','09154545454','Wawandue Subic, Zambales','2026-04-09','Party','2026-04-10 18:50:00','2026-04-13 17:51:00','2026-04-09 10:54:37',0,NULL,0),(10,'20260409105501','TED Faculty','09154545454','Wawandue Subic, Zambales','2026-04-09','Party','2026-04-09 17:55:00','2026-04-13 17:55:00','2026-04-09 10:55:47',0,NULL,0),(11,'20260409105913','CSD Faculty','09154545454','Wawandue Subic, Zambales','2026-04-09','Party','2026-04-09 16:59:00','2026-04-13 17:59:00','2026-04-09 10:59:50',0,NULL,0),(12,'20260409105913','CSD Faculty','09154545454','Wawandue Subic, Zambales','2026-04-09','Party','2026-04-09 16:59:00','2026-04-13 17:59:00','2026-04-09 11:02:34',0,NULL,0),(13,'20260409110234','TED Faculty','09154545454','Wawandue Subic, Zambales','2026-04-09','Party','2026-04-09 17:02:00','2026-04-14 17:03:00','2026-04-09 11:03:12',0,NULL,0),(14,'20260409113145','TED Faculty','0997979977','Wawandue Subic, Zambales','2026-04-09','Party','2026-04-09 17:32:00','2026-04-13 18:32:00','2026-04-09 11:32:33',0,NULL,0);
/*!40000 ALTER TABLE `rse_header` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rse_items`
--

DROP TABLE IF EXISTS `rse_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rse_items` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `rse_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `quantity` int(100) NOT NULL DEFAULT 1,
  `borrowed_from` int(11) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_rse_id` (`rse_id`),
  KEY `fk_borrowed_from` (`borrowed_from`),
  KEY `fk_property_id` (`property_id`),
  CONSTRAINT `fk_borrowed_from` FOREIGN KEY (`borrowed_from`) REFERENCES `tbl_office` (`id`),
  CONSTRAINT `fk_property_id` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`),
  CONSTRAINT `fk_rse_id` FOREIGN KEY (`rse_id`) REFERENCES `rse_header` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rse_items`
--

LOCK TABLES `rse_items` WRITE;
/*!40000 ALTER TABLE `rse_items` DISABLE KEYS */;
INSERT INTO `rse_items` VALUES (3,4,32,3,5,0),(4,5,33,3,24,0),(6,7,33,0,24,0),(7,8,51,5,20,0),(8,8,52,1,20,0),(9,9,32,1,6,0),(10,10,37,1,4,0),(11,11,32,1,6,0),(12,12,32,1,6,0),(13,13,37,1,4,0),(14,14,37,1,4,0);
/*!40000 ALTER TABLE `rse_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_category`
--

DROP TABLE IF EXISTS `tbl_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_category` (
  `catid` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(200) NOT NULL,
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_category`
--

LOCK TABLES `tbl_category` WRITE;
/*!40000 ALTER TABLE `tbl_category` DISABLE KEYS */;
INSERT INTO `tbl_category` VALUES (89,'furniture'),(91,'ICT Equipment'),(93,'Office Equipment'),(94,'Office Supply'),(95,'Building & Infrastructure'),(97,'IT'),(99,'Maintenance Supplies');
/*!40000 ALTER TABLE `tbl_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_disposal`
--

DROP TABLE IF EXISTS `tbl_disposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_disposal` (
  `disposal_id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `disposed_by` varchar(100) DEFAULT NULL,
  `disposed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`disposal_id`),
  KEY `property_id` (`property_id`),
  KEY `office_id` (`office_id`),
  CONSTRAINT `tbl_disposal_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`),
  CONSTRAINT `tbl_disposal_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `tbl_office` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_disposal`
--

LOCK TABLES `tbl_disposal` WRITE;
/*!40000 ALTER TABLE `tbl_disposal` DISABLE KEYS */;
INSERT INTO `tbl_disposal` VALUES (1,29,4,1,'sumabog','l@gmail.com','2026-03-11 07:05:23'),(2,37,4,1,'nakatae','l@gmail.com','2026-03-25 16:37:33'),(3,37,4,1,'nakatae','l@gmail.com','2026-03-25 16:46:48'),(7,38,4,1,'nsdoansd','l@gmail.com','2026-03-25 17:06:52'),(10,33,24,1,'nabasag','l@gmail.com','2026-03-25 17:23:27'),(11,33,24,1,'naputol','l@gmail.com','2026-03-25 17:26:48'),(14,39,4,1,'....','l@gmail.com','2026-03-25 19:34:02'),(16,40,4,1,'....','l@gmail.com','2026-03-25 19:49:35'),(18,42,4,1,'sumabog','l@gmail.com','2026-03-25 20:06:30'),(19,41,4,1,'','l@gmail.com','2026-03-25 20:09:23'),(20,43,4,1,'nabasag','l@gmail.com','2026-03-25 20:13:13'),(24,52,20,1,'nawarak','Lea Hilario','2026-04-04 10:05:22'),(25,51,20,1,'Disposed automatically from Incident Report #11','lyka salazar','2026-04-04 19:56:24'),(26,43,4,1,'Disposed automatically from Incident Report #12','lyka salazar','2026-04-04 20:18:16'),(27,33,24,1,'Disposed automatically from Incident Report #14','lyka salazar','2026-04-04 21:04:51'),(28,32,6,1,'Disposed automatically from Incident Report #16','lyka salazar','2026-04-04 22:12:04'),(29,32,6,2,'Disposed automatically from Incident Report #17','lyka salazar','2026-04-04 22:15:03'),(30,32,6,2,'Disposed automatically from Incident Report #18','lyka salazar','2026-04-04 22:56:57'),(31,52,20,1,'Disposed automatically from Incident Report #19','lyka salazar','2026-04-04 22:59:05'),(32,33,24,1,'Disposed automatically from Incident Report #20','lyka salazar','2026-04-04 23:01:14'),(33,38,5,1,'Disposed automatically from Incident Report #22','Lea Hilario','2026-04-06 17:20:35');
/*!40000 ALTER TABLE `tbl_disposal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_instructors`
--

DROP TABLE IF EXISTS `tbl_instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `contact` varchar(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `assigned_dept` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_instructors`
--

LOCK TABLES `tbl_instructors` WRITE;
/*!40000 ALTER TABLE `tbl_instructors` DISABLE KEYS */;
INSERT INTO `tbl_instructors` VALUES (2,'Jomar Gonzaga','09675454361','jomar@gmail.com','CSD Faculty',0),(6,'Pauline Gacillos','91234567851','pau@gmail.com','Hospitality Management',0),(7,'Jamel Gonzaga','09465728391','jam@gmail.com','CSD Faculty',0),(8,'Sam John Coching','09465728391','sam@gmail.com','TED Faculty',0),(9,'kiko c kineme','91234567851','kiko@gmail.com','Guidance Office',0);
/*!40000 ALTER TABLE `tbl_instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_item`
--

DROP TABLE IF EXISTS `tbl_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_item` (
  `itemid` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `date_added` date NOT NULL,
  PRIMARY KEY (`itemid`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `tbl_item_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`catid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_item`
--

LOCK TABLES `tbl_item` WRITE;
/*!40000 ALTER TABLE `tbl_item` DISABLE KEYS */;
INSERT INTO `tbl_item` VALUES (31,'bondpaper',94,'0000-00-00'),(32,'ballpen',94,'0000-00-00'),(34,'table',89,'0000-00-00'),(35,'aircon',95,'0000-00-00'),(39,'powder cleanser',99,'0000-00-00'),(40,'laptop',91,'0000-00-00'),(42,'chair',89,'0000-00-00');
/*!40000 ALTER TABLE `tbl_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_office`
--

DROP TABLE IF EXISTS `tbl_office`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_office` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `office_name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 0,
  `instructor_id` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_parent_office` (`parent_id`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `fk_parent_office` FOREIGN KEY (`parent_id`) REFERENCES `tbl_office` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_office_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_office`
--

LOCK TABLES `tbl_office` WRITE;
/*!40000 ALTER TABLE `tbl_office` DISABLE KEYS */;
INSERT INTO `tbl_office` VALUES (2,NULL,'Admin','Lea Hilario','09811876338',100,NULL,0),(3,NULL,'Faculty','Philip','09999999999',0,NULL,0),(4,3,'CSD Faculty','Jennifer M. Asuncion','09675454362',0,2,0),(5,3,'BED Faculty','Roderick Tan','09675454360',0,NULL,0),(6,3,'TED Faculty','Helen Gacillos','09668141086',0,NULL,0),(7,2,'Registrar Office','Thelma Laxamana','0999-999-9999',0,NULL,0),(8,2,'Office of Student Affairs','Hasmin Ellaso','09675454362',0,NULL,0),(9,2,'Clinic ','Al','09675454360',0,NULL,0),(10,2,'Guidance Office','Pablo Mendiogarin','0999-999-9999',0,NULL,0),(13,NULL,'Hospitality Management','Charl Bilson Flores','09811876338',0,NULL,0),(15,13,'Bartending','Lea','09999999',0,NULL,0),(17,2,'Library','Mykella Corpuz','09675454362',0,NULL,0),(18,2,'Property and Supplies Office','Marites Mendigorin','09668141086',0,NULL,0),(19,NULL,'Classroom','','',0,NULL,0),(20,19,'VTB1','Marites Mendigorin','09668141086',30,NULL,0),(21,19,'VTB2','Marites Mendigorin','09675454360',30,NULL,0),(22,19,'JFK1','Marites Mendigorin','09675454360',0,NULL,0),(23,19,'JFK2','Marites Mendigorin','09675454360',0,NULL,0),(24,19,'EB1','Marites Mendigorin','09675454360',0,NULL,0),(25,2,'Conference Room ','Marites Mendigorin','09675454360',0,NULL,0),(26,13,'Kitchen','Jlou','09675454360',30,NULL,0),(27,13,'Front Office','Lyka','09675454360',30,NULL,0),(28,2,'Covered Court','Marites Mendigorin','09675454362',200,NULL,0),(29,2,'College President','Dr. Rosely H. Agustin','09675454362',0,NULL,0),(32,19,'VTB11','Marites Mendigorin','09675454360',30,NULL,0);
/*!40000 ALTER TABLE `tbl_office` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_organization`
--

DROP TABLE IF EXISTS `tbl_organization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_organization` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `org_name` varchar(255) NOT NULL,
  `president` varchar(200) NOT NULL,
  `org_logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_organization`
--

LOCK TABLES `tbl_organization` WRITE;
/*!40000 ALTER TABLE `tbl_organization` DISABLE KEYS */;
INSERT INTO `tbl_organization` VALUES (1,'JPCS','Jayvee Nacino','uploads/org_logos/jpcs.jpg','2026-03-10 08:09:16',0),(2,'The Navigators','Kian Ramos','uploads/org_logos/navigators.jpg','2026-03-22 12:06:53',0),(3,'Indak Silab','Arthur Nery','uploads/org_logos/indaksilab.jpg','2026-03-22 12:07:20',0),(4,'CSD ','Zachy Pogi','uploads/org_logos/69d06124a0052.jpg','2026-04-04 00:47:15',0);
/*!40000 ALTER TABLE `tbl_organization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_product`
--

DROP TABLE IF EXISTS `tbl_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_product` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `barcode` varchar(1000) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(200) NOT NULL,
  `acquisition_type` enum('Purchased','Donated','','') NOT NULL,
  `category` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `stock` int(11) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `date_added` date DEFAULT NULL,
  `image` varchar(200) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_product`
--

LOCK TABLES `tbl_product` WRITE;
/*!40000 ALTER TABLE `tbl_product` DISABLE KEYS */;
INSERT INTO `tbl_product` VALUES (71,'71111504','ballpen','hbw','Purchased','drinks','red',32,5,NULL,'69a16ea8c2c06.jpeg',0),(72,'72112204','bondpaper','easy','Donated','drinks','long',95,5,NULL,'69a1704ca63d2.jpeg',0),(73,'73160349','bondpaper','easy','Purchased','drinks','long',3,5,NULL,'69a1b2556ff59.jpeg',0),(76,'BC-20260310-0003','ballpen','panda','Purchased','94','black',5,2,NULL,'69af81753784b.jpg',0),(77,'BC-20260310-0004','ballpen','panda','Purchased','94','blue',9,5,NULL,'69afa32eca09a.jpg',0),(78,'4801981116072','bondpaper','easy','Purchased','94','long',9,5,'2026-03-28','69c7b2a014f5c.jpg',0),(79,'BC-20260330-0001','ballpen','hbww','Donated','94','red',5,1,'2026-03-30','69ca596aabf31.png',0),(80,'BC-20260330-0002','ballpen','hbww','Donated','94','red',5,1,'2026-03-30','69ca59a22afa9.png',0),(81,'BC-20260330-0003','bondpaper','ww','Donated','94','long',3,1,'2026-03-30','69ca5b2ee0c06.png',0),(82,'BC-20260330-0004','bondpaper','ll','Donated','94','lllll',2,1,'2026-03-30','69ca5cf4a2640.png',1),(83,'BC-20260331-0001','bondpaper','eaasy','Donated','94','longg',5,1,'2026-03-31','69cba7fe10634.png',1),(84,'BC-20260401-0001','bondpaper','le','Donated','94','shorts',8,1,'2026-04-01','69cc93564fe32.png',0),(85,'BC-20260404-0001','powder cleanser','calla','Purchased','99','1.6kg pink',20,6,'2026-04-04','69d063b552e22.png',0),(86,'BC-20260409-0001','bondpaper','easy','Purchased','94','long',10,5,'2026-04-09','69d7651805b95.jpg',0);
/*!40000 ALTER TABLE `tbl_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_property`
--

DROP TABLE IF EXISTS `tbl_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_property` (
  `property_id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_no` varchar(50) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `acquisition_type` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `date_added` date DEFAULT NULL,
  `month_added` varchar(20) DEFAULT NULL,
  `year_added` varchar(10) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `warranty_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`property_id`),
  UNIQUE KEY `inventory_no` (`inventory_no`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_property`
--

LOCK TABLES `tbl_property` WRITE;
/*!40000 ALTER TABLE `tbl_property` DISABLE KEYS */;
INSERT INTO `tbl_property` VALUES (29,'KNS26-0007','','laptop','Acer','laptop','Purchased',1,'2026-03-09','March','2026','Serviceable',4,NULL,'69ae274ca1573.png',NULL,1,0),(32,'KNS26-0011','SN-20260310-0003','table','orocan','table','Purchased',6,'2026-03-10','March','2026','Partially Disposed',4,NULL,'69b03aab1f082.jpeg',NULL,1,1),(33,'KNS26-0012','SN-20260310-0004','chair','orocan','chair','Purchased',10,'2026-03-11','March','2026','Partially Disposed',24,NULL,'69b06831720ed.jpeg',NULL,1,0),(37,'KNS26-0015','SN-20260319-0001','Aircon','LG','split type','Donated',0,'2026-03-19','March','2026','Serviceable',4,2,'69bb9f6177905.jpeg',NULL,0,0),(38,'KNS26-0016','SN-20260325-0001','table','lenovo','long','Donated',3,'2026-03-25','March','2026','Partially Disposed',5,2,'69c3a58e20f35.jpg','w_69c3a58e216cc.png',0,1),(39,'KNS26-0017','SN-20260325-0002','com','Lenovo','black\r\n','Donated',3,'2026-03-25','March','2026','Cleared / OK',4,2,'69c3c79fa1a9d.png','w_69c3c79fa260b.png',1,0),(40,'KNS26-0018','SN-20260325-0003','com','LG','com','Donated',2,'2026-03-25','March','2026','Serviceable',4,2,'69c3cb78e30f4.png','w_69c3cb78e34db.jpg',0,0),(41,'KNS26-0019','SN-20260325-0004','table','orocan','table','Donated',5,'2026-03-25','March','2026','Serviceable',4,2,'69c3ccdda6b32.png',NULL,0,0),(42,'KNS26-0020','SN-20260325-0005','Aircon','LG','Aircon','Donated',3,'2026-03-25','March','2026','Serviceable',4,2,'69c3ce21a28c1.png',NULL,0,0),(43,'KNS26-0021','SN-20260325-0006','table','iphone','teachers table','Donated',3,'2026-03-25','March','2026','Serviceable',4,2,'69c3d1137f669.jpg',NULL,0,0),(47,'KNS26-0022','SN-20260331-0001','Aircons','LG','N/A','Donated',1,'2026-03-31','March','2026','Serviceable',17,0,NULL,NULL,1,1),(48,'KNS26-0023','SN-20260404-0001','aircon','LG','Split type','Purchased',1,'2026-04-04','April','2026','Serviceable',25,0,'69d064d60d90e.jpeg','w_69d064d60db6d.jpeg',1,0),(49,'KNS26-0024','SN-20260404-0002','laptop','asus','pink','Purchased',1,'2026-04-04','April','2026','Serviceable',4,0,'69d06c6cbc3ac.png','w_69d06c6cbc721.jpeg',1,0),(50,'KNS26-0025','SN-20260404-0003','laptop','acer','light pink','Purchased',1,'2026-04-04','April','2026','Serviceable',5,0,'69d06d1895baa.png','w_69d0ebe881dc7.jpeg',1,0),(51,'KNS26-0026','SN-20260404-0004','chair','orocan','beige','Purchased',11,'2026-04-04','April','2026','Cleared / OK',20,0,'69d06e5b2a1bd.jpeg',NULL,1,0),(52,'KNS26-0027','SN-20260404-0005','table','orocan','long','Purchased',3,'2026-04-04','April','2026','Partially Disposed',20,0,'69d06efc78a59.jpeg',NULL,1,0),(53,'KNS26-0028','SN-20260404-0006','aircon','Midea','Split type','Purchased',1,'2026-04-04','April','2026','Serviceable',7,0,'69d12a6ae6e64.jpeg','w_69d12a6ae7673.jpeg',1,0),(54,'KNS26-0029','SN-20260404-0007','aircon','Carrier','window type','Purchased',1,'2026-04-04','April','2026','Serviceable',20,0,'69d13a3c3d646.jpeg','w_69d13a3c3e0d2.jpeg',1,0),(55,'KNS26-0030','SN-20260407-0001','table','orocan','long','Purchased',3,'2026-04-07','April','2026','Serviceable',22,0,'69d47c916eb26.jpeg',NULL,1,0),(56,'KNS26-0031','SN-20260413-0001','laptop','asus','','Donated',1,'2026-04-13','April','2026','Serviceable',5,0,'69dc61df39e3d.jpg',NULL,1,0),(57,'KNS26-0032','SN-20260413-0002','laptop','asus','silver','Purchased',1,'2026-04-13','April','2026','Serviceable',29,0,'69dc6218ae2a5.jpg',NULL,1,0);
/*!40000 ALTER TABLE `tbl_property` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_stockout`
--

DROP TABLE IF EXISTS `tbl_stockout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_stockout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `instructor_id` int(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `stockout_date` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `office_id` (`office_id`),
  CONSTRAINT `tbl_stockout_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`pid`),
  CONSTRAINT `tbl_stockout_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `tbl_office` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_stockout`
--

LOCK TABLES `tbl_stockout` WRITE;
/*!40000 ALTER TABLE `tbl_stockout` DISABLE KEYS */;
INSERT INTO `tbl_stockout` VALUES (1,71,4,0,10,'2026-03-11 05:44:26','For Signing'),(2,71,4,0,10,'2026-03-11 05:47:01','For Signing'),(3,72,7,0,5,'2026-03-11 05:48:12','5 ream for enrollment'),(4,71,5,0,3,'2026-03-11 09:35:38',''),(5,78,4,2,1,'2026-03-28 19:09:49','for enrollment'),(6,71,4,2,5,'2026-03-30 19:24:05','for enrollment'),(7,85,4,2,10,'2026-04-04 09:15:33','panglinis');
/*!40000 ALTER TABLE `tbl_stockout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_user`
--

DROP TABLE IF EXISTS `tbl_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_user` (
  `userid` int(10) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(200) NOT NULL,
  `username` varchar(200) NOT NULL,
  `useremail` varchar(200) NOT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `userpassword` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `role` varchar(50) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `recovery_question` varchar(255) DEFAULT NULL,
  `recovery_answer` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_user`
--

LOCK TABLES `tbl_user` WRITE;
/*!40000 ALTER TABLE `tbl_user` DISABLE KEYS */;
INSERT INTO `tbl_user` VALUES (226,'lea','leah','lea@gmail.com','09465728391','','','','Lea@12345',0,'Admin','69dc60f9afdfa.jpg','What is the name of your first teacher?','Quimen',0);
/*!40000 ALTER TABLE `tbl_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-13 11:46:03
