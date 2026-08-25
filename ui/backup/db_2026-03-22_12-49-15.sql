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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'Database Backup Created','2026-03-22 18:44:03',NULL);
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
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
  `contact_no` varchar(15) NOT NULL,
  `address` varchar(255) NOT NULL,
  `date_of_filing` date NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `num_participants` int(10) unsigned NOT NULL,
  `remarks` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_header`
--

LOCK TABLES `facility_header` WRITE;
/*!40000 ALTER TABLE `facility_header` DISABLE KEYS */;
INSERT INTO `facility_header` VALUES (2,'20260310192259','JPCS','09304871699','Wawandue Subic, Zambales','2026-03-11','Seminar',50,'Standing','2026-03-11 02:24:00','2026-03-12 02:24:00','2026-03-10 19:40:32'),(3,'20260310192259','JPCS','09304871699','Wawandue Subic, Zambales','2026-03-11','Seminar',50,'Standing','2026-03-11 02:24:00','2026-03-12 02:24:00','2026-03-10 19:42:47');
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
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  CONSTRAINT `facility_items_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facility_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_items`
--

LOCK TABLES `facility_items` WRITE;
/*!40000 ALTER TABLE `facility_items` DISABLE KEYS */;
INSERT INTO `facility_items` VALUES (1,2,NULL);
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
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `last_borrower` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `incident_items_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incident_items`
--

LOCK TABLES `incident_items` WRITE;
/*!40000 ALTER TABLE `incident_items` DISABLE KEYS */;
INSERT INTO `incident_items` VALUES (3,10,29,'','CSD Faculty','Linux B. Flores');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incident_reports`
--

LOCK TABLES `incident_reports` WRITE;
/*!40000 ALTER TABLE `incident_reports` DISABLE KEYS */;
INSERT INTO `incident_reports` VALUES (10,'IR-0002','Lea Hilario','4','2026-03-10','15:55:00','basag','buong screen','2026-03-10 14:56:16',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reports`
--

LOCK TABLES `maintenance_reports` WRITE;
/*!40000 ALTER TABLE `maintenance_reports` DISABLE KEYS */;
INSERT INTO `maintenance_reports` VALUES (1,'Aircon','4','LG','SN-20260310-0001','MC-2026-0001','Cleaning',180,'2026-03-10','2026-09-06',179,'2026-03-10 06:11:35'),(2,'Aircon','4','LG','SN-20260319-0001','MC-2026-0002','Cleaning',180,'2026-03-19','2026-09-15',179,'2026-03-19 07:04:47'),(3,'Aircon','4','LG','SN-20260310-0001','MC-2026-0003','Cleaning',180,'2026-03-19','2026-09-15',-1,'2026-03-19 07:07:37');
/*!40000 ALTER TABLE `maintenance_reports` ENABLE KEYS */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptr_header`
--

LOCK TABLES `ptr_header` WRITE;
/*!40000 ALTER TABLE `ptr_header` DISABLE KEYS */;
INSERT INTO `ptr_header` VALUES (4,'PTR-20260310221500','2026-03-11',5,6,NULL,NULL,'');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptr_items`
--

LOCK TABLES `ptr_items` WRITE;
/*!40000 ALTER TABLE `ptr_items` DISABLE KEYS */;
INSERT INTO `ptr_items` VALUES (3,4,32,'KNS26-0011','table',1);
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
  `last_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(225) DEFAULT NULL,
  `mi_name` varchar(11) DEFAULT NULL,
  `cp_number` int(11) NOT NULL,
  `position` varchar(225) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed` tinyint(1) DEFAULT 0,
  `is_returned` tinyint(1) NOT NULL DEFAULT 0,
  `return_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ris_header`
--

LOCK TABLES `ris_header` WRITE;
/*!40000 ALTER TABLE `ris_header` DISABLE KEYS */;
INSERT INTO `ris_header` VALUES (17,'Flores','Rose Anne','S.',2147483647,'criminal','happy birthday party','2026-03-07','2026-03-07 16:37:00','2026-04-09 16:37:00','2026-03-07 08:38:14',0,1,'2026-03-09 11:17:06'),(20,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-10 13:01:00','2026-03-04 13:01:00','2026-03-10 05:02:27',0,1,'2026-03-10 13:02:59'),(21,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:11',0,0,NULL),(22,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:13',0,0,NULL),(23,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:15',0,0,NULL),(24,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:17',0,0,NULL),(25,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:19',0,1,'2026-03-19 17:30:45'),(26,'Flores','Linux','B.',2147483647,'OIC','Linux Flores','2026-03-02','2026-03-12 09:40:00','2026-03-13 09:40:00','2026-03-11 01:41:21',0,1,'2026-03-11 09:41:59');
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
  PRIMARY KEY (`id`),
  KEY `ris_id` (`ris_id`),
  KEY `property_id` (`property_id`),
  KEY `borrowed_from` (`borrowed_from`),
  CONSTRAINT `fk_ris` FOREIGN KEY (`ris_id`) REFERENCES `ris_header` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ris_items_office_new` FOREIGN KEY (`borrowed_from`) REFERENCES `tbl_office` (`id`),
  CONSTRAINT `fk_ris_items_property_new` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`),
  CONSTRAINT `fk_ris_items_ris_new` FOREIGN KEY (`ris_id`) REFERENCES `ris_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ris_items`
--

LOCK TABLES `ris_items` WRITE;
/*!40000 ALTER TABLE `ris_items` DISABLE KEYS */;
INSERT INTO `ris_items` VALUES (11,17,22,5,4),(14,20,29,1,4),(15,21,33,1,24),(16,22,33,1,24),(17,23,33,1,24),(18,24,33,1,24),(19,25,33,1,24),(20,26,33,1,24);
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
  `contact_no` int(50) NOT NULL,
  `address` varchar(100) NOT NULL,
  `date_of_filing` date NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rse_header`
--

LOCK TABLES `rse_header` WRITE;
/*!40000 ALTER TABLE `rse_header` DISABLE KEYS */;
INSERT INTO `rse_header` VALUES (4,'20260310175118','JPCS',2147483647,'Wawandue Subic, Zambales','2026-03-11','kasal','2026-03-13 13:00:00','2026-03-14 13:00:00','2026-03-10 18:00:52'),(5,'20260311023928','CSD Faculty',2147483647,'Wawandue Subic, Zambales','2026-03-11','kasal','2026-03-12 09:39:00','2026-03-17 09:40:00','2026-03-11 02:40:27');
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
  PRIMARY KEY (`id`),
  KEY `fk_rse_id` (`rse_id`),
  KEY `fk_borrowed_from` (`borrowed_from`),
  KEY `fk_property_id` (`property_id`),
  CONSTRAINT `fk_borrowed_from` FOREIGN KEY (`borrowed_from`) REFERENCES `tbl_office` (`id`),
  CONSTRAINT `fk_property_id` FOREIGN KEY (`property_id`) REFERENCES `tbl_property` (`property_id`),
  CONSTRAINT `fk_rse_id` FOREIGN KEY (`rse_id`) REFERENCES `rse_header` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rse_items`
--

LOCK TABLES `rse_items` WRITE;
/*!40000 ALTER TABLE `rse_items` DISABLE KEYS */;
INSERT INTO `rse_items` VALUES (3,4,32,3,5),(4,5,33,3,24);
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
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_category`
--

LOCK TABLES `tbl_category` WRITE;
/*!40000 ALTER TABLE `tbl_category` DISABLE KEYS */;
INSERT INTO `tbl_category` VALUES (89,'furniture'),(91,'ICT Equipment'),(93,'Office Equipment'),(94,'Office Supply'),(95,'Building & Infrastructure'),(97,'IT');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_disposal`
--

LOCK TABLES `tbl_disposal` WRITE;
/*!40000 ALTER TABLE `tbl_disposal` DISABLE KEYS */;
INSERT INTO `tbl_disposal` VALUES (1,29,4,1,'sumabog','l@gmail.com','2026-03-11 07:05:23');
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
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_instructors`
--

LOCK TABLES `tbl_instructors` WRITE;
/*!40000 ALTER TABLE `tbl_instructors` DISABLE KEYS */;
INSERT INTO `tbl_instructors` VALUES (2,'Jomar Gonzaga','09675454360','j@gmail.com');
/*!40000 ALTER TABLE `tbl_instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_invoice`
--

DROP TABLE IF EXISTS `tbl_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_invoice` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_date` date NOT NULL,
  `subtotal` double NOT NULL,
  `discount` double NOT NULL,
  `sgst` float NOT NULL,
  `cgst` float NOT NULL,
  `total` double NOT NULL,
  `payment_type` tinytext NOT NULL,
  `due` double NOT NULL,
  `paid` double NOT NULL,
  PRIMARY KEY (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_invoice`
--

LOCK TABLES `tbl_invoice` WRITE;
/*!40000 ALTER TABLE `tbl_invoice` DISABLE KEYS */;
INSERT INTO `tbl_invoice` VALUES (10,'2024-11-21',360,2,2.5,2.5,370.8,'Cash',-29.2,400),(15,'2024-11-21',200,2,2.5,2.5,206,'Cash',-94,300),(18,'2024-11-21',160,2,2.5,2.5,164.8,'Cash',-135.2,300),(19,'2024-11-21',200,2,2.5,2.5,206,'Card',-94,300),(20,'2024-11-21',1800,2,2.5,2.5,1854,'Check',-146,2000),(21,'2024-11-21',1800,2,2.5,2.5,1854,'Cash',1854,0),(22,'2024-11-21',1300,2,2.5,2.5,1339,'Check',1339,0),(23,'2024-11-21',160,2,2.5,2.5,164.8,'Check',164.8,0),(24,'2024-11-21',2399,2,2.5,2.5,2470.97,'Card',2470.97,0),(27,'2024-11-21',1300,2,2.5,2.5,1339,'Card',1339,0),(28,'2024-11-21',599,2,2.5,2.5,616.97,'Check',616.97,0),(29,'2024-11-21',1800,2,2.5,2.5,1854,'Check',1854,0),(36,'2024-11-27',3654,2,2.5,2.5,3763.62,'Card',0,3763.62);
/*!40000 ALTER TABLE `tbl_invoice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_invoice_details`
--

DROP TABLE IF EXISTS `tbl_invoice_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_invoice_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `barcode` varchar(200) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `qty` int(11) NOT NULL,
  `rate` double NOT NULL,
  `saleprice` double NOT NULL,
  `order_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_invoice_details`
--

LOCK TABLES `tbl_invoice_details` WRITE;
/*!40000 ALTER TABLE `tbl_invoice_details` DISABLE KEYS */;
INSERT INTO `tbl_invoice_details` VALUES (18,10,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(19,10,'60032301',60,'UWU',1,160,160,'2024-11-21'),(20,11,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(21,11,'60032301',60,'UWU',1,160,160,'2024-11-21'),(22,12,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(23,12,'60032301',60,'UWU',1,160,160,'2024-11-21'),(24,13,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(25,13,'60032301',60,'UWU',1,160,160,'2024-11-21'),(26,14,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(27,14,'60032301',60,'UWU',1,160,160,'2024-11-21'),(28,15,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(29,16,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(30,17,'58023754',58,'SAO cds',1,599,599,'2024-11-21'),(31,18,'60032301',60,'UWU',1,160,160,'2024-11-21'),(32,19,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-21'),(33,20,'59023856',59,'rem cosplay',1,1800,1800,'2024-11-21'),(34,21,'59023856',59,'rem cosplay',1,1800,1800,'2024-11-21'),(35,22,'57113522',57,'rem ',1,1300,1300,'2024-11-21'),(36,23,'60032301',60,'UWU',1,160,160,'2024-11-21'),(37,24,'59023856',59,'rem cosplay',1,1800,1800,'2024-11-21'),(38,24,'58023754',58,'SAO cds',1,599,599,'2024-11-21'),(41,27,'57113522',57,'rem ',1,1300,1300,'2024-11-21'),(42,28,'58023754',58,'SAO cds',1,599,599,'2024-11-21'),(43,29,'59023856',59,'rem cosplay',1,1800,1800,'2024-11-21'),(65,36,'61025922',61,'pic1',1,130,130,'2024-11-27'),(66,36,'62025945',62,'pic2',1,111,111,'2024-11-27'),(67,36,'64030032',64,'pic3',1,333,333,'2024-11-27'),(68,36,'63030009',63,'pic4',1,222,222,'2024-11-27'),(69,36,'58023754',58,'SAO cds',2,599,1198,'2024-11-27'),(70,36,'60032301',60,'UWU',1,160,160,'2024-11-27'),(71,36,'56012337',56,'Ahegai Hood',1,200,200,'2024-11-27'),(72,36,'57113522',57,'rem ',1,1300,1300,'2024-11-27');
/*!40000 ALTER TABLE `tbl_invoice_details` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_item`
--

LOCK TABLES `tbl_item` WRITE;
/*!40000 ALTER TABLE `tbl_item` DISABLE KEYS */;
INSERT INTO `tbl_item` VALUES (30,'Aircon',93,'0000-00-00'),(31,'bondpaper',94,'0000-00-00'),(32,'ballpen',94,'0000-00-00'),(34,'table',89,'0000-00-00'),(35,'Aircon',95,'0000-00-00'),(37,'com',97,'0000-00-00');
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
  PRIMARY KEY (`id`),
  KEY `fk_parent_office` (`parent_id`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `fk_parent_office` FOREIGN KEY (`parent_id`) REFERENCES `tbl_office` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_office_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_office`
--

LOCK TABLES `tbl_office` WRITE;
/*!40000 ALTER TABLE `tbl_office` DISABLE KEYS */;
INSERT INTO `tbl_office` VALUES (2,NULL,'Admin','Lea Hilario','09811876338',100,NULL),(3,NULL,'Faculty','Philip','09999999999',0,NULL),(4,3,'CSD Faculty','Jennifer M. Asuncion','09675454362',0,2),(5,3,'BED Faculty','Roderick Tan','09675454360',0,NULL),(6,3,'TED Faculty','Helen','09668141086',0,NULL),(7,2,'Registrar Office','Thelma Laxamana','0999-999-9999',0,NULL),(8,2,'Office of Student Affairs','Hasmin Ellaso','09675454362',0,NULL),(9,2,'Clinic ','Al','09675454360',0,NULL),(10,2,'Guidance Office','Pablo Mendiogarin','0999-999-9999',0,NULL),(13,NULL,'Hospitality Management','Charl Bilson Flores','09811876338',0,NULL),(14,13,'pwet','Lia','09989798897989',0,NULL),(15,13,'Bartending','Lea','09999999',0,NULL),(17,2,'Library','Mykella Corpuz','09675454362',0,NULL),(18,2,'Property and Supplies Office','Marites Mendigorin','09668141086',0,NULL),(19,NULL,'Classroom','','',0,NULL),(20,19,'VTB1','Marites Mendigorin','09668141086',0,NULL),(21,19,'VTB2','','',0,NULL),(22,19,'JFK1','','',0,NULL),(23,19,'JFK2','','',0,NULL),(24,19,'EB1','','',0,NULL),(25,2,'Conference Room ','Marites Mendigorin','09675454360',0,NULL);
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
  `org_logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_organization`
--

LOCK TABLES `tbl_organization` WRITE;
/*!40000 ALTER TABLE `tbl_organization` DISABLE KEYS */;
INSERT INTO `tbl_organization` VALUES (1,'JPCS','uploads/org_logos/jpcs.jpg','2026-03-10 08:09:16');
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
  `image` varchar(200) NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_product`
--

LOCK TABLES `tbl_product` WRITE;
/*!40000 ALTER TABLE `tbl_product` DISABLE KEYS */;
INSERT INTO `tbl_product` VALUES (71,'71111504','ballpen','hbw','Purchased','drinks','red',37,5,'69a16ea8c2c06.jpeg'),(72,'72112204','bondpaper','easy','Donated','drinks','long',95,5,'69a1704ca63d2.jpeg'),(73,'73160349','bondpaper','easy','Purchased','drinks','long',3,5,'69a1b2556ff59.jpeg'),(76,'BC-20260310-0003','ballpen','panda','Purchased','94','black',5,2,'69af81753784b.jpg'),(77,'BC-20260310-0004','ballpen','panda','Purchased','94','blue',9,5,'69afa32eca09a.jpg');
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
  PRIMARY KEY (`property_id`),
  UNIQUE KEY `inventory_no` (`inventory_no`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_property`
--

LOCK TABLES `tbl_property` WRITE;
/*!40000 ALTER TABLE `tbl_property` DISABLE KEYS */;
INSERT INTO `tbl_property` VALUES (22,'KNS26-0006','','PC','iphone','PC','Purchased',18,'2026-03-07','March','2026','Serviceable',4,NULL,NULL,NULL),(29,'KNS26-0007','','laptop','Acer','laptop','Purchased',0,'2026-03-09','March','2026','Serviceable',4,NULL,'69ae274ca1573.png',NULL),(30,'KNS26-0009','SN-20260310-0001','Aircon','LG','Aircon','Purchased',2,'2026-03-10','March','2026','Serviceable',4,NULL,'69af7087ea3eb.jpeg',NULL),(31,'KNS26-0010','SN-20260310-0002','laptop','asus','laptop','Purchased',2,'2026-03-10','March','2026','Serviceable',5,NULL,'69afa4ea3f483.png',NULL),(32,'KNS26-0011','SN-20260310-0003','table','orocan','table','Purchased',2,'2026-03-10','March','2026','Not Serviceable',6,NULL,'69b03aab1f082.jpeg',NULL),(33,'KNS26-0012','SN-20260310-0004','chair','orocan','chair','Purchased',3,'2026-03-11','March','2026','Not Serviceable',24,NULL,'69b06831720ed.jpeg',NULL),(35,'KNS26-0013','SN-20260311-0001','laptop','asus','laptop','Purchased',1,'2026-03-11','March','2026','Serviceable',4,NULL,'69b0c54c52bef.png',NULL),(36,'KNS26-0014','SN-20260314-0001','laptop','LG','laptop','Donated',1,'2026-03-14','March','2026','Serviceable',4,2,'69b51c81e7689.png',NULL),(37,'KNS26-0015','SN-20260319-0001','Aircon','LG','Aircon','Donated',1,'2026-03-19','March','2026','Serviceable',4,2,'69bb9f6177905.jpeg',NULL);
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
  `quantity` int(11) NOT NULL,
  `stockout_date` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `office_id` (`office_id`),
  CONSTRAINT `tbl_stockout_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`pid`),
  CONSTRAINT `tbl_stockout_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `tbl_office` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_stockout`
--

LOCK TABLES `tbl_stockout` WRITE;
/*!40000 ALTER TABLE `tbl_stockout` DISABLE KEYS */;
INSERT INTO `tbl_stockout` VALUES (1,71,4,10,'2026-03-11 05:44:26','For Signing'),(2,71,4,10,'2026-03-11 05:47:01','For Signing'),(3,72,7,5,'2026-03-11 05:48:12','5 ream for enrollment'),(4,71,5,3,'2026-03-11 09:35:38','');
/*!40000 ALTER TABLE `tbl_stockout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_supplier`
--

DROP TABLE IF EXISTS `tbl_supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_supplier` (
  `sup_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(200) NOT NULL,
  PRIMARY KEY (`sup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_supplier`
--

LOCK TABLES `tbl_supplier` WRITE;
/*!40000 ALTER TABLE `tbl_supplier` DISABLE KEYS */;
INSERT INTO `tbl_supplier` VALUES (18,'Randell'),(19,'Jerome'),(20,'Jaymae'),(21,'Jamber'),(23,'Darwin'),(24,'Erwin');
/*!40000 ALTER TABLE `tbl_supplier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_supply`
--

DROP TABLE IF EXISTS `tbl_supply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_supply` (
  `supplyid` int(11) NOT NULL AUTO_INCREMENT,
  `supply_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT 'pcs',
  `supplier` varchar(255) DEFAULT '',
  `date_added` date DEFAULT curdate(),
  `status` varchar(50) DEFAULT 'Available',
  PRIMARY KEY (`supplyid`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `tbl_supply_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`catid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_supply`
--

LOCK TABLES `tbl_supply` WRITE;
/*!40000 ALTER TABLE `tbl_supply` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_supply` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_taxdis`
--

DROP TABLE IF EXISTS `tbl_taxdis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_taxdis` (
  `taxdis_id` int(11) NOT NULL AUTO_INCREMENT,
  `sgst` float NOT NULL,
  `cgst` float NOT NULL,
  `discount` float NOT NULL,
  PRIMARY KEY (`taxdis_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_taxdis`
--

LOCK TABLES `tbl_taxdis` WRITE;
/*!40000 ALTER TABLE `tbl_taxdis` DISABLE KEYS */;
INSERT INTO `tbl_taxdis` VALUES (1,2.5,2.5,2);
/*!40000 ALTER TABLE `tbl_taxdis` ENABLE KEYS */;
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
  `contact_number` varchar(20) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `userpassword` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_user`
--

LOCK TABLES `tbl_user` WRITE;
/*!40000 ALTER TABLE `tbl_user` DISABLE KEYS */;
INSERT INTO `tbl_user` VALUES (200,'Lea Hilario','Lea1','lea@gmail.com','09465728391',NULL,NULL,'12345','',NULL),(208,'Lea Hilario','Lea H.','l@gmail.com',NULL,NULL,NULL,'Lea@12345','Admin',NULL),(210,'Kian Hilario','kianhilario','kianhilario@gmail.com','09465728391','4th Year BSCS','4th Year','Kianh@123','Student Assistant','699b02c357c57.jpg');
/*!40000 ALTER TABLE `tbl_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_user1`
--

DROP TABLE IF EXISTS `tbl_user1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_user1` (
  `userid` int(10) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(200) NOT NULL,
  `username` varchar(200) NOT NULL,
  `useremail` varchar(200) NOT NULL,
  `userpassword` varchar(200) NOT NULL,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_user1`
--

LOCK TABLES `tbl_user1` WRITE;
/*!40000 ALTER TABLE `tbl_user1` DISABLE KEYS */;
INSERT INTO `tbl_user1` VALUES (24,'kuro david','kuro','kuro@gmail.com','1234','User'),(25,'randell royce sinfuego','randell','randell03@gmail.com','123','Admin');
/*!40000 ALTER TABLE `tbl_user1` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-22 19:49:16
